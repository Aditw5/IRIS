package repo

import (
	"context"
	"fmt"
	"strconv"
	"strings"
	"time"

	"gorm.io/gorm"
)

type HistoryRepo struct {
	db *gorm.DB
}

func NewHistoryRepo(db *gorm.DB) *HistoryRepo {
	return &HistoryRepo{db: db}
}

func (r *HistoryRepo) GetUserMitraFK(ctx context.Context, userID int) (string, error) {
	var row struct {
		MitraFK string `gorm:"column:mitrafk"`
	}

	err := r.db.WithContext(ctx).
		Table("users").
		Select("CAST(mitrafk AS text) as mitrafk").
		Where("id = ?", userID).
		Take(&row).Error
	if err != nil {
		return "", err
	}

	return strings.TrimSpace(row.MitraFK), nil
}

type HistoryOrderGroupItem struct {
	IDDetail             string  `json:"iddetail" gorm:"column:iddetail"`
	ID                   string  `json:"id" gorm:"column:id"`
	NamaPerusahaan       string  `json:"namaperusahaan" gorm:"column:namaperusahaan"`
	TglRegistrasi        string  `json:"tglregistrasi" gorm:"column:tglregistrasi"`
	NoPendaftaran        *string `json:"nopendaftaran" gorm:"column:nopendaftaran"`
	StatusOrder          *int    `json:"statusorder" gorm:"column:statusorder"`
	IsKaji               *bool   `json:"iskaji" gorm:"column:iskaji"`
	JenisOrder           string  `json:"jenisorder" gorm:"column:jenisorder"`
	VerifRegisCustomer   *bool   `json:"verifregiscustomer" gorm:"column:verifregiscustomer"`
	IsiKepuasanPelanggan *bool   `json:"isikepuasanpelanggan" gorm:"column:isikepuasanpelanggan"`
	FileCustomerAMS      *string `json:"filecustomerams" gorm:"column:filecustomerams"`
	FileCustomerTools    *string `json:"filecustomertools" gorm:"column:filecustomertools"`
	CanCetakAMS          bool    `json:"can_cetak_ams" gorm:"-"`
	CanCetakTerima       bool    `json:"can_cetak_tanda_terima" gorm:"-"`
	CanCetakSelesai      bool    `json:"can_cetak_tanda_terima_selesai" gorm:"-"`
	JumlahDetail         int     `json:"jumlahdetail"`
	JumlahSelesai        int     `json:"jumlahselesai"`
	JumlahDiterima       int     `json:"jumlahditerima"`
	JumlahBelumSelesai   int     `json:"jumlahbelumselesai"`
	JumlahBelumTerima    int     `json:"jumlahbelumterima"`
}

type HistoryOrderDetailItem struct {
	Norec                         string     `json:"norec" gorm:"column:norec"`
	NorecDetail                   string     `json:"norec_detail" gorm:"column:norec_detail"`
	IDAlat                        string     `json:"idalat" gorm:"column:idalat"`
	NamaProduk                    string     `json:"namaproduk" gorm:"column:namaproduk"`
	NamaMerk                      *string    `json:"namamerk" gorm:"column:namamerk"`
	NamaTipe                      *string    `json:"namatipe" gorm:"column:namatipe"`
	NamaSerialNumber              *string    `json:"namaserialnumber" gorm:"column:namaserialnumber"`
	FotoProduk                    *string    `json:"fotoproduk" gorm:"column:fotoproduk"`
	NamaPerusahaan                string     `json:"namaperusahaan" gorm:"column:namaperusahaan"`
	TglRegistrasi                 string     `json:"tglregistrasi" gorm:"column:tglregistrasi"`
	NoPendaftaran                 *string    `json:"nopendaftaran" gorm:"column:nopendaftaran"`
	NoOrderAlat                   *string    `json:"noorderalat" gorm:"column:noorderalat"`
	JenisOrder                    string     `json:"jenisorder" gorm:"column:jenisorder"`
	LokasiKalibrasiFK             *string    `json:"lokasikalibrasifk" gorm:"column:lokasikalibrasifk"`
	Lokasi                        *string    `json:"lokasi" gorm:"column:lokasi"`
	LokasiRepairFK                *string    `json:"lokasirepairfk" gorm:"column:lokasirepairfk"`
	LokasiRepair                  *string    `json:"lokasirepair" gorm:"column:lokasirepair"`
	LingkupFK                     *string    `json:"lingkupfk" gorm:"column:lingkupfk"`
	LingkupKalibrasi              *string    `json:"lingkupkalibrasi" gorm:"column:lingkupkalibrasi"`
	DurasiKalibrasi               *int       `json:"durasikalbrasi" gorm:"column:durasikalbrasi"`
	DurasiProses                  *string    `json:"durasi_proses"`
	DurasiDetik                   *int64     `json:"durasi_detik"`
	VerifRegisCustomer            *bool      `json:"verifregiscustomer" gorm:"column:verifregiscustomer"`
	TanggalVerifRegisCustomer     *string    `json:"tanggalverifregiscustomer" gorm:"column:tanggalverifregiscustomer"`
	TglVerifAsman                 *string    `json:"tglverifasman" gorm:"column:tglverifasman"`
	TglSetujuManagerLembarKerja   *string    `json:"tglsetujumanagerlembarkerja" gorm:"column:tglsetujumanagerlembarkerja"`
	TglSetujuManagerLaporanRepair *string    `json:"tglsetujumanagerlaporanrepair" gorm:"column:tglsetujumanagerlaporanrepair"`
	IsReviewAlat                  *bool      `json:"isireviewalat" gorm:"column:isireviewalat"`
	VersiSertifikat               *int       `json:"versisertifikat" gorm:"column:versisertifikat"`
	VersiLaporanRepair            *int       `json:"versilaporanrepair" gorm:"column:versilaporanrepair"`
	BintangPenilaian              *string    `json:"bintangpenilaian" gorm:"column:bintangpenilaian"`
	UlasanPenilaian               *string    `json:"ulasanpenilaian" gorm:"column:ulasanpenilaian"`
	IsVerifikasi                  *bool      `json:"isverifikasi" gorm:"column:isverifikasi"`
	IsVendor                      *bool      `json:"isvendor" gorm:"column:isvendor"`
	FileSertiVendor               *string    `json:"filesertivendor" gorm:"column:filesertivendor"`
	TglUploadSertiVendor          *string    `json:"tglupladosertivendor" gorm:"column:tglupladosertivendor"`
	TanggalMulai                  *time.Time `json:"-" gorm:"column:tanggalmulai"`
}

