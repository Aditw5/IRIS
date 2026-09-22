package repo

import (
	"context"
	"errors"
	"fmt"
	"io"
	"mime/multipart"
	"os"
	"path/filepath"
	"strings"
	"time"

	"gorm.io/gorm"
)

type SaveMasterProdukCustomerParams struct {
	ID               int64
	MitraFK          int64
	NamaProduk       string
	NamaMerk         string
	NamaTipe         string
	NamaSerialNumber string
	StatusEnabled    bool
	NamaFileLama     string
	FileMitra        *multipart.FileHeader
}

type SaveMasterProdukCustomerResult struct {
	ID                  int64   `json:"id"`
	NamaProduk          string  `json:"namaproduk"`
	NamaMerk            string  `json:"namamerk"`
	NamaTipe            string  `json:"namatipe"`
	NamaSerialNumber    string  `json:"namaserialnumber"`
	FotoProduk          string  `json:"fotoproduk"`
	FotoProdukThumbnail *string `json:"fotoproduk_thumbnail,omitempty"`
	ObjectMitraFK       int64   `json:"objectmitrafk"`
	StatusEnabled       bool    `json:"statusenabled"`
}

func (r *AlatRepo) SaveMasterProdukCustomer(
	ctx context.Context,
	p SaveMasterProdukCustomerParams,
) (*SaveMasterProdukCustomerResult, error) {
	namaproduk := strings.ToUpper(strings.TrimSpace(p.NamaProduk))
	namamerk := strings.ToUpper(strings.TrimSpace(p.NamaMerk))
	namatipe := strings.ToUpper(strings.TrimSpace(p.NamaTipe))
	namaserialnumber := normalizeAlatSerialNumber(p.NamaSerialNumber)

	if namaproduk == "" {
		return nil, errors.New("nama alat harus diisi")
	}
	if namamerk == "" {
		return nil, errors.New("merk harus diisi")
	}
	if namatipe == "" {
		return nil, errors.New("tipe harus diisi")
	}
	if namaserialnumber == "" {
		return nil, errors.New("serial number harus diisi")
	}
	if p.MitraFK <= 0 {
		return nil, errors.New("mitrafk tidak valid")
	}

	filename := strings.TrimSpace(p.NamaFileLama)
	if p.ID <= 0 && p.FileMitra == nil && filename == "" {
		return nil, errors.New("foto alat wajib diunggah")
	}

	var result *SaveMasterProdukCustomerResult
	savedNewFiles := []string{}

	err := r.db.WithContext(ctx).Transaction(func(tx *gorm.DB) error {
		var existing struct {
			ID            int64   `gorm:"column:id"`
			FotoProduk    *string `gorm:"column:fotoproduk"`
			NamaSerial    string  `gorm:"column:namaserialnumber"`
			StatusEnabled bool    `gorm:"column:statusenabled"`
		}

		if p.ID > 0 {
			if err := tx.Table("mapunittoalat_m").
				Select("id, fotoproduk, namaserialnumber, statusenabled").
				Where("id = ?", p.ID).
				Where("objectmitrafk = ?", p.MitraFK).
				First(&existing).Error; err != nil {
				if errors.Is(err, gorm.ErrRecordNotFound) {
					return errors.New("data alat tidak ditemukan")
				}
				return err
			}

		}

		mustCheckSerial := p.ID <= 0 || (p.StatusEnabled && (normalizeAlatSerialNumber(existing.NamaSerial) != namaserialnumber || !existing.StatusEnabled))
		if mustCheckSerial {
			if err := ensureActiveAlatSerialUnique(tx, namaserialnumber, p.ID); err != nil {
				return err
			}
		}

		if p.FileMitra != nil {
			stored, err := r.imageOptimizer.StoreToolImage(ctx, p.FileMitra, getProdukUploadDir(), func() (string, error) {
				return saveProdukFile(p.FileMitra)
			})
			if err != nil {
				return err
			}
			filename = stored.Filename
			savedNewFiles = stored.CreatedFiles
		}

		if p.ID > 0 {
			if filename == "" && existing.FotoProduk != nil {
				filename = strings.TrimSpace(*existing.FotoProduk)
			}

			updateData := map[string]any{
				"namaproduk":       namaproduk,
				"namamerk":         namamerk,
				"namatipe":         namatipe,
				"namaserialnumber": namaserialnumber,
				"fotoproduk":       filename,
				"statusenabled":    p.StatusEnabled,
				"objectmitrafk":    p.MitraFK,
				"updated_at":       time.Now(),
			}

			if err := tx.Table("mapunittoalat_m").
				Where("id = ?", p.ID).
				Where("objectmitrafk = ?", p.MitraFK).
				Updates(updateData).Error; err != nil {
				return err
			}

			result = &SaveMasterProdukCustomerResult{
				ID:               p.ID,
				NamaProduk:       namaproduk,
				NamaMerk:         namamerk,
				NamaTipe:         namatipe,
				NamaSerialNumber: namaserialnumber,
				FotoProduk:       filename,
				ObjectMitraFK:    p.MitraFK,
				StatusEnabled:    p.StatusEnabled,
			}
			return nil
		}

		// INSERT BARU:
		// ambil ID yang aman sekaligus sync seqmaster,
		// supaya save dari mobile tidak bentrok dengan web Laravel
		nextID, err := r.nextSafeAlatIDTx(tx, 1, "mapunittoalat_m", "id")
		if err != nil {
			return err
		}

		now := time.Now()
		insertData := map[string]any{
			"id":               nextID,
			"kdprofile":        1,
			"namaproduk":       namaproduk,
			"namamerk":         namamerk,
			"namatipe":         namatipe,
			"namaserialnumber": namaserialnumber,
			"fotoproduk":       filename,
			"statusenabled":    p.StatusEnabled,
			"objectmitrafk":    p.MitraFK,
			"created_at":       now,
			"updated_at":       now,
		}

		if err := tx.Table("mapunittoalat_m").Create(insertData).Error; err != nil {
			return err
		}

		result = &SaveMasterProdukCustomerResult{
			ID:               nextID,
			NamaProduk:       namaproduk,
			NamaMerk:         namamerk,
			NamaTipe:         namatipe,
			NamaSerialNumber: namaserialnumber,
			FotoProduk:       filename,
			ObjectMitraFK:    p.MitraFK,
			StatusEnabled:    p.StatusEnabled,
		}

		return nil
	})
	if err != nil {
		for _, savedNewFile := range savedNewFiles {
			_ = os.Remove(filepath.Join(getProdukUploadDir(), savedNewFile))
		}
		return nil, normalizeDuplicateAlatSerialError(err, namaserialnumber)
	}

	result.FotoProdukThumbnail = toolThumbnailFilename(&result.FotoProduk)
	return result, nil
}

