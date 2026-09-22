package repo

import (
	"context"
	"errors"
	"fmt"
	"io"
	"mime/multipart"
	"os"
	"path/filepath"
	"regexp"
	"strconv"
	"strings"
	"time"

	"github.com/google/uuid"
	"github.com/ulab/ulab-go-api/internal/imageoptimizer"
	"github.com/ulab/ulab-go-api/internal/models"
	"gorm.io/gorm"
	"gorm.io/gorm/clause"
)

type AdminRegistrationRepo struct {
	db             *gorm.DB
	imageOptimizer *imageoptimizer.Client
}

func NewAdminRegistrationRepo(db *gorm.DB, optimizers ...*imageoptimizer.Client) *AdminRegistrationRepo {
	var optimizer *imageoptimizer.Client
	if len(optimizers) > 0 {
		optimizer = optimizers[0]
	}
	return &AdminRegistrationRepo{db: db, imageOptimizer: optimizer}
}

var unsafeUploadFilenameChars = regexp.MustCompile(`[^A-Za-z0-9._-]+`)

type AdminRegistrationFilter struct {
	Dari       string
	Sampai     string
	Search     string
	LokasiFK   string
	UnitFK     string
	JenisOrder string
	LingkupFK  string
	StatusAlat string
	Page       int
	Limit      int
}

type AdminRegistrationSummary struct {
	TotalRegistrasi int64 `json:"total_registrasi" gorm:"column:total_registrasi"`
	TotalAlat       int64 `json:"total_alat" gorm:"column:total_alat"`
	TotalSelesai    int64 `json:"total_selesai" gorm:"column:total_selesai"`
	TotalDiambil    int64 `json:"total_diambil" gorm:"column:total_diambil"`
	BelumSelesai    int64 `json:"belum_selesai"`
	BelumDiambil    int64 `json:"belum_diambil"`
	BelumKaji       int64 `json:"belum_kaji" gorm:"column:belum_kaji"`
	SudahKaji       int64 `json:"sudah_kaji" gorm:"column:sudah_kaji"`
	VerifAsman      int64 `json:"verif_asman" gorm:"column:verif_asman"`
	MenungguAdmin   int64 `json:"menunggu_admin" gorm:"column:menunggu_admin"`
}

type AdminTopUnitItem struct {
	UnitFK          string `json:"unitfk" gorm:"column:unitfk"`
	NamaPerusahaan  string `json:"namaperusahaan" gorm:"column:namaperusahaan"`
	TotalRegistrasi int64  `json:"total_registrasi" gorm:"column:total_registrasi"`
	TotalAlat       int64  `json:"total_alat" gorm:"column:total_alat"`
	TotalSelesai    int64  `json:"total_selesai" gorm:"column:total_selesai"`
	TotalDiambil    int64  `json:"total_diambil" gorm:"column:total_diambil"`
}

type AdminJenisOrderItem struct {
	JenisOrder string `json:"jenisorder" gorm:"column:jenisorder"`
	Total      int64  `json:"total" gorm:"column:total"`
}

type AdminDashboardStats struct {
	Summary      AdminRegistrationSummary `json:"summary"`
	ByUnit       []AdminTopUnitItem       `json:"by_unit"`
	ByJenisOrder []AdminJenisOrderItem    `json:"by_jenisorder"`
}

type AdminRegistrationUnitItem struct {
	ID                  string  `json:"id" gorm:"column:id"`
	NamaPerusahaan      string  `json:"namaperusahaan" gorm:"column:namaperusahaan"`
	Email               *string `json:"email" gorm:"column:email"`
	NoHP                *string `json:"nohp" gorm:"column:nohp"`
	TglRegistrasi       string  `json:"tglregistrasi" gorm:"column:tglregistrasi"`
	NoPendaftaran       *string `json:"nopendaftaran" gorm:"column:nopendaftaran"`
	IDDetail            string  `json:"iddetail" gorm:"column:iddetail"`
	IsKaji              *bool   `json:"iskaji" gorm:"column:iskaji"`
	StatusOrder         *int    `json:"statusorder" gorm:"column:statusorder"`
	JenisOrder          string  `json:"jenisorder" gorm:"column:jenisorder"`
	IsRegisCustomer     *bool   `json:"isregiscustomer" gorm:"column:isregiscustomer"`
	VerifRegisCustomer  *bool   `json:"verifregiscustomer" gorm:"column:verifregiscustomer"`
	IsStandarUlab       *bool   `json:"isstandarulab" gorm:"column:isstandarulab"`
	IsKalibrasiInternal *bool   `json:"iskalibrasiinternal" gorm:"column:iskalibrasiinternal"`
	FileCustomerAMS     *string `json:"filecustomerams" gorm:"column:filecustomerams"`
	Lokasi              *string `json:"lokasi" gorm:"column:lokasi"`
	JumlahDetail        int     `json:"jumlahdetail"`
	JumlahSelesai       int     `json:"jumlahselesai"`
	JumlahDiterima      int     `json:"jumlahditerima"`
	JumlahBelumSelesai  int     `json:"jumlahbelumselesai"`
	JumlahBelumTerima   int     `json:"jumlahbelumterima"`
}

type AdminRegistrationToolItem struct {
	Norec                         string  `json:"norec" gorm:"column:norec"`
	NorecDetail                   string  `json:"norec_detail" gorm:"column:norec_detail"`
	IsStandarUlab                 *bool   `json:"isstandarulab" gorm:"column:isstandarulab"`
	IsKalibrasiInternal           *bool   `json:"iskalibrasiinternal" gorm:"column:iskalibrasiinternal"`
	IsKaji                        *bool   `json:"iskaji" gorm:"column:iskaji"`
	TglRegistrasi                 string  `json:"tglregistrasi" gorm:"column:tglregistrasi"`
	NoPendaftaran                 *string `json:"nopendaftaran" gorm:"column:nopendaftaran"`
	JenisOrder                    string  `json:"jenisorder" gorm:"column:jenisorder"`
	NamaProduk                    string  `json:"namaproduk" gorm:"column:namaproduk"`
	NamaMerk                      *string `json:"namamerk" gorm:"column:namamerk"`
	NamaTipe                      *string `json:"namatipe" gorm:"column:namatipe"`
	NamaSerialNumber              *string `json:"namaserialnumber" gorm:"column:namaserialnumber"`
	NamaPerusahaan                string  `json:"namaperusahaan" gorm:"column:namaperusahaan"`
	IDUnit                        string  `json:"idunit" gorm:"column:idunit"`
	PenyeliaTeknik                *string `json:"penyeliateknik" gorm:"column:penyeliateknik"`
	PelaksanaTeknik               *string `json:"pelaksanateknik" gorm:"column:pelaksanateknik"`
	Lokasi                        *string `json:"lokasi" gorm:"column:lokasi"`
	LingkupKalibrasi              *string `json:"lingkupkalibrasi" gorm:"column:lingkupkalibrasi"`
	NoOrderAlat                   *string `json:"noorderalat" gorm:"column:noorderalat"`
	IsVendor                      *bool   `json:"isVendor" gorm:"column:isVendor"`
	NamaVendor                    *string `json:"namavendor" gorm:"column:namavendor"`
	FileSertiVendor               *string `json:"fileSertiVendor" gorm:"column:fileSertiVendor"`
	VersiSertifikat               *int    `json:"versisertifikat" gorm:"column:versisertifikat"`
	VersiLaporanRepair            *int    `json:"versilaporanrepair" gorm:"column:versilaporanrepair"`
	IsTerima                      *bool   `json:"isterima" gorm:"column:isterima"`
	StatusOrderManager            *int    `json:"statusordermanager" gorm:"column:statusordermanager"`
	StatusAktifPendaftaranAlat    *bool   `json:"statusaktifpendaftaranalat" gorm:"column:statusaktifpendaftaranalat"`
	PelaksanaIsiLembarKerjaFK     *int64  `json:"pelaksanaisilembarkerjafk" gorm:"column:pelaksanaisilembarkerjafk"`
	PelaksanaIsiLaporanRepairFK   *int64  `json:"pelaksanaisilaporanrepairfk" gorm:"column:pelaksanaisilaporanrepairfk"`
	TglSetujuManagerLembarKerja   *string `json:"tglsetujumanagerlembarkerja" gorm:"column:tglsetujumanagerlembarkerja"`
	TglSetujuManagerLaporanRepair *string `json:"tglsetujumanagerlaporanrepair" gorm:"column:tglsetujumanagerlaporanrepair"`
	StatusLabel                   string  `json:"status_label" gorm:"-"`
}

type AdminLookupItem struct {
	ID    string `json:"id" gorm:"column:id"`
	Label string `json:"label" gorm:"column:label"`
}

type AdminUnitToolItem struct {
	ID                  int     `json:"id" gorm:"column:id"`
	NamaProduk          string  `json:"namaproduk" gorm:"column:namaproduk"`
	NamaMerk            string  `json:"namamerk" gorm:"column:namamerk"`
	NamaTipe            string  `json:"namatipe" gorm:"column:namatipe"`
	NamaSerialNumber    string  `json:"namaserialnumber" gorm:"column:namaserialnumber"`
	FotoProduk          *string `json:"fotoproduk" gorm:"column:fotoproduk"`
	FotoProdukThumbnail *string `json:"fotoproduk_thumbnail,omitempty" gorm:"-"`
	ObjectMitraFK       string  `json:"objectmitrafk" gorm:"column:objectmitrafk"`
	NamaPerusahaan      string  `json:"namaperusahaan" gorm:"column:namaperusahaan"`
}

type AdminSaveToolRequest struct {
	ID               int64  `json:"id"`
	UnitID           string `json:"unit_id"`
	NamaProduk       string `json:"namaproduk"`
	NamaMerk         string `json:"namamerk"`
	NamaTipe         string `json:"namatipe"`
	NamaSerialNumber string `json:"namaserialnumber"`
	StatusEnabled    *bool  `json:"statusenabled"`
	NamaFileLama     string `json:"namaFileLama"`
	FileMitra        *multipart.FileHeader
}

type AdminSaveUnitRequest struct {
	ID                string `json:"id"`
	NamaPerusahaan    string `json:"namaperusahaan"`
	NoHP              string `json:"nohp"`
	Email             string `json:"email"`
	Alamat            string `json:"alamat"`
	RTRW              string `json:"rtrw"`
	KodePos           string `json:"kodepos"`
	ObjectNegaraFK    string `json:"objectnegarafk"`
	ObjectPropinsiFK  string `json:"objectpropinsifk"`
	ObjectKotaFK      string `json:"objectkotakabupatenfk"`
	ObjectKecamatanFK string `json:"objectkecamatanfk"`
	ObjectKelurahanFK string `json:"objectdesakelurahanfk"`
	Progress          int    `json:"progress"`
}

