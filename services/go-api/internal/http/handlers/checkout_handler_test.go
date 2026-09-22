package handlers

import (
	"errors"
	"net/http"
	"testing"

	"github.com/ulab/ulab-go-api/internal/repo"
)

func TestValidateCheckoutItems(t *testing.T) {
	items := []repo.CheckoutItem{
		{Norec: " cart-1 ", ID: 10, JenisOrder: " KALIBRASI "},
		{Norec: "cart-2", ID: 11, JenisOrder: "repair"},
	}

	hasCalibration, err := validateCheckoutItems(items)
	if err != nil {
		t.Fatalf("validateCheckoutItems() error = %v", err)
	}
	if !hasCalibration {
		t.Fatal("checkout campuran harus terdeteksi memiliki item kalibrasi")
	}
	if items[0].Norec != "cart-1" || items[0].JenisOrder != "kalibrasi" {
		t.Fatalf("item tidak dinormalisasi: %#v", items[0])
	}
}

func TestValidateCheckoutItemsRejectsDuplicateCart(t *testing.T) {
	items := []repo.CheckoutItem{
		{Norec: "cart-1", ID: 10, JenisOrder: "kalibrasi"},
		{Norec: "cart-1", ID: 11, JenisOrder: "repair"},
	}

	if _, err := validateCheckoutItems(items); err == nil {
		t.Fatal("norec keranjang duplikat harus ditolak")
	}
}

func TestCheckoutSaveErrorResponseAlreadyProcessed(t *testing.T) {
	status, code, _ := checkoutSaveErrorResponse(
		errors.Join(repo.ErrCheckoutItemsAlreadyProcessed, errors.New("detail")),
	)
	if status != http.StatusConflict || code != "checkout_already_processed" {
		t.Fatalf("respons salah: status=%d code=%q", status, code)
	}
}

func TestValidCheckoutMeasurementRange(t *testing.T) {
	for _, value := range []string{"standarLab", "permintaanPelanggan", "lainLain"} {
		if !validCheckoutMeasurementRange(value) {
			t.Fatalf("rentang ukur valid ditolak: %q", value)
		}
	}
	if validCheckoutMeasurementRange("bebas") {
		t.Fatal("rentang ukur di luar pilihan harus ditolak")
	}
}

func TestCheckoutMeasurementRangeNeedsNote(t *testing.T) {
	for _, value := range []string{"permintaanPelanggan", "lainLain"} {
		if !checkoutMeasurementRangeNeedsNote(value) {
			t.Fatalf("rentang ukur %q seharusnya memerlukan keterangan", value)
		}
	}
	if checkoutMeasurementRangeNeedsNote("standarLab") {
		t.Fatal("standar lab tidak memerlukan keterangan tambahan")
	}
}
