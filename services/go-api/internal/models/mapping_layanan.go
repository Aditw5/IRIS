package models

// MappingLayananItem merepresentasikan satu alat/layanan pada mapping layanan,
// setara dengan hasil query getLandingMappingLayanan di Laravel (mappinglayanan_m
// join lingkupkalibrasi_m), tapi disajikan langsung dari Go tanpa lewat Laravel.
type MappingLayananItem struct {
	ID              int64   `json:"id"              gorm:"column:id"`
	Kategori        string  `json:"kategori"        gorm:"column:kategori"`
	ObjectLingkupFK int64   `json:"objectlingkupfk" gorm:"column:objectlingkupfk"`
	NamaLayanan     string  `json:"namalayanan"     gorm:"column:namalayanan"`
	MerkTipe        string  `json:"merktipe"        gorm:"column:merktipe"`
	Gambar          *string `json:"gambar"          gorm:"column:gambar"`
	Lingkup         string  `json:"lingkup"         gorm:"column:lingkup"`
}
