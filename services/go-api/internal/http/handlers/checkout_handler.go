package handlers

import (
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"log"
	"mime/multipart"
	"net/http"
	"net/url"
	"path/filepath"
	"strconv"
	"strings"
	"time"
	"unicode/utf8"

	"github.com/gin-gonic/gin"
	"github.com/ulab/ulab-go-api/internal/push"
	"github.com/ulab/ulab-go-api/internal/repo"
)

const maxCheckoutUploadSize = 10_000_000

type CheckoutHandler struct {
	repo     *repo.CheckoutRepo
	WAToken  string
	WASecret string
	notifier push.AdminNotifier
}

func NewCheckoutHandler(
	r *repo.CheckoutRepo,
	waToken,
	waSecret string,
	notifier push.AdminNotifier,
) *CheckoutHandler {
	return &CheckoutHandler{
		repo:     r,
		WAToken:  waToken,
		WASecret: waSecret,
		notifier: notifier,
	}
}

func (h *CheckoutHandler) ListLokasiKalibrasi(c *gin.Context) {
	query := c.Query("query")
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))

	rows, err := h.repo.ListLokasiKalibrasi(c.Request.Context(), query, limit)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": err.Error(),
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status": 200,
		"data":   rows,
	})
}

func (h *CheckoutHandler) ListPaketKalibrasi(c *gin.Context) {
	query := c.Query("query")
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))

	rows, err := h.repo.ListPaketKalibrasi(c.Request.Context(), query, limit)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": err.Error(),
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status": 200,
		"data":   rows,
	})
}

