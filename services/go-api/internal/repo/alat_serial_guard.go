package repo

import (
	"errors"
	"fmt"
	"strings"

	"github.com/jackc/pgx/v5/pgconn"
	"gorm.io/gorm"
)

var ErrDuplicateAlatSerialNumber = errors.New("alat dengan serial number yang sama sudah ada")

type DuplicateAlatSerialNumberError struct {
	SerialNumber string
}

func (e *DuplicateAlatSerialNumberError) Error() string {
	return fmt.Sprintf("Alat dengan Serial Number \"%s\" sudah ada.", e.SerialNumber)
}

func (e *DuplicateAlatSerialNumberError) Unwrap() error {
	return ErrDuplicateAlatSerialNumber
}

func normalizeAlatSerialNumber(value string) string {
	return strings.ToUpper(strings.TrimSpace(value))
}

func ensureActiveAlatSerialUnique(tx *gorm.DB, serialNumber string, excludeID int64) error {
	serialNumber = normalizeAlatSerialNumber(serialNumber)
	if serialNumber == "" {
		return errors.New("serial number harus diisi")
	}

	lockKey := "mapunittoalat-sn:" + serialNumber
	if err := tx.Exec(
		"SELECT pg_advisory_xact_lock(hashtextextended(?, 0))",
		lockKey,
	).Error; err != nil {
		return err
	}

	var existing struct {
		ID int64 `gorm:"column:id"`
	}
	query := tx.Table("mapunittoalat_m").
		Select("id").
		Where("statusenabled = TRUE").
		Where("UPPER(TRIM(namaserialnumber)) = ?", serialNumber)
	if excludeID > 0 {
		query = query.Where("id <> ?", excludeID)
	}

	err := query.Take(&existing).Error
	if err == nil {
		return &DuplicateAlatSerialNumberError{SerialNumber: serialNumber}
	}
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil
	}

	return err
}

func normalizeDuplicateAlatSerialError(err error, serialNumber string) error {
	var pgErr *pgconn.PgError
	if errors.As(err, &pgErr) && pgErr.Code == "23505" &&
		strings.EqualFold(pgErr.ConstraintName, "uq_mapunittoalat_active_serial_number") {
		return &DuplicateAlatSerialNumberError{
			SerialNumber: normalizeAlatSerialNumber(serialNumber),
		}
	}
	return err
}
