package repo

import (
	"context"
	"strconv"
	"strings"

	"gorm.io/gorm"
)

// mapping minimal ke tabel mitra_m (punya Laravel)
type MitraM struct {
	ID             string  `gorm:"column:id"` // varchar(36)
	StatusEnabled  *bool   `gorm:"column:statusenabled"`
	NamaPerusahaan string  `gorm:"column:namaperusahaan"`
	KodeExternal   *string `gorm:"column:kodeexternal"` // sering dipakai sebagai numeric code
	IsEksternal    *bool   `gorm:"column:iseksternal"`
}

func (MitraM) TableName() string { return "mitra_m" }

type MitraRepo struct {
	db *gorm.DB
}

func NewMitraRepo(db *gorm.DB) *MitraRepo { return &MitraRepo{db: db} }

// ListMitra: search by namaperusahaan (ILIKE), paginasi sederhana
type MitraItem struct {
	ID             string `json:"id"` // id varchar (UUID/ULID)
	NamaPerusahaan string `json:"namaperusahaan"`
	// kirimkan juga angka turunan jika ada (diambil dari kodeexternal)
	// front-end bebas pakai ini bila perlu numeric
	MitraFK *int `json:"mitrafk,omitempty"`
}

func (r *MitraRepo) ListMitra(ctx context.Context, search string, page, limit int) ([]MitraItem, error) {
	if page <= 0 {
		page = 1
	}
	if limit <= 0 || limit > 100 {
		limit = 20
	}

	q := r.db.WithContext(ctx).
		Table("mitra_m").
		Select("id, namaperusahaan, kodeexternal").
		Where("statusenabled = TRUE")

	if s := strings.TrimSpace(search); s != "" {
		q = q.Where("namaperusahaan ILIKE ?", "%"+s+"%")
	}

	q = q.Order("namaperusahaan ASC").
		Offset((page - 1) * limit).
		Limit(limit)

	var rows []MitraM
	if err := q.Find(&rows).Error; err != nil {
		return nil, err
	}

	out := make([]MitraItem, 0, len(rows))
	for _, m := range rows {
		var fk *int
		if m.KodeExternal != nil {
			if n, err := strconv.Atoi(strings.TrimSpace(*m.KodeExternal)); err == nil {
				fk = new(int)
				*fk = n
			}
		}
		out = append(out, MitraItem{
			ID:             m.ID,
			NamaPerusahaan: m.NamaPerusahaan,
			MitraFK:        fk,
		})
	}
	return out, nil
}

// ListMitraEksternal: hanya mitra yang iseksternal = true
func (r *MitraRepo) ListMitraEksternal(ctx context.Context, search string, page, limit int) ([]MitraItem, error) {
	if page <= 0 {
		page = 1
	}
	if limit <= 0 || limit > 100 {
		limit = 20
	}

	q := r.db.WithContext(ctx).
		Table("mitra_m").
		Select("id, namaperusahaan, kodeexternal, iseksternal").
		Where("statusenabled = TRUE").
		Where("iseksternal = TRUE")

	if s := strings.TrimSpace(search); s != "" {
		q = q.Where("namaperusahaan ILIKE ?", "%"+s+"%")
	}

	q = q.Order("namaperusahaan ASC").
		Offset((page - 1) * limit).
		Limit(limit)

	var rows []MitraM
	if err := q.Find(&rows).Error; err != nil {
		return nil, err
	}

	out := make([]MitraItem, 0, len(rows))
	for _, m := range rows {
		var fk *int
		if m.KodeExternal != nil {
			if n, err := strconv.Atoi(strings.TrimSpace(*m.KodeExternal)); err == nil {
				fk = new(int)
				*fk = n
			}
		}
		out = append(out, MitraItem{
			ID:             m.ID,
			NamaPerusahaan: m.NamaPerusahaan,
			MitraFK:        fk,
		})
	}
	return out, nil
}

// Ambil numeric mitrafk dari unit_id (berdasarkan kodeexternal).
// Return (mitrafk, found)
func (r *MitraRepo) NumericFromUnitID(ctx context.Context, unitID string) (int, bool, error) {
	var m MitraM
	if err := r.db.WithContext(ctx).
		Table("mitra_m").
		Select("kodeexternal").
		Where("id = ? AND statusenabled = TRUE", unitID).
		Take(&m).Error; err != nil {
		if err == gorm.ErrRecordNotFound {
			return 0, false, nil
		}
		return 0, false, err
	}
	if m.KodeExternal == nil {
		return 0, false, nil
	}
	if n, err := strconv.Atoi(strings.TrimSpace(*m.KodeExternal)); err == nil {
		return n, true, nil
	}
	return 0, false, nil
}