func (h *CheckoutHandler) SaveCheckoutCustomer(c *gin.Context) {
	uid, ok := userIDFromCtx(c)
	if !ok || uid <= 0 {
		c.JSON(http.StatusUnauthorized, gin.H{
			"status":  401,
			"message": "unauthorized",
		})
		return
	}

	lokasiRaw := strings.TrimSpace(c.PostForm("lokasi"))
	paketRaw := strings.TrimSpace(c.PostForm("paketkalibrasi"))
	rentangUkurRaw := strings.TrimSpace(c.PostForm("rentangUkur"))
	rentangKetRaw := strings.TrimSpace(c.PostForm("rentangUkurketPermintaanPelanggan"))
	mitraRegistrasiDetailRaw := c.PostForm("mitraregistrasidetail")

	log.Printf(
		"[checkout] request received | uid=%d | lokasi=%q | paket=%q | detailLen=%d",
		uid,
		lokasiRaw,
		paketRaw,
		len(strings.TrimSpace(mitraRegistrasiDetailRaw)),
	)

	profile, err := h.repo.GetCheckoutCustomerProfile(c.Request.Context(), uid)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"code":    "profile_lookup_failed",
			"message": "gagal memeriksa profil customer",
		})
		return
	}

	missingProfileFields := make([]string, 0, 4)
	if strings.TrimSpace(profile.Name) == "" {
		missingProfileFields = append(missingProfileFields, "nama")
	}
	if profile.MitraFK <= 0 {
		missingProfileFields = append(missingProfileFields, "unit/perusahaan")
	}
	phone := normalizePhone(profile.NoWA)
	if len(phone) < 10 || len(phone) > 15 {
		missingProfileFields = append(missingProfileFields, "nomor WhatsApp")
	}
	if strings.TrimSpace(profile.Jabatan) == "" {
		missingProfileFields = append(missingProfileFields, "jabatan")
	}
	if len(missingProfileFields) > 0 {
		c.JSON(http.StatusUnprocessableEntity, gin.H{
			"status":  422,
			"code":    "profile_incomplete",
			"message": "Lengkapi profil sebelum checkout: " + strings.Join(missingProfileFields, ", "),
			"fields":  missingProfileFields,
		})
		return
	}

	fileAms, err := c.FormFile("fileCustomer")
	if err != nil {
		if !errors.Is(err, http.ErrMissingFile) {
			c.JSON(http.StatusBadRequest, gin.H{
				"status":  400,
				"code":    "multipart_invalid",
				"message": "Data checkout atau file unggahan tidak dapat dibaca",
			})
			return
		}
		if !profile.IsExternal {
			c.JSON(http.StatusBadRequest, gin.H{
				"status":  400,
				"code":    "ams_required",
				"message": "Dokumen AMS wajib diunggah",
			})
			return
		}
		fileAms = nil
	}

	if fileAms != nil {
		extAms := strings.ToLower(filepath.Ext(fileAms.Filename))
		if extAms != ".pdf" {
			c.JSON(http.StatusBadRequest, gin.H{
				"status":  400,
				"code":    "ams_type_invalid",
				"message": "File AMS harus berformat PDF",
			})
			return
		}
		if fileAms.Size <= 0 || fileAms.Size > maxCheckoutUploadSize {
			c.JSON(http.StatusBadRequest, gin.H{
				"status":  400,
				"code":    "ams_size_invalid",
				"message": "Ukuran file AMS harus lebih dari 0 dan maksimal 10 MB",
			})
			return
		}
	}

	var fileTools *multipart.FileHeader
	ft, errTools := c.FormFile("fileCustomerTools")
	if errTools != nil && !errors.Is(errTools, http.ErrMissingFile) {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"code":    "multipart_invalid",
			"message": "Data checkout atau file unggahan tidak dapat dibaca",
		})
		return
	}
	if ft != nil {
		extTools := strings.ToLower(filepath.Ext(ft.Filename))
		if extTools != ".xls" && extTools != ".xlsx" {
			c.JSON(http.StatusBadRequest, gin.H{
				"status":  400,
				"code":    "tools_type_invalid",
				"message": "File List Tools harus berformat Excel (.xls/.xlsx)",
			})
			return
		}
		if ft.Size <= 0 || ft.Size > maxCheckoutUploadSize {
			c.JSON(http.StatusBadRequest, gin.H{
				"status":  400,
				"code":    "tools_size_invalid",
				"message": "Ukuran file List Tools harus lebih dari 0 dan maksimal 10 MB",
			})
			return
		}
		fileTools = ft
	}

	lokasi, errLokasi := strconv.Atoi(lokasiRaw)
	if errLokasi != nil || lokasi <= 0 {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"code":    "location_required",
			"message": "lokasi wajib dipilih",
		})
		return
	}

	if strings.TrimSpace(mitraRegistrasiDetailRaw) == "" {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "mitraregistrasidetail wajib diisi",
		})
		return
	}

	catatanRaw := strings.TrimSpace(c.PostForm("catatan"))
	if utf8.RuneCountInString(catatanRaw) > 1000 {
		c.JSON(http.StatusUnprocessableEntity, gin.H{
			"status":  422,
			"code":    "notes_too_long",
			"message": "Catatan maksimal 1000 karakter",
		})
		return
	}
	if utf8.RuneCountInString(rentangKetRaw) > 2000 {
		c.JSON(http.StatusUnprocessableEntity, gin.H{
			"status":  422,
			"code":    "measurement_range_note_too_long",
			"message": "Keterangan rentang ukur maksimal 2000 karakter",
		})
		return
	}

	var items []repo.CheckoutItem
	if err := json.Unmarshal([]byte(mitraRegistrasiDetailRaw), &items); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"code":    "checkout_items_format_invalid",
			"message": "format mitraregistrasidetail tidak valid",
		})
		return
	}

	if len(items) == 0 {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "item checkout kosong",
		})
		return
	}

	hasCalibration, itemValidationErr := validateCheckoutItems(items)
	if itemValidationErr != nil {
		c.JSON(http.StatusUnprocessableEntity, gin.H{
			"status":  422,
			"code":    "checkout_items_invalid",
			"message": itemValidationErr.Error(),
		})
		return
	}

	paketKalibrasi := 0
	if hasCalibration {
		var errPaket error
		paketKalibrasi, errPaket = strconv.Atoi(paketRaw)
		if errPaket != nil || paketKalibrasi <= 0 {
			c.JSON(http.StatusUnprocessableEntity, gin.H{
				"status":  422,
				"code":    "package_required",
				"message": "Paket kalibrasi wajib dipilih",
			})
			return
		}

		if !validCheckoutMeasurementRange(rentangUkurRaw) {
			c.JSON(http.StatusUnprocessableEntity, gin.H{
				"status":  422,
				"code":    "measurement_range_required",
				"message": "Rentang ukur wajib dipilih",
			})
			return
		}
		if checkoutMeasurementRangeNeedsNote(rentangUkurRaw) && rentangKetRaw == "" {
			c.JSON(http.StatusUnprocessableEntity, gin.H{
				"status":  422,
				"code":    "measurement_range_note_required",
				"message": "Keterangan rentang ukur wajib diisi",
			})
			return
		}
	} else {
		rentangUkurRaw = ""
		rentangKetRaw = ""
	}

	req := repo.SaveCheckoutRequest{
		UserID:      uid,
		NoMitraFK:   strconv.Itoa(profile.MitraFK),
		NamaPJ:      strings.TrimSpace(profile.Name),
		NoHPPJ:      strings.TrimSpace(profile.NoWA),
		JabatanPJ:   strings.TrimSpace(profile.Jabatan),
		Catatan:     catatanRaw,
		PaketID:     paketKalibrasi,
		LokasiID:    lokasi,
		RentangUkur: rentangUkurRaw,
		RentangKet:  rentangKetRaw,
		FileAMS:     fileAms,
		FileTools:   fileTools,
		Items:       items,
	}

	result, err := h.repo.SaveCheckoutCustomer(c.Request.Context(), req)
	if err != nil {
		log.Printf("[checkout] save failed: %v", err)
		status, code, message := checkoutSaveErrorResponse(err)
		c.JSON(status, gin.H{
			"status":  status,
			"code":    code,
			"message": message,
		})
		return
	}

	log.Printf("[checkout] saved | customer=%s | lokasi=%d | jumlahAlat=%d",
		result.NamaCustomer, result.LokasiID, result.JumlahAlat)

	c.JSON(http.StatusOK, gin.H{
		"status":  200,
		"message": "Simpan Registrasi Sukses",
		"data":    result,
	})

	// WhatsApp dan FCM adalah efek samping. Jangan menahan respons checkout
	// setelah transaksi database sudah berhasil commit.
	go h.sendCheckoutNotifications(result)
}