type historyDetailCounter struct {
	NoregistrasiFK                string     `gorm:"column:noregistrasifk"`
	TglSetujuManagerLembarKerja   *time.Time `gorm:"column:tglsetujumanagerlembarkerja"`
	TglSetujuManagerLaporanRepair *time.Time `gorm:"column:tglsetujumanagerlaporanrepair"`
	IsTerima                      *bool      `gorm:"column:isterima"`
}

func (r *HistoryRepo) ListHistoryOrderKelompok(ctx context.Context, mitraUser string, search string, limit int, offset int) ([]HistoryOrderGroupItem, int64, error) {
	if limit <= 0 {
		limit = 10
	}
	if offset < 0 {
		offset = 0
	}

	q := r.db.WithContext(ctx).
		Table("mitra_m as mt").
		Joins("left join mitraregistrasi_t as mtr on mtr.nomitrafk = mt.id").
		Select(`
			CAST(mt.id AS text) as id,
			mt.namaperusahaan,
			TO_CHAR(mtr.tglregistrasi, 'YYYY-MM-DD HH24:MI:SS') as tglregistrasi,
			mtr.nopendaftaran,
			CAST(mtr.norec AS text) as iddetail,
			mtr.statusorder,
			mtr.iskaji,
			mtr.jenisorder,
			mtr.verifregiscustomer,
			mtr.isikepuasanpelanggan,
			mtr.filecustomerams,
			mtr.filecustomertools
		`).
		Where("mt.statusenabled = TRUE").
		Where("mt.id = ?", mitraUser).
		Where("mtr.statusenabled = TRUE")

	if strings.TrimSpace(search) != "" {
		like := "%" + strings.TrimSpace(search) + "%"
		q = q.Where("(mt.namaperusahaan ILIKE ? OR mtr.nopendaftaran ILIKE ?)", like, like)
	}

	var total int64
	if err := q.Count(&total).Error; err != nil {
		return nil, 0, err
	}

	var rows []HistoryOrderGroupItem
	if err := q.Order("mtr.tglregistrasi desc").Limit(limit).Offset(offset).Scan(&rows).Error; err != nil {
		return nil, 0, err
	}

	if len(rows) == 0 {
		return rows, total, nil
	}

	regisIDs := make([]string, 0, len(rows))
	for _, row := range rows {
		regisIDs = append(regisIDs, row.IDDetail)
	}

	var detailRows []historyDetailCounter
	if err := r.db.WithContext(ctx).
		Table("mitraregistrasidetail_t").
		Select("CAST(noregistrasifk AS text) as noregistrasifk, tglsetujumanagerlembarkerja, tglsetujumanagerlaporanrepair, isterima").
		Where("statusenabled = TRUE").
		Where("noregistrasifk IN ?", regisIDs).
		Scan(&detailRows).Error; err != nil {
		return nil, 0, err
	}

	grouped := map[string][]historyDetailCounter{}
	for _, d := range detailRows {
		grouped[d.NoregistrasiFK] = append(grouped[d.NoregistrasiFK], d)
	}

	for i := range rows {
		details := grouped[rows[i].IDDetail]
		rows[i].JumlahDetail = len(details)
		rows[i].JumlahSelesai = 0
		rows[i].JumlahDiterima = 0

		for _, d := range details {
			if rows[i].JenisOrder == "kalibrasi" && d.TglSetujuManagerLembarKerja != nil {
				rows[i].JumlahSelesai++
			}
			if rows[i].JenisOrder == "repair" && d.TglSetujuManagerLaporanRepair != nil {
				rows[i].JumlahSelesai++
			}
			if d.IsTerima != nil && *d.IsTerima {
				rows[i].JumlahDiterima++
			}
		}

		rows[i].JumlahBelumSelesai = rows[i].JumlahDetail - rows[i].JumlahSelesai
		rows[i].JumlahBelumTerima = rows[i].JumlahDetail - rows[i].JumlahDiterima
		rows[i].CanCetakAMS = rows[i].FileCustomerAMS != nil && strings.TrimSpace(*rows[i].FileCustomerAMS) != ""
		rows[i].CanCetakTerima = rows[i].IsKaji != nil
		rows[i].CanCetakSelesai = rows[i].CanCetakTerima &&
			rows[i].StatusOrder != nil &&
			*rows[i].StatusOrder == 1 &&
			rows[i].JumlahDiterima > 0
	}

	return rows, total, nil
}

func (r *HistoryRepo) HasCompletedTool(ctx context.Context, norecPD string) (bool, error) {
	var registration struct {
		JenisOrder string `gorm:"column:jenisorder"`
	}

	err := r.db.WithContext(ctx).
		Table("mitraregistrasi_t").
		Select("jenisorder").
		Where("norec = ?", strings.TrimSpace(norecPD)).
		Where("statusenabled = TRUE").
		Take(&registration).Error
	if err != nil {
		if err == gorm.ErrRecordNotFound {
			return false, nil
		}
		return false, err
	}

	completionColumn := "tglsetujumanagerlembarkerja"
	if strings.EqualFold(strings.TrimSpace(registration.JenisOrder), "repair") {
		completionColumn = "tglsetujumanagerlaporanrepair"
	}

	var count int64
	err = r.db.WithContext(ctx).
		Table("mitraregistrasidetail_t").
		Where("noregistrasifk = ?", strings.TrimSpace(norecPD)).
		Where("statusenabled = TRUE").
		Where(completionColumn + " IS NOT NULL").
		Count(&count).Error
	if err != nil {
		return false, err
	}

	return count > 0, nil
}

// ===============================
// NEW SECTION: HISTORY ALAT SCAN
// ===============================

type alatOwnershipRow struct {
	ID            string `gorm:"column:id"`
	ObjectMitraFK string `gorm:"column:objectmitrafk"`
	StatusEnabled bool   `gorm:"column:statusenabled"`
}