type AdminRegistrationDetailInput struct {
	NamaAlatFK       string `json:"namaalatfk"`
	LingkupFK        string `json:"lingkupkalibrasifk"`
	PenyeliaTeknikFK string `json:"penyeliateknikfk"`
	PelaksanaFK      string `json:"pelaksanateknikfk"`
}

type AdminSaveRegistrationRequest struct {
	UnitID                            string                         `json:"nomitrafk"`
	JenisOrder                        string                         `json:"jenisorder"`
	TglRegistrasi                     string                         `json:"tglregistrasi"`
	LokasiKalibrasi                   string                         `json:"lokasikalibrasi"`
	LokasiRepair                      string                         `json:"lokasirepair"`
	PaketKalibrasi                    string                         `json:"paketkalibrasi"`
	NamaPenanggungJawab               string                         `json:"namapenanggungjawab"`
	NoHPPenanggungJawab               string                         `json:"nohppenanggungjawab"`
	JabatanPenanggungJawab            string                         `json:"jabatanpenanggungjawab"`
	Catatan                           string                         `json:"catatan"`
	RentangUkur                       string                         `json:"rentangUkur"`
	RentangUkurKetPermintaanPelanggan string                         `json:"rentangUkurketPermintaanPelanggan"`
	IsKalibrasiInternal               bool                           `json:"iskalibrasiinternal"`
	IsStandarUlab                     bool                           `json:"isstandarulab"`
	PetugasKajiFK                     string                         `json:"petugaskaji"`
	Details                           []AdminRegistrationDetailInput `json:"mitraregistrasidetail"`
}

type AdminSaveRegistrationResult struct {
	Norec         string `json:"norec"`
	NoPendaftaran string `json:"nopendaftaran"`
	JenisOrder    string `json:"jenisorder"`
	JumlahAlat    int    `json:"jumlah_alat"`
}

type AdminKajiDetailItem struct {
	Norec             string   `json:"norec" gorm:"column:norec"`
	NorecDetail       string   `json:"norec_detail" gorm:"column:norec_detail"`
	IsKaji            *bool    `json:"iskaji" gorm:"column:iskaji"`
	NamaFile          *string  `json:"namafile" gorm:"column:namafile"`
	Keterangan        *string  `json:"keterangan" gorm:"column:keterangan"`
	DurasiKalibrasi   *int     `json:"durasikalbrasi" gorm:"column:durasikalbrasi"`
	NamaProduk        string   `json:"namaproduk" gorm:"column:namaproduk"`
	TglRegistrasi     string   `json:"tglregistrasi" gorm:"column:tglregistrasi"`
	NoPendaftaran     *string  `json:"nopendaftaran" gorm:"column:nopendaftaran"`
	PaketKalibrasi    *string  `json:"paketkalibrasi" gorm:"column:paketkalibrasi"`
	NamaPaket         *string  `json:"namapaket" gorm:"column:namapaket"`
	Catatan           *string  `json:"catatan" gorm:"column:catatan"`
	NamaMerk          *string  `json:"namamerk" gorm:"column:namamerk"`
	NamaTipe          *string  `json:"namatipe" gorm:"column:namatipe"`
	NamaSerialNumber  *string  `json:"namaserialnumber" gorm:"column:namaserialnumber"`
	NamaPerusahaan    string   `json:"namaperusahaan" gorm:"column:namaperusahaan"`
	PenyeliaTeknikFK  *string  `json:"penyeliateknikfk" gorm:"column:penyeliateknikfk"`
	PenyeliaTeknik    *string  `json:"penyeliateknik" gorm:"column:penyeliateknik"`
	PelaksanaTeknikFK *string  `json:"pelaksanateknikfk" gorm:"column:pelaksanateknikfk"`
	PelaksanaTeknik   *string  `json:"pelaksanateknik" gorm:"column:pelaksanateknik"`
	LokasiKalibrasiFK *string  `json:"lokasikalibrasifk" gorm:"column:lokasikalibrasifk"`
	LokasiRepairFK    *string  `json:"lokasirepairfk" gorm:"column:lokasirepairfk"`
	Lokasi            *string  `json:"lokasi" gorm:"column:lokasi"`
	LingkupFK         *string  `json:"lingkupfk" gorm:"column:lingkupfk"`
	LingkupKalibrasi  *string  `json:"lingkupkalibrasi" gorm:"column:lingkupkalibrasi"`
	IsRegisCustomer   *bool    `json:"isregiscustomer" gorm:"column:isregiscustomer"`
	JenisSurkesFK     *string  `json:"jenissurkesfk" gorm:"column:jenissurkesfk"`
	JenisSurkes       *string  `json:"jenissurkes" gorm:"column:jenissurkes"`
	NamaAlatFK        *string  `json:"namaalatfk" gorm:"column:namaalatfk"`
	IDUnit            *string  `json:"idunit" gorm:"column:idunit"`
	MappingSurkesID   int      `json:"mapping_surkes_id" gorm:"column:mapping_surkes_id"`
	IsVendor          *bool    `json:"isVendor" gorm:"column:isVendor"`
	VendorKalibrasiFK *string  `json:"vendorkalibrasifk" gorm:"column:vendorkalibrasifk"`
	NamaVendor        *string  `json:"namavendor" gorm:"column:namavendor"`
	FotoFiles         []string `json:"foto_files" gorm:"-"`
}

type AdminKajiMeta struct {
	Details        []AdminKajiDetailItem `json:"detail"`
	Length         int                   `json:"length"`
	TotalDurasi    int                   `json:"totalDurasi"`
	TanggalSelesai string                `json:"tanggalSelesai"`
	Manager        *AdminLookupItem      `json:"manager"`
	Asman          *AdminLookupItem      `json:"asman"`
}

type AdminKajiItemRequest struct {
	NoregistrasiFK    string
	NorecDetail       string
	Keterangan        string
	TanggalKajian     string
	LokasiKalibrasi   string
	LokasiRepairFK    string
	LingkupKalibrasi  string
	PenyeliaTeknikFK  string
	PelaksanaFK       string
	StatusSurkes      string
	Manager           string
	NamaAsman         string
	DurasiKalibrasi   string
	VendorKalibrasiFK string
	IsVendor          bool
	Files             []*multipart.FileHeader
}

func (f *AdminRegistrationFilter) normalize() {
	if f.Page <= 0 {
		f.Page = 1
	}
	if f.Limit <= 0 {
		f.Limit = 10
	}
	if f.Limit > 100 {
		f.Limit = 100
	}
	if strings.TrimSpace(f.Dari) == "" {
		now := time.Now()
		f.Dari = time.Date(now.Year(), 1, 1, 0, 0, 0, 0, now.Location()).Format("2006-01-02 15:04:05")
	}
	if strings.TrimSpace(f.Sampai) == "" {
		f.Sampai = time.Now().Format("2006-01-02 15:04:05")
	}
}

func (r *AdminRegistrationRepo) registrationBase(ctx context.Context, f AdminRegistrationFilter) *gorm.DB {
	f.normalize()

	q := r.db.WithContext(ctx).
		Table("mitraregistrasi_t as mtr").
		Joins("join mitra_m as mt on mt.id = mtr.nomitrafk").
		Where("mt.statusenabled = TRUE").
		Where("mtr.statusenabled = TRUE").
		Where("mtr.tglregistrasi >= ?", f.Dari).
		Where("mtr.tglregistrasi <= ?", f.Sampai)

	if strings.TrimSpace(f.Search) != "" {
		like := "%" + strings.TrimSpace(f.Search) + "%"
		q = q.Where("(mt.namaperusahaan ILIKE ? OR mtr.nopendaftaran ILIKE ?)", like, like)
	}
	if strings.TrimSpace(f.LokasiFK) != "" {
		q = q.Where("COALESCE(mtr.lokasikalibrasi, mtr.lokasirepair) = ?", f.LokasiFK)
	}
	if strings.TrimSpace(f.JenisOrder) != "" {
		q = q.Where("mtr.jenisorder = ?", f.JenisOrder)
	}
	if strings.TrimSpace(f.UnitFK) != "" {
		q = q.Where("mt.id = ?", f.UnitFK)
	}

	return q
}

func (r *AdminRegistrationRepo) DashboardStats(ctx context.Context, f AdminRegistrationFilter) (*AdminDashboardStats, error) {
	baseSelect := r.registrationBase(ctx, f).Select(`
		mtr.norec,
		mtr.tglregistrasi,
		mtr.jenisorder,
		mtr.iskaji,
		mtr.statusorder,
		mtr.isregiscustomer,
		mtr.verifregiscustomer,
		mt.id as unitfk,
		mt.namaperusahaan
	`)

	selesaiCase := `
		CASE
			WHEN reg.jenisorder = 'kalibrasi' AND mtrd.tglsetujumanagerlembarkerja IS NOT NULL THEN 1
			WHEN reg.jenisorder = 'repair' AND mtrd.tglsetujumanagerlaporanrepair IS NOT NULL THEN 1
			ELSE 0
		END
	`

	var summary AdminRegistrationSummary
	err := r.db.WithContext(ctx).
		Table("(?) as reg", baseSelect).
		Joins("left join mitraregistrasidetail_t as mtrd on mtrd.noregistrasifk = reg.norec and mtrd.statusenabled = TRUE").
		Select(`
			COUNT(DISTINCT reg.norec) as total_registrasi,
			COUNT(mtrd.norec) as total_alat,
			COALESCE(SUM(` + selesaiCase + `), 0) as total_selesai,
			COALESCE(SUM(CASE WHEN COALESCE(mtrd.isterima, false) = true THEN 1 ELSE 0 END), 0) as total_diambil
		`).
		Scan(&summary).Error
	if err != nil {
		return nil, err
	}

	var statusSummary AdminRegistrationSummary
	err = r.db.WithContext(ctx).
		Table("(?) as reg", r.registrationBase(ctx, f).Select(`
			mtr.norec,
			mtr.iskaji,
			mtr.statusorder,
			mtr.isregiscustomer,
			mtr.verifregiscustomer
		`)).
		Select(`
			COALESCE(SUM(CASE WHEN reg.iskaji IS NULL OR reg.iskaji = false THEN 1 ELSE 0 END), 0) as belum_kaji,
			COALESCE(SUM(CASE WHEN reg.iskaji = true AND COALESCE(reg.statusorder, 0) <> 1 THEN 1 ELSE 0 END), 0) as sudah_kaji,
			COALESCE(SUM(CASE WHEN COALESCE(reg.statusorder, 0) = 1 THEN 1 ELSE 0 END), 0) as verif_asman,
			COALESCE(SUM(CASE WHEN reg.isregiscustomer = true AND reg.verifregiscustomer IS NULL THEN 1 ELSE 0 END), 0) as menunggu_admin
		`).
		Scan(&statusSummary).Error
	if err != nil {
		return nil, err
	}

	summary.BelumKaji = statusSummary.BelumKaji
	summary.SudahKaji = statusSummary.SudahKaji
	summary.VerifAsman = statusSummary.VerifAsman
	summary.MenungguAdmin = statusSummary.MenungguAdmin
	summary.BelumSelesai = summary.TotalAlat - summary.TotalSelesai
	if summary.BelumSelesai < 0 {
		summary.BelumSelesai = 0
	}
	summary.BelumDiambil = summary.TotalAlat - summary.TotalDiambil
	if summary.BelumDiambil < 0 {
		summary.BelumDiambil = 0
	}

	var byUnit []AdminTopUnitItem
	err = r.db.WithContext(ctx).
		Table("(?) as reg", r.registrationBase(ctx, f).Select("mtr.norec, mtr.jenisorder, mt.id as unitfk, mt.namaperusahaan")).
		Joins("left join mitraregistrasidetail_t as mtrd on mtrd.noregistrasifk = reg.norec and mtrd.statusenabled = TRUE").
		Select(`
			CAST(reg.unitfk AS text) as unitfk,
			reg.namaperusahaan,
			COUNT(DISTINCT reg.norec) as total_registrasi,
			COUNT(mtrd.norec) as total_alat,
			COALESCE(SUM(` + selesaiCase + `), 0) as total_selesai,
			COALESCE(SUM(CASE WHEN COALESCE(mtrd.isterima, false) = true THEN 1 ELSE 0 END), 0) as total_diambil
		`).
		Group("reg.unitfk, reg.namaperusahaan").
		Order("COUNT(DISTINCT reg.norec) DESC").
		Limit(8).
		Scan(&byUnit).Error
	if err != nil {
		return nil, err
	}

	var byJenis []AdminJenisOrderItem
	err = r.db.WithContext(ctx).
		Table("(?) as reg", r.registrationBase(ctx, f).Select("mtr.jenisorder")).
		Select(`
			COALESCE(NULLIF(reg.jenisorder, ''), 'lainnya') as jenisorder,
			COUNT(*) as total
		`).
		Group("COALESCE(NULLIF(reg.jenisorder, ''), 'lainnya')").
		Order("total DESC").
		Scan(&byJenis).Error
	if err != nil {
		return nil, err
	}

	return &AdminDashboardStats{
		Summary:      summary,
		ByUnit:       byUnit,
		ByJenisOrder: byJenis,
	}, nil
}