func toolThumbnailFilename(filename *string) *string {
	if filename == nil {
		return nil
	}
	name := strings.TrimSpace(*filename)
	if name == "" || !strings.EqualFold(filepath.Ext(name), ".webp") {
		return nil
	}
	thumbnail := strings.TrimSuffix(name, filepath.Ext(name)) + "-thumb.webp"
	if _, err := os.Stat(filepath.Join(getProdukUploadDir(), thumbnail)); err != nil {
		return nil
	}
	return &thumbnail
}

func saveProdukFile(file *multipart.FileHeader) (string, error) {
	if file == nil {
		return "", nil
	}

	ext := strings.ToLower(filepath.Ext(file.Filename))
	switch ext {
	case ".jpg", ".jpeg", ".png", ".webp":
	default:
		return "", errors.New("file harus berupa JPG, JPEG, PNG, atau WEBP")
	}

	baseDir := getProdukUploadDir()
	if err := os.MkdirAll(baseDir, 0755); err != nil {
		return "", err
	}

	filename := fmt.Sprintf("%d%s", time.Now().UnixNano(), ext)
	dstPath := filepath.Join(baseDir, filename)

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

	if _, err = io.Copy(dst, src); err != nil {
		return "", err
	}

	return filename, nil
}

func getProdukUploadDir() string {
	if v := strings.TrimSpace(os.Getenv("UPLOAD_PRODUK_DIR")); v != "" {
		return v
	}

	return "/var/www/ulab-kalibrasi/backend/public/produk"
}