func (r *HistoryRepo) ValidateAlatOwnershipForHistory(ctx context.Context, idAlat string, mitraFK string) (bool, string, error) {
	var alat alatOwnershipRow
	err := r.db.WithContext(ctx).
		Table("mapunittoalat_m").
		Select(`
			CAST(id AS text) as id,
			CAST(objectmitrafk AS text) as objectmitrafk,
			statusenabled
		`).
		Where("id = ?", idAlat).
		Take(&alat).Error
	if err != nil {
		if err == gorm.ErrRecordNotFound {
			return false, "Data alat tidak ditemukan atau tidak aktif", nil
		}
		return false, "", err
	}

	if !alat.StatusEnabled {
		return false, "Data alat tidak ditemukan atau tidak aktif", nil
	}

	if strings.TrimSpace(alat.ObjectMitraFK) != strings.TrimSpace(mitraFK) {
		return false, "Alat ini tidak terdaftar sebagai milik akun Anda", nil
	}

	return true, "", nil
}

type HistoryAlatScanGroupItem struct {
	Norec                         string  `json:"norec" gorm:"column:norec"`
	NorecDetail                   string  `json:"norec_detail" gorm:"column:norec_detail"`
	IDAlat                        string  `json:"idalat" gorm:"column:idalat"`
	NamaProduk                    string  `json:"namaproduk" gorm:"column:namaproduk"`
	NamaMerk                      *string `json:"namamerk" gorm:"column:namamerk"`
	NamaTipe                      *string `json:"namatipe" gorm:"column:namatipe"`
	NamaSerialNumber              *string `json:"namaserialnumber" gorm:"column:namaserialnumber"`
	FotoProduk                    *string `json:"fotoproduk" gorm:"column:fotoproduk"`
	NamaPerusahaan                string  `json:"namaperusahaan" gorm:"column:namaperusahaan"`
	TglRegistrasi                 string  `json:"tglregistrasi" gorm:"column:tglregistrasi"`
	NoPendaftaran                 *string `json:"nopendaftaran" gorm:"column:nopendaftaran"`
	NoOrderAlat                   *string `json:"noorderalat" gorm:"column:noorderalat"`
	StatusOrder                   *int    `json:"statusorder" gorm:"column:statusorder"`
	IsKaji                        *bool   `json:"iskaji" gorm:"column:iskaji"`
	JenisOrder                    string  `json:"jenisorder" gorm:"column:jenisorder"`
	FileCustomerAMS               *string `json:"filecustomerams" gorm:"column:filecustomerams"`
	FileCustomerTools             *string `json:"filecustomertools" gorm:"column:filecustomertools"`
	Lokasi                        *string `json:"lokasi" gorm:"column:lokasi"`
	LokasiRepair                  *string `json:"lokasirepair" gorm:"column:lokasirepair"`
	LingkupKalibrasi              *string `json:"lingkupkalibrasi" gorm:"column:lingkupkalibrasi"`
	VerifRegisCustomer            *bool   `json:"verifregiscustomer" gorm:"column:verifregiscustomer"`
	TanggalVerifRegisCustomer     *string `json:"tanggalverifregiscustomer" gorm:"column:tanggalverifregiscustomer"`
	TglVerifAsman                 *string `json:"tglverifasman" gorm:"column:tglverifasman"`
	TglSetujuManagerLembarKerja   *string `json:"tglsetujumanagerlembarkerja" gorm:"column:tglsetujumanagerlembarkerja"`
	TglSetujuManagerLaporanRepair *string `json:"tglsetujumanagerlaporanrepair" gorm:"column:tglsetujumanagerlaporanrepair"`
	IsReviewAlat                  *bool   `json:"isireviewalat" gorm:"column:isireviewalat"`
	IsVerifikasi                  *bool   `json:"isverifikasi" gorm:"column:isverifikasi"`
	IsVendor                      *bool   `json:"isvendor" gorm:"column:isvendor"`
	BintangPenilaian              *string `json:"bintangpenilaian" gorm:"column:bintangpenilaian"`
	CanCetakAMS                   bool    `json:"can_cetak_ams" gorm:"-"`
	CanCetakTerima                bool    `json:"can_cetak_tanda_terima" gorm:"-"`
	CanCetakSelesai               bool    `json:"can_cetak_tanda_terima_selesai" gorm:"-"`
	JumlahDetail                  int     `json:"jumlahdetail"`
	JumlahSelesai                 int     `json:"jumlahselesai"`
	JumlahDiterima                int     `json:"jumlahditerima"`
	JumlahBelumSelesai            int     `json:"jumlahbelumselesai"`
	JumlahBelumTerima             int     `json:"jumlahbelumterima"`
}

type HistoryTerimaVersionItem struct {
	TerimaFK      string  `json:"terimafk" gorm:"column:terimafk"`
	Versi         int     `json:"versi" gorm:"column:versi"`
	TanggalTerima *string `json:"tanggalterima" gorm:"column:tanggalterima"`
	JumlahAlat    int     `json:"jumlah_alat" gorm:"column:jumlah_alat"`
}

func (r *HistoryRepo) ListVersiTerima(ctx context.Context, norecPD string, mitraFK string) ([]HistoryTerimaVersionItem, error) {
	var rows []HistoryTerimaVersionItem

	err := r.db.WithContext(ctx).
		Table("mitraregistrasi_terimah_t as th").
		Joins("join mitraregistrasi_t as mtr on mtr.norec = th.noregistrasifk").
		Joins("left join mitraregistrasi_terimad_t as td on td.noregistrasiterimahfk = th.norec").
		Select(`
			CAST(th.norec AS text) as terimafk,
			COALESCE(th.versi, 0) as versi,
			CASE WHEN th.tanggalterima IS NOT NULL THEN TO_CHAR(th.tanggalterima, 'YYYY-MM-DD') END as tanggalterima,
			COUNT(td.norec) as jumlah_alat
		`).
		Where("mtr.statusenabled = TRUE").
		Where("th.statusenabled = TRUE").
		Where("th.noregistrasifk = ?", norecPD).
		Where("mtr.nomitrafk = ?", mitraFK).
		Group("th.norec, th.versi, th.tanggalterima").
		Order("th.versi asc").
		Scan(&rows).Error
	if err != nil {
		return nil, err
	}

	return rows, nil
}

