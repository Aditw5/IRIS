package domain

import "time"

// Mapping 1:1 ke tabel Laravel: public.users
type User struct {
	ID              uint       `gorm:"primaryKey;column:id"` // int4
	Name            string     `gorm:"column:name;size:255;not null"`
	Email           string     `gorm:"column:email;size:255;not null;uniqueIndex"`
	EmailVerifiedAt *time.Time `gorm:"column:email_verified_at"` // nullable
	Password        string     `gorm:"column:password;size:255;not null"`
	RememberToken   *string    `gorm:"column:remember_token;size:100"` // nullable
	CreatedAt       *time.Time `gorm:"column:created_at"`              // nullable (Laravel default bisa null)
	UpdatedAt       *time.Time `gorm:"column:updated_at"`              // nullable

	// Kolom-kolom custom yang ada di DB kamu:
	StatusEnabled *bool   `gorm:"column:statusenabled"`    // nullable
	NoWa          *string `gorm:"column:nowa;size:255"`    // nullable
	KdProfile     *int16  `gorm:"column:kdprofile"`        // int2 (smallint), nullable
	IsUserBaru    *bool   `gorm:"column:isuserbaru"`       // nullable
	MitraFk       *int32  `gorm:"column:mitrafk"`          // int4, nullable
	Jabatan       *string `gorm:"column:jabatan;size:255"` // nullable
	IsEksternal   *bool   `gorm:"column:iseksternal"`      // nullable
}

// Pastikan pakai tabel "users" milik Laravel
func (User) TableName() string { return "users" }
