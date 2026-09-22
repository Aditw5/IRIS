package handlers

import (
	"errors"
	"fmt"
	"mime/multipart"
	"net/http"
	"strconv"
	"strings"

	"github.com/gin-gonic/gin"
	"github.com/golang-jwt/jwt/v5"
	"github.com/ulab/ulab-go-api/internal/repo"
	"gorm.io/gorm"
)

type AdminRegistrationHandler struct {
	repo *repo.AdminRegistrationRepo
}

func NewAdminRegistrationHandler(r *repo.AdminRegistrationRepo) *AdminRegistrationHandler {
	return &AdminRegistrationHandler{repo: r}
}

func (h *AdminRegistrationHandler) DashboardStats(c *gin.Context) {
	if !requireRegistrationAdmin(c) {
		return
	}

	data, err := h.repo.DashboardStats(c.Request.Context(), adminFilterFromQuery(c))
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"status": 500, "message": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"status": 200, "data": data})
}

func (h *AdminRegistrationHandler) ListUnits(c *gin.Context) {
	if !requireRegistrationAdmin(c) {
		return
	}

	filter := adminFilterFromQuery(c)
	rows, total, err := h.repo.ListUnits(c.Request.Context(), filter)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"status": 500, "message": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status":       200,
		"data":         rows,
		"total":        total,
		"per_page":     filter.Limit,
		"current_page": filter.Page,
		"last_page":    lastPage(total, filter.Limit),
	})
}

func (h *AdminRegistrationHandler) ListTools(c *gin.Context) {
	if !requireRegistrationAdmin(c) {
		return
	}

	filter := adminFilterFromQuery(c)
	rows, total, err := h.repo.ListTools(c.Request.Context(), filter)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"status": 500, "message": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status":       200,
		"data":         rows,
		"total":        total,
		"per_page":     filter.Limit,
		"current_page": filter.Page,
		"last_page":    lastPage(total, filter.Limit),
	})
}

func (h *AdminRegistrationHandler) ListLokasi(c *gin.Context) {
	if !requireRegistrationAdmin(c) {
		return
	}

	rows, err := h.repo.ListLokasi(c.Request.Context())
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"status": 500, "message": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"status": 200, "data": rows})
}

func (h *AdminRegistrationHandler) SearchUnits(c *gin.Context) {
	if !requireRegistrationAdmin(c) {
		return
	}

	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))
	rows, err := h.repo.SearchUnits(c.Request.Context(), strings.TrimSpace(c.Query("search")), limit)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"status": 500, "message": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"status": 200, "data": rows})
}

func (h *AdminRegistrationHandler) SearchLabUnits(c *gin.Context) {
	if !requireRegistrationAdmin(c) {
		return
	}

	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "10"))
	rows, err := h.repo.SearchLabUnits(c.Request.Context(), strings.TrimSpace(c.Query("search")), limit)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"status": 500, "message": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"status": 200, "data": rows})
}

func (h *AdminRegistrationHandler) SearchEmployees(c *gin.Context) {
	if !requireRegistrationAdmin(c) {
		return
	}

	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))
	jenisPegawai, _ := strconv.Atoi(c.DefaultQuery("jenispegawai", "0"))
	rows, err := h.repo.SearchEmployees(
		c.Request.Context(),
		strings.TrimSpace(c.Query("lokasi")),
		jenisPegawai,
		strings.TrimSpace(c.Query("search")),
		limit,
	)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"status": 500, "message": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"status": 200, "data": rows})
}

func (h *AdminRegistrationHandler) SearchUnitTools(c *gin.Context) {
	if !requireRegistrationAdmin(c) {
		return
	}

	page, _ := strconv.Atoi(c.DefaultQuery("page", "1"))
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))
	rows, total, err := h.repo.SearchUnitTools(
		c.Request.Context(),
		strings.TrimSpace(c.Query("unit_id")),
		strings.TrimSpace(c.Query("search")),
		page,
		limit,
	)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"status": 400, "message": err.Error()})
		return
	}

	if page <= 0 {
		page = 1
	}
	if limit <= 0 {
		limit = 20
	}
	lastPage := int64(1)
	if total > 0 {
		lastPage = (total + int64(limit) - 1) / int64(limit)
	}

	c.JSON(http.StatusOK, gin.H{
		"status":       200,
		"data":         rows,
		"total":        total,
		"per_page":     limit,
		"current_page": page,
		"last_page":    lastPage,
	})
}

