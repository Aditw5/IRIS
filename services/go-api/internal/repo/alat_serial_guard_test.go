package repo

import (
	"errors"
	"testing"

	"github.com/jackc/pgx/v5/pgconn"
)

func TestNormalizeAlatSerialNumber(t *testing.T) {
	got := normalizeAlatSerialNumber("  sn-123  ")
	if got != "SN-123" {
		t.Fatalf("normalizeAlatSerialNumber() = %q, want %q", got, "SN-123")
	}
}

func TestNormalizeDuplicateAlatSerialError(t *testing.T) {
	databaseErr := &pgconn.PgError{
		Code:           "23505",
		ConstraintName: "uq_mapunittoalat_active_serial_number",
	}

	err := normalizeDuplicateAlatSerialError(databaseErr, "  sn-123  ")
	if !errors.Is(err, ErrDuplicateAlatSerialNumber) {
		t.Fatalf("error = %v, want ErrDuplicateAlatSerialNumber", err)
	}
	if got, want := err.Error(), `Alat dengan Serial Number "SN-123" sudah ada.`; got != want {
		t.Fatalf("error message = %q, want %q", got, want)
	}
}

func TestNormalizeDuplicateAlatSerialErrorKeepsOtherErrors(t *testing.T) {
	databaseErr := &pgconn.PgError{
		Code:           "23505",
		ConstraintName: "some_other_constraint",
	}

	if got := normalizeDuplicateAlatSerialError(databaseErr, "SN-123"); got != databaseErr {
		t.Fatalf("unexpected error conversion: %v", got)
	}
}