func (r *AdminRegistrationRepo) ListUnits(ctx context.Context, f AdminRegistrationFilter) ([]AdminRegistrationUnitItem, int64, error) {
	f.normalize()
	offset := (f.Page - 1) * f.Limit

	q := r.registrationBase(ctx, f).
		Joins("left join lokasikalibrasi_m as lk on lk.id = COALESCE(mtr.lokasikalibrasi, mtr.lokasirepair)").
		Select(`
			CAST(mt.id AS text) as id,
			mt.namaperusahaan,
			mt.email,
			mt.nohp,
			TO_CHAR(mtr.tglregistrasi, 'YYYY-MM-DD HH24:MI:SS') as tglregistrasi,
			mtr.nopendaftaran,
			CAST(mtr.norec AS text) as iddetail,
			mtr.iskaji,
			mtr.statusorder,
			COALESCE(mtr.jenisorder, '') as jenisorder,
			mtr.isregiscustomer,
			mtr.verifregiscustomer,
			mtr.isstandarulab,
			mtr.iskalibrasiinternal,
			mtr.filecustomerams,
			lk.lokasi
		`)

	var total int64
	if err := q.Count(&total).Error; err != nil {
		return nil, 0, err
	}

	var rows []AdminRegistrationUnitItem
	if err := q.Order("mtr.tglregistrasi DESC").Limit(f.Limit).Offset(offset).Scan(&rows).Error; err != nil {
		return nil, 0, err
	}

	if len(rows) == 0 {
		return rows, total, nil
	}

	regisIDs := make([]string, 0, len(rows))
	for _, row := range rows {
		regisIDs = append(regisIDs, row.IDDetail)
	}

	type detailCounter struct {
		NoregistrasiFK string `gorm:"column:noregistrasifk"`
		Total          int    `gorm:"column:total"`
		Selesai        int    `gorm:"column:selesai"`
		Diterima       int    `gorm:"column:diterima"`
	}

	var counters []detailCounter
	err := r.db.WithContext(ctx).
		Table("mitraregistrasidetail_t as d").
		Joins("join mitraregistrasi_t as h on h.norec = d.noregistrasifk").
		Select(`
			CAST(d.noregistrasifk AS text) as noregistrasifk,
			COUNT(*) as total,
			COALESCE(SUM(CASE
				WHEN h.jenisorder = 'kalibrasi' AND d.tglsetujumanagerlembarkerja IS NOT NULL THEN 1
				WHEN h.jenisorder = 'repair' AND d.tglsetujumanagerlaporanrepair IS NOT NULL THEN 1
				ELSE 0
			END), 0) as selesai,
			COALESCE(SUM(CASE WHEN COALESCE(d.isterima, false) = true THEN 1 ELSE 0 END), 0) as diterima
		`).
		Where("d.statusenabled = TRUE").
		Where("d.noregistrasifk IN ?", regisIDs).
		Group("d.noregistrasifk").
		Scan(&counters).Error
	if err != nil {
		return nil, 0, err
	}

	counterByID := map[string]detailCounter{}
	for _, counter := range counters {
		counterByID[counter.NoregistrasiFK] = counter
	}

	for i := range rows {
		counter := counterByID[rows[i].IDDetail]
		rows[i].JumlahDetail = counter.Total
		rows[i].JumlahSelesai = counter.Selesai
		rows[i].JumlahDiterima = counter.Diterima
		rows[i].JumlahBelumSelesai = counter.Total - counter.Selesai
		rows[i].JumlahBelumTerima = counter.Total - counter.Diterima
	}

	return rows, total, nil
}

func (r *AdminRegistrationRepo) ListTools(ctx context.Context, f AdminRegistrationFilter) ([]AdminRegistrationToolItem, int64, error) {
	f.normalize()
	offset := (f.Page - 1) * f.Limit

	q := r.db.WithContext(ctx).
		Table("mitraregistrasi_t as mtr").
		Joins("join mitraregistrasidetail_t as mtrd on mtrd.noregistrasifk = mtr.norec").
		Joins("left join mapalatstandar_m as mmps on mmps.id = mtrd.namaalatfk and mmps.statusenabled = TRUE and mtr.isstandarulab = TRUE").
		Joins("left join mapunittoalat_m as mmp on mmp.id = mtrd.namaalatfk and mmp.statusenabled = TRUE and mtr.isstandarulab IS NULL").
		Joins("join mitra_m as mt on mt.id = mtr.nomitrafk").
		Joins("left join pegawai_m as pg on pg.id = mtrd.penyeliateknikfk").
		Joins("left join pegawai_m as pg2 on pg2.id = mtrd.pelaksanateknikfk").
		Joins("left join lokasikalibrasi_m as lk on lk.id = COALESCE(mtrd.lokasikajifk, mtrd.lokasirepairfk, mtr.lokasikalibrasi, mtr.lokasirepair)").
		Joins("left join lingkupkalibrasi_m as lp on lp.id = mtrd.lingkupkalibrasifk").
		Joins("left join vendor_m as vm on vm.id = mtrd.vendorkalibrasifk").
		Where("mtr.statusenabled = TRUE").
		Where("mtr.tglregistrasi::date >= ?", shortDate(f.Dari)).
		Where("mtr.tglregistrasi::date <= ?", shortDate(f.Sampai))

	if strings.TrimSpace(f.Search) != "" {
		like := "%" + strings.TrimSpace(f.Search) + "%"
		q = q.Where(`
			(
				mt.namaperusahaan ILIKE ?
				OR mtrd.noorderalat ILIKE ?
				OR mtr.nopendaftaran ILIKE ?
				OR COALESCE(mmps.namaalatstandar, mmp.namaproduk) ILIKE ?
				OR COALESCE(mmps.namamerk, mmp.namamerk) ILIKE ?
				OR COALESCE(mmps.namatipe, mmp.namatipe) ILIKE ?
				OR COALESCE(mmps.namaserialnumber, mmp.namaserialnumber) ILIKE ?
			)
		`, like, like, like, like, like, like, like)
	}
	if strings.TrimSpace(f.LokasiFK) != "" {
		q = q.Where("COALESCE(mtr.lokasikalibrasi, mtr.lokasirepair) = ?", f.LokasiFK)
	}
	if strings.TrimSpace(f.UnitFK) != "" {
		q = q.Where("mt.id = ?", f.UnitFK)
	}
	if strings.TrimSpace(f.JenisOrder) != "" {
		q = q.Where("mtr.jenisorder = ?", f.JenisOrder)
	}
	if strings.TrimSpace(f.LingkupFK) != "" {
		q = q.Where("mtrd.lingkupkalibrasifk = ?", f.LingkupFK)
	}

	statusAlat := strings.ToLower(strings.TrimSpace(f.StatusAlat))
	doneExpr := `(
		(mtr.jenisorder = 'repair' AND mtrd.tglsetujumanagerlaporanrepair IS NOT NULL)
		OR (mtr.jenisorder <> 'repair' AND mtrd.tglsetujumanagerlembarkerja IS NOT NULL)
		OR COALESCE(mtrd.statusordermanager, 0) = 2
	)`
	cancelledExpr := `(COALESCE(mtrd.statusenabled, FALSE) = FALSE OR mtrd.alasanpembatalanorderalat IS NOT NULL OR mtrd.ketgagalkalibrasi IS NOT NULL)`
	switch statusAlat {
	case "selesai", "done":
		q = q.Where("mtrd.statusenabled = TRUE").Where("NOT " + cancelledExpr).Where(doneExpr)
	case "batal", "cancelled", "dibatalkan":
		q = q.Where(cancelledExpr)
	case "belum", "pending":
		q = q.Where("mtrd.statusenabled = TRUE").Where("NOT " + cancelledExpr).Where("NOT " + doneExpr)
	default:
		q = q.Where("mtrd.statusenabled = TRUE")
	}

	var total int64
	if err := q.Count(&total).Error; err != nil {
		return nil, 0, err
	}

	var rows []AdminRegistrationToolItem
	err := q.Select(`
			CAST(mtr.norec AS text) as norec,
			CAST(mtrd.norec AS text) as norec_detail,
			mtr.isstandarulab,
			mtr.iskalibrasiinternal,
			mtrd.iskaji,
			TO_CHAR(mtr.tglregistrasi, 'YYYY-MM-DD HH24:MI:SS') as tglregistrasi,
			mtr.nopendaftaran,
			COALESCE(mtr.jenisorder, '') as jenisorder,
			COALESCE(mmps.namaalatstandar, mmp.namaproduk, '-') as namaproduk,
			COALESCE(mmps.namamerk, mmp.namamerk) as namamerk,
			COALESCE(mmps.namatipe, mmp.namatipe) as namatipe,
			COALESCE(mmps.namaserialnumber, mmp.namaserialnumber) as namaserialnumber,
			mt.namaperusahaan,
			CAST(mt.id AS text) as idunit,
			pg.namalengkap as penyeliateknik,
			pg2.namalengkap as pelaksanateknik,
			lk.lokasi,
			lp.lingkupkalibrasi,
			mtrd.noorderalat,
			mtrd."isVendor",
			vm.namavendor,
			mtrd."fileSertiVendor",
			mtrd.versisertifikat,
			mtrd.versilaporanrepair,
			mtrd.isterima,
			mtrd.statusordermanager,
			mtrd.statusenabled as statusaktifpendaftaranalat,
			mtrd.pelaksanaisilembarkerjafk,
			mtrd.pelaksanaisilaporanrepairfk,
			CASE WHEN mtrd.tglsetujumanagerlembarkerja IS NOT NULL THEN TO_CHAR(mtrd.tglsetujumanagerlembarkerja, 'YYYY-MM-DD HH24:MI:SS') END as tglsetujumanagerlembarkerja,
			CASE WHEN mtrd.tglsetujumanagerlaporanrepair IS NOT NULL THEN TO_CHAR(mtrd.tglsetujumanagerlaporanrepair, 'YYYY-MM-DD HH24:MI:SS') END as tglsetujumanagerlaporanrepair
		`).
		Order("mtr.tglregistrasi DESC, lp.lingkupkalibrasi ASC NULLS LAST").
		Limit(f.Limit).
		Offset(offset).
		Scan(&rows).Error
	if err != nil {
		return nil, 0, err
	}

	for i := range rows {
		rows[i].StatusLabel = adminToolStatusLabel(rows[i])
	}

	return rows, total, nil
}