func (h *AdminRegistrationHandler) SearchStandardTools(c *gin.Context) {
	if !requireRegistrationAdmin(c) {
		return
	}

	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))
	rows, err := h.repo.SearchStandardTools(
		c.Request.Context(),
		strings.TrimSpace(c.Query("unit_id")),
		strings.TrimSpace(c.Query("search")),
		limit,
	)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"status": 400, "message": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"status": 200, "data": rows})
}

func (h *AdminRegistrationHandler) ListLingkupKalibrasi(c *gin.Context) {
	if !requireRegistrationAdmin(c) {
		return
	}

	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))
	rows, err := h.repo.ListLingkupKalibrasi(
		c.Request.Context(),
		strings.TrimSpace(c.Query("search")),
		limit,
	)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"status": 500, "message": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"status": 200, "data": rows})
}

func (h *AdminRegistrationHandler) ListPaketKalibrasi(c *gin.Context) {
	if !requireRegistrationAdmin(c) {
		return
	}

	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))
	rows, err := h.repo.ListPaketKalibrasi(
		c.Request.Context(),
		strings.TrimSpace(c.Query("search")),
		limit,
	)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"status": 500, "message": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"status": 200, "data": rows})
}

func (h *AdminRegistrationHandler) ListStatusSurkes(c *gin.Context) {
	if !requireRegistrationAdmin(c) {
		return
	}

	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))
	rows, err := h.repo.ListStatusSurkes(
		c.Request.Context(),
		strings.TrimSpace(c.Query("search")),
		limit,
	)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"status": 500, "message": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"status": 200, "data": rows})
}

func (h *AdminRegistrationHandler) ListVendors(c *gin.Context) {
	if !requireRegistrationAdmin(c) {
		return
	}

	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))
	rows, err := h.repo.ListVendors(
		c.Request.Context(),
		strings.TrimSpace(c.Query("search")),
		limit,
	)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"status": 500, "message": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"status": 200, "data": rows})
}

func (h *AdminRegistrationHandler) KajiDetails(c *gin.Context) {
	if !requireRegistrationAdmin(c) {
		return
	}

	data, err := h.repo.KajiDetails(
		c.Request.Context(),
		strings.TrimSpace(c.Query("norec")),
		strings.TrimSpace(c.Query("norec_detail")),
	)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"status": 400, "message": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"status": 200, "data": data})
}

func (h *AdminRegistrationHandler) SaveTool(c *gin.Context) {
	if !requireRegistrationAdmin(c) {
		return
	}

	var req repo.AdminSaveToolRequest
	contentType := strings.ToLower(c.GetHeader("Content-Type"))
	if strings.Contains(contentType, "multipart/form-data") || strings.Contains(contentType, "application/x-www-form-urlencoded") {
		idStr := strings.TrimSpace(c.PostForm("id"))
		if idStr != "" {
			id, err := strconv.ParseInt(idStr, 10, 64)
			if err != nil || id < 0 {
				c.JSON(http.StatusBadRequest, gin.H{"status": 400, "message": "id alat tidak valid"})
				return
			}
			req.ID = id
		}
		req.UnitID = strings.TrimSpace(c.PostForm("unit_id"))
		req.NamaProduk = strings.TrimSpace(c.PostForm("namaproduk"))
		req.NamaMerk = strings.TrimSpace(c.PostForm("namamerk"))
		req.NamaTipe = strings.TrimSpace(c.PostForm("namatipe"))
		req.NamaSerialNumber = strings.TrimSpace(c.PostForm("namaserialnumber"))
		req.NamaFileLama = strings.TrimSpace(c.PostForm("namaFileLama"))
		status := alatParseBoolDefaultTrue(c.PostForm("statusenabled"))
		req.StatusEnabled = &status
		req.FileMitra, _ = c.FormFile("fileMitra")
	} else {
		if err := c.ShouldBindJSON(&req); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"status": 400, "message": "payload alat tidak valid"})
			return
		}
	}

	row, err := h.repo.SaveTool(c.Request.Context(), req)
	if err != nil {
		status := http.StatusBadRequest
		if errors.Is(err, repo.ErrDuplicateAlatSerialNumber) ||
			strings.Contains(strings.ToLower(err.Error()), "sudah terdaftar") {
			status = http.StatusConflict
		}
		c.JSON(status, gin.H{"status": status, "message": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status":  200,
		"message": "Master alat berhasil disimpan",
		"data":    row,
	})
}

type adminDeleteToolRequest struct {
	ID int64 `json:"id"`
}

