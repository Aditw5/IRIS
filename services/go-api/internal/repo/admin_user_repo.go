package repo

import (
	"context"
	"fmt"
	"mime/multipart"
	"os"
	"path/filepath"
	"strings"
	"time"

	"gorm.io/gorm"
)

const AdminRegistrationGroupID = 11

type AdminUserRepo struct {
	db *gorm.DB
}

func NewAdminUserRepo(db *gorm.DB) *AdminUserRepo {
	return &AdminUserRepo{db: db}
}

type AdminUser struct {
	ID                uint    `json:"id" gorm:"column:id"`
	KdProfile         int     `json:"kdprofile" gorm:"column:kdprofile"`
	Username          string  `json:"username" gorm:"column:username"`
	Password          string  `json:"-" gorm:"column:password"`
	KelompokUserID    int     `json:"kelompokuser_id" gorm:"column:kelompokuser_id"`
	KelompokUser      string  `json:"kelompokuser" gorm:"column:kelompokuser"`
	PegawaiID         *int64  `json:"pegawaifk" gorm:"column:pegawaifk"`
	NamaLengkap       string  `json:"nama_lengkap" gorm:"column:nama_lengkap"`
	LokasiKalibrasiFK *int64  `json:"lokasikalibrasifk" gorm:"column:lokasikalibrasifk"`
	FotoPegawai       *string `json:"fotopegawai" gorm:"column:fotopegawai"`
	NoHP              string  `json:"nohp" gorm:"column:nohp"`
	Unit              string  `json:"unit" gorm:"column:unit"`
	FotoURL           string  `json:"foto_url" gorm:"column:foto_url"`
	StatusEnabled     bool    `json:"statusenabled" gorm:"column:statusenabled"`
}

func quoteIdent(name string) string {
	return `"` + strings.ReplaceAll(name, `"`, `""`) + `"`
}

func (r *AdminUserRepo) findColumn(ctx context.Context, table string, candidates ...string) string {
	for _, candidate := range candidates {
		candidate = strings.TrimSpace(candidate)
		if candidate == "" {
			continue
		}

		var column string
		err := r.db.WithContext(ctx).
			Raw(`
SELECT column_name
FROM information_schema.columns
WHERE (table_schema = ANY (current_schemas(true)) OR table_schema = 'public')
	AND table_name = ?
	AND column_name = ?
LIMIT 1
`, table, candidate).
			Scan(&column).Error
		if err == nil && column != "" {
			return column
		}
	}

	return ""
}

func (r *AdminUserRepo) pegawaiPhotoExpr(ctx context.Context, alias string) string {
	column := r.findColumn(ctx, "pegawai_m", "filenameFoto", "filenamefoto")
	if column == "" {
		return "NULL::text"
	}
	return alias + "." + quoteIdent(column)
}

func (r *AdminUserRepo) fillAdminPhoto(ctx context.Context, admin *AdminUser) {
	if admin == nil || admin.PegawaiID == nil || *admin.PegawaiID <= 0 {
		return
	}

	photoExpr := r.pegawaiPhotoExpr(ctx, "pg")
	if photoExpr == "NULL::text" {
		return
	}

	var row struct {
		FotoPegawai *string `gorm:"column:fotopegawai"`
	}
	if err := r.db.WithContext(ctx).
		Table("pegawai_m as pg").
		Select(photoExpr+" as fotopegawai").
		Where("pg.id = ?", *admin.PegawaiID).
		Take(&row).Error; err != nil {
		return
	}

	if row.FotoPegawai == nil {
		return
	}

	filename := strings.TrimSpace(*row.FotoPegawai)
	if filename == "" {
		return
	}

	admin.FotoPegawai = &filename
	admin.FotoURL = "https://ulabumro.id/berkas-mutu/" + filename
}

func (u *AdminUser) IsRegistrationAdmin() bool {
	group := strings.ToLower(strings.TrimSpace(u.KelompokUser))
	return u.KelompokUserID == AdminRegistrationGroupID || group == "registrasi"
}

func (r *AdminUserRepo) FindByLogin(ctx context.Context, login string) (*AdminUser, error) {
	login = strings.TrimSpace(login)

	var row AdminUser
	err := r.db.WithContext(ctx).
		Table("loginuser_s as lu").
		Joins("join kelompokuser_s as ku on ku.id = lu.objectkelompokuserfk").
		Joins("join pegawai_m as pg on pg.id = lu.objectpegawaifk").
		Joins("left join lokasikalibrasi_m as lk on lk.id = pg.lokasikalibrasifk").
		Select(`
			lu.id,
			COALESCE(lu.kdprofile, 1) as kdprofile,
			COALESCE(lu.namauser, '') as username,
			COALESCE(lu.katasandi, '') as password,
			COALESCE(lu.objectkelompokuserfk, 0) as kelompokuser_id,
			COALESCE(ku.kelompokuser, '') as kelompokuser,
			lu.objectpegawaifk as pegawaifk,
			COALESCE(pg.namalengkap, lu.namauser, '') as nama_lengkap,
			pg.lokasikalibrasifk,
			NULL::text as fotopegawai,
			COALESCE(NULLIF(pg.nohandphone, ''), NULLIF(pg.notlp, ''), '') as nohp,
			COALESCE(NULLIF(lk.lokasi, ''), '') as unit,
			'' as foto_url,
			COALESCE(lu.statusenabled, false) as statusenabled
		`).
		Where("lu.statusenabled = TRUE").
		Where("ku.statusenabled = TRUE").
		Where("pg.statusenabled = TRUE").
		Where("LOWER(TRIM(lu.namauser)) = LOWER(TRIM(?))", login).
		Take(&row).Error
	if err != nil {
		return nil, err
	}

	r.fillAdminPhoto(ctx, &row)
	return &row, nil
}

