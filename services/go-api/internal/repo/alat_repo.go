package repo

import (
	"context"
	"errors"
	"fmt"
	"strings"
	"time"

	"github.com/ulab/ulab-go-api/internal/imageoptimizer"
	"github.com/ulab/ulab-go-api/internal/models"
	"gorm.io/gorm"
	"gorm.io/gorm/clause"
)

type AlatRepo struct {
	db             *gorm.DB
	imageOptimizer *imageoptimizer.Client
}

func NewAlatRepo(db *gorm.DB, optimizers ...*imageoptimizer.Client) *AlatRepo {
	var optimizer *imageoptimizer.Client
	if len(optimizers) > 0 {
		optimizer = optimizers[0]
	}
	return &AlatRepo{db: db, imageOptimizer: optimizer}
}

var ErrAlatPernahDidaftarkan = errors.New("alat sudah pernah didaftarkan")

type GetAlatParams struct {
	MitraUser int
	Search    string
	Page      int
	Limit     int
}

type PageResult[T any] struct {
	Items    []T   `json:"data"`
	Total    int64 `json:"total"`
	PerPage  int   `json:"per_page"`
	Current  int   `json:"current_page"`
	LastPage int   `json:"last_page"`
	From     int   `json:"from"`
	To       int   `json:"to"`
}

func (r *AlatRepo) GetAlatCustomer(ctx context.Context, p GetAlatParams) (PageResult[models.AlatListItem], error) {
	if p.Page <= 0 {
		p.Page = 1
	}
	if p.Limit <= 0 {
		p.Limit = 10
	}

	q := r.db.WithContext(ctx).
		Table("mapunittoalat_m AS mmp").
		Select(`
			mmp.id,
			mmp.namaproduk,
			mmp.namatipe,
			mmp.namamerk,
			mmp.namaserialnumber,
			mmp.fotoproduk,
			mmp.statuskanfk,
			EXISTS (
				SELECT 1
				FROM mitraregistrasidetail_t AS mrd
				WHERE mrd.namaalatfk = mmp.id
			) AS pernahdidaftarkan,
			mmp.created_at
		`).
		Where("mmp.statusenabled = ?", true).
		Where("mmp.objectmitrafk = ?", p.MitraUser)

	if s := strings.TrimSpace(p.Search); s != "" {
		s = "%" + s + "%"
		q = q.Where(`
			(
				mmp.namaproduk ILIKE ?
				OR mmp.namamerk ILIKE ?
				OR mmp.namaserialnumber ILIKE ?
				OR mmp.namatipe ILIKE ?
				OR CAST(mmp.id AS TEXT) ILIKE ?
			)
		`, s, s, s, s, s)
	}

	var total int64
	if err := q.Count(&total).Error; err != nil {
		return PageResult[models.AlatListItem]{}, err
	}

	offset := (p.Page - 1) * p.Limit
	var rows []models.AlatListItem
	if err := q.
		Order("mmp.created_at DESC").
		Limit(p.Limit).
		Offset(offset).
		Scan(&rows).Error; err != nil {
		return PageResult[models.AlatListItem]{}, err
	}
	for i := range rows {
		rows[i].FotoProdukThumbnail = toolThumbnailFilename(rows[i].FotoProduk)
	}

	last := int((total + int64(p.Limit) - 1) / int64(p.Limit))
	from, to := 0, 0
	if total > 0 {
		from = offset + 1
		to = offset + len(rows)
	}

	return PageResult[models.AlatListItem]{
		Items:    rows,
		Total:    total,
		PerPage:  p.Limit,
		Current:  p.Page,
		LastPage: last,
		From:     from,
		To:       to,
	}, nil
}

func (r *AlatRepo) GetMasterProdukCustomerByID(
	ctx context.Context,
	id int,
	mitraUser int,
) (*models.AlatDetailItem, error) {
	var row models.AlatDetailItem

	err := r.db.WithContext(ctx).
		Table("mapunittoalat_m AS mmp").
		Select(`
			mmp.id,
			mmp.namaproduk,
			mmp.namatipe,
			mmp.namamerk,
			mmp.namaserialnumber,
			mmp.fotoproduk,
			mmp.objectmitrafk,
			mmp.statusenabled,
			mmp.statuskanfk,
			mmp.created_at,
			mmp.updated_at
		`).
		Where("mmp.statusenabled = ?", true).
		Where("mmp.objectmitrafk = ?", mitraUser).
		Where("mmp.id = ?", id).
		Take(&row).Error

	if err != nil {
		return nil, err
	}

	return &row, nil
}

func (r *AlatRepo) DeleteMasterProdukCustomer(
	ctx context.Context,
	id int,
	mitraUser int,
) error {
	return r.db.WithContext(ctx).Transaction(func(tx *gorm.DB) error {
		var alat struct {
			ID int `gorm:"column:id"`
		}
		if err := tx.
			Table("mapunittoalat_m").
			Select("id").
			Where("id = ?", id).
			Where("objectmitrafk = ?", mitraUser).
			Where("statusenabled = ?", true).
			Take(&alat).Error; err != nil {
			return err
		}

		var registrationCount int64
		if err := tx.
			Table("mitraregistrasidetail_t").
			Where("namaalatfk = ?", id).
			Count(&registrationCount).Error; err != nil {
			return err
		}

		if registrationCount > 0 {
			return ErrAlatPernahDidaftarkan
		}

		res := tx.
			Table("mapunittoalat_m").
			Where("id = ?", id).
			Where("objectmitrafk = ?", mitraUser).
			Where("statusenabled = ?", true).
			Updates(map[string]any{
				"statusenabled": false,
				"updated_at":    time.Now(),
			})

		if res.Error != nil {
			return res.Error
		}

		if res.RowsAffected == 0 {
			return gorm.ErrRecordNotFound
		}

		return nil
	})
}

type seqMasterRow struct {
	KdProfile  int64      `gorm:"column:kdprofile"`
	SeqTable   string     `gorm:"column:table"`
	Tgl        float64    `gorm:"column:tgl"`
	Norec      string     `gorm:"column:norec"`
	IdTerakhir int64      `gorm:"column:idterakhir"`
	CreatedAt  *time.Time `gorm:"column:created_at"`
	UpdatedAt  *time.Time `gorm:"column:updated_at"`
}

const seqMasterTableName = "seqmaster_t"

func (seqMasterRow) TableName() string {
	return seqMasterTableName
}

func (r *AlatRepo) nextSafeAlatIDTx(
	tx *gorm.DB,
	kdProfile int64,
	tableName string,
	idField string,
) (int64, error) {
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
	maxExpr := fmt.Sprintf("COALESCE(MAX(%s), 0)", idField)
	if err := tx.
		Table(tableName).
		Where("kdprofile = ?", kdProfile).
		Select(maxExpr).
		Scan(&maxID).Error; err != nil {
		return 0, err
	}

	// Kalau row seqmaster belum ada, bikin baru dari MAX(id) real
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

	// Sinkronkan dengan kondisi real tabel
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

func uuidString() string {
	return fmt.Sprintf("%d-%d", time.Now().UnixNano(), time.Now().Unix())
}
