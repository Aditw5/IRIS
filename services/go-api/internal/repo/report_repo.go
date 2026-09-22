package repo

import (
	"context"
	"time"

	"gorm.io/gorm"
)

type ReportRepo struct {
	db *gorm.DB
}

func NewReportRepo(db *gorm.DB) *ReportRepo {
	return &ReportRepo{db: db}
}

type UnitReportSummary struct {
	TotalAlat         int     `json:"total_alat" gorm:"column:total_alat"`
	TotalRegistrasi   int     `json:"total_registrasi" gorm:"column:total_registrasi"`
	TotalOrderDetail  int     `json:"total_order_detail" gorm:"column:total_order_detail"`
	TotalSelesai      int     `json:"total_selesai" gorm:"column:total_selesai"`
	TotalDiterima     int     `json:"total_diterima" gorm:"column:total_diterima"`
	TotalBelumSelesai int     `json:"total_belum_selesai" gorm:"column:total_belum_selesai"`
	TotalBelumTerima  int     `json:"total_belum_terima" gorm:"column:total_belum_terima"`
	TotalKalibrasi    int     `json:"total_kalibrasi" gorm:"column:total_kalibrasi"`
	TotalRepair       int     `json:"total_repair" gorm:"column:total_repair"`
	TotalBelumVerif   int     `json:"total_belum_verif" gorm:"column:total_belum_verif"`
	CompletionPercent float64 `json:"completion_percent" gorm:"column:completion_percent"`
	AcceptancePercent float64 `json:"acceptance_percent" gorm:"column:acceptance_percent"`
	AverageRating     float64 `json:"average_rating" gorm:"column:average_rating"`
}

type UnitReportCountRow struct {
	Label   string  `json:"label" gorm:"column:label"`
	Count   int     `json:"count" gorm:"column:count"`
	Percent float64 `json:"percent" gorm:"column:percent"`
}

type UnitReportMonthlyRow struct {
	Month         string `json:"month" gorm:"column:month"`
	Label         string `json:"label" gorm:"column:label"`
	Registrations int    `json:"registrations" gorm:"column:registrations"`
	Tools         int    `json:"tools" gorm:"column:tools"`
}

type UnitReportTopToolRow struct {
	Alat        string `json:"alat" gorm:"column:alat"`
	Serial      string `json:"serial" gorm:"column:serial"`
	JumlahOrder int    `json:"jumlah_order" gorm:"column:jumlah_order"`
}

type UnitReport struct {
	UnitName        string                 `json:"unit_name"`
	GeneratedAt     string                 `json:"generated_at"`
	Summary         UnitReportSummary      `json:"summary"`
	Progress        []UnitReportCountRow   `json:"progress"`
	Lingkup         []UnitReportCountRow   `json:"lingkup"`
	JenisOrder      []UnitReportCountRow   `json:"jenis_order"`
	Lokasi          []UnitReportCountRow   `json:"lokasi"`
	Monthly         []UnitReportMonthlyRow `json:"monthly"`
	TopTools        []UnitReportTopToolRow `json:"top_tools"`
	Recommendations []string               `json:"recommendations"`
}

func (r *ReportRepo) UnitName(ctx context.Context, mitraFK string) (string, error) {
	var row struct {
		Name string `gorm:"column:name"`
	}

	err := r.db.WithContext(ctx).
		Table("mitra_m").
		Select("COALESCE(namaperusahaan, '-') as name").
		Where("statusenabled = TRUE").
		Where("CAST(id AS text) = ?", mitraFK).
		Limit(1).
		Scan(&row).Error
	if err != nil {
		return "", err
	}
	if row.Name == "" {
		row.Name = "-"
	}
	return row.Name, nil
}