func (r *AdminRegistrationRepo) ListLokasi(ctx context.Context) ([]AdminLookupItem, error) {
	var rows []AdminLookupItem
	err := r.db.WithContext(ctx).
		Table("lokasikalibrasi_m").
		Select("CAST(id AS text) as id, COALESCE(lokasi, '-') as label").
		Where("statusenabled = TRUE").
		Order("lokasi ASC").
		Scan(&rows).Error
	return rows, err
}

func (r *AdminRegistrationRepo) SearchUnits(ctx context.Context, search string, limit int) ([]AdminLookupItem, error) {
	if limit <= 0 {
		limit = 20
	}
	if limit > 50 {
		limit = 50
	}

	q := r.db.WithContext(ctx).
		Table("mitra_m").
		Select("CAST(id AS text) as id, COALESCE(namaperusahaan, '-') as label").
		Where("statusenabled = TRUE")

	if strings.TrimSpace(search) != "" {
		q = q.Where("namaperusahaan ILIKE ?", "%"+strings.TrimSpace(search)+"%")
	}

	var rows []AdminLookupItem
	err := q.Order("namaperusahaan ASC").Limit(limit).Scan(&rows).Error
	return rows, err
}

func (r *AdminRegistrationRepo) SearchLabUnits(ctx context.Context, search string, limit int) ([]AdminLookupItem, error) {
	if limit <= 0 {
		limit = 10
	}
	if limit > 20 {
		limit = 20
	}

	q := r.db.WithContext(ctx).
		Table("mitra_m").
		Select("CAST(id AS text) as id, COALESCE(namaperusahaan, '-') as label").
		Where("statusenabled = TRUE").
		Where("islab = TRUE")

	if strings.TrimSpace(search) != "" {
		q = q.Where("namaperusahaan ILIKE ?", "%"+strings.TrimSpace(search)+"%")
	}

	var rows []AdminLookupItem
	err := q.Order("namaperusahaan ASC").Limit(limit).Scan(&rows).Error
	return rows, err
}

func (r *AdminRegistrationRepo) SearchEmployees(ctx context.Context, lokasiID string, jenisPegawai int, search string, limit int) ([]AdminLookupItem, error) {
	if limit <= 0 {
		limit = 20
	}
	if limit > 50 {
		limit = 50
	}

	q := r.db.WithContext(ctx).
		Table("pegawai_m").
		Select("CAST(id AS text) as id, COALESCE(namalengkap, '-') as label").
		Where("statusenabled = TRUE").
		Where("statuspegawaifk = 1")

	if strings.TrimSpace(lokasiID) != "" {
		q = q.Where("CAST(lokasikalibrasifk AS text) = ?", strings.TrimSpace(lokasiID))
	}
	if jenisPegawai > 0 {
		q = q.Where("objectjenispegawaifk = ?", jenisPegawai)
	}
	if strings.TrimSpace(search) != "" {
		q = q.Where("namalengkap ILIKE ?", "%"+strings.TrimSpace(search)+"%")
	}

	var rows []AdminLookupItem
	err := q.Order("namalengkap ASC").Limit(limit).Scan(&rows).Error
	return rows, err
}

func (r *AdminRegistrationRepo) SearchUnitTools(ctx context.Context, unitID, search string, page, limit int) ([]AdminUnitToolItem, int64, error) {
	if page <= 0 {
		page = 1
	}
	if limit <= 0 {
		limit = 20
	}
	if limit > 100 {
		limit = 100
	}

	q := r.db.WithContext(ctx).
		Table("mapunittoalat_m as mmp").
		Joins("LEFT JOIN mitra_m as mm ON CAST(mm.id AS text) = CAST(mmp.objectmitrafk AS text)").
		Select(`
			mmp.id,
			COALESCE(mmp.namaproduk, '-') as namaproduk,
			COALESCE(mmp.namamerk, '-') as namamerk,
			COALESCE(mmp.namatipe, '-') as namatipe,
			COALESCE(mmp.namaserialnumber, '-') as namaserialnumber,
			mmp.fotoproduk,
			CAST(mmp.objectmitrafk AS text) as objectmitrafk,
			COALESCE(mm.namaperusahaan, '-') as namaperusahaan
		`).
		Where("mmp.statusenabled = TRUE")

	if strings.TrimSpace(unitID) != "" {
		q = q.Where("CAST(mmp.objectmitrafk AS text) = ?", strings.TrimSpace(unitID))
	}

	if strings.TrimSpace(search) != "" {
		like := "%" + strings.TrimSpace(search) + "%"
		q = q.Where(`
			(
				mmp.namaproduk ILIKE ?
				OR mmp.namamerk ILIKE ?
				OR mmp.namatipe ILIKE ?
				OR mmp.namaserialnumber ILIKE ?
				OR mm.namaperusahaan ILIKE ?
				OR CAST(mmp.id AS text) ILIKE ?
			)
		`, like, like, like, like, like, like)
	}

	var total int64
	if err := q.Count(&total).Error; err != nil {
		return nil, 0, err
	}

	var rows []AdminUnitToolItem
	err := q.Order("mmp.namaproduk ASC, mmp.id DESC").
		Offset((page - 1) * limit).
		Limit(limit).
		Scan(&rows).Error
	for i := range rows {
		rows[i].FotoProdukThumbnail = toolThumbnailFilename(rows[i].FotoProduk)
	}
	return rows, total, err
}

func (r *AdminRegistrationRepo) SearchStandardTools(ctx context.Context, unitID, search string, limit int) ([]AdminUnitToolItem, error) {
	if strings.TrimSpace(unitID) == "" {
		return nil, errors.New("pemilik standar harus dipilih")
	}
	if limit <= 0 {
		limit = 20
	}
	if limit > 100 {
		limit = 100
	}

	q := r.db.WithContext(ctx).
		Table("mapalatstandar_m as mas").
		Select(`
			mas.id,
			COALESCE(mas.namaalatstandar, '-') as namaproduk,
			COALESCE(mas.namamerk, '-') as namamerk,
			COALESCE(mas.namatipe, '-') as namatipe,
			COALESCE(mas.namaserialnumber, '-') as namaserialnumber,
			NULL::text as fotoproduk,
			CAST(mas.standarmilikfk AS text) as objectmitrafk
		`).
		Where("mas.statusenabled = TRUE").
		Where("CAST(mas.standarmilikfk AS text) = ?", strings.TrimSpace(unitID))

	if strings.TrimSpace(search) != "" {
		like := "%" + strings.TrimSpace(search) + "%"
		q = q.Where(`
			(
				mas.namaalatstandar ILIKE ?
				OR mas.namamerk ILIKE ?
				OR mas.namatipe ILIKE ?
				OR mas.namaserialnumber ILIKE ?
				OR CAST(mas.id AS text) ILIKE ?
			)
		`, like, like, like, like, like)
	}

	var rows []AdminUnitToolItem
	err := q.Order("mas.namaalatstandar ASC, mas.id DESC").Limit(limit).Scan(&rows).Error
	return rows, err
}

func (r *AdminRegistrationRepo) ListLingkupKalibrasi(ctx context.Context, search string, limit int) ([]AdminLookupItem, error) {
	if limit <= 0 {
		limit = 20
	}
	if limit > 100 {
		limit = 100
	}

	q := r.db.WithContext(ctx).
		Table("lingkupkalibrasi_m").
		Select("CAST(id AS text) as id, COALESCE(lingkupkalibrasi, '-') as label").
		Where("statusenabled = TRUE")

	if strings.TrimSpace(search) != "" {
		q = q.Where("lingkupkalibrasi ILIKE ?", "%"+strings.TrimSpace(search)+"%")
	}

	var rows []AdminLookupItem
	err := q.Order("lingkupkalibrasi ASC").Limit(limit).Scan(&rows).Error
	return rows, err
}

func (r *AdminRegistrationRepo) ListPaketKalibrasi(ctx context.Context, search string, limit int) ([]DropdownItem, error) {
	if limit <= 0 {
		limit = 20
	}
	if limit > 100 {
		limit = 100
	}

	q := r.db.WithContext(ctx).
		Table("paketkalibrasi_m").
		Select("CAST(id AS text) as value, COALESCE(namapaket, '-') as label, hari").
		Where("statusenabled = TRUE")

	if strings.TrimSpace(search) != "" {
		q = q.Where("namapaket ILIKE ?", "%"+strings.TrimSpace(search)+"%")
	}

	var rows []DropdownItem
	err := q.Order("namapaket ASC").Limit(limit).Scan(&rows).Error
	return rows, err
}

func (r *AdminRegistrationRepo) ListStatusSurkes(ctx context.Context, search string, limit int) ([]AdminLookupItem, error) {
	if limit <= 0 {
		limit = 20
	}
	if limit > 100 {
		limit = 100
	}

	q := r.db.WithContext(ctx).
		Table("jenissurkes_m").
		Select("CAST(id AS text) as id, COALESCE(jenissurkes, '-') as label").
		Where("statusenabled = TRUE")

	if strings.TrimSpace(search) != "" {
		q = q.Where("jenissurkes ILIKE ?", "%"+strings.TrimSpace(search)+"%")
	}

	var rows []AdminLookupItem
	err := q.Order("jenissurkes ASC").Limit(limit).Scan(&rows).Error
	return rows, err
}