func (r *HistoryRepo) ListHistoryAlatScanGrouped(ctx context.Context, idAlat string, mitraFK string, limit int, offset int) ([]HistoryAlatScanGroupItem, int64, error) {
	if limit <= 0 {
		limit = 10
	}
	if offset < 0 {
		offset = 0
	}

	base := r.db.WithContext(ctx).
		Table("mitraregistrasi_t as mtr").
		Joins("join mitraregistrasidetail_t as mtrd on mtrd.noregistrasifk = mtr.norec").
		Joins("join mapunittoalat_m as mmp on mmp.id = mtrd.namaalatfk").
		Joins("join mitra_m as mt on mt.id = mtr.nomitrafk").
		Joins("left join lokasikalibrasi_m as lk on lk.id = mtrd.lokasikajifk").
		Joins("left join lokasikalibrasi_m as lk1 on lk1.id = mtrd.lokasirepairfk").
		Joins("left join lingkupkalibrasi_m as lp on lp.id = mtrd.lingkupkalibrasifk").
		Where("mtr.statusenabled = TRUE").
		Where("mtrd.statusenabled = TRUE").
		Where("mmp.statusenabled = TRUE").
		Where("mmp.id = ?", idAlat).
		Where("mtr.isstandarulab IS NULL")

	if strings.TrimSpace(mitraFK) != "" {
		base = base.Where("mtr.nomitrafk = ?", mitraFK)
	}

	var total int64
	if err := base.Session(&gorm.Session{}).
		Distinct("mtr.norec").
		Count(&total).Error; err != nil {
		return nil, 0, err
	}

	var rows []HistoryAlatScanGroupItem
	err := base.
		Select(`
			CAST(mtr.norec AS text) as norec,
			CAST(mtrd.norec AS text) as norec_detail,
			CAST(mmp.id AS text) as idalat,
			mmp.namaproduk,
			mmp.namamerk,
			mmp.namatipe,
			mmp.namaserialnumber,
			mmp.fotoproduk,
			mt.namaperusahaan,
			TO_CHAR(mtr.tglregistrasi, 'YYYY-MM-DD HH24:MI:SS') as tglregistrasi,
			mtr.nopendaftaran,
			mtrd.noorderalat,
			mtr.statusorder,
			mtr.iskaji,
			mtr.jenisorder,
			mtr.filecustomerams,
			mtr.filecustomertools,
			lk.lokasi,
			lk1.lokasi as lokasirepair,
			lp.lingkupkalibrasi,
			mtr.verifregiscustomer,
			CASE WHEN mtr.tanggalverifregiscustomer IS NOT NULL THEN TO_CHAR(mtr.tanggalverifregiscustomer, 'YYYY-MM-DD HH24:MI:SS') END as tanggalverifregiscustomer,
			CASE WHEN mtrd.tglverifasman IS NOT NULL THEN TO_CHAR(mtrd.tglverifasman, 'YYYY-MM-DD HH24:MI:SS') END as tglverifasman,
			CASE WHEN mtrd.tglsetujumanagerlembarkerja IS NOT NULL THEN TO_CHAR(mtrd.tglsetujumanagerlembarkerja, 'YYYY-MM-DD HH24:MI:SS') END as tglsetujumanagerlembarkerja,
			CASE WHEN mtrd.tglsetujumanagerlaporanrepair IS NOT NULL THEN TO_CHAR(mtrd.tglsetujumanagerlaporanrepair, 'YYYY-MM-DD HH24:MI:SS') END as tglsetujumanagerlaporanrepair,
			mtrd.isireviewalat,
			mtrd.isverifikasi,
			mtrd."isVendor" as isvendor,
			CAST(mtrd.bintangpenilaian AS text) as bintangpenilaian
		`).
		Order("mtr.tglregistrasi desc").
		Limit(limit).
		Offset(offset).
		Scan(&rows).Error
	if err != nil {
		return nil, 0, err
	}

	if len(rows) == 0 {
		return rows, total, nil
	}

	regisIDs := make([]string, 0, len(rows))
	for _, row := range rows {
		regisIDs = append(regisIDs, row.Norec)
	}

	var detailRows []historyDetailCounter
	if err := r.db.WithContext(ctx).
		Table("mitraregistrasidetail_t").
		Select("CAST(noregistrasifk AS text) as noregistrasifk, tglsetujumanagerlembarkerja, tglsetujumanagerlaporanrepair, isterima").
		Where("statusenabled = TRUE").
		Where("noregistrasifk IN ?", regisIDs).
		Scan(&detailRows).Error; err != nil {
		return nil, 0, err
	}

	groupedCounter := map[string][]historyDetailCounter{}
	for _, d := range detailRows {
		groupedCounter[d.NoregistrasiFK] = append(groupedCounter[d.NoregistrasiFK], d)
	}

	for i := range rows {
		details := groupedCounter[rows[i].Norec]
		rows[i].JumlahDetail = len(details)
		rows[i].JumlahSelesai = 0
		rows[i].JumlahDiterima = 0

		for _, d := range details {
			if rows[i].JenisOrder == "kalibrasi" && d.TglSetujuManagerLembarKerja != nil {
				rows[i].JumlahSelesai++
			}
			if rows[i].JenisOrder == "repair" && d.TglSetujuManagerLaporanRepair != nil {
				rows[i].JumlahSelesai++
			}
			if d.IsTerima != nil && *d.IsTerima {
				rows[i].JumlahDiterima++
			}
		}

		rows[i].JumlahBelumSelesai = rows[i].JumlahDetail - rows[i].JumlahSelesai
		rows[i].JumlahBelumTerima = rows[i].JumlahDetail - rows[i].JumlahDiterima
		rows[i].CanCetakAMS = rows[i].FileCustomerAMS != nil && strings.TrimSpace(*rows[i].FileCustomerAMS) != ""
		rows[i].CanCetakTerima = rows[i].IsKaji != nil
		rows[i].CanCetakSelesai = rows[i].CanCetakTerima &&
			rows[i].StatusOrder != nil &&
			*rows[i].StatusOrder == 1 &&
			rows[i].JumlahDiterima > 0
	}

	return rows, total, nil
}

