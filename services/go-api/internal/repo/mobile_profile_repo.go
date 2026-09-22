package repo

import (
	"context"
	"database/sql"
	"fmt"
	"mime/multipart"
	"os"
	"path/filepath"
	"strings"
	"time"

	"gorm.io/gorm"
)

type MobileProfileRepo struct {
	db *gorm.DB
}

func NewMobileProfileRepo(db *gorm.DB) *MobileProfileRepo {
	return &MobileProfileRepo{db: db}
}

type MobileProfileDetail struct {
	ID            int64      `json:"id" gorm:"column:id"`
	Name          string     `json:"name" gorm:"column:name"`
	Email         string     `json:"email" gorm:"column:email"`
	NoWA          string     `json:"nowa" gorm:"column:nowa"`
	KdProfile     int64      `json:"kdprofile" gorm:"column:kdprofile"`
	IsUserBaru    *bool      `json:"isuserbaru" gorm:"column:isuserbaru"`
	MitraFK       *int64     `json:"mitrafk" gorm:"column:mitrafk"`
	Jabatan       string     `json:"jabatan" gorm:"column:jabatan"`
	IsEksternal   *bool      `json:"iseksternal" gorm:"column:iseksternal"`
	FilenameFoto  string     `json:"filenameFoto" gorm:"column:filenameFoto"`
	StatusEnabled *bool      `json:"statusenabled" gorm:"column:statusenabled"`
	CreatedAt     *time.Time `json:"created_at" gorm:"column:created_at"`
	UpdatedAt     *time.Time `json:"updated_at" gorm:"column:updated_at"`
	Unit          string     `json:"unit" gorm:"column:unit"`
	FotoURL       string     `json:"foto_url" gorm:"column:foto_url"`
}

type UpdateMobileProfilePayload struct {
	Name    string `json:"name"`
	Email   string `json:"email"`
	NoWA    string `json:"nowa"`
	Jabatan string `json:"jabatan"`
}

func (r *MobileProfileRepo) GetDetail(ctx context.Context, userID int64) (*MobileProfileDetail, error) {
	const webBase = "https://ulabumro.id"

	var result MobileProfileDetail

	q := `
SELECT
	u.id,
	COALESCE(u.name, '') AS name,
	COALESCE(u.email, '') AS email,
	COALESCE(u.nowa, '') AS nowa,
	COALESCE(u.kdprofile, 0) AS kdprofile,
	u.isuserbaru,
	u.mitrafk,
	COALESCE(u.jabatan, '') AS jabatan,
	u.iseksternal,
	COALESCE(u."filenameFoto", '') AS "filenameFoto",
	u.statusenabled,
	u.created_at,
	u.updated_at,
	COALESCE(mm.namaperusahaan, '') AS unit,
	CASE
		WHEN COALESCE(u."filenameFoto", '') <> ''
		THEN ? || '/berkas-user/' || u."filenameFoto"
		ELSE ''
	END AS foto_url
FROM users u
LEFT JOIN mitra_m mm
	ON CAST(u.mitrafk AS TEXT) = mm.id
WHERE u.id = ?
LIMIT 1
`

	err := r.db.WithContext(ctx).
		Raw(q, webBase, userID).
		Scan(&result).Error
	if err != nil {
		return nil, err
	}

	if result.ID == 0 {
		return nil, sql.ErrNoRows
	}

	return &result, nil
}

func (r *MobileProfileRepo) UpdateDetail(ctx context.Context, userID int64, payload UpdateMobileProfilePayload) (*MobileProfileDetail, error) {
	payload.Name = strings.TrimSpace(payload.Name)
	payload.Email = strings.TrimSpace(payload.Email)
	payload.NoWA = strings.TrimSpace(payload.NoWA)
	payload.Jabatan = strings.TrimSpace(payload.Jabatan)

	if payload.Name == "" {
		return nil, fmt.Errorf("nama wajib diisi")
	}
	if payload.Email == "" {
		return nil, fmt.Errorf("email wajib diisi")
	}

	type emailCheck struct {
		ID int64 `gorm:"column:id"`
	}

	var cek emailCheck
	err := r.db.WithContext(ctx).
		Raw(`SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1`, payload.Email, userID).
		Scan(&cek).Error
	if err != nil {
		return nil, err
	}
	if cek.ID != 0 {
		return nil, fmt.Errorf("email sudah digunakan user lain")
	}

	updateData := map[string]interface{}{
		"name":       payload.Name,
		"email":      payload.Email,
		"nowa":       payload.NoWA,
		"jabatan":    payload.Jabatan,
		"updated_at": time.Now(),
	}

	if err := r.db.WithContext(ctx).
		Table("users").
		Where("id = ?", userID).
		Updates(updateData).Error; err != nil {
		return nil, err
	}

	return r.GetDetail(ctx, userID)
}

func getUserUploadDir() string {
	if envDir := strings.TrimSpace(os.Getenv("USER_UPLOAD_DIR")); envDir != "" {
		return envDir
	}
	return filepath.Clean(filepath.Join("..", "..", "backend", "public", "berkas-user"))
}

func saveUserMultipartFile(srcFile *multipart.FileHeader, dst string) error {
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

func saveUserPhotoToLaravelPublic(file *multipart.FileHeader, userID int64) (string, error) {
	if file == nil {
		return "", fmt.Errorf("file tidak ditemukan")
	}

	uploadDir := getUserUploadDir()
	if err := os.MkdirAll(uploadDir, 0755); err != nil {
		return "", err
	}

	ext := strings.ToLower(filepath.Ext(strings.TrimSpace(file.Filename)))
	if ext == "" {
		ext = ".jpg"
	}

	filename := fmt.Sprintf("foto_user_%d_%d%s", userID, time.Now().Unix(), ext)
	dst := filepath.Join(uploadDir, filename)

	if err := saveUserMultipartFile(file, dst); err != nil {
		return "", err
	}

	return filename, nil
}

func (r *MobileProfileRepo) UploadPhoto(ctx context.Context, userID int64, file *multipart.FileHeader) (*MobileProfileDetail, error) {
	if file == nil {
		return nil, fmt.Errorf("file foto tidak ditemukan")
	}

	var current struct {
		FilenameFoto *string `gorm:"column:filenameFoto"`
	}

	if err := r.db.WithContext(ctx).
		Table("users").
		Select(`"filenameFoto"`).
		Where("id = ?", userID).
		Take(&current).Error; err != nil {
		return nil, err
	}

	filename, err := saveUserPhotoToLaravelPublic(file, userID)
	if err != nil {
		return nil, err
	}

	if err := r.db.WithContext(ctx).
		Table("users").
		Where("id = ?", userID).
		Updates(map[string]interface{}{
			"filenameFoto": filename,
			"updated_at":   time.Now(),
		}).Error; err != nil {
		return nil, err
	}

	if current.FilenameFoto != nil && strings.TrimSpace(*current.FilenameFoto) != "" {
		oldPath := filepath.Join(getUserUploadDir(), strings.TrimSpace(*current.FilenameFoto))
		_ = os.Remove(oldPath)
	}

	return r.GetDetail(ctx, userID)
}
