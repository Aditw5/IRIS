package repo

import (
	"testing"
	"time"
)

func TestFormatCheckoutTimeWIBConvertsUTC(t *testing.T) {
	utc := time.Date(2026, time.July, 20, 4, 14, 0, 0, time.UTC)
	got := formatCheckoutTimeWIB(utc)
	want := "20 Juli 2026 11:14"
	if got != want {
		t.Fatalf("formatCheckoutTimeWIB() = %q, want %q", got, want)
	}
}

func TestCheckoutNowUsesWIB(t *testing.T) {
	_, offset := checkoutNow().Zone()
	if offset != 7*60*60 {
		t.Fatalf("checkoutNow() offset = %d, want %d", offset, 7*60*60)
	}
}