func (r *HistoryRepo) ListHistoryOrderDetail(ctx context.Context, norecPD string) ([]HistoryOrderDetailItem, error) {
	var rows []HistoryOrderDetailItem

	err := r.db.WithContext(ctx).
		Table("mitraregistrasi_t as mtr").
		Joins("join mitraregistrasidetail_t as mtrd on mtrd.noregistrasifk = mtr.norec").
		Joins("join mapunittoalat_m as mmp on mmp.id = mtrd.namaalatfk").
		Joins("join mitra_m as mt on mt.id = mtr.nomitrafk").
		Joins("left join lokasikalibrasi_m as lk on lk.id = mtrd.lokasikajifk").
		Joins(`left join lokasikalibrasi_m as lk1 on lk1.id = mtrd.lokasirepairfk`).
		Joins("left join lingkupkalibrasi_m as lp on lp.id = mtrd.lingkupkalibrasifk").
		Select(`
			CAST(mtr.norec AS text) as norec,
			CAST(mtrd.norec AS text) as norec_detail,
			CAST(mmp.id AS text) as idalat,
			mmp.namaproduk,
			mmp.namamerk,
			mmp.namatipe,
			mmp.namaserialnumber,
			mmp.fotoproduk,
			mt.namaperusahaan,
			TO_CHAR(mtr.tglregistrasi, 'YYYY-MM-DD HH24:MI:SS') as tglregistrasi,
			mtr.nopendaftaran,
			mtrd.noorderalat,
			mtr.jenisorder,
			CAST(lk.id AS text) as lokasikalibrasifk,
			lk.lokasi,
			CAST(lk1.id AS text) as lokasirepairfk,
			lk1.lokasi as lokasirepair,
			CAST(lp.id AS text) as lingkupfk,
			lp.lingkupkalibrasi,
			mtrd.durasikalbrasi,
			mtr.verifregiscustomer,
			CASE WHEN mtr.tanggalverifregiscustomer IS NOT NULL THEN TO_CHAR(mtr.tanggalverifregiscustomer, 'YYYY-MM-DD HH24:MI:SS') END as tanggalverifregiscustomer,
			CASE WHEN mtrd.tglverifasman IS NOT NULL THEN TO_CHAR(mtrd.tglverifasman, 'YYYY-MM-DD HH24:MI:SS') END as tglverifasman,
			CASE WHEN mtrd.tglsetujumanagerlembarkerja IS NOT NULL THEN TO_CHAR(mtrd.tglsetujumanagerlembarkerja, 'YYYY-MM-DD HH24:MI:SS') END as tglsetujumanagerlembarkerja,
			CASE WHEN mtrd.tglsetujumanagerlaporanrepair IS NOT NULL THEN TO_CHAR(mtrd.tglsetujumanagerlaporanrepair, 'YYYY-MM-DD HH24:MI:SS') END as tglsetujumanagerlaporanrepair,
			mtrd.isireviewalat,
			mtrd.versisertifikat,
			mtrd.versilaporanrepair,
			CAST(mtrd.bintangpenilaian AS text) as bintangpenilaian,
			mtrd.ulasanpenilaian,
			mtrd.isverifikasi,
			mtrd."isVendor" as isvendor,
			mtrd."fileSertiVendor" as filesertivendor,
			CASE WHEN mtrd.tglupladosertivendor IS NOT NULL THEN TO_CHAR(mtrd.tglupladosertivendor, 'YYYY-MM-DD HH24:MI:SS') END as tglupladosertivendor,
			mtrd.tanggalmulai
		`).
		Where("mtr.statusenabled = TRUE").
		Where("mtrd.statusenabled = TRUE").
		Where("mmp.statusenabled = TRUE").
		Where("mtr.norec = ?", norecPD).
		Order("mmp.namaproduk asc").
		Scan(&rows).Error
	if err != nil {
		return nil, err
	}

	holidayDates := map[string]bool{}

	var holidayRows []struct {
		Tanggal string `gorm:"column:tanggal"`
	}

	err = r.db.WithContext(ctx).
		Table("master_hari_libur_m").
		Select("TO_CHAR(tanggal, 'YYYY-MM-DD') as tanggal").
		Where("statusenabled = TRUE").
		Scan(&holidayRows).Error
	if err != nil {
		return nil, err
	}

	for _, holiday := range holidayRows {
		if holiday.Tanggal != "" {
			holidayDates[holiday.Tanggal] = true
		}
	}

	calcWorkingSeconds := func(start, end *time.Time) *int64 {
		if start == nil || end == nil {
			return nil
		}

		s := *start
		e := *end

		if e.Before(s) {
			s, e = e, s
		}

		workStartHour, workStartMinute := 7, 30
		workEndHour, workEndMinute := 16, 0

		var totalSeconds int64 = 0

		cursor := time.Date(s.Year(), s.Month(), s.Day(), 0, 0, 0, 0, s.Location())
		lastDay := time.Date(e.Year(), e.Month(), e.Day(), 0, 0, 0, 0, e.Location())

		for !cursor.After(lastDay) {
			weekday := cursor.Weekday()
			currentDate := cursor.Format("2006-01-02")

			isWeekend := weekday == time.Saturday || weekday == time.Sunday
			isHoliday := holidayDates[currentDate]
			isWorkday := !isWeekend && !isHoliday

			if isWorkday {
				dayWorkStart := time.Date(
					cursor.Year(),
					cursor.Month(),
					cursor.Day(),
					workStartHour,
					workStartMinute,
					0,
					0,
					cursor.Location(),
				)

				dayWorkEnd := time.Date(
					cursor.Year(),
					cursor.Month(),
					cursor.Day(),
					workEndHour,
					workEndMinute,
					0,
					0,
					cursor.Location(),
				)

				effectiveStart := dayWorkStart
				if s.After(dayWorkStart) {
					effectiveStart = s
				}

				effectiveEnd := dayWorkEnd
				if e.Before(dayWorkEnd) {
					effectiveEnd = e
				}

				if effectiveEnd.After(effectiveStart) {
					totalSeconds += int64(effectiveEnd.Sub(effectiveStart).Seconds())
				}
			}

			cursor = cursor.AddDate(0, 0, 1)
		}

		return &totalSeconds
	}

	for i := range rows {
		if rows[i].TanggalMulai != nil && rows[i].TglSetujuManagerLembarKerja != nil {
			approved, err := time.Parse("2006-01-02 15:04:05", *rows[i].TglSetujuManagerLembarKerja)
			if err == nil {
				seconds := calcWorkingSeconds(rows[i].TanggalMulai, &approved)
				if seconds != nil {
					rows[i].DurasiDetik = seconds

					workdaySeconds := int64((8 * 60 * 60) + (30 * 60))
					days := *seconds / workdaySeconds
					rem := *seconds % workdaySeconds

					hours := rem / 3600
					rem = rem % 3600

					minutes := rem / 60
					sec := rem % 60

					text := fmt.Sprintf("%d hari kerja %d jam %d menit %d detik", days, hours, minutes, sec)
					rows[i].DurasiProses = &text
				}
			}
		}
	}

	return rows, nil
}

