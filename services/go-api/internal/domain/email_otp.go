package domain

import "time"

type EmailOTP struct {
	ID         uint       `gorm:"primaryKey"`
	UserID     uint       `gorm:"index"`
	Email      string     `gorm:"size:255;index"`
	Code       string     `gorm:"size:6;not null"`
	Purpose    string     `gorm:"size:32;not null"`
	ExpiresAt  time.Time  `gorm:"index"`
	ConsumedAt *time.Time `gorm:"index"`
	Attempts   int        `gorm:"default:0"`
	CreatedAt  time.Time
	UpdatedAt  time.Time
}

func (EmailOTP) TableName() string { return "go_email_otps" }
