package repo

import (
	"context"
	"strings"

	"github.com/ulab/ulab-go-api/internal/models"
	"gorm.io/gorm"
)

type MappingLayananRepo struct {
	db *gorm.DB
}

func NewMappingLayananRepo(db *gorm.DB) *MappingLayananRepo { return &MappingLayananRepo{db: db} }

// scopeAliases meniru $scopeAliases pada NoAuthCtrl::getLandingMappingLayanan (Laravel),
// supaya scope key yang sama di web (kelistrikan, tekanan, dst) tetap konsisten di mobile.
var scopeAliases = map[string][]string{
	"kelistrikan": {"kelistrikan"},
	"tekanan":     {"tekanan"},
	"suhu":        {"suhu", "suhu & kelembaban", "suhu & kelembapan"},
	"vibrasi":     {"vibrasi"},
	"dimensi":     {"dimensi", "gaya"},
	"repair":      {"repair"},
}

var ScopeTitles = map[string]string{
	"kelistrikan": "Kelistrikan",
	"tekanan":     "Tekanan",
	"suhu":        "Suhu",
	"vibrasi":     "Vibrasi",
	"dimensi":     "Dimensi dan Gaya",
	"repair":      "Repair",
}

func scopeLabels(scope string) []string {
	scope = strings.ToLower(strings.TrimSpace(scope))
	if scope == "" {
		return nil
	}

	if labels, ok := scopeAliases[scope]; ok {
		return labels
	}

	fallback := strings.ReplaceAll(strings.ReplaceAll(scope, "-", " "), "_", " ")
	fallback = strings.TrimSpace(fallback)
	if fallback == "" {
		return nil
	}
	return []string{fallback}
}

type ListMappingLayananParams struct {
	Scope    string
	Kategori string
	Search   string
}

func (r *MappingLayananRepo) ListByScope(ctx context.Context, p ListMappingLayananParams) ([]models.MappingLayananItem, error) {
	q := r.db.WithContext(ctx).
		Table("mappinglayanan_m AS ml").
		Joins("JOIN lingkupkalibrasi_m AS lk ON lk.id = ml.objectlingkupfk").
		Where("ml.statusenabled = ?", true).
		Where("lk.statusenabled = ?", true).
		Select(`
			ml.id,
			COALESCE(ml.kategori, '') AS kategori,
			ml.objectlingkupfk,
			COALESCE(ml.namalayanan, '') AS namalayanan,
			COALESCE(ml.merktipe, '') AS merktipe,
			ml.gambar,
			COALESCE(lk.lingkupkalibrasi, '') AS lingkup
		`)

	if labels := scopeLabels(p.Scope); len(labels) > 0 {
		q = q.Where("LOWER(TRIM(lk.lingkupkalibrasi)) IN ?", labels)
	}

	kategori := strings.TrimSpace(p.Kategori)
	if kategori == "KAN" || kategori == "Non KAN" {
		q = q.Where("ml.kategori = ?", kategori)
	}

	if s := strings.TrimSpace(p.Search); s != "" {
		like := "%" + strings.ToLower(s) + "%"
		q = q.Where(`(
			LOWER(ml.namalayanan) LIKE ?
			OR LOWER(ml.merktipe) LIKE ?
			OR LOWER(lk.lingkupkalibrasi) LIKE ?
		)`, like, like, like)
	}

	var rows []models.MappingLayananItem
	err := q.
		Order("lk.lingkupkalibrasi").
		Order("ml.kategori").
		Order("ml.namalayanan").
		Order("ml.merktipe").
		Scan(&rows).Error

	if err != nil {
		return nil, err
	}
	return rows, nil
}
