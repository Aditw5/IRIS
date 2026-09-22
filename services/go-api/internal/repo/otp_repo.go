package repo

import (
	"time"

	"github.com/ulab/ulab-go-api/internal/domain"
	"gorm.io/gorm"
)

type OTPRepo struct{ db *gorm.DB }

func NewOTPRepo(db *gorm.DB) *OTPRepo { return &OTPRepo{db: db} }

func (r *OTPRepo) Create(o *domain.EmailOTP) error { return r.db.Create(o).Error }

func (r *OTPRepo) Latest(email, purpose string) (*domain.EmailOTP, error) {
	var o domain.EmailOTP
	err := r.db.Where("email=? AND purpose=? AND consumed_at IS NULL", email, purpose).
		Order("created_at DESC").First(&o).Error
	if err != nil {
		return nil, err
	}
	return &o, nil
}

func (r *OTPRepo) LatestActive(email, purpose string, now time.Time) (*domain.EmailOTP, error) {
	var o domain.EmailOTP
	err := r.db.Where(`
        email = ? AND purpose = ?
        AND consumed_at IS NULL AND expires_at > ?`,
		email, purpose, now,
	).Order("created_at DESC").First(&o).Error
	if err != nil {
		return nil, err
	}
	return &o, nil
}

func (r *OTPRepo) Consume(id uint) error {
	now := time.Now()
	return r.db.Model(&domain.EmailOTP{}).Where("id=?", id).
		Updates(map[string]interface{}{"consumed_at": now}).Error
}

func (r *OTPRepo) IncAttempt(id uint) error {
	return r.db.Model(&domain.EmailOTP{}).Where("id=?", id).
		UpdateColumn("attempts", gorm.Expr("attempts + 1")).Error
}
