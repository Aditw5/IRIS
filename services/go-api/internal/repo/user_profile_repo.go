package repo

import (
	"context"
	"fmt"
	"strconv"
	"strings"

	"gorm.io/gorm"
)

type UserProfileRepo struct {
	db *gorm.DB
}

func NewUserProfileRepo(db *gorm.DB) *UserProfileRepo {
	return &UserProfileRepo{db: db}
}

// ====== UPDATE LAMA ======
func (r *UserProfileRepo) UpdateNewUserProfileIfNew(ctx context.Context, userID int, mitraFK int, jabatan string) (int64, error) {
	tx := r.db.WithContext(ctx).
		Table("users").
		Where("id = ? AND isuserbaru = true", userID).
		Updates(map[string]any{
			"mitrafk":     int32(mitraFK),
			"jabatan":     strings.ToUpper(strings.TrimSpace(jabatan)),
			"iseksternal": nil,
			"isuserbaru":  false,
		})
	return tx.RowsAffected, tx.Error
}

func (r *UserProfileRepo) UpdateNewUserProfile(ctx context.Context, userID int, mitraFK int, jabatan string) (int64, error) {
	tx := r.db.WithContext(ctx).
		Table("users").
		Where("id = ?", userID).
		Updates(map[string]any{
			"mitrafk":     int32(mitraFK),
			"jabatan":     strings.ToUpper(strings.TrimSpace(jabatan)),
			"iseksternal": nil,
			"isuserbaru":  false,
		})
	return tx.RowsAffected, tx.Error
}

// ====== STATUS ======
type NewUserStatus struct {
	IsUserBaru  bool    `gorm:"column:isuserbaru" json:"isuserbaru"`
	MitraFK     *int32  `gorm:"column:mitrafk" json:"mitrafk"`
	Jabatan     *string `gorm:"column:jabatan" json:"jabatan"`
	IsEksternal *bool   `gorm:"column:iseksternal" json:"iseksternal"`
}

func (r *UserProfileRepo) GetNewUserStatus(ctx context.Context, userID int) (NewUserStatus, error) {
	var s NewUserStatus
	err := r.db.WithContext(ctx).
		Table("users").
		Select("isuserbaru, mitrafk, jabatan, iseksternal").
		Where("id = ?", userID).
		Take(&s).Error
	return s, err
}

// cari next numeric code dari kodeexternal
func (r *UserProfileRepo) nextMitraKodeExternal(ctx context.Context) (int, error) {
	var nextID int
	err := r.db.WithContext(ctx).Raw(`
		SELECT COALESCE(MAX(CAST(kodeexternal AS INTEGER)), 0) + 1
		FROM mitra_m
		WHERE kodeexternal ~ '^[0-9]+$'
	`).Scan(&nextID).Error
	return nextID, err
}

// bikin string id sederhana untuk mitra_m
func (r *UserProfileRepo) nextMitraStringID(ctx context.Context) (string, error) {
	var nextNum int
	err := r.db.WithContext(ctx).Raw(`
		SELECT COALESCE(MAX(
			CASE
				WHEN id ~ '^[0-9]+$' THEN CAST(id AS INTEGER)
				ELSE 0
			END
		), 0) + 1
		FROM mitra_m
	`).Scan(&nextNum).Error
	if err != nil {
		return "", err
	}
	return strconv.Itoa(nextNum), nil
}

// INTERNAL
func (r *UserProfileRepo) SetupCustomerProfile(
	ctx context.Context,
	userID int,
	kategori string,
	mitraFK *int,
	institusi *string,
	jabatan string,
) (int64, error) {
	_ = kategori
	_ = institusi

	data := map[string]any{
		"jabatan":     strings.ToUpper(strings.TrimSpace(jabatan)),
		"iseksternal": nil,
		"isuserbaru":  false,
	}

	if mitraFK != nil && *mitraFK > 0 {
		data["mitrafk"] = int32(*mitraFK)
	}

	tx := r.db.WithContext(ctx).
		Table("users").
		Where("id = ?", userID).
		Updates(data)

	return tx.RowsAffected, tx.Error
}

// EKSTERNAL
func (r *UserProfileRepo) SetupExternalCustomerProfile(
	ctx context.Context,
	userID int,
	kategori string,
	mitraFK *int,
	institusi *string,
	jabatan string,
) (int64, *int, error) {
	_ = kategori

	var finalMitraID *int
	var rowsAffected int64

	err := r.db.WithContext(ctx).Transaction(func(tx *gorm.DB) error {
		// kalau tidak pilih mitra, tapi isi institusi manual -> buat mitra baru
		if (mitraFK == nil || *mitraFK <= 0) && institusi != nil && strings.TrimSpace(*institusi) != "" {
			nextKode, err := r.nextMitraKodeExternal(ctx)
			if err != nil {
				return err
			}

			nextID, err := r.nextMitraStringID(ctx)
			if err != nil {
				return err
			}

			statusEnabled := true
			isEksternal := true
			kodeExternal := strconv.Itoa(nextKode)

			newMitra := MitraM{
				ID:             nextID,
				StatusEnabled:  &statusEnabled,
				NamaPerusahaan: strings.ToUpper(strings.TrimSpace(*institusi)),
				KodeExternal:   &kodeExternal,
				IsEksternal:    &isEksternal,
			}

			if err := tx.WithContext(ctx).Table("mitra_m").Create(&newMitra).Error; err != nil {
				return err
			}

			finalMitraID = &nextKode
		} else if mitraFK != nil && *mitraFK > 0 {
			finalMitraID = mitraFK
		}

		data := map[string]any{
			"jabatan":     strings.ToUpper(strings.TrimSpace(jabatan)),
			"iseksternal": true,
			"isuserbaru":  false,
		}

		if finalMitraID != nil && *finalMitraID > 0 {
			data["mitrafk"] = int32(*finalMitraID)
		}

		res := tx.WithContext(ctx).
			Table("users").
			Where("id = ?", userID).
			Updates(data)

		if res.Error != nil {
			return res.Error
		}

		rowsAffected = res.RowsAffected
		return nil
	})

	if err != nil {
		return 0, nil, err
	}

	return rowsAffected, finalMitraID, nil
}

// helper debug optional
func (r *UserProfileRepo) debugUserMitra(ctx context.Context, userID int) (string, error) {
	var row struct {
		ID      int    `gorm:"column:id"`
		MitraFK *int32 `gorm:"column:mitrafk"`
		Jabatan string `gorm:"column:jabatan"`
	}
	err := r.db.WithContext(ctx).Table("users").
		Select("id, mitrafk, jabatan").
		Where("id = ?", userID).
		Take(&row).Error
	if err != nil {
		return "", err
	}
	return fmt.Sprintf("user=%d mitrafk=%v jabatan=%s", row.ID, row.MitraFK, row.Jabatan), nil
}