func (r *AdminRegistrationRepo) ListVendors(ctx context.Context, search string, limit int) ([]AdminLookupItem, error) {
	if limit <= 0 {
		limit = 20
	}
	if limit > 100 {
		limit = 100
	}

	q := r.db.WithContext(ctx).
		Table("vendor_m").
		Select("CAST(id AS text) as id, COALESCE(namavendor, '-') as label").
		Where("statusenabled = TRUE")

	if strings.TrimSpace(search) != "" {
		q = q.Where("namavendor ILIKE ?", "%"+strings.TrimSpace(search)+"%")
	}

	var rows []AdminLookupItem
	err := q.Order("namavendor ASC").Limit(limit).Scan(&rows).Error
	return rows, err
}

func (r *AdminRegistrationRepo) FindPegawaiByJabatan(ctx context.Context, jabatan string, lokasi string) (*AdminLookupItem, error) {
	q := r.db.WithContext(ctx).
		Table("pegawai_m").
		Select("CAST(id AS text) as id, COALESCE(namalengkap, '-') as label").
		Where("statusenabled = TRUE")

	if strings.TrimSpace(jabatan) != "" {
		q = q.Where("jabatan1fk = ?", strings.TrimSpace(jabatan))
	}
	if strings.TrimSpace(lokasi) != "" {
		q = q.Where("lokasikalibrasifk = ?", strings.TrimSpace(lokasi))
	}

	var row AdminLookupItem
	if err := q.Order("id ASC").Limit(1).Take(&row).Error; err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, nil
		}
		return nil, err
	}
	return &row, nil
}

func (r *AdminRegistrationRepo) KajiDetails(ctx context.Context, norec string, norecDetail string) (*AdminKajiMeta, error) {
	norec = strings.TrimSpace(norec)
	if norec == "" {
		return nil, errors.New("norec registrasi tidak valid")
	}

	q := r.db.WithContext(ctx).
		Table("mitraregistrasi_t as mtr").
		Joins("join mitraregistrasidetail_t as mtrd on mtrd.noregistrasifk = mtr.norec").
		Joins("join mapunittoalat_m as mmp on mmp.id = mtrd.namaalatfk").
		Joins("join mitra_m as mt on mt.id = mtr.nomitrafk").
		Joins("left join pegawai_m as pg on pg.id = mtrd.penyeliateknikfk").
		Joins("left join pegawai_m as pg2 on pg2.id = mtrd.pelaksanateknikfk").
		Joins("left join lokasikalibrasi_m as lk on lk.id = COALESCE(mtrd.lokasikajifk, mtrd.lokasirepairfk, mtr.lokasikalibrasi, mtr.lokasirepair)").
		Joins("left join lingkupkalibrasi_m as lp on lp.id = mtrd.lingkupkalibrasifk").
		Joins("left join paketkalibrasi_m as pk on pk.id = mtr.paketkalibrasi").
		Joins("left join jenissurkes_m as jm on jm.id = mtrd.statussurkesfk").
		Joins("left join vendor_m as vm on vm.id = mtrd.vendorkalibrasifk").
		Where("mtr.statusenabled = TRUE").
		Where("mtrd.statusenabled = TRUE").
		Where("mmp.statusenabled = TRUE").
		Where("mtr.norec = ?", norec)

	if strings.TrimSpace(norecDetail) != "" {
		q = q.Where("mtrd.norec = ?", strings.TrimSpace(norecDetail))
	}

	var rows []AdminKajiDetailItem
	err := q.Select(`
			CAST(mtr.norec AS text) as norec,
			CAST(mtrd.norec AS text) as norec_detail,
			mtrd.iskaji,
			mtrd.namafile,
			mtrd.keterangan,
			mtrd.durasikalbrasi,
			mmp.namaproduk,
			TO_CHAR(mtr.tglregistrasi, 'YYYY-MM-DD HH24:MI:SS') as tglregistrasi,
			mtr.nopendaftaran,
			CAST(mtr.paketkalibrasi AS text) as paketkalibrasi,
			mtr.catatan,
			mmp.namamerk,
			mmp.namatipe,
			mmp.namaserialnumber,
			mt.namaperusahaan,
			CAST(pg.id AS text) as penyeliateknikfk,
			pg.namalengkap as penyeliateknik,
			CAST(pg2.id AS text) as pelaksanateknikfk,
			pg2.namalengkap as pelaksanateknik,
			CAST(COALESCE(mtrd.lokasikajifk, mtr.lokasikalibrasi) AS text) as lokasikalibrasifk,
			CAST(COALESCE(mtrd.lokasirepairfk, mtr.lokasirepair) AS text) as lokasirepairfk,
			lk.lokasi,
			CAST(lp.id AS text) as lingkupfk,
			lp.lingkupkalibrasi,
			pk.namapaket,
			mtr.isregiscustomer,
			CAST(jm.id AS text) as jenissurkesfk,
			jm.jenissurkes,
			CAST(mtrd.namaalatfk AS text) as namaalatfk,
			CAST(mtr.nomitrafk AS text) as idunit,
			CASE WHEN EXISTS (
				SELECT 1 FROM mapalattolingkup_t AS mal
				WHERE mal.objectalatfk = mtrd.namaalatfk
				AND mal.statusenabled = TRUE
			) THEN 1 ELSE 0 END as mapping_surkes_id,
			mtrd."isVendor",
			CAST(mtrd.vendorkalibrasifk AS text) as vendorkalibrasifk,
			vm.namavendor
		`).
		Order("mmp.namaproduk DESC").
		Scan(&rows).Error
	if err != nil {
		return nil, err
	}

	detailIDs := make([]string, 0, len(rows))
	for _, row := range rows {
		if row.NorecDetail != "" {
			detailIDs = append(detailIDs, row.NorecDetail)
		}
	}

	if len(detailIDs) > 0 {
		var photos []struct {
			DetailID string `gorm:"column:mitraregistrasidetailfk"`
			NamaFile string `gorm:"column:namafile"`
		}
		if err := r.db.WithContext(ctx).
			Table("mitraregistrasidetailfoto_t").
			Select("mitraregistrasidetailfk, namafile").
			Where("mitraregistrasidetailfk IN ?", detailIDs).
			Where("statusenabled = TRUE").
			Order("created_at ASC").
			Scan(&photos).Error; err != nil {
			return nil, err
		}

		photoMap := map[string][]string{}
		for _, photo := range photos {
			name := strings.TrimSpace(photo.NamaFile)
			if name == "" {
				continue
			}
			photoMap[photo.DetailID] = append(photoMap[photo.DetailID], name)
		}

		for i := range rows {
			files := photoMap[rows[i].NorecDetail]
			if len(files) == 0 && rows[i].NamaFile != nil && strings.TrimSpace(*rows[i].NamaFile) != "" {
				files = []string{strings.TrimSpace(*rows[i].NamaFile)}
			}
			rows[i].FotoFiles = files
		}
	}

	totalDurasi := 0
	sumByLingkup := map[string]int{}
	for _, row := range rows {
		if row.PaketKalibrasi == nil || strings.TrimSpace(*row.PaketKalibrasi) == "" {
			continue
		}
		key := ""
		if row.LingkupFK != nil {
			key = strings.TrimSpace(*row.LingkupFK)
		}
		if row.DurasiKalibrasi != nil {
			sumByLingkup[key] += *row.DurasiKalibrasi
		}
	}
	for _, total := range sumByLingkup {
		if total > totalDurasi {
			totalDurasi = total
		}
	}
	tanggalSelesai := ""
	if totalDurasi > 0 {
		tanggalSelesai = time.Now().AddDate(0, 0, totalDurasi).Format("02-01-2006")
	}

	manager, err := r.FindPegawaiByJabatan(ctx, "2", "")
	if err != nil {
		return nil, err
	}
	asman, err := r.FindPegawaiByJabatan(ctx, "3", "")
	if err != nil {
		return nil, err
	}

	return &AdminKajiMeta{
		Details:        rows,
		Length:         len(rows),
		TotalDurasi:    totalDurasi,
		TanggalSelesai: tanggalSelesai,
		Manager:        manager,
		Asman:          asman,
	}, nil
}

