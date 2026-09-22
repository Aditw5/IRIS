package handlers

import (
	"errors"
	"net/http"
	"strconv"
	"strings"

	"github.com/gin-gonic/gin"
	"github.com/ulab/ulab-go-api/internal/repo"
	"gorm.io/gorm"
)

type AlatHandler struct {
	repo *repo.AlatRepo
}

func NewAlatHandler(r *repo.AlatRepo) *AlatHandler { return &AlatHandler{repo: r} }

// GET /api/alat?search=fluke&page=1&limit=10
func (h *AlatHandler) GetAlatCustomer(c *gin.Context) {
	mitraID, ok := alatTokenMitraID(c)
	if !ok || mitraID <= 0 {
		c.JSON(http.StatusUnauthorized, gin.H{
			"status":  401,
			"message": "mitrafk user tidak valid",
		})
		return
	}

	// Jika frontend masih kirim mitrauser, validasi agar tidak bisa beda dengan token
	queryMitra := strings.TrimSpace(c.Query("mitrauser"))
	if queryMitra != "" {
		queryMitraID, err := strconv.ParseInt(queryMitra, 10, 64)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{
				"status":  400,
				"message": "mitrauser query tidak valid",
			})
			return
		}

		if queryMitraID != mitraID {
			c.JSON(http.StatusForbidden, gin.H{
				"status":  403,
				"message": "Akses ditolak. Anda tidak dapat melihat alat milik unit lain.",
				"data": gin.H{
					"token_mitrafk": mitraID,
					"query_mitra":   queryMitraID,
				},
			})
			return
		}
	}

	page, _ := strconv.Atoi(c.DefaultQuery("page", "1"))
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "10"))
	search := strings.TrimSpace(c.Query("search"))

	if page <= 0 {
		page = 1
	}
	if limit <= 0 {
		limit = 10
	}
	if limit > 100 {
		limit = 100
	}

	res, err := h.repo.GetAlatCustomer(c.Request.Context(), repo.GetAlatParams{
		MitraUser: int(mitraID),
		Search:    search,
		Page:      page,
		Limit:     limit,
	})
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": err.Error(),
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status":       200,
		"message":      "OK",
		"data":         res.Items,
		"total":        res.Total,
		"per_page":     res.PerPage,
		"current_page": res.Current,
		"last_page":    res.LastPage,
		"from":         res.From,
		"to":           res.To,
		"search":       search,
		"mitrauser":    mitraID,
	})
}

// GET /api/customer/master-produk/:id
func (h *AlatHandler) GetMasterProdukCustomerByID(c *gin.Context) {
	mitraID, ok := alatTokenMitraID(c)
	if !ok || mitraID <= 0 {
		c.JSON(http.StatusUnauthorized, gin.H{
			"status":  401,
			"message": "mitrafk user tidak valid",
		})
		return
	}

	idStr := strings.TrimSpace(c.Param("id"))
	if idStr == "" {
		idStr = strings.TrimSpace(c.Query("id"))
	}
	if idStr == "" {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "id alat harus diisi",
		})
		return
	}

	id, err := strconv.ParseInt(idStr, 10, 64)
	if err != nil || id <= 0 {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "id alat tidak valid",
		})
		return
	}

	row, err := h.repo.GetMasterProdukCustomerByID(c.Request.Context(), int(id), int(mitraID))
	if err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			c.JSON(http.StatusNotFound, gin.H{
				"status":  404,
				"message": "Data alat tidak ditemukan",
			})
			return
		}

		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": err.Error(),
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status":  200,
		"message": "Detail alat berhasil diambil",
		"data":    row,
	})
}