func (h *CheckoutHandler) sendCheckoutNotifications(result repo.SaveCheckoutResult) {
	defer func() {
		if recovered := recover(); recovered != nil {
			log.Printf("[checkout-notification] panic recovered: %v", recovered)
		}
	}()

	// WA ke customer
	if strings.TrimSpace(result.NoHPCustomer) != "" {
		msgCustomer := fmt.Sprintf(
			"Yth. %s,\n\nPendaftaran alat kalibrasi/repair Anda telah berhasil diterima.\nTanggal/Waktu Pendaftaran: *%s WIB*\nTotal alat yang didaftarkan: *%d* buah.\n\nRincian alat yang didaftarkan:\n%s\n\nSalam,\nU-LAB ! Cepat, Tepat, Akurat 💯",
			result.NamaCustomer,
			result.WaktuOrder,
			result.JumlahAlat,
			result.DetailAlatText(),
		)
		if err := h.sendWhatsAppCheckout(result.NoHPCustomer, msgCustomer); err != nil {
			log.Printf("[checkout] WA customer gagal: %v", err)
		} else {
			log.Printf("[checkout] WA customer sukses")
		}
	} else {
		log.Printf("[checkout] WA customer skip | nohp customer kosong")
	}

	// WA ke pegawai admin lokasi
	adminCtx, cancelAdmin := context.WithTimeout(context.Background(), 10*time.Second)
	admins, errAdmin := h.repo.ListAdminByLokasi(adminCtx, result.LokasiID)
	cancelAdmin()
	if errAdmin != nil {
		log.Printf("[checkout] query admin gagal | lokasi=%d | err=%v", result.LokasiID, errAdmin)
	} else {
		log.Printf("[checkout] admin ditemukan | lokasi=%d | total=%d", result.LokasiID, len(admins))
	}

	if errAdmin == nil && len(admins) > 0 {
		for _, admin := range admins {
			if admin.NoHandphone == nil || strings.TrimSpace(*admin.NoHandphone) == "" {
				log.Printf("[checkout] admin tanpa nohp | id=%v | nama=%s", admin.ID, admin.NamaLengkap)
				continue
			}

			msgAdmin := fmt.Sprintf(
				"Halo Tim Hebat U-LAB ! 👋\n\nYth. %s,\n\nPemberitahuan: Telah masuk pesanan registrasi alat dari customer atas nama *%s* pada *%s WIB*.\nTotal alat yang didaftarkan: *%d* buah.\n\nRincian alat yang didaftarkan:\n%s\n\nMohon untuk segera melakukan verifikasi dan proses lebih lanjut di aplikasi.\n\nSalam,\nU-LAB ! Cepat, Tepat, Akurat 💯",
				admin.NamaLengkap,
				result.NamaCustomer,
				result.WaktuOrder,
				result.JumlahAlat,
				result.DetailAlatText(),
			)

			if err := h.sendWhatsAppCheckout(*admin.NoHandphone, msgAdmin); err != nil {
				log.Printf("[checkout] WA admin gagal | id=%v | err=%v", admin.ID, err)
			} else {
				log.Printf("[checkout] WA admin sukses | id=%v", admin.ID)
			}
		}
	} else if errAdmin == nil && len(admins) == 0 {
		log.Printf("[checkout] WA admin skip | tidak ada admin ditemukan untuk lokasi=%d", result.LokasiID)
	}

	if h.notifier != nil {
		notifyCtx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
		errNotify := h.notifier.NotifyNewRegistration(notifyCtx, push.NewRegistration{
			RegistrationIDs: result.RegistrasiNorec,
			CustomerName:    result.NamaCustomer,
			ToolCount:       result.JumlahAlat,
			LocationID:      result.LokasiID,
			OrderTime:       result.WaktuOrder,
		})
		cancel()
		if errNotify != nil {
			log.Printf("[fcm-admin] pengiriman notifikasi registrasi gagal: %v", errNotify)
		} else {
			log.Printf("[fcm-admin] notifikasi registrasi berhasil diproses")
		}
	}

}