func (h *AdminRegistrationHandler) DeleteTool(c *gin.Context) {
	if !requireRegistrationAdmin(c) {
		return
	}

	var req adminDeleteToolRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		idStr := strings.TrimSpace(c.PostForm("id"))
		if idStr == "" {
			idStr = strings.TrimSpace(c.Query("id"))
		}
		id, convErr := strconv.ParseInt(idStr, 10, 64)
		if convErr != nil || id <= 0 {
			c.JSON(http.StatusBadRequest, gin.H{"status": 400, "message": "id alat tidak valid"})
			return
		}
		req.ID = id
	}

	if err := h.repo.DeleteTool(c.Request.Context(), req.ID); err != nil {
		status := http.StatusBadRequest
		if errors.Is(err, gorm.ErrRecordNotFound) {
			status = http.StatusNotFound
		} else if errors.Is(err, repo.ErrAlatPernahDidaftarkan) {
			status = http.StatusConflict
		}
		c.JSON(status, gin.H{"status": status, "message": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status":  200,
		"message": "Master alat berhasil dihapus",
	})
}

func (h *AdminRegistrationHandler) SaveUnit(c *gin.Context) {
	if !requireRegistrationAdmin(c) {
		return
	}

	var req repo.AdminSaveUnitRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"status": 400, "message": "payload unit tidak valid"})
		return
	}

	row, err := h.repo.SaveUnit(c.Request.Context(), req)
	if err != nil {
		status := http.StatusBadRequest
		if strings.Contains(strings.ToLower(err.Error()), "record not found") {
			status = http.StatusNotFound
		}
		c.JSON(status, gin.H{"status": status, "message": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status":  200,
		"message": "Unit berhasil disimpan",
		"data":    row,
	})
}

func (h *AdminRegistrationHandler) SaveRegistration(c *gin.Context) {
	if !requireRegistrationAdmin(c) {
		return
	}

	var req repo.AdminSaveRegistrationRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"status": 400, "message": "payload registrasi tidak valid"})
		return
	}
	if req.PetugasKajiFK == "" {
		req.PetugasKajiFK = claimString(c, "pegawaifk")
	}

	row, err := h.repo.SaveRegistration(c.Request.Context(), req)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"status": 400, "message": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status":  200,
		"message": "Registrasi berhasil disimpan",
		"data":    row,
	})
}

func (h *AdminRegistrationHandler) SaveKajiItem(c *gin.Context) {
	if !requireRegistrationAdmin(c) {
		return
	}

	req := repo.AdminKajiItemRequest{
		NoregistrasiFK:    strings.TrimSpace(c.PostForm("noregistrasifk")),
		NorecDetail:       strings.TrimSpace(c.PostForm("norec")),
		Keterangan:        strings.TrimSpace(c.PostForm("keterangan")),
		TanggalKajian:     strings.TrimSpace(c.PostForm("tanggalKajian")),
		LokasiKalibrasi:   strings.TrimSpace(c.PostForm("lokasikalibrasi")),
		LokasiRepairFK:    strings.TrimSpace(c.PostForm("lokasirepairfk")),
		LingkupKalibrasi:  strings.TrimSpace(c.PostForm("lingkupkalibrasi")),
		PenyeliaTeknikFK:  strings.TrimSpace(c.PostForm("penyeliateknik")),
		PelaksanaFK:       strings.TrimSpace(c.PostForm("pelaksana")),
		StatusSurkes:      strings.TrimSpace(c.PostForm("statussurkes")),
		Manager:           strings.TrimSpace(c.PostForm("manager")),
		NamaAsman:         strings.TrimSpace(c.PostForm("namaasman")),
		DurasiKalibrasi:   strings.TrimSpace(c.PostForm("durasikalbrasi")),
		VendorKalibrasiFK: strings.TrimSpace(c.PostForm("vendorkalibrasi")),
		IsVendor:          parseBoolLoose(c.PostForm("is_vendor")),
		Files:             collectMultipartFiles(c, "fileMitra", "fileMitra[]"),
	}

	row, err := h.repo.SaveKajiItem(c.Request.Context(), req)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"status": 400, "message": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status":  200,
		"message": "Simpan kajian ulang alat sukses",
		"data":    row,
	})
}

type adminSaveKajiHeaderRequest struct {
	Kajian struct {
		Norec string `json:"norec"`
	} `json:"kajian"`
}

func (h *AdminRegistrationHandler) SaveKajiHeader(c *gin.Context) {
	if !requireRegistrationAdmin(c) {
		return
	}

	var req adminSaveKajiHeaderRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"status": 400, "message": "payload kaji ulang tidak valid"})
		return
	}

	if err := h.repo.SaveKajiHeader(c.Request.Context(), req.Kajian.Norec, claimString(c, "pegawaifk")); err != nil {
		status := http.StatusBadRequest
		if errors.Is(err, gorm.ErrRecordNotFound) {
			status = http.StatusNotFound
		}
		c.JSON(status, gin.H{"status": status, "message": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status":  200,
		"message": "Simpan kajian ulang sukses",
	})
}