func (r *ReportRepo) UnitReport(ctx context.Context, mitraFK string) (*UnitReport, error) {
	unitName, err := r.UnitName(ctx, mitraFK)
	if err != nil {
		return nil, err
	}

	report := &UnitReport{
		UnitName:    unitName,
		GeneratedAt: time.Now().Format(time.RFC3339),
	}

	if err := r.loadSummary(ctx, mitraFK, &report.Summary); err != nil {
		return nil, err
	}
	if err := r.loadCountRows(ctx, mitraFK, progressSQL, &report.Progress); err != nil {
		return nil, err
	}
	if err := r.loadCountRows(ctx, mitraFK, lingkupSQL, &report.Lingkup); err != nil {
		return nil, err
	}
	if err := r.loadCountRows(ctx, mitraFK, jenisOrderSQL, &report.JenisOrder); err != nil {
		return nil, err
	}
	if err := r.loadCountRows(ctx, mitraFK, lokasiSQL, &report.Lokasi); err != nil {
		return nil, err
	}
	if err := r.loadMonthly(ctx, mitraFK, &report.Monthly); err != nil {
		return nil, err
	}
	if err := r.loadTopTools(ctx, mitraFK, &report.TopTools); err != nil {
		return nil, err
	}

	report.Recommendations = buildUnitReportRecommendations(report)
	return report, nil
}

func (r *ReportRepo) loadSummary(ctx context.Context, mitraFK string, summary *UnitReportSummary) error {
	return r.db.WithContext(ctx).Raw(summarySQL, mitraFK, mitraFK).Scan(summary).Error
}

func (r *ReportRepo) loadCountRows(ctx context.Context, mitraFK string, query string, dest *[]UnitReportCountRow) error {
	return r.db.WithContext(ctx).Raw(query, mitraFK).Scan(dest).Error
}

func (r *ReportRepo) loadMonthly(ctx context.Context, mitraFK string, dest *[]UnitReportMonthlyRow) error {
	return r.db.WithContext(ctx).Raw(monthlySQL, mitraFK).Scan(dest).Error
}

func (r *ReportRepo) loadTopTools(ctx context.Context, mitraFK string, dest *[]UnitReportTopToolRow) error {
	return r.db.WithContext(ctx).Raw(topToolsSQL, mitraFK).Scan(dest).Error
}

func buildUnitReportRecommendations(report *UnitReport) []string {
	items := []string{}

	if report.Summary.TotalAlat == 0 {
		items = append(items, "Belum ada alat terdaftar pada unit ini. Tambahkan master alat agar riwayat kalibrasi/repair bisa dipantau.")
	}
	if report.Summary.TotalOrderDetail > 0 && report.Summary.TotalBelumSelesai > 0 {
		items = append(items, "Masih ada alat dalam proses. Pantau menu Pesanan untuk melihat dokumen dan status terbaru.")
	}
	if report.Summary.TotalOrderDetail > 0 && report.Summary.AcceptancePercent < 80 {
		items = append(items, "Sebagian alat selesai belum diterima. Cetak atau cek tanda terima selesai saat dokumen sudah tersedia.")
	}
	if len(report.Lingkup) > 0 {
		items = append(items, "Distribusi lingkup dapat dipakai untuk melihat kebutuhan kalibrasi dominan unit dan merencanakan jadwal berikutnya.")
	}
	if len(items) == 0 {
		items = append(items, "Aktivitas unit sudah tercatat dengan baik. Gunakan laporan ini sebagai ringkasan monitoring berkala.")
	}

	return items
}

