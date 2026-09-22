package repo

import (
	"time"

	"github.com/ulab/ulab-go-api/internal/domain"
	"gorm.io/gorm"
)

type RefreshTokenRepo struct {
	db *gorm.DB
}

func NewRefreshTokenRepo(db *gorm.DB) *RefreshTokenRepo {
	return &RefreshTokenRepo{db: db}
}

func (r *RefreshTokenRepo) Create(t *domain.RefreshToken) error {
	if t.AccountType == "" {
		t.AccountType = "customer"
	}
	return r.db.Create(t).Error
}

func (r *RefreshTokenRepo) FindValid(token string, now time.Time) (*domain.RefreshToken, error) {
	var rt domain.RefreshToken
	if err := r.db.
		Where("token = ?", token).
		Where("revoked_at IS NULL").
		Where("expires_at > ?", now).
		First(&rt).Error; err != nil {
		return nil, err
	}
	if rt.AccountType == "" {
		rt.AccountType = "customer"
	}
	return &rt, nil
}

func (r *RefreshTokenRepo) RevokeByToken(token string) error {
	now := time.Now()
	return r.db.Model(&domain.RefreshToken{}).
		Where("token = ?", token).
		Where("revoked_at IS NULL").
		Update("revoked_at", &now).Error
}

func (r *RefreshTokenRepo) RevokeAllByUser(userID uint) error {
	return r.RevokeAllByUserAndAccount(userID, "customer")
}

func (r *RefreshTokenRepo) RevokeAllByUserAndAccount(userID uint, accountType string) error {
	if accountType == "" {
		accountType = "customer"
	}
	now := time.Now()
	return r.db.Model(&domain.RefreshToken{}).
		Where("user_id = ?", userID).
		Where("COALESCE(account_type, 'customer') = ?", accountType).
		Where("revoked_at IS NULL").
		Update("revoked_at", &now).Error
}