func validateCheckoutItems(items []repo.CheckoutItem) (bool, error) {
	seenNorecs := make(map[string]struct{}, len(items))
	seenToolIDs := make(map[int]struct{}, len(items))
	hasCalibration := false

	for index := range items {
		items[index].Norec = strings.TrimSpace(items[index].Norec)
		items[index].JenisOrder = strings.ToLower(strings.TrimSpace(items[index].JenisOrder))

		if items[index].Norec == "" {
			return false, fmt.Errorf("Identitas item ke-%d tidak valid", index+1)
		}
		if _, exists := seenNorecs[items[index].Norec]; exists {
			return false, fmt.Errorf("Item ke-%d tercantum lebih dari satu kali", index+1)
		}
		seenNorecs[items[index].Norec] = struct{}{}

		if items[index].ID <= 0 {
			return false, fmt.Errorf("Master alat ke-%d tidak valid", index+1)
		}
		if _, exists := seenToolIDs[items[index].ID]; exists {
			return false, fmt.Errorf("Alat ke-%d tercantum lebih dari satu kali", index+1)
		}
		seenToolIDs[items[index].ID] = struct{}{}

		switch items[index].JenisOrder {
		case "kalibrasi":
			hasCalibration = true
		case "repair":
		default:
			return false, fmt.Errorf("Jenis order item ke-%d harus kalibrasi atau repair", index+1)
		}
	}

	return hasCalibration, nil
}

func validCheckoutMeasurementRange(value string) bool {
	switch strings.TrimSpace(value) {
	case "standarLab", "permintaanPelanggan", "lainLain":
		return true
	default:
		return false
	}
}

func checkoutMeasurementRangeNeedsNote(value string) bool {
	value = strings.TrimSpace(value)
	return value == "permintaanPelanggan" || value == "lainLain"
}

func checkoutSaveErrorResponse(err error) (int, string, string) {
	switch {
	case errors.Is(err, repo.ErrCheckoutItemsAlreadyProcessed):
		return http.StatusConflict, "checkout_already_processed", "Item ini sudah diproses. Muat ulang keranjang untuk melihat status terbaru."
	case errors.Is(err, repo.ErrCheckoutItemsInvalid):
		return http.StatusUnprocessableEntity, "checkout_items_invalid", "Item checkout berubah atau tidak lagi valid. Muat ulang keranjang lalu coba kembali."
	case errors.Is(err, repo.ErrCheckoutLocationInvalid):
		return http.StatusUnprocessableEntity, "location_invalid", "Lokasi yang dipilih sudah tidak tersedia. Pilih lokasi kembali."
	case errors.Is(err, repo.ErrCheckoutPackageInvalid):
		return http.StatusUnprocessableEntity, "package_invalid", "Paket kalibrasi yang dipilih sudah tidak tersedia. Pilih paket kembali."
	default:
		return http.StatusInternalServerError, "checkout_save_failed", "Checkout belum dapat disimpan. Silakan coba kembali."
	}
}

func (h *CheckoutHandler) sendWhatsAppCheckout(noWa, msg string) error {
	noWa = normalizePhone(noWa)
	if noWa == "" {
		return fmt.Errorf("invalid whatsapp number")
	}

	if strings.TrimSpace(h.WAToken) == "" || strings.TrimSpace(h.WASecret) == "" {
		return fmt.Errorf("whatsapp gateway config is missing")
	}

	fullToken := fmt.Sprintf("%s.%s", h.WAToken, h.WASecret)
	endpoint := fmt.Sprintf(
		"https://sby.wablas.com/api/send-message?token=%s&phone=%s&message=%s",
		url.QueryEscape(fullToken),
		url.QueryEscape(noWa),
		url.QueryEscape(msg),
	)

	client := &http.Client{Timeout: 10 * time.Second}
	resp, err := client.Get(endpoint)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	var result map[string]any
	if err := json.NewDecoder(resp.Body).Decode(&result); err != nil {
		return fmt.Errorf("failed to decode wa response: %w", err)
	}

	statusVal, ok := result["status"]
	if !ok {
		return fmt.Errorf("invalid wa response: %v", result)
	}

	success := false
	switch v := statusVal.(type) {
	case bool:
		success = v
	case string:
		success = strings.ToLower(v) == "true" || strings.ToLower(v) == "success"
	case float64:
		success = v == 1
	}

	if !success {
		msgResp := "unknown error"
		if m, ok := result["message"].(string); ok && m != "" {
			msgResp = m
		}
		return fmt.Errorf("failed to send whatsapp checkout: %s", msgResp)
	}

	return nil
}