func (r *AdminRegistrationRepo) SaveTool(ctx context.Context, req AdminSaveToolRequest) (*models.AlatDetailItem, error) {
	unitID := strings.TrimSpace(req.UnitID)
	mitraFK, err := strconv.ParseInt(unitID, 10, 64)
	if err != nil || mitraFK <= 0 {
		return nil, errors.New("unit tidak valid")
	}

	namaproduk := strings.ToUpper(strings.TrimSpace(req.NamaProduk))
	namamerk := strings.ToUpper(strings.TrimSpace(req.NamaMerk))
	namatipe := strings.ToUpper(strings.TrimSpace(req.NamaTipe))
	namaserial := normalizeAlatSerialNumber(req.NamaSerialNumber)

	if namaproduk == "" {
		return nil, errors.New("nama alat harus diisi")
	}
	if namamerk == "" {
		return nil, errors.New("merk harus diisi")
	}
	if namatipe == "" {
		return nil, errors.New("tipe harus diisi")
	}
	if namaserial == "" {
		return nil, errors.New("serial number harus diisi")
	}

	statusEnabled := true
	if req.StatusEnabled != nil {
		statusEnabled = *req.StatusEnabled
	}
	filename := strings.TrimSpace(req.NamaFileLama)
	if req.ID <= 0 && req.FileMitra == nil && filename == "" {
		return nil, errors.New("foto alat wajib diunggah")
	}

	var result models.AlatDetailItem
	savedNewFiles := []string{}
	err = r.db.WithContext(ctx).Transaction(func(tx *gorm.DB) error {
		now := time.Now()
		var existing struct {
			ID            int64   `gorm:"column:id"`
			FotoProduk    *string `gorm:"column:fotoproduk"`
			NamaSerial    string  `gorm:"column:namaserialnumber"`
			StatusEnabled bool    `gorm:"column:statusenabled"`
		}
		if req.ID > 0 {
			if err := tx.Table("mapunittoalat_m").
				Select("id, fotoproduk, namaserialnumber, statusenabled").
				Where("id = ?", req.ID).
				Where("statusenabled = TRUE").
				Take(&existing).Error; err != nil {
				return err
			}
		}

		mustCheckSerial := req.ID <= 0 || (statusEnabled && (normalizeAlatSerialNumber(existing.NamaSerial) != namaserial || !existing.StatusEnabled))
		if mustCheckSerial {
			if err := ensureActiveAlatSerialUnique(tx, namaserial, req.ID); err != nil {
				return err
			}
		}

		if req.FileMitra != nil {
			stored, err := r.imageOptimizer.StoreToolImage(ctx, req.FileMitra, getProdukUploadDir(), func() (string, error) {
				return saveProdukFile(req.FileMitra)
			})
			if err != nil {
				return err
			}
			filename = stored.Filename
			savedNewFiles = stored.CreatedFiles
		}

		if req.ID > 0 {
			if filename == "" && existing.FotoProduk != nil {
				filename = strings.TrimSpace(*existing.FotoProduk)
			}
			if filename == "" {
				return errors.New("foto alat wajib tersedia")
			}

			res := tx.Table("mapunittoalat_m").
				Where("id = ?", req.ID).
				Updates(map[string]any{
					"namaproduk":       namaproduk,
					"namamerk":         namamerk,
					"namatipe":         namatipe,
					"namaserialnumber": namaserial,
					"fotoproduk":       filename,
					"statusenabled":    statusEnabled,
					"objectmitrafk":    mitraFK,
					"updated_at":       now,
				})
			if res.Error != nil {
				return res.Error
			}
			if res.RowsAffected == 0 {
				return gorm.ErrRecordNotFound
			}
			result.ID = int(req.ID)
		} else {
			nextID, err := r.nextSafeIDTx(tx, 1, "mapunittoalat_m", "id")
			if err != nil {
				return err
			}

			if err := tx.Table("mapunittoalat_m").Create(map[string]any{
				"id":               nextID,
				"kdprofile":        1,
				"namaproduk":       namaproduk,
				"namamerk":         namamerk,
				"namatipe":         namatipe,
				"namaserialnumber": namaserial,
				"fotoproduk":       filename,
				"statusenabled":    statusEnabled,
				"objectmitrafk":    mitraFK,
				"created_at":       now,
				"updated_at":       now,
			}).Error; err != nil {
				return err
			}
			result.ID = int(nextID)
		}

		return tx.Table("mapunittoalat_m").
			Select(`
				id,
				namaproduk,
				namatipe,
				namamerk,
				namaserialnumber,
				fotoproduk,
				objectmitrafk,
				statusenabled,
				statuskanfk,
				created_at,
				updated_at
			`).
			Where("id = ?", result.ID).
			Take(&result).Error
	})
	if err != nil {
		for _, savedNewFile := range savedNewFiles {
			_ = os.Remove(filepath.Join(getProdukUploadDir(), savedNewFile))
		}
		return nil, normalizeDuplicateAlatSerialError(err, namaserial)
	}

	result.FotoProdukThumbnail = toolThumbnailFilename(result.FotoProduk)
	return &result, nil
}

func (r *AdminRegistrationRepo) SaveKajiItem(ctx context.Context, req AdminKajiItemRequest) (map[string]any, error) {
	norecDetail := strings.TrimSpace(req.NorecDetail)
	if norecDetail == "" {
		return nil, errors.New("detail alat tidak valid")
	}

	now := time.Now()
	savedFiles := []string{}
	err := r.db.WithContext(ctx).Transaction(func(tx *gorm.DB) error {
		var detailInfo struct {
			NamaAlatFK      *string `gorm:"column:namaalatfk"`
			LingkupFK       *string `gorm:"column:lingkupkalibrasifk"`
			NamaFile        *string `gorm:"column:namafile"`
			ObjectMitraFK   *string `gorm:"column:objectmitrafk"`
			MappingSurkesID int     `gorm:"column:mapping_surkes_id"`
		}

		if err := tx.Table("mitraregistrasidetail_t as mtrd").
			Joins("join mitraregistrasi_t as mtr on mtr.norec = mtrd.noregistrasifk").
			Select(`
				CAST(mtrd.namaalatfk AS text) as namaalatfk,
				CAST(mtrd.lingkupkalibrasifk AS text) as lingkupkalibrasifk,
				mtrd.namafile,
				CAST(mtr.nomitrafk AS text) as objectmitrafk,
				CASE WHEN EXISTS (
					SELECT 1 FROM mapalattolingkup_t AS mal
					WHERE mal.objectalatfk = mtrd.namaalatfk
					AND mal.statusenabled = TRUE
				) THEN 1 ELSE 0 END as mapping_surkes_id
			`).
			Where("mtrd.norec = ?", norecDetail).
			Take(&detailInfo).Error; err != nil {
			return errors.New("detail alat tidak ditemukan")
		}

		var existingFotoCount int64
		if err := tx.Table("mitraregistrasidetailfoto_t").
			Where("mitraregistrasidetailfk = ?", norecDetail).
			Where("statusenabled = TRUE").
			Count(&existingFotoCount).Error; err != nil {
			return err
		}
		legacyFile := ""
		if detailInfo.NamaFile != nil {
			legacyFile = strings.TrimSpace(*detailInfo.NamaFile)
		}
		if len(req.Files) == 0 && existingFotoCount == 0 && legacyFile == "" {
			return errors.New("minimal 1 foto harus diunggah")
		}

		for _, file := range req.Files {
			filename, err := saveAdminImageUpload(file, "berkas-mitra")
			if err != nil {
				return err
			}
			savedFiles = append(savedFiles, filename)
			if err := tx.Table("mitraregistrasidetailfoto_t").Create(map[string]any{
				"norec":                   uuid.NewString(),
				"mitraregistrasidetailfk": norecDetail,
				"namafile":                filename,
				"keterangan":              nullableString(req.Keterangan),
				"statusenabled":           true,
				"created_at":              now,
				"updated_at":              now,
			}).Error; err != nil {
				return err
			}
		}

		lingkup := strings.TrimSpace(req.LingkupKalibrasi)
		if lingkup == "" && detailInfo.LingkupFK != nil {
			lingkup = strings.TrimSpace(*detailInfo.LingkupFK)
		}

		update := map[string]any{
			"keterangan":         nullableString(req.Keterangan),
			"lokasikajifk":       nullableString(req.LokasiKalibrasi),
			"lokasirepairfk":     nullableString(req.LokasiRepairFK),
			"lingkupkalibrasifk": nullableString(lingkup),
			"namamanager":        nullableString(req.Manager),
			"namaasman":          nullableString(req.NamaAsman),
			"iskaji":             true,
			"updated_at":         now,
		}

		if req.IsVendor {
			update["statussurkesfk"] = 2
			update["durasikalbrasi"] = nil
			update["vendorkalibrasifk"] = nullableString(req.VendorKalibrasiFK)
			update["isVendor"] = true
		} else {
			statusSurkes := strings.TrimSpace(req.StatusSurkes)
			if detailInfo.MappingSurkesID > 0 {
				statusSurkes = "1"
			}
			update["penyeliateknikfk"] = nullableString(req.PenyeliaTeknikFK)
			update["pelaksanateknikfk"] = nullableString(req.PelaksanaFK)
			update["statussurkesfk"] = nullableString(statusSurkes)
			update["durasikalbrasi"] = nullableString(req.DurasiKalibrasi)
			update["statusorderpelaksana"] = 0
			update["statusorderpenyelia"] = 0

			if statusSurkes == "1" && detailInfo.NamaAlatFK != nil && detailInfo.ObjectMitraFK != nil {
				if err := ensureAdminMappingAlatSurkesTx(tx, *detailInfo.NamaAlatFK, *detailInfo.ObjectMitraFK, lingkup); err != nil {
					return err
				}
			}
		}

		res := tx.Table("mitraregistrasidetail_t").
			Where("norec = ?", norecDetail).
			Updates(update)
		if res.Error != nil {
			return res.Error
		}
		if res.RowsAffected == 0 {
			return gorm.ErrRecordNotFound
		}
		return nil
	})
	if err != nil {
		return nil, err
	}

	firstFile := ""
	if len(savedFiles) > 0 {
		firstFile = savedFiles[0]
	}
	return map[string]any{
		"norec_detail": norecDetail,
		"namafile":     firstFile,
		"foto_files":   savedFiles,
	}, nil
}

func (r *AdminRegistrationRepo) SaveKajiHeader(ctx context.Context, norec string, petugasKaji string) error {
	norec = strings.TrimSpace(norec)
	if norec == "" {
		return errors.New("norec registrasi tidak valid")
	}

	update := map[string]any{
		"iskaji":       true,
		"tglkajiulang": time.Now(),
		"updated_at":   time.Now(),
	}
	if strings.TrimSpace(petugasKaji) != "" {
		update["petugaskaji"] = strings.TrimSpace(petugasKaji)
	}

	res := r.db.WithContext(ctx).
		Table("mitraregistrasi_t").
		Where("norec = ?", norec).
		Updates(update)
	if res.Error != nil {
		return res.Error
	}
	if res.RowsAffected == 0 {
		return gorm.ErrRecordNotFound
	}
	return nil
}

func (r *AdminRegistrationRepo) DeleteTool(ctx context.Context, id int64) error {
	if id <= 0 {
		return errors.New("id alat tidak valid")
	}

	return r.db.WithContext(ctx).Transaction(func(tx *gorm.DB) error {
		var alat struct {
			ID int64 `gorm:"column:id"`
		}
		if err := tx.Table("mapunittoalat_m").
			Select("id").
			Where("id = ?", id).
			Where("statusenabled = TRUE").
			Take(&alat).Error; err != nil {
			return err
		}

		var registrationCount int64
		if err := tx.Table("mitraregistrasidetail_t").
			Where("namaalatfk = ?", id).
			Count(&registrationCount).Error; err != nil {
			return err
		}
		if registrationCount > 0 {
			return ErrAlatPernahDidaftarkan
		}

		res := tx.Table("mapunittoalat_m").
			Where("id = ?", id).
			Where("statusenabled = TRUE").
			Updates(map[string]any{
				"statusenabled": false,
				"updated_at":    time.Now(),
			})
		if res.Error != nil {
			return res.Error
		}
		if res.RowsAffected == 0 {
			return gorm.ErrRecordNotFound
		}

		return nil
	})
}