type SavePenilaianPayload struct {
	Norec       string
	NorecDetail string
	NoOrderAlat string
	Bintang     string
	Ulasan      string
}

func (r *HistoryRepo) SavePenilaianPelanggan(ctx context.Context, p SavePenilaianPayload) error {
	return r.db.WithContext(ctx).Transaction(func(tx *gorm.DB) error {
		result := tx.Table("mitraregistrasidetail_t").
			Where("norec = ?", p.NorecDetail).
			Where("statusenabled = TRUE").
			Updates(map[string]any{
				"bintangpenilaian": strings.TrimSpace(p.Bintang),
				"ulasanpenilaian":  strings.TrimSpace(p.Ulasan),
				"isireviewalat":    true,
			})

		if result.Error != nil {
			return result.Error
		}
		if result.RowsAffected == 0 {
			return fmt.Errorf("detail registrasi tidak ditemukan")
		}

		return nil
	})
}

type SurveyAtributPayload struct {
	No       int  `json:"no"`
	Harapan  *int `json:"harapan"`
	Kepuasan *int `json:"kepuasan"`
}

type SaveSurveyPayload struct {
	RegistrasiFK         string                 `json:"registrasifk"`
	NamaResponden        string                 `json:"namaresponden"`
	UnitDivisiKerja      string                 `json:"unitdivisikerja"`
	JabatanResponden     string                 `json:"jabatanresponden"`
	LamaBekerja          string                 `json:"lamabekerja"`
	JenisKelamin         any                    `json:"jeniskelamin"`
	Usia                 string                 `json:"usia"`
	NoTelpon             string                 `json:"notelpon"`
	NotePendidikanTelpon any                    `json:"notependidikanlpon"`
	Pendidikan           any                    `json:"pendidikan"`
	LamaMenjadiMitra     string                 `json:"lamamenjadimitra"`
	AtributList          []SurveyAtributPayload `json:"atributList"`
	B21                  *int                   `json:"b21"`
	B22                  string                 `json:"b22"`
	B23                  string                 `json:"b23"`
	KetidakpuasanLayanan string                 `json:"ketidakpuasanlayanan"`
	MasukanSaran         string                 `json:"masukansaran"`
	D1Skor               *int                   `json:"d1_skor"`
	D1Penjelasan         string                 `json:"d1_penjelasan"`
	D2Skor               *int                   `json:"d2_skor"`
	D2Penjelasan         string                 `json:"d2_penjelasan"`
	NamaPenanggungJawab  string                 `json:"namapenanggungjawab"`
}

const surveyHeaderTable = "surveypelanggan_t"
const surveyDetailTable = "surveydetailpelanggan_t"

type SurveyHeaderRow struct {
	Norec         string     `json:"norec" gorm:"column:norec"`
	RegistrasiFK  string     `json:"registrasifk" gorm:"column:registrasifk"`
	StatusEnabled *bool      `json:"statusenabled" gorm:"column:statusenabled"`
	Petugas       *string    `json:"petugas" gorm:"column:petugas"`
	CreatedAt     *time.Time `json:"created_at" gorm:"column:created_at"`
	UpdatedAt     *time.Time `json:"updated_at" gorm:"column:updated_at"`

	NamaResponden    *string `json:"namaresponden" gorm:"column:namaresponden"`
	UnitDivisiKerja  *string `json:"unitdivisikerja" gorm:"column:unitdivisikerja"`
	JabatanResponden *string `json:"jabatanresponden" gorm:"column:jabatanresponden"`
	LamaBekerja      *string `json:"lamabekerja" gorm:"column:lamabekerja"`

	JenisKelaminFK *string `json:"jeniskelaminfk" gorm:"column:jeniskelaminfk"`
	PendidikanFK   *string `json:"pendidikanfk" gorm:"column:pendidikanfk"`

	IDJenisKelamin *string `json:"idjeniskelamin" gorm:"column:idjeniskelamin"`
	JenisKelamin   *string `json:"jeniskelamin" gorm:"column:jeniskelamin"`
	IDPendidikan   *string `json:"idpendidikan" gorm:"column:idpendidikan"`
	Pendidikan     *string `json:"pendidikan" gorm:"column:pendidikan"`

	Usia             *string `json:"usia" gorm:"column:usia"`
	NoTelpon         *string `json:"notelpon" gorm:"column:notelpon"`
	LamaMenjadiMitra *string `json:"lamamenjadimitra" gorm:"column:lamamenjadimitra"`

	B21                  *string `json:"b21" gorm:"column:b21"`
	B22                  *string `json:"b22" gorm:"column:b22"`
	B23                  *string `json:"b23" gorm:"column:b23"`
	KetidakpuasanLayanan *string `json:"ketidakpuasanlayanan" gorm:"column:ketidakpuasanlayanan"`
	MasukanSaran         *string `json:"masukansaran" gorm:"column:masukansaran"`
	D1Skor               *string `json:"d1_skor" gorm:"column:d1_skor"`
	D1Penjelasan         *string `json:"d1_penjelasan" gorm:"column:d1_penjelasan"`
	D2Skor               *string `json:"d2_skor" gorm:"column:d2_skor"`
	D2Penjelasan         *string `json:"d2_penjelasan" gorm:"column:d2_penjelasan"`
	NamaPenanggungJawab  *string `json:"namapenanggungjawab" gorm:"column:namapenanggungjawab"`

	DetailSurvey []SurveyDetailRow `json:"detailSurvey" gorm:"-"`
}

type SurveyDetailRow struct {
	No       int     `json:"no" gorm:"column:no"`
	Harapan  *string `json:"harapan" gorm:"column:harapan"`
	Kepuasan *string `json:"kepuasan" gorm:"column:kepuasan"`
}

type SurveyDetailRowWithFK struct {
	SurveyFK string  `gorm:"column:surveyfk"`
	No       int     `gorm:"column:no"`
	Harapan  *string `gorm:"column:harapan"`
	Kepuasan *string `gorm:"column:kepuasan"`
}

func normalizeLookupValue(v any) *string {
	if v == nil {
		return nil
	}
	switch val := v.(type) {
	case string:
		s := strings.TrimSpace(val)
		if s == "" {
			return nil
		}
		return &s
	case map[string]any:
		if x, ok := val["value"]; ok {
			s := strings.TrimSpace(fmt.Sprintf("%v", x))
			if s != "" {
				return &s
			}
		}
		if x, ok := val["label"]; ok {
			s := strings.TrimSpace(fmt.Sprintf("%v", x))
			if s != "" {
				return &s
			}
		}
	}
	s := strings.TrimSpace(fmt.Sprintf("%v", v))
	if s == "" || s == "<nil>" {
		return nil
	}
	return &s
}

