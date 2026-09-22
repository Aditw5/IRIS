package repo

import (
	"errors"
	"time"

	"github.com/ulab/ulab-go-api/internal/domain"
	"gorm.io/gorm"
	"gorm.io/gorm/clause"
)

type UserRepo struct{ db *gorm.DB }

func NewUserRepo(db *gorm.DB) *UserRepo { return &UserRepo{db: db} }

// Insert hanya kolom yang kita set (termasuk kolom2 custom)
func (r *UserRepo) Create(u *domain.User) error {
	return r.db.Transaction(func(tx *gorm.DB) error {
		kdProfile := int64(1)
		if u.KdProfile != nil {
			kdProfile = int64(*u.KdProfile)
		}

		nextID, err := nextSafeUserIDTx(tx, kdProfile)
		if err != nil {
			return err
		}

		now := time.Now()
		u.ID = uint(nextID)
		u.CreatedAt = &now
		u.UpdatedAt = &now

		return tx.
			Select("id", "name", "email", "password", "statusenabled", "kdprofile", "isuserbaru", "nowa", "created_at", "updated_at").
			Create(u).Error
	})
}

func nextSafeUserIDTx(tx *gorm.DB, kdProfile int64) (int64, error) {
	const tableName = "users"

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
	if err := tx.
		Table(tableName).
		Where("kdprofile = ?", kdProfile).
		Select("COALESCE(MAX(id), 0)").
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

func (r *UserRepo) FindByEmail(email string) (*domain.User, error) {
	var u domain.User
	if err := r.db.Where("LOWER(email) = LOWER(?)", email).First(&u).Error; err != nil {
		return nil, err
	}
	return &u, nil
}

func (r *UserRepo) FindByID(id uint) (*domain.User, error) {
	var u domain.User
	if err := r.db.First(&u, id).Error; err != nil {
		return nil, err
	}
	return &u, nil
}

func (r *UserRepo) SetEmailVerified(id uint) error {
	now := time.Now()
	return r.db.Model(&domain.User{}).
		Where("id = ?", id).
		Update("email_verified_at", &now).Error
}

// dipakai untuk rollback jika send OTP gagal
func (r *UserRepo) DeleteByID(id uint) error {
	return r.db.Delete(&domain.User{}, id).Error
}

func (r *UserRepo) SetPassword(userID uint, hashedPassword string) error {
	return r.db.
		Model(&domain.User{}).
		Where("id = ?", userID).
		Updates(map[string]any{
			"password":   hashedPassword,
			"updated_at": time.Now(),
		}).Error
}