func (r *AdminRegistrationRepo) SaveUnit(ctx context.Context, req AdminSaveUnitRequest) (AdminLookupItem, error) {
	name := strings.ToUpper(strings.TrimSpace(req.NamaPerusahaan))
	if name == "" {
		return AdminLookupItem{}, errors.New("nama perusahaan harus diisi")
	}

	now := time.Now()
	var out AdminLookupItem

	err := r.db.WithContext(ctx).Transaction(func(tx *gorm.DB) error {
		id := strings.TrimSpace(req.ID)
		if id == "" {
			nextID, nextKode, err := r.nextMitraIDsTx(tx)
			if err != nil {
				return err
			}
			id = nextID

			data := map[string]any{
				"id":             id,
				"statusenabled":  true,
				"namaperusahaan": name,
				"tgldaftar":      now,
				"kodeexternal":   nextKode,
				"nohp":           nullableString(req.NoHP),
				"email":          nullableString(req.Email),
				"alamatktr":      nullableString(req.Alamat),
				"rtrw":           nullableString(req.RTRW),
				"kodepos":        nullableString(req.KodePos),
				"progress":       req.Progress,
				"created_at":     now,
				"updated_at":     now,
			}
			putNullable(data, "objectnegarafk", req.ObjectNegaraFK)
			putNullable(data, "objectpropinsifk", req.ObjectPropinsiFK)
			putNullable(data, "objectkotakabupatenfk", req.ObjectKotaFK)
			putNullable(data, "objectkecamatanfk", req.ObjectKecamatanFK)
			putNullable(data, "objectdesakelurahanfk", req.ObjectKelurahanFK)

			if err := tx.Table("mitra_m").Create(data).Error; err != nil {
				return err
			}
		} else {
			data := map[string]any{
				"namaperusahaan": name,
				"nohp":           nullableString(req.NoHP),
				"email":          nullableString(req.Email),
				"alamatktr":      nullableString(req.Alamat),
				"rtrw":           nullableString(req.RTRW),
				"kodepos":        nullableString(req.KodePos),
				"progress":       req.Progress,
				"updated_at":     now,
			}
			putNullable(data, "objectnegarafk", req.ObjectNegaraFK)
			putNullable(data, "objectpropinsifk", req.ObjectPropinsiFK)
			putNullable(data, "objectkotakabupatenfk", req.ObjectKotaFK)
			putNullable(data, "objectkecamatanfk", req.ObjectKecamatanFK)
			putNullable(data, "objectdesakelurahanfk", req.ObjectKelurahanFK)

			res := tx.Table("mitra_m").
				Where("id = ?", id).
				Where("statusenabled = TRUE").
				Updates(data)
			if res.Error != nil {
				return res.Error
			}
			if res.RowsAffected == 0 {
				return gorm.ErrRecordNotFound
			}
		}

		out = AdminLookupItem{ID: id, Label: name}
		return nil
	})

	return out, err
}

func (r *AdminRegistrationRepo) SaveRegistration(ctx context.Context, req AdminSaveRegistrationRequest) (*AdminSaveRegistrationResult, error) {
	unitID := strings.TrimSpace(req.UnitID)
	if unitID == "" {
		return nil, errors.New("unit harus dipilih")
	}

	jenisOrder := strings.ToLower(strings.TrimSpace(req.JenisOrder))
	if jenisOrder == "" {
		jenisOrder = "kalibrasi"
	}
	if jenisOrder != "kalibrasi" && jenisOrder != "repair" {
		return nil, errors.New("jenis order tidak valid")
	}

	if strings.TrimSpace(req.NamaPenanggungJawab) == "" {
		return nil, errors.New("nama penanggung jawab harus diisi")
	}
	if strings.TrimSpace(req.JabatanPenanggungJawab) == "" && jenisOrder != "kalibrasi" {
		return nil, errors.New("jabatan penanggung jawab harus diisi")
	}
	if len(req.Details) == 0 {
		return nil, errors.New("minimal satu alat harus dipilih")
	}

	lokasiID := strings.TrimSpace(req.LokasiKalibrasi)
	if jenisOrder == "repair" {
		lokasiID = strings.TrimSpace(req.LokasiRepair)
	}
	if lokasiID == "" {
		return nil, errors.New("lokasi harus dipilih")
	}

	tglRegistrasi, err := parseRegistrationTime(req.TglRegistrasi)
	if err != nil {
		return nil, err
	}

	var result AdminSaveRegistrationResult
	err = r.db.WithContext(ctx).Transaction(func(tx *gorm.DB) error {
		norec := uuid.NewString()
		noPendaftaran, err := r.nextNoPendaftaranTx(tx, jenisOrder, lokasiID, tglRegistrasi)
		if err != nil {
			return err
		}

		now := time.Now()
		header := map[string]any{
			"norec":                     norec,
			"statusenabled":             true,
			"nomitrafk":                 unitID,
			"tglregistrasi":             tglRegistrasi,
			"nopendaftaran":             noPendaftaran,
			"catatan":                   nullableString(req.Catatan),
			"namapenanggungjawab":       strings.TrimSpace(req.NamaPenanggungJawab),
			"nohppenanggungjawab":       nullableString(req.NoHPPenanggungJawab),
			"jabatanpenanggungjawab":    nullableString(req.JabatanPenanggungJawab),
			"statusorder":               0,
			"statusordermanager":        0,
			"jenisorder":                jenisOrder,
			"verifregiscustomer":        true,
			"tanggalverifregiscustomer": now,
			"created_at":                now,
			"updated_at":                now,
		}

		if jenisOrder == "repair" {
			header["lokasirepair"] = lokasiID
		} else {
			header["lokasikalibrasi"] = lokasiID
			header["rentangUkur"] = nullableString(req.RentangUkur)
			header["rentangUkurketPermintaanPelanggan"] = nullableString(req.RentangUkurKetPermintaanPelanggan)
			if strings.TrimSpace(req.PaketKalibrasi) != "" {
				header["paketkalibrasi"] = strings.TrimSpace(req.PaketKalibrasi)
			}
			if req.IsKalibrasiInternal {
				header["iskalibrasiinternal"] = true
			}
			if req.IsStandarUlab {
				header["isstandarulab"] = true
			}
		}
		if req.IsKalibrasiInternal || req.IsStandarUlab {
			header["iskaji"] = true
			header["tglkajiulang"] = now
			if strings.TrimSpace(req.PetugasKajiFK) != "" {
				header["petugaskaji"] = strings.TrimSpace(req.PetugasKajiFK)
			}
		}

		if err := tx.Table("mitraregistrasi_t").Create(header).Error; err != nil {
			return err
		}

		var durasiKalibrasi *int
		if jenisOrder == "kalibrasi" && strings.TrimSpace(req.PaketKalibrasi) != "" {
			var paket struct {
				Hari *int `gorm:"column:hari"`
			}
			if err := tx.Table("paketkalibrasi_m").
				Select("hari").
				Where("id = ?", strings.TrimSpace(req.PaketKalibrasi)).
				Take(&paket).Error; err == nil {
				durasiKalibrasi = paket.Hari
			}
		}

		for _, detailReq := range req.Details {
			alatID := strings.TrimSpace(detailReq.NamaAlatFK)
			if alatID == "" {
				return errors.New("ada alat yang belum dipilih")
			}

			var alatCount int64
			alatTable := "mapunittoalat_m"
			unitColumn := "objectmitrafk"
			if req.IsStandarUlab {
				alatTable = "mapalatstandar_m"
				unitColumn = "standarmilikfk"
			}
			if err := tx.Table(alatTable).
				Where("id = ?", alatID).
				Where("CAST("+unitColumn+" AS text) = ?", unitID).
				Where("statusenabled = TRUE").
				Count(&alatCount).Error; err != nil {
				return err
			}
			if alatCount == 0 {
				return fmt.Errorf("alat %s tidak ditemukan pada unit terpilih", alatID)
			}

			detail := map[string]any{
				"norec":          uuid.NewString(),
				"statusenabled":  true,
				"namaalatfk":     alatID,
				"noregistrasifk": norec,
				"created_at":     now,
				"updated_at":     now,
			}
			if jenisOrder == "kalibrasi" {
				putNullable(detail, "lingkupkalibrasifk", detailReq.LingkupFK)
				putNullable(detail, "penyeliateknikfk", detailReq.PenyeliaTeknikFK)
				putNullable(detail, "pelaksanateknikfk", detailReq.PelaksanaFK)
				if req.IsKalibrasiInternal || req.IsStandarUlab {
					putNullable(detail, "lokasikajifk", lokasiID)
					detail["statusorderpelaksana"] = 0
					detail["statusorderpenyelia"] = 0
					detail["iskaji"] = true
				}
				if durasiKalibrasi != nil {
					detail["durasikalbrasi"] = *durasiKalibrasi
				}
			}

			if err := tx.Table("mitraregistrasidetail_t").Create(detail).Error; err != nil {
				return err
			}
		}

		result = AdminSaveRegistrationResult{
			Norec:         norec,
			NoPendaftaran: noPendaftaran,
			JenisOrder:    jenisOrder,
			JumlahAlat:    len(req.Details),
		}
		return nil
	})
	if err != nil {
		return nil, err
	}

	return &result, nil
}

func (r *AdminRegistrationRepo) SaveVendorCertificate(ctx context.Context, norecDetail, jenisOrder string, file *multipart.FileHeader, oldName string) (string, error) {
	norecDetail = strings.TrimSpace(norecDetail)
	if norecDetail == "" {
		return "", errors.New("norec detail wajib diisi")
	}

	filename, err := saveAdminPDFUpload(file, oldName, "berkas-vendor")
	if err != nil {
		return "", err
	}

	now := time.Now()
	updates := []any{filename, now}
	query := `UPDATE mitraregistrasidetail_t SET "fileSertiVendor" = ?, tglupladosertivendor = ?, statusordermanager = 2`
	if strings.EqualFold(strings.TrimSpace(jenisOrder), "repair") {
		query += `, tglsetujumanagerlaporanrepair = ?`
		updates = append(updates, now)
	} else {
		query += `, tglsetujumanagerlembarkerja = ?`
		updates = append(updates, now)
	}
	query += ` WHERE norec = ?`
	updates = append(updates, norecDetail)

	if err := r.db.WithContext(ctx).Exec(query, updates...).Error; err != nil {
		return "", err
	}

	return filename, nil
}

func (r *AdminRegistrationRepo) SaveRegistrationAMS(ctx context.Context, norec string, file *multipart.FileHeader, oldName string) (string, error) {
	norec = strings.TrimSpace(norec)
	if norec == "" {
		return "", errors.New("norec registrasi wajib diisi")
	}

	filename, err := saveAdminPDFUpload(file, oldName, "berkas-customer")
	if err != nil {
		return "", err
	}

	if err := r.db.WithContext(ctx).
		Exec(`UPDATE mitraregistrasi_t SET filecustomerams = ? WHERE norec = ?`, filename, norec).
		Error; err != nil {
		return "", err
	}

	return filename, nil
}

func shortDate(value string) string {
	value = strings.TrimSpace(value)
	if len(value) >= 10 {
		return value[:10]
	}
	return value
}

func saveAdminPDFUpload(file *multipart.FileHeader, oldName, folder string) (string, error) {
	if file == nil {
		filename := strings.TrimSpace(oldName)
		if filename == "" {
			return "", errors.New("file PDF wajib dipilih")
		}
		return filepath.Base(filename), nil
	}

	if file.Size > 10*1024*1024 {
		return "", errors.New("maksimal file PDF 10 MB")
	}

	if strings.ToLower(filepath.Ext(file.Filename)) != ".pdf" {
		return "", errors.New("file yang diizinkan harus PDF")
	}

	dir, err := backendPublicUploadDir(folder)
	if err != nil {
		return "", err
	}
	if err := os.MkdirAll(dir, 0o755); err != nil {
		return "", err
	}

	filename := fmt.Sprintf("%d_%s", time.Now().Unix(), safeUploadFilename(file.Filename))
	dstPath := filepath.Join(dir, filename)
	src, err := file.Open()
	if err != nil {
		return "", err
	}
	defer src.Close()

	dst, err := os.Create(dstPath)
	if err != nil {
		return "", err
	}
	defer dst.Close()

	if _, err := io.Copy(dst, src); err != nil {
		return "", err
	}

	return filename, nil
}

