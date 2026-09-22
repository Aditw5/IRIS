package repo

import (
	"database/sql"
	"strconv"
	"strings"

	"gorm.io/gorm"
)

type KeranjangRepo struct {
	DB *gorm.DB
}

func NewKeranjangRepo(db *gorm.DB) *KeranjangRepo {
	return &KeranjangRepo{DB: db}
}

type AlatOwnerCheckResult struct {
	IsOwner       bool
	NamaProduk    string
	ObjectMitraFK string
	UserMitraFK   string
}

func (r *KeranjangRepo) CheckAlatOwnedByUserUnit(tx *gorm.DB, customerID int, idAlat int) (*AlatOwnerCheckResult, error) {
	type userRow struct {
		MitraFK sql.NullInt64 `gorm:"column:mitrafk"`
	}

	var u userRow
	if err := tx.Raw(`
		select mitrafk
		from users
		where id = ?
		limit 1
	`, customerID).Scan(&u).Error; err != nil {
		return nil, err
	}

	if !u.MitraFK.Valid || u.MitraFK.Int64 <= 0 {
		return &AlatOwnerCheckResult{
			IsOwner:       false,
			UserMitraFK:   "",
			ObjectMitraFK: "",
			NamaProduk:    "",
		}, nil
	}

	type alatRow struct {
		ID            int            `gorm:"column:id"`
		NamaProduk    sql.NullString `gorm:"column:namaproduk"`
		ObjectMitraFK sql.NullString `gorm:"column:objectmitrafk"`
	}

	var a alatRow
	if err := tx.Raw(`
		select
			m.id,
			m.namaproduk,
			cast(m.objectmitrafk as text) as objectmitrafk
		from mapunittoalat_m m
		where m.id = ?
		  and m.statusenabled = true
		limit 1
	`, idAlat).Scan(&a).Error; err != nil {
		return nil, err
	}

	userMitraFk := strconv.FormatInt(u.MitraFK.Int64, 10)

	if a.ID <= 0 {
		return &AlatOwnerCheckResult{
			IsOwner:       false,
			NamaProduk:    "",
			ObjectMitraFK: "",
			UserMitraFK:   userMitraFk,
		}, nil
	}

	objectMitraFk := ""
	if a.ObjectMitraFK.Valid {
		objectMitraFk = strings.TrimSpace(a.ObjectMitraFK.String)
	}

	namaProduk := ""
	if a.NamaProduk.Valid {
		namaProduk = strings.TrimSpace(a.NamaProduk.String)
	}

	return &AlatOwnerCheckResult{
		IsOwner:       userMitraFk != "" && objectMitraFk != "" && userMitraFk == objectMitraFk,
		NamaProduk:    namaProduk,
		ObjectMitraFK: objectMitraFk,
		UserMitraFK:   userMitraFk,
	}, nil
}
