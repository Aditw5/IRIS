package domain

import "time"

type RefreshToken struct {
	ID          uint      `gorm:"primaryKey"`
	UserID      uint      `gorm:"index;not null"`
	AccountType string    `gorm:"column:account_type;size:30;index;not null;default:customer"`
	Token       string    `gorm:"type:text;uniqueIndex;not null"`
	ExpiresAt   time.Time `gorm:"not null"`
	RevokedAt   *time.Time
	CreatedAt   time.Time
	UpdatedAt   time.Time
}

func (RefreshToken) TableName() string {
	return "refresh_tokens"
}