func (r *AdminUserRepo) FindByID(ctx context.Context, id uint) (*AdminUser, error) {
	var row AdminUser
	err := r.db.WithContext(ctx).
		Table("loginuser_s as lu").
		Joins("join kelompokuser_s as ku on ku.id = lu.objectkelompokuserfk").
		Joins("join pegawai_m as pg on pg.id = lu.objectpegawaifk").
		Joins("left join lokasikalibrasi_m as lk on lk.id = pg.lokasikalibrasifk").
		Select(`
			lu.id,
			COALESCE(lu.kdprofile, 1) as kdprofile,
			COALESCE(lu.namauser, '') as username,
			COALESCE(lu.katasandi, '') as password,
			COALESCE(lu.objectkelompokuserfk, 0) as kelompokuser_id,
			COALESCE(ku.kelompokuser, '') as kelompokuser,
			lu.objectpegawaifk as pegawaifk,
			COALESCE(pg.namalengkap, lu.namauser, '') as nama_lengkap,
			pg.lokasikalibrasifk,
			NULL::text as fotopegawai,
			COALESCE(NULLIF(pg.nohandphone, ''), NULLIF(pg.notlp, ''), '') as nohp,
			COALESCE(NULLIF(lk.lokasi, ''), '') as unit,
			'' as foto_url,
			COALESCE(lu.statusenabled, false) as statusenabled
		`).
		Where("lu.id = ?", id).
		Where("lu.statusenabled = TRUE").
		Where("ku.statusenabled = TRUE").
		Where("pg.statusenabled = TRUE").
		Take(&row).Error
	if err != nil {
		return nil, err
	}

	r.fillAdminPhoto(ctx, &row)
	return &row, nil
}

func getPegawaiUploadDir() string {
	if envDir := strings.TrimSpace(os.Getenv("PEGAWAI_UPLOAD_DIR")); envDir != "" {
		return envDir
	}
	return filepath.Clean(filepath.Join("..", "..", "backend", "public", "berkas-mutu"))
}

func savePegawaiMultipartFile(srcFile *multipart.FileHeader, dst string) error {
	src, err := srcFile.Open()
	if err != nil {
		return err
	}
	defer src.Close()

	out, err := os.Create(dst)
	if err != nil {
		return err
	}
	defer out.Close()

	_, err = out.ReadFrom(src)
	return err
}

func savePegawaiPhotoToLaravelPublic(file *multipart.FileHeader, pegawaiID int64) (string, error) {
	if file == nil {
		return "", fmt.Errorf("file tidak ditemukan")
	}

	uploadDir := getPegawaiUploadDir()
	if err := os.MkdirAll(uploadDir, 0755); err != nil {
		return "", err
	}

	ext := strings.ToLower(filepath.Ext(strings.TrimSpace(file.Filename)))
	if ext == "" {
		ext = ".jpg"
	}

	filename := fmt.Sprintf("foto_pegawai_%d_%s%s", pegawaiID, time.Now().Format("20060102_150405"), ext)
	dst := filepath.Join(uploadDir, filename)

	if err := savePegawaiMultipartFile(file, dst); err != nil {
		return "", err
	}

	return filename, nil
}

func (r *AdminUserRepo) UploadPhoto(ctx context.Context, loginUserID uint, file *multipart.FileHeader) (*AdminUser, error) {
	if file == nil {
		return nil, fmt.Errorf("file foto tidak ditemukan")
	}

	admin, err := r.FindByID(ctx, loginUserID)
	if err != nil {
		return nil, err
	}
	if admin.PegawaiID == nil || *admin.PegawaiID <= 0 {
		return nil, fmt.Errorf("pegawai admin tidak ditemukan")
	}

	filename, err := savePegawaiPhotoToLaravelPublic(file, *admin.PegawaiID)
	if err != nil {
		return nil, err
	}

	photoColumn := r.findColumn(ctx, "pegawai_m", "filenameFoto", "filenamefoto")
	if photoColumn == "" {
		return nil, fmt.Errorf("kolom foto pegawai tidak ditemukan")
	}

	updateData := map[string]interface{}{
		photoColumn: filename,
	}
	if isPhotoColumn := r.findColumn(ctx, "pegawai_m", "isfotoPegawai", "isfotopegawai"); isPhotoColumn != "" {
		updateData[isPhotoColumn] = true
	}

	if err := r.db.WithContext(ctx).
		Table("pegawai_m").
		Where("id = ?", *admin.PegawaiID).
		Updates(updateData).Error; err != nil {
		return nil, err
	}

	if admin.FotoPegawai != nil && strings.TrimSpace(*admin.FotoPegawai) != "" {
		oldPath := filepath.Join(getPegawaiUploadDir(), strings.TrimSpace(*admin.FotoPegawai))
		_ = os.Remove(oldPath)
	}

	return r.FindByID(ctx, loginUserID)
}