// POST /api/customer/save-master-produk
func (h *AlatHandler) SaveMasterProdukCustomer(c *gin.Context) {
	mitraID, ok := alatTokenMitraID(c)
	if !ok || mitraID <= 0 {
		c.JSON(http.StatusUnauthorized, gin.H{
			"status":  401,
			"message": "mitrafk user tidak valid",
		})
		return
	}

	idStr := strings.TrimSpace(c.PostForm("id"))
	var id int64
	if idStr != "" {
		var err error
		id, err = strconv.ParseInt(idStr, 10, 64)
		if err != nil || id < 0 {
			c.JSON(http.StatusBadRequest, gin.H{
				"status":  400,
				"message": "id tidak valid",
			})
			return
		}
	}

	namaproduk := strings.TrimSpace(c.PostForm("namaproduk"))
	namamerk := strings.TrimSpace(c.PostForm("namamerk"))
	namatipe := strings.TrimSpace(c.PostForm("namatipe"))
	namaserialnumber := strings.TrimSpace(c.PostForm("namaserialnumber"))
	namaFileLama := strings.TrimSpace(c.PostForm("namaFileLama"))
	statusenabled := alatParseBoolDefaultTrue(c.PostForm("statusenabled"))

	if namaproduk == "" {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "Nama alat harus diisi",
		})
		return
	}
	if namamerk == "" {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "Merk harus diisi",
		})
		return
	}
	if namatipe == "" {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "Tipe harus diisi",
		})
		return
	}
	if namaserialnumber == "" {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "Serial Number harus diisi",
		})
		return
	}

	fileMitra, _ := c.FormFile("fileMitra")

	// Tambah baru: foto wajib
	if id == 0 && fileMitra == nil && namaFileLama == "" {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "Foto alat wajib diunggah",
		})
		return
	}

	res, err := h.repo.SaveMasterProdukCustomer(c.Request.Context(), repo.SaveMasterProdukCustomerParams{
		ID:               id,
		MitraFK:          mitraID,
		NamaProduk:       namaproduk,
		NamaMerk:         namamerk,
		NamaTipe:         namatipe,
		NamaSerialNumber: namaserialnumber,
		StatusEnabled:    statusenabled,
		NamaFileLama:     namaFileLama,
		FileMitra:        fileMitra,
	})
	if err != nil {
		msg := err.Error()
		statusCode := http.StatusBadRequest

		if errors.Is(err, gorm.ErrRecordNotFound) {
			statusCode = http.StatusNotFound
		} else if errors.Is(err, repo.ErrDuplicateAlatSerialNumber) ||
			strings.Contains(strings.ToLower(msg), "sudah terdaftar") {
			statusCode = http.StatusConflict
		}

		c.JSON(statusCode, gin.H{
			"status":  statusCode,
			"message": msg,
		})
		return
	}

	msg := "Master alat berhasil disimpan"
	if id > 0 {
		msg = "Master alat berhasil diperbarui"
	}

	c.JSON(http.StatusOK, gin.H{
		"status":  200,
		"message": msg,
		"data":    res,
	})
}

type deleteMasterProdukCustomerRequest struct {
	ID int64 `json:"id"`
}

// POST /api/customer/delete-master-produk
func (h *AlatHandler) DeleteMasterProdukCustomer(c *gin.Context) {
	mitraID, ok := alatTokenMitraID(c)
	if !ok || mitraID <= 0 {
		c.JSON(http.StatusUnauthorized, gin.H{
			"status":  401,
			"message": "mitrafk user tidak valid",
		})
		return
	}

	var req deleteMasterProdukCustomerRequest

	// Prioritas JSON body
	if err := c.ShouldBindJSON(&req); err != nil {
		// fallback form-data / x-www-form-urlencoded
		idStr := strings.TrimSpace(c.PostForm("id"))
		if idStr == "" {
			idStr = strings.TrimSpace(c.Query("id"))
		}

		if idStr == "" {
			c.JSON(http.StatusBadRequest, gin.H{
				"status":  400,
				"message": "id alat harus diisi",
			})
			return
		}

		id, convErr := strconv.ParseInt(idStr, 10, 64)
		if convErr != nil || id <= 0 {
			c.JSON(http.StatusBadRequest, gin.H{
				"status":  400,
				"message": "id alat tidak valid",
			})
			return
		}
		req.ID = id
	}

	if req.ID <= 0 {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "id alat tidak valid",
		})
		return
	}

	err := h.repo.DeleteMasterProdukCustomer(c.Request.Context(), int(req.ID), int(mitraID))
	if err != nil {
		if errors.Is(err, repo.ErrAlatPernahDidaftarkan) {
			c.JSON(http.StatusConflict, gin.H{
				"status":  409,
				"message": "Alat tidak dapat dihapus karena sudah pernah didaftarkan. Riwayat alat harus tetap tersimpan.",
			})
			return
		}

		if errors.Is(err, gorm.ErrRecordNotFound) {
			c.JSON(http.StatusNotFound, gin.H{
				"status":  404,
				"message": "Data alat tidak ditemukan atau bukan milik unit Anda",
			})
			return
		}

		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": err.Error(),
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status":  200,
		"message": "Master alat berhasil dihapus",
	})
}

func alatTokenMitraID(c *gin.Context) (int64, bool) {
	mitraVal, ok := c.Get("mitrafk")
	if !ok {
		return 0, false
	}
	return alatAnyToInt64(mitraVal)
}

func alatParseBoolDefaultTrue(v string) bool {
	v = strings.TrimSpace(strings.ToLower(v))
	if v == "" {
		return true
	}

	switch v {
	case "1", "true", "t", "yes", "y":
		return true
	case "0", "false", "f", "no", "n":
		return false
	default:
		return true
	}
}

func alatAnyToInt64(v any) (int64, bool) {
	switch val := v.(type) {
	case int:
		return int64(val), true
	case int8:
		return int64(val), true
	case int16:
		return int64(val), true
	case int32:
		return int64(val), true
	case int64:
		return val, true
	case uint:
		return int64(val), true
	case uint8:
		return int64(val), true
	case uint16:
		return int64(val), true
	case uint32:
		return int64(val), true
	case uint64:
		return int64(val), true
	case float32:
		return int64(val), true
	case float64:
		return int64(val), true
	case string:
		n, err := strconv.ParseInt(strings.TrimSpace(val), 10, 64)
		if err != nil {
			return 0, false
		}
		return n, true
	default:
		return 0, false
	}
}
