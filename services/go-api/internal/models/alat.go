package models

import "time"

// Hanya field yang diperlukan untuk listing
type AlatListItem struct {
	ID                  int        `json:"id"               gorm:"column:id"`
	NamaProduk          string     `json:"namaproduk"       gorm:"column:namaproduk"`
	NamaTipe            string     `json:"namatipe"         gorm:"column:namatipe"`
	NamaMerk            string     `json:"namamerk"         gorm:"column:namamerk"`
	NamaSerial          string     `json:"namaserialnumber" gorm:"column:namaserialnumber"`
	FotoProduk          *string    `json:"fotoproduk"       gorm:"column:fotoproduk"`
	FotoProdukThumbnail *string    `json:"fotoproduk_thumbnail,omitempty" gorm:"-"`
	StatusKanFK         *int       `json:"statuskanfk"       gorm:"column:statuskanfk"`
	PernahDaftar        bool       `json:"pernahdidaftarkan" gorm:"column:pernahdidaftarkan"`
	CreatedAt           *time.Time `json:"created_at"        gorm:"column:created_at"`
}

func (AlatListItem) TableName() string { return "mapunittoalat_m" }

type AlatDetailItem struct {
	ID                  int        `json:"id"               gorm:"column:id"`
	NamaProduk          string     `json:"namaproduk"       gorm:"column:namaproduk"`
	NamaTipe            string     `json:"namatipe"         gorm:"column:namatipe"`
	NamaMerk            string     `json:"namamerk"         gorm:"column:namamerk"`
	NamaSerial          string     `json:"namaserialnumber" gorm:"column:namaserialnumber"`
	FotoProduk          *string    `json:"fotoproduk"       gorm:"column:fotoproduk"`
	FotoProdukThumbnail *string    `json:"fotoproduk_thumbnail,omitempty" gorm:"-"`
	ObjectMitraFK       int        `json:"objectmitrafk"    gorm:"column:objectmitrafk"`
	StatusEnabled       bool       `json:"statusenabled"    gorm:"column:statusenabled"`
	StatusKanFK         *int       `json:"statuskanfk"      gorm:"column:statuskanfk"`
	CreatedAt           *time.Time `json:"created_at"       gorm:"column:created_at"`
	UpdatedAt           *time.Time `json:"updated_at"       gorm:"column:updated_at"`
}

func (AlatDetailItem) TableName() string { return "mapunittoalat_m" }
