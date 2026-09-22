package db

import (
	"database/sql"
	"fmt"
	"log"
	"strings"

	"gorm.io/gorm"
)

// SyncSequence: samakan sequence kolom ke MAX(col)+1.
func SyncSequence(d *gorm.DB, table, column string) error {
	var seq sql.NullString
	// Dapatkan nama sequence untuk kolom (serial/identity)
	if err := d.Raw("SELECT pg_get_serial_sequence(?, ?) AS seq", table, column).Scan(&seq).Error; err != nil {
		return err
	}
	if !seq.Valid || strings.TrimSpace(seq.String) == "" {
		// Tidak ada sequence terkait; lewati
		return nil
	}
	stmt := fmt.Sprintf(
		"SELECT setval('%s', COALESCE((SELECT MAX(%s) FROM %s), 0) + 1, false)",
		seq.String, column, table,
	)
	return d.Exec(stmt).Error
}

// Helper khusus tabel users
func SyncUsersIDSequence(d *gorm.DB) {
	if err := SyncSequence(d, "public.users", "id"); err != nil {
		log.Printf("warn: users.id sequence sync failed: %v", err)
	}
}