const summarySQL = `
WITH alat AS (
	SELECT COUNT(*)::int AS total_alat
	FROM mapunittoalat_m
	WHERE statusenabled = TRUE
		AND CAST(objectmitrafk AS text) = ?
),
detail AS (
	SELECT
		mtr.norec,
		mtr.jenisorder,
		mtr.verifregiscustomer,
		mtrd.norec AS detail_norec,
		mtrd.tglverifasman,
		mtrd.tglsetujumanagerlembarkerja,
		mtrd.tglsetujumanagerlaporanrepair,
		mtrd.isterima,
		CASE
			WHEN CAST(mtrd.bintangpenilaian AS text) ~ '^[0-9]+([.][0-9]+){0,1}$'
			THEN CAST(mtrd.bintangpenilaian AS numeric)
		END AS rating
	FROM mitraregistrasi_t mtr
	LEFT JOIN mitraregistrasidetail_t mtrd
		ON mtrd.noregistrasifk = mtr.norec
		AND mtrd.statusenabled = TRUE
	WHERE mtr.statusenabled = TRUE
		AND CAST(mtr.nomitrafk AS text) = ?
),
agg AS (
	SELECT
		COUNT(DISTINCT norec)::int AS total_registrasi,
		COUNT(detail_norec)::int AS total_order_detail,
		SUM(CASE
			WHEN detail_norec IS NOT NULL
				AND (
					(jenisorder = 'kalibrasi' AND tglsetujumanagerlembarkerja IS NOT NULL)
					OR (jenisorder = 'repair' AND tglsetujumanagerlaporanrepair IS NOT NULL)
				)
			THEN 1 ELSE 0
		END)::int AS total_selesai,
		SUM(CASE WHEN detail_norec IS NOT NULL AND isterima = TRUE THEN 1 ELSE 0 END)::int AS total_diterima,
		SUM(CASE WHEN detail_norec IS NOT NULL AND jenisorder = 'kalibrasi' THEN 1 ELSE 0 END)::int AS total_kalibrasi,
		SUM(CASE WHEN detail_norec IS NOT NULL AND jenisorder = 'repair' THEN 1 ELSE 0 END)::int AS total_repair,
		SUM(CASE WHEN detail_norec IS NOT NULL AND tglverifasman IS NULL THEN 1 ELSE 0 END)::int AS total_belum_verif,
		COALESCE(AVG(rating), 0)::float AS average_rating
	FROM detail
)
SELECT
	COALESCE(alat.total_alat, 0) AS total_alat,
	COALESCE(agg.total_registrasi, 0) AS total_registrasi,
	COALESCE(agg.total_order_detail, 0) AS total_order_detail,
	COALESCE(agg.total_selesai, 0) AS total_selesai,
	COALESCE(agg.total_diterima, 0) AS total_diterima,
	GREATEST(COALESCE(agg.total_order_detail, 0) - COALESCE(agg.total_selesai, 0), 0) AS total_belum_selesai,
	GREATEST(COALESCE(agg.total_order_detail, 0) - COALESCE(agg.total_diterima, 0), 0) AS total_belum_terima,
	COALESCE(agg.total_kalibrasi, 0) AS total_kalibrasi,
	COALESCE(agg.total_repair, 0) AS total_repair,
	COALESCE(agg.total_belum_verif, 0) AS total_belum_verif,
	CASE
		WHEN COALESCE(agg.total_order_detail, 0) = 0 THEN 0
		ELSE ROUND((COALESCE(agg.total_selesai, 0)::numeric / agg.total_order_detail::numeric) * 100, 1)
	END::float AS completion_percent,
	CASE
		WHEN COALESCE(agg.total_order_detail, 0) = 0 THEN 0
		ELSE ROUND((COALESCE(agg.total_diterima, 0)::numeric / agg.total_order_detail::numeric) * 100, 1)
	END::float AS acceptance_percent,
	ROUND(COALESCE(agg.average_rating, 0)::numeric, 2)::float AS average_rating
FROM alat
CROSS JOIN agg
`

const progressSQL = `
WITH rows AS (
	SELECT
		CASE
			WHEN mtrd.isterima = TRUE THEN 'Diterima'
			WHEN (
				(mtr.jenisorder = 'kalibrasi' AND mtrd.tglsetujumanagerlembarkerja IS NOT NULL)
				OR (mtr.jenisorder = 'repair' AND mtrd.tglsetujumanagerlaporanrepair IS NOT NULL)
			) THEN 'Selesai Belum Diterima'
			WHEN mtrd.tglverifasman IS NOT NULL THEN 'Dalam Proses'
			ELSE 'Menunggu Verifikasi'
		END AS label
	FROM mitraregistrasi_t mtr
	JOIN mitraregistrasidetail_t mtrd ON mtrd.noregistrasifk = mtr.norec
	WHERE mtr.statusenabled = TRUE
		AND mtrd.statusenabled = TRUE
		AND CAST(mtr.nomitrafk AS text) = ?
),
total AS (SELECT COUNT(*)::numeric AS n FROM rows)
SELECT
	rows.label,
	COUNT(*)::int AS count,
	CASE WHEN total.n = 0 THEN 0 ELSE ROUND((COUNT(*)::numeric / total.n) * 100, 1) END::float AS percent
FROM rows, total
GROUP BY rows.label, total.n
ORDER BY count DESC, rows.label ASC
`