func (h *AdminRegistrationHandler) UploadVendorCertificate(c *gin.Context) {
	if !requireRegistrationAdmin(c) {
		return
	}

	file, _ := c.FormFile("fileSertiVendor")
	filename, err := h.repo.SaveVendorCertificate(
		c.Request.Context(),
		strings.TrimSpace(c.PostForm("norec")),
		strings.TrimSpace(c.PostForm("jenisorder")),
		file,
		strings.TrimSpace(c.PostForm("namaFileLama")),
	)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"status": 400, "message": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status":   200,
		"message":  "Sertifikat vendor berhasil disimpan",
		"filename": filename,
	})
}

func (h *AdminRegistrationHandler) UploadAMS(c *gin.Context) {
	if !requireRegistrationAdmin(c) {
		return
	}

	file, _ := c.FormFile("fileAMSBaru")
	filename, err := h.repo.SaveRegistrationAMS(
		c.Request.Context(),
		strings.TrimSpace(c.PostForm("norec")),
		file,
		strings.TrimSpace(c.PostForm("namaFileLama")),
	)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"status": 400, "message": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status":   200,
		"message":  "AMS berhasil disimpan",
		"filename": filename,
	})
}

func adminFilterFromQuery(c *gin.Context) repo.AdminRegistrationFilter {
	page, _ := strconv.Atoi(c.DefaultQuery("page", "1"))
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "10"))

	return repo.AdminRegistrationFilter{
		Dari:       strings.TrimSpace(c.Query("dari")),
		Sampai:     strings.TrimSpace(c.Query("sampai")),
		Search:     strings.TrimSpace(c.Query("search")),
		LokasiFK:   strings.TrimSpace(c.Query("lokasifk")),
		UnitFK:     strings.TrimSpace(c.Query("unitfk")),
		JenisOrder: strings.TrimSpace(c.Query("jenisorder")),
		LingkupFK:  strings.TrimSpace(c.Query("ruanglingkupfk")),
		StatusAlat: strings.TrimSpace(c.Query("statusalat")),
		Page:       page,
		Limit:      limit,
	}
}

func requireRegistrationAdmin(c *gin.Context) bool {
	rawClaims, ok := c.Get("claims")
	if !ok {
		c.JSON(http.StatusUnauthorized, gin.H{"status": 401, "message": "missing claims"})
		return false
	}

	claims, ok := rawClaims.(jwt.MapClaims)
	if !ok {
		c.JSON(http.StatusUnauthorized, gin.H{"status": 401, "message": "invalid claims"})
		return false
	}

	role := strings.ToLower(strings.TrimSpace(fmt.Sprint(claims["role"])))
	accountType := strings.ToLower(strings.TrimSpace(fmt.Sprint(claims["account_type"])))
	group := strings.ToLower(strings.TrimSpace(fmt.Sprint(claims["kelompokuser"])))
	groupID := strings.TrimSpace(fmt.Sprint(claims["kelompokuser_id"]))

	if role != "admin" && accountType != "admin" {
		c.JSON(http.StatusForbidden, gin.H{"status": 403, "message": "route khusus admin"})
		return false
	}

	if group != "registrasi" && groupID != "11" && groupID != "11.0" {
		c.JSON(http.StatusForbidden, gin.H{"status": 403, "message": "route khusus admin registrasi"})
		return false
	}

	return true
}

func claimString(c *gin.Context, key string) string {
	rawClaims, ok := c.Get("claims")
	if !ok {
		return ""
	}
	claims, ok := rawClaims.(jwt.MapClaims)
	if !ok {
		return ""
	}
	value := strings.TrimSpace(fmt.Sprint(claims[key]))
	if value == "" || value == "<nil>" {
		return ""
	}
	return value
}

func collectMultipartFiles(c *gin.Context, keys ...string) []*multipart.FileHeader {
	form, err := c.MultipartForm()
	if err != nil || form == nil {
		return nil
	}

	var files []*multipart.FileHeader
	for _, key := range keys {
		if items := form.File[key]; len(items) > 0 {
			files = append(files, items...)
		}
	}
	return files
}

func parseBoolLoose(value string) bool {
	switch strings.ToLower(strings.TrimSpace(value)) {
	case "1", "true", "t", "yes", "y", "vendor":
		return true
	default:
		return false
	}
}

func lastPage(total int64, limit int) int {
	if limit <= 0 {
		limit = 10
	}
	if total <= 0 {
		return 0
	}
	return int((total + int64(limit) - 1) / int64(limit))
}