func saveAdminImageUpload(file *multipart.FileHeader, folder string) (string, error) {
	if file == nil {
		return "", errors.New("foto wajib dipilih")
	}
	if file.Size > 10*1024*1024 {
		return "", errors.New("maksimal foto 10 MB")
	}

	ext := strings.ToLower(strings.TrimPrefix(filepath.Ext(file.Filename), "."))
	switch ext {
	case "jpg", "jpeg", "png", "webp":
	default:
		return "", errors.New("file harus berupa gambar (jpg, jpeg, png, webp)")
	}

	dir, err := backendPublicUploadDir(folder)
	if err != nil {
		return "", err
	}
	if err := os.MkdirAll(dir, 0o755); err != nil {
		return "", err
	}

	filename := fmt.Sprintf("%d_%s_%s", time.Now().Unix(), uuid.NewString()[:6], safeUploadFilename(file.Filename))
	dstPath := filepath.Join(dir, filename)
	src, err := file.Open()
	if err != nil {
		return "", err
	}
	defer src.Close()

	dst, err := os.Create(dstPath)
	if err != nil {
		return "", err
	}
	defer dst.Close()

	if _, err := io.Copy(dst, src); err != nil {
		return "", err
	}
	return filename, nil
}

func ensureAdminMappingAlatSurkesTx(tx *gorm.DB, objectAlatFK string, objectMitraFK string, objectLingkupFK string) error {
	objectAlatFK = strings.TrimSpace(objectAlatFK)
	objectMitraFK = strings.TrimSpace(objectMitraFK)
	objectLingkupFK = strings.TrimSpace(objectLingkupFK)
	if objectAlatFK == "" || objectMitraFK == "" || objectLingkupFK == "" {
		return nil
	}

	var alatCount int64
	if err := tx.Table("mapunittoalat_m").
		Where("CAST(id AS text) = ?", objectAlatFK).
		Where("statusenabled = TRUE").
		Count(&alatCount).Error; err != nil {
		return err
	}
	if alatCount == 0 {
		return nil
	}

	var existing struct {
		ID int64 `gorm:"column:id"`
	}
	err := tx.Table("mapalattolingkup_t").
		Select("id").
		Where("CAST(objectalatfk AS text) = ?", objectAlatFK).
		Limit(1).
		Take(&existing).Error
	if err != nil && !errors.Is(err, gorm.ErrRecordNotFound) {
		return err
	}

	now := time.Now()
	if existing.ID > 0 {
		return tx.Table("mapalattolingkup_t").
			Where("id = ?", existing.ID).
			Updates(map[string]any{
				"objectmitrafk":   objectMitraFK,
				"objectlingkupfk": objectLingkupFK,
				"statusenabled":   true,
				"updated_at":      now,
			}).Error
	}

	return tx.Table("mapalattolingkup_t").Create(map[string]any{
		"objectalatfk":    objectAlatFK,
		"objectmitrafk":   objectMitraFK,
		"objectlingkupfk": objectLingkupFK,
		"statusenabled":   true,
		"created_at":      now,
		"updated_at":      now,
	}).Error
}

func backendPublicUploadDir(folder string) (string, error) {
	cwd, err := os.Getwd()
	if err != nil {
		return "", err
	}

	candidates := []string{
		filepath.Join(cwd, "..", "..", "backend", "public", folder),
		filepath.Join(cwd, "..", "backend", "public", folder),
		filepath.Join(cwd, "backend", "public", folder),
	}

	for _, candidate := range candidates {
		parent := filepath.Dir(candidate)
		if stat, err := os.Stat(parent); err == nil && stat.IsDir() {
			return filepath.Clean(candidate), nil
		}
	}

	return filepath.Clean(candidates[0]), nil
}

func safeUploadFilename(name string) string {
	base := filepath.Base(strings.TrimSpace(name))
	base = strings.ReplaceAll(base, " ", "_")
	base = unsafeUploadFilenameChars.ReplaceAllString(base, "_")
	base = strings.Trim(base, "._-")
	if base == "" {
		return "document.pdf"
	}
	return base
}

func adminToolStatusLabel(row AdminRegistrationToolItem) string {
	if row.StatusAktifPendaftaranAlat != nil && !*row.StatusAktifPendaftaranAlat {
		return "Dibatalkan"
	}

	jenis := strings.ToLower(strings.TrimSpace(row.JenisOrder))
	if jenis == "repair" && row.TglSetujuManagerLaporanRepair != nil && strings.TrimSpace(*row.TglSetujuManagerLaporanRepair) != "" {
		return "Selesai"
	}
	if jenis != "repair" && row.TglSetujuManagerLembarKerja != nil && strings.TrimSpace(*row.TglSetujuManagerLembarKerja) != "" {
		return "Selesai"
	}

	if row.IsKaji == nil || !*row.IsKaji {
		return "Belum Kaji"
	}
	if row.PelaksanaIsiLembarKerjaFK != nil || row.PelaksanaIsiLaporanRepairFK != nil {
		return "Diproses"
	}
	if row.StatusOrderManager != nil && *row.StatusOrderManager > 0 {
		return "Verifikasi"
	}
	if row.IsVendor != nil && *row.IsVendor {
		return "Vendor"
	}
	return "Sudah Kaji"
}

func nullableString(value string) any {
	text := strings.TrimSpace(value)
	if text == "" {
		return nil
	}
	return text
}

func putNullable(data map[string]any, key string, value string) {
	if strings.TrimSpace(value) == "" {
		data[key] = nil
		return
	}
	data[key] = strings.TrimSpace(value)
}

func parseRegistrationTime(value string) (time.Time, error) {
	text := strings.TrimSpace(value)
	if text == "" {
		return time.Now(), nil
	}

	layouts := []string{
		"2006-01-02 15:04:05",
		time.RFC3339,
		"2006-01-02",
	}
	for _, layout := range layouts {
		if t, err := time.ParseInLocation(layout, text, time.Local); err == nil {
			return t, nil
		}
	}

	return time.Time{}, errors.New("format tanggal registrasi tidak valid")
}

func (r *AdminRegistrationRepo) nextNoPendaftaranTx(tx *gorm.DB, jenisOrder, lokasiID string, tgl time.Time) (string, error) {
	lokasiPrefix := "G"
	if strings.TrimSpace(lokasiID) == "1" {
		lokasiPrefix = "J"
	}

	prefix := lokasiPrefix
	lokasiColumn := "lokasikalibrasi"
	if jenisOrder == "repair" {
		prefix = "R" + lokasiPrefix
		lokasiColumn = "lokasirepair"
	}

	type row struct {
		NoPendaftaran *string `gorm:"column:nopendaftaran"`
	}
	var last row

	err := tx.Table("mitraregistrasi_t").
		Clauses(clause.Locking{Strength: "UPDATE"}).
		Select("nopendaftaran").
		Where(lokasiColumn+" = ?", lokasiID).
		Where("EXTRACT(YEAR FROM tglregistrasi) = ?", tgl.Year()).
		Where("nopendaftaran IS NOT NULL").
		Where("nopendaftaran <> ''").
		Where("nopendaftaran LIKE ?", prefix+"%").
		Order("(substring(nopendaftaran from '(\\d+)$'))::int DESC").
		Limit(1).
		Scan(&last).Error
	if err != nil {
		return "", err
	}

	next := 1
	if last.NoPendaftaran != nil {
		parts := strings.Split(*last.NoPendaftaran, "-")
		if len(parts) > 0 {
			if n, err := strconv.Atoi(parts[len(parts)-1]); err == nil {
				next = n + 1
			}
		}
	}

	return fmt.Sprintf("%s-%02d-%03d", prefix, tgl.Year()%100, next), nil
}

func (r *AdminRegistrationRepo) nextSafeIDTx(tx *gorm.DB, kdProfile int64, tableName string, idField string) (int64, error) {
	var seq seqMasterRow
	err := tx.
		Clauses(clause.Locking{Strength: "UPDATE"}).
		Table(seqMasterTableName).
		Where("kdprofile = ?", kdProfile).
		Where("\"table\" = ?", tableName).
		Take(&seq).Error

	if err != nil && !errors.Is(err, gorm.ErrRecordNotFound) {
		return 0, err
	}

	var maxID int64
	maxExpr := fmt.Sprintf("COALESCE(MAX(%s), 0)", idField)
	if err := tx.
		Table(tableName).
		Where("kdprofile = ?", kdProfile).
		Select(maxExpr).
		Scan(&maxID).Error; err != nil {
		return 0, err
	}

	if errors.Is(err, gorm.ErrRecordNotFound) {
		nextID := maxID + 1
		newSeq := map[string]any{
			"kdprofile":  kdProfile,
			"table":      tableName,
			"tgl":        float64(time.Now().Unix()),
			"norec":      uuidString(),
			"idterakhir": nextID,
			"created_at": time.Now(),
			"updated_at": time.Now(),
		}
		if err := tx.Table(seqMasterTableName).Create(newSeq).Error; err != nil {
			return 0, err
		}
		return nextID, nil
	}

	base := seq.IdTerakhir
	if maxID > base {
		base = maxID
	}
	nextID := base + 1

	if err := tx.Table(seqMasterTableName).
		Where("norec = ?", seq.Norec).
		Updates(map[string]any{
			"idterakhir": nextID,
			"tgl":        float64(time.Now().Unix()),
			"updated_at": time.Now(),
		}).Error; err != nil {
		return 0, err
	}

	return nextID, nil
}

func (r *AdminRegistrationRepo) nextMitraIDsTx(tx *gorm.DB) (string, string, error) {
	var nextID int
	if err := tx.Raw(`
		SELECT COALESCE(MAX(
			CASE
				WHEN id::text ~ '^[0-9]+$' THEN CAST(id AS INTEGER)
				ELSE 0
			END
		), 0) + 1
		FROM mitra_m
	`).Scan(&nextID).Error; err != nil {
		return "", "", err
	}

	var nextKode int
	if err := tx.Raw(`
		SELECT COALESCE(MAX(CAST(kodeexternal AS INTEGER)), 0) + 1
		FROM mitra_m
		WHERE kodeexternal ~ '^[0-9]+$'
	`).Scan(&nextKode).Error; err != nil {
		return "", "", err
	}

	return strconv.Itoa(nextID), strconv.Itoa(nextKode), nil
}