const lingkupSQL = `
WITH rows AS (
	SELECT COALESCE(NULLIF(TRIM(lp.lingkupkalibrasi), ''), 'Belum Ada Lingkup') AS label
	FROM mitraregistrasi_t mtr
	JOIN mitraregistrasidetail_t mtrd ON mtrd.noregistrasifk = mtr.norec
	LEFT JOIN lingkupkalibrasi_m lp ON lp.id = mtrd.lingkupkalibrasifk
	WHERE mtr.statusenabled = TRUE
		AND mtrd.statusenabled = TRUE
		AND CAST(mtr.nomitrafk AS text) = ?
),
total AS (SELECT COUNT(*)::numeric AS n FROM rows)
SELECT
	rows.label,
	COUNT(*)::int AS count,
	CASE WHEN total.n = 0 THEN 0 ELSE ROUND((COUNT(*)::numeric / total.n) * 100, 1) END::float AS percent
FROM rows, total
GROUP BY rows.label, total.n
ORDER BY count DESC, rows.label ASC
LIMIT 8
`

const jenisOrderSQL = `
WITH rows AS (
	SELECT COALESCE(NULLIF(TRIM(mtr.jenisorder), ''), 'Lainnya') AS label
	FROM mitraregistrasi_t mtr
	WHERE mtr.statusenabled = TRUE
		AND CAST(mtr.nomitrafk AS text) = ?
),
total AS (SELECT COUNT(*)::numeric AS n FROM rows)
SELECT
	INITCAP(rows.label) AS label,
	COUNT(*)::int AS count,
	CASE WHEN total.n = 0 THEN 0 ELSE ROUND((COUNT(*)::numeric / total.n) * 100, 1) END::float AS percent
FROM rows, total
GROUP BY rows.label, total.n
ORDER BY count DESC, rows.label ASC
`

const lokasiSQL = `
WITH rows AS (
	SELECT COALESCE(NULLIF(TRIM(COALESCE(lk.lokasi, lkr.lokasi)), ''), 'Belum Ada Lokasi') AS label
	FROM mitraregistrasi_t mtr
	JOIN mitraregistrasidetail_t mtrd ON mtrd.noregistrasifk = mtr.norec
	LEFT JOIN lokasikalibrasi_m lk ON lk.id = mtrd.lokasikajifk
	LEFT JOIN lokasikalibrasi_m lkr ON lkr.id = mtrd.lokasirepairfk
	WHERE mtr.statusenabled = TRUE
		AND mtrd.statusenabled = TRUE
		AND CAST(mtr.nomitrafk AS text) = ?
),
total AS (SELECT COUNT(*)::numeric AS n FROM rows)
SELECT
	rows.label,
	COUNT(*)::int AS count,
	CASE WHEN total.n = 0 THEN 0 ELSE ROUND((COUNT(*)::numeric / total.n) * 100, 1) END::float AS percent
FROM rows, total
GROUP BY rows.label, total.n
ORDER BY count DESC, rows.label ASC
`

const monthlySQL = `
SELECT
	TO_CHAR(date_trunc('month', mtr.tglregistrasi), 'YYYY-MM') AS month,
	TO_CHAR(date_trunc('month', mtr.tglregistrasi), 'Mon YYYY') AS label,
	COUNT(DISTINCT mtr.norec)::int AS registrations,
	COUNT(mtrd.norec)::int AS tools
FROM mitraregistrasi_t mtr
LEFT JOIN mitraregistrasidetail_t mtrd
	ON mtrd.noregistrasifk = mtr.norec
	AND mtrd.statusenabled = TRUE
WHERE mtr.statusenabled = TRUE
	AND CAST(mtr.nomitrafk AS text) = ?
	AND mtr.tglregistrasi >= date_trunc('month', CURRENT_DATE) - INTERVAL '11 months'
GROUP BY date_trunc('month', mtr.tglregistrasi)
ORDER BY date_trunc('month', mtr.tglregistrasi) ASC
`

const topToolsSQL = `
SELECT
	COALESCE(NULLIF(TRIM(mmp.namaproduk), ''), 'Alat') AS alat,
	COALESCE(NULLIF(TRIM(mmp.namaserialnumber), ''), '-') AS serial,
	COUNT(mtrd.norec)::int AS jumlah_order
FROM mitraregistrasi_t mtr
JOIN mitraregistrasidetail_t mtrd ON mtrd.noregistrasifk = mtr.norec
JOIN mapunittoalat_m mmp ON mmp.id = mtrd.namaalatfk
WHERE mtr.statusenabled = TRUE
	AND mtrd.statusenabled = TRUE
	AND CAST(mtr.nomitrafk AS text) = ?
GROUP BY mmp.id, mmp.namaproduk, mmp.namaserialnumber
ORDER BY jumlah_order DESC, alat ASC
LIMIT 8
`