func isNumericText(s string) bool {
	if s == "" {
		return false
	}
	for _, r := range s {
		if r < '0' || r > '9' {
			return false
		}
	}
	return true
}

func firstLookupValue(values ...any) any {
	for _, v := range values {
		normalized := normalizeLookupValue(v)
		if normalized != nil {
			return *normalized
		}
	}
	return nil
}

func lookupAliases(table string, labelColumn string, value string) []string {
	key := strings.ToLower(strings.TrimSpace(value))
	key = strings.ReplaceAll(key, "_", " ")
	key = strings.Join(strings.Fields(key), " ")

	if table == "jeniskelamin_m" && labelColumn == "jeniskelamin" {
		switch strings.ReplaceAll(key, "-", " ") {
		case "laki laki":
			return []string{"Laki-Laki", "Laki-laki"}
		case "perempuan":
			return []string{"Perempuan"}
		}
	}

	if table == "pendidikan_m" && labelColumn == "pendidikan" {
		switch key {
		case "sma", "smk", "slta", "slta sederajat", "slta/sederajat":
			return []string{"SLTA/SEDERAJAT"}
		case "d1", "d2", "d3", "d1-d3", "d1 d3", "d1-d3 sederajat":
			return []string{"D1-D3 SEDERAJAT"}
		}
	}

	return nil
}

func resolveLookupID(ctx context.Context, tx *gorm.DB, table string, labelColumn string, v any) (*string, error) {
	normalized := normalizeLookupValue(v)
	if normalized == nil {
		return nil, nil
	}

	value := strings.TrimSpace(*normalized)
	if isNumericText(value) {
		return &value, nil
	}

	candidates := append([]string{value}, lookupAliases(table, labelColumn, value)...)
	seen := map[string]bool{}

	for _, candidate := range candidates {
		candidate = strings.TrimSpace(candidate)
		if candidate == "" {
			continue
		}
		key := strings.ToLower(candidate)
		if seen[key] {
			continue
		}
		seen[key] = true

		var row struct {
			ID string `gorm:"column:id"`
		}

		err := tx.WithContext(ctx).
			Table(table).
			Select("CAST(id AS text) as id").
			Where("statusenabled = TRUE").
			Where(fmt.Sprintf("LOWER(TRIM(%s)) = LOWER(?)", labelColumn), candidate).
			Take(&row).Error
		if err == nil && strings.TrimSpace(row.ID) != "" {
			return &row.ID, nil
		}
		if err != nil && err != gorm.ErrRecordNotFound {
			return nil, err
		}
	}

	var row struct {
		ID string `gorm:"column:id"`
	}
	err := tx.WithContext(ctx).
		Table(table).
		Select("CAST(id AS text) as id").
		Where("statusenabled = TRUE").
		Where(fmt.Sprintf("%s ILIKE ?", labelColumn), "%"+value+"%").
		Order("id asc").
		Take(&row).Error
	if err == nil && strings.TrimSpace(row.ID) != "" {
		return &row.ID, nil
	}
	if err != nil && err != gorm.ErrRecordNotFound {
		return nil, err
	}

	return nil, nil
}

type columnDataType struct {
	DataType string `gorm:"column:data_type"`
	UDTName  string `gorm:"column:udt_name"`
}

func surveyColumnTypes(ctx context.Context, tx *gorm.DB, table string, columns ...string) (map[string]columnDataType, error) {
	rows := []struct {
		ColumnName string `gorm:"column:column_name"`
		DataType   string `gorm:"column:data_type"`
		UDTName    string `gorm:"column:udt_name"`
	}{}

	err := tx.WithContext(ctx).
		Raw(`
			SELECT column_name, data_type, udt_name
			FROM information_schema.columns
			WHERE table_schema = 'public'
				AND table_name = ?
				AND column_name IN ?
		`, table, columns).
		Scan(&rows).Error
	if err != nil {
		return nil, err
	}

	result := map[string]columnDataType{}
	for _, row := range rows {
		result[row.ColumnName] = columnDataType{
			DataType: strings.ToLower(strings.TrimSpace(row.DataType)),
			UDTName:  strings.ToLower(strings.TrimSpace(row.UDTName)),
		}
	}

	return result, nil
}

func isNumericColumn(t columnDataType) bool {
	switch t.UDTName {
	case "int2", "int4", "int8", "float4", "float8", "numeric":
		return true
	}

	switch t.DataType {
	case "smallint", "integer", "bigint", "numeric", "real", "double precision":
		return true
	default:
		return false
	}
}

func nullableScoreValue(v *int, types map[string]columnDataType, column string) any {
	if v == nil {
		return nil
	}
	if isNumericColumn(types[column]) {
		return *v
	}
	return strconv.Itoa(*v)
}

func intColumnValue(v int, types map[string]columnDataType, column string) any {
	if isNumericColumn(types[column]) {
		return v
	}
	return strconv.Itoa(v)
}

