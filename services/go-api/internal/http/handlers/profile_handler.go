package handlers

import (
	"net/http"
	"strconv"
	"strings"

	"github.com/gin-gonic/gin"
	"github.com/ulab/ulab-go-api/internal/repo"
)

type ProfileHandler struct {
	repo *repo.UserProfileRepo
}

func NewProfileHandler(r *repo.UserProfileRepo) *ProfileHandler {
	return &ProfileHandler{repo: r}
}

type setupProfileReq struct {
	Kategori  string  `json:"kategori" binding:"required"`
	MitraFK   *int    `json:"mitrafk"`
	Institusi *string `json:"institusi"`
	Jabatan   string  `json:"jabatan" binding:"required"`
}

func userIDFromCtx(c *gin.Context) (int, bool) {
	if v, ok := c.Get("uid"); ok && v != nil {
		switch t := v.(type) {
		case uint:
			return int(t), true
		case int:
			return t, true
		case float64:
			return int(t), true
		case string:
			if id, err := strconv.Atoi(t); err == nil {
				return id, true
			}
		}
	}
	if v, ok := c.Get("user_id"); ok && v != nil {
		switch t := v.(type) {
		case int:
			return t, true
		case float64:
			return int(t), true
		case string:
			if id, err := strconv.Atoi(t); err == nil {
				return id, true
			}
		}
	}
	return 0, false
}

// PATCH /api/profile/setup
func (h *ProfileHandler) SetupNewUserProfile(c *gin.Context) {
	var req setupProfileReq
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "invalid request",
			"error":   err.Error(),
		})
		return
	}

	uid, ok := userIDFromCtx(c)
	if !ok || uid <= 0 {
		c.JSON(http.StatusUnauthorized, gin.H{
			"status":  401,
			"message": "unauthorized: user id missing in token",
		})
		return
	}

	kategori := strings.ToUpper(strings.TrimSpace(req.Kategori))
	jabatan := strings.ToUpper(strings.TrimSpace(req.Jabatan))

	if kategori == "" {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "Kategori belum dipilih",
		})
		return
	}
	if jabatan == "" {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "Jabatan harus di isi",
		})
		return
	}

	switch kategori {
	case "INTERNAL":
		if req.MitraFK == nil || *req.MitraFK <= 0 {
			c.JSON(http.StatusBadRequest, gin.H{
				"status":  400,
				"message": "Unit harus di isi",
			})
			return
		}

		rows, err := h.repo.SetupCustomerProfile(
			c.Request.Context(),
			uid,
			kategori,
			req.MitraFK,
			nil,
			jabatan,
		)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{
				"status":  500,
				"message": err.Error(),
			})
			return
		}
		if rows == 0 {
			c.JSON(http.StatusConflict, gin.H{
				"status":  409,
				"message": "profile gagal diperbarui atau user tidak ditemukan",
			})
			return
		}

		c.JSON(http.StatusOK, gin.H{
			"status":  200,
			"message": "Simpan Status Customer Sukses",
			"result": gin.H{
				"mitrafk":  *req.MitraFK,
				"kategori": kategori,
			},
		})
		return

	case "EKSTERNAL":
		hasMitra := req.MitraFK != nil && *req.MitraFK > 0
		hasInstitusi := req.Institusi != nil && strings.TrimSpace(*req.Institusi) != ""

		if !hasMitra && !hasInstitusi {
			c.JSON(http.StatusBadRequest, gin.H{
				"status":  400,
				"message": "Institusi harus di isi (pilih dari daftar atau isi manual)",
			})
			return
		}

		var institusi *string
		if hasInstitusi {
			s := strings.TrimSpace(*req.Institusi)
			institusi = &s
		}

		rows, mitraID, err := h.repo.SetupExternalCustomerProfile(
			c.Request.Context(),
			uid,
			kategori,
			req.MitraFK,
			institusi,
			jabatan,
		)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{
				"status":  500,
				"message": err.Error(),
			})
			return
		}
		if rows == 0 {
			c.JSON(http.StatusConflict, gin.H{
				"status":  409,
				"message": "profile gagal diperbarui atau user tidak ditemukan",
			})
			return
		}

		c.JSON(http.StatusOK, gin.H{
			"status":  200,
			"message": "Simpan Status Customer Sukses",
			"result": gin.H{
				"mitrafk":  mitraID,
				"kategori": kategori,
			},
		})
		return

	default:
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "Kategori tidak valid",
		})
		return
	}
}