func (r *HistoryRepo) GetSurveyPelanggan(ctx context.Context, norecPD string) ([]SurveyHeaderRow, error) {
	var data []SurveyHeaderRow

	err := r.db.WithContext(ctx).
		Table(surveyHeaderTable+" as st").
		Joins("left join jeniskelamin_m as jk on jk.id = st.jeniskelamin").
		Joins("left join pendidikan_m as pd on pd.id = st.pendidikan").
		Select(`
			CAST(st.norec AS text) as norec,
			CAST(st.registrasifk AS text) as registrasifk,
			st.statusenabled,
			CAST(st.petugas AS text) as petugas,
			st.created_at,
			st.updated_at,

			st.namaresponden,
			st.unitdivisikerja,
			st.jabatanresponden,
			st.lamabekerja,

			CAST(st.jeniskelamin AS text) as jeniskelaminfk,
			CAST(st.pendidikan AS text) as pendidikanfk,

			CAST(jk.id AS text) as idjeniskelamin,
			jk.jeniskelamin,
			CAST(pd.id AS text) as idpendidikan,
			pd.pendidikan,

			st.usia,
			st.notelpon,
			st.lamamenjadimitra,

			CAST(st.b21 AS text) as b21,
			st.b22,
			st.b23,
			st.ketidakpuasanlayanan,
			st.masukansaran,
			CAST(st.d1_skor AS text) as d1_skor,
			st.d1_penjelasan,
			CAST(st.d2_skor AS text) as d2_skor,
			st.d2_penjelasan,
			st.namapenanggungjawab
		`).
		Where("st.statusenabled = TRUE").
		Where("st.registrasifk = ?", norecPD).
		Scan(&data).Error
	if err != nil {
		return nil, err
	}

	var detailAll []SurveyDetailRowWithFK
	err = r.db.WithContext(ctx).
		Table(surveyDetailTable).
		Select(`
			CAST(surveyfk AS text) as surveyfk,
			no,
			CAST(harapan AS text) as harapan,
			CAST(kepuasan AS text) as kepuasan
		`).
		Where("statusenabled = TRUE").
		Scan(&detailAll).Error
	if err != nil {
		return nil, err
	}

	grouped := map[string][]SurveyDetailRow{}
	for _, d := range detailAll {
		grouped[d.SurveyFK] = append(grouped[d.SurveyFK], SurveyDetailRow{
			No:       d.No,
			Harapan:  d.Harapan,
			Kepuasan: d.Kepuasan,
		})
	}

	for i := range data {
		data[i].DetailSurvey = grouped[data[i].Norec]
		if data[i].DetailSurvey == nil {
			data[i].DetailSurvey = []SurveyDetailRow{}
		}
	}

	return data, nil
}

func (r *HistoryRepo) SaveSurveyPelanggan(ctx context.Context, p SaveSurveyPayload) error {
	return r.db.WithContext(ctx).Transaction(func(tx *gorm.DB) error {
		var existing struct {
			Norec string `gorm:"column:norec"`
		}

		err := tx.Table(surveyHeaderTable).
			Select("CAST(norec AS text) as norec").
			Where("registrasifk = ?", p.RegistrasiFK).
			Where("statusenabled = TRUE").
			Order("created_at desc NULLS LAST, norec desc").
			Take(&existing).Error
		if err != nil && err != gorm.ErrRecordNotFound {
			return err
		}

		now := time.Now()

		jenisKelamin, err := resolveLookupID(ctx, tx, "jeniskelamin_m", "jeniskelamin", p.JenisKelamin)
		if err != nil {
			return err
		}

		pendidikan, err := resolveLookupID(
			ctx,
			tx,
			"pendidikan_m",
			"pendidikan",
			firstLookupValue(p.NotePendidikanTelpon, p.Pendidikan),
		)
		if err != nil {
			return err
		}

		scoreColumnTypes, err := surveyColumnTypes(ctx, tx, surveyHeaderTable, "b21", "d1_skor", "d2_skor")
		if err != nil {
			return err
		}
		detailColumnTypes, err := surveyColumnTypes(ctx, tx, surveyDetailTable, "no", "harapan", "kepuasan")
		if err != nil {
			return err
		}

		header := map[string]any{
			"registrasifk":         p.RegistrasiFK,
			"namaresponden":        strings.TrimSpace(p.NamaResponden),
			"unitdivisikerja":      strings.TrimSpace(p.UnitDivisiKerja),
			"jabatanresponden":     strings.TrimSpace(p.JabatanResponden),
			"lamabekerja":          strings.TrimSpace(p.LamaBekerja),
			"usia":                 strings.TrimSpace(p.Usia),
			"notelpon":             strings.TrimSpace(p.NoTelpon),
			"lamamenjadimitra":     strings.TrimSpace(p.LamaMenjadiMitra),
			"b21":                  nullableScoreValue(p.B21, scoreColumnTypes, "b21"),
			"b22":                  strings.TrimSpace(p.B22),
			"b23":                  strings.TrimSpace(p.B23),
			"ketidakpuasanlayanan": strings.TrimSpace(p.KetidakpuasanLayanan),
			"masukansaran":         strings.TrimSpace(p.MasukanSaran),
			"d1_skor":              nullableScoreValue(p.D1Skor, scoreColumnTypes, "d1_skor"),
			"d1_penjelasan":        strings.TrimSpace(p.D1Penjelasan),
			"d2_skor":              nullableScoreValue(p.D2Skor, scoreColumnTypes, "d2_skor"),
			"d2_penjelasan":        strings.TrimSpace(p.D2Penjelasan),
			"namapenanggungjawab":  strings.TrimSpace(p.NamaPenanggungJawab),
			"jeniskelamin":         nil,
			"pendidikan":           nil,
			"statusenabled":        true,
			"updated_at":           now,
		}

		if jenisKelamin != nil {
			header["jeniskelamin"] = *jenisKelamin
		}
		if pendidikan != nil {
			header["pendidikan"] = *pendidikan
		}

		var surveyNorec string

		if err == nil && strings.TrimSpace(existing.Norec) != "" {
			if err := tx.Table(surveyHeaderTable).
				Where("norec = ?", existing.Norec).
				Updates(header).Error; err != nil {
				return err
			}
			surveyNorec = existing.Norec

			if err := tx.Table(surveyDetailTable).
				Where("surveyfk = ?", surveyNorec).
				Update("statusenabled", false).Error; err != nil {
				return err
			}
		} else {
			surveyNorec = fmt.Sprintf("SVY-%d", now.UnixNano())
			header["norec"] = surveyNorec
			header["created_at"] = now

			if err := tx.Table(surveyHeaderTable).Create(header).Error; err != nil {
				return err
			}
		}

		for _, a := range p.AtributList {
			row := map[string]any{
				"norec":         fmt.Sprintf("SVYD-%d-%d", now.UnixNano(), a.No),
				"surveyfk":      surveyNorec,
				"no":            intColumnValue(a.No, detailColumnTypes, "no"),
				"harapan":       nullableScoreValue(a.Harapan, detailColumnTypes, "harapan"),
				"kepuasan":      nullableScoreValue(a.Kepuasan, detailColumnTypes, "kepuasan"),
				"statusenabled": true,
				"created_at":    now,
				"updated_at":    now,
			}
			if err := tx.Table(surveyDetailTable).Create(row).Error; err != nil {
				return err
			}
		}

		if err := tx.Table("mitraregistrasi_t").
			Where("norec = ?", p.RegistrasiFK).
			Updates(map[string]any{
				"isikepuasanpelanggan": true,
			}).Error; err != nil {
			return err
		}

		return nil
	})
}
