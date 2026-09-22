package handlers

import (
	"database/sql"
	"net/http"
	"strconv"
	"strings"

	"github.com/gin-gonic/gin"
	"github.com/ulab/ulab-go-api/internal/repo"
)

type MobileProfileHandler struct {
	repo      *repo.MobileProfileRepo
	adminRepo *repo.AdminUserRepo
}

func NewMobileProfileHandler(r *repo.MobileProfileRepo, adminRepo *repo.AdminUserRepo) *MobileProfileHandler {
	return &MobileProfileHandler{repo: r, adminRepo: adminRepo}
}

type mobileUpdateProfileReq struct {
	Name    string `json:"name" binding:"required"`
	Email   string `json:"email" binding:"required"`
	NoWA    string `json:"nowa"`
	Jabatan string `json:"jabatan"`
}

func mobileUserIDFromCtx(c *gin.Context) (int64, bool) {
	if v, ok := c.Get("uid"); ok && v != nil {
		switch t := v.(type) {
		case uint:
			return int64(t), true
		case int:
			return int64(t), true
		case int64:
			return t, true
		case float64:
			return int64(t), true
		case string:
			if id, err := strconv.ParseInt(t, 10, 64); err == nil {
				return id, true
			}
		}
	}
	if v, ok := c.Get("user_id"); ok && v != nil {
		switch t := v.(type) {
		case int:
			return int64(t), true
		case int64:
			return t, true
		case float64:
			return int64(t), true
		case string:
			if id, err := strconv.ParseInt(t, 10, 64); err == nil {
				return id, true
			}
		}
	}
	return 0, false
}

// GET /api/mobile-profile/detail
func (h *MobileProfileHandler) GetDetail(c *gin.Context) {
	uid, ok := mobileUserIDFromCtx(c)
	if !ok || uid <= 0 {
		c.JSON(http.StatusUnauthorized, gin.H{
			"status":  401,
			"message": "unauthorized: user id missing in token",
		})
		return
	}

	data, err := h.repo.GetDetail(c.Request.Context(), uid)
	if err != nil {
		if err == sql.ErrNoRows {
			c.JSON(http.StatusNotFound, gin.H{
				"status":  404,
				"message": "profil user tidak ditemukan",
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
		"message": "OK",
		"data":    data,
	})
}

// PATCH /api/mobile-profile/update
func (h *MobileProfileHandler) UpdateDetail(c *gin.Context) {
	var req mobileUpdateProfileReq
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "invalid request",
			"error":   err.Error(),
		})
		return
	}

	uid, ok := mobileUserIDFromCtx(c)
	if !ok || uid <= 0 {
		c.JSON(http.StatusUnauthorized, gin.H{
			"status":  401,
			"message": "unauthorized: user id missing in token",
		})
		return
	}

	req.Name = strings.TrimSpace(req.Name)
	req.Email = strings.TrimSpace(req.Email)
	req.NoWA = strings.TrimSpace(req.NoWA)
	req.Jabatan = strings.TrimSpace(req.Jabatan)

	if req.Name == "" {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "Nama harus diisi",
		})
		return
	}

	if req.Email == "" {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "Email harus diisi",
		})
		return
	}

	data, err := h.repo.UpdateDetail(c.Request.Context(), uid, repo.UpdateMobileProfilePayload{
		Name:    req.Name,
		Email:   req.Email,
		NoWA:    req.NoWA,
		Jabatan: req.Jabatan,
	})
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": err.Error(),
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status":  200,
		"message": "Update profil berhasil",
		"data":    data,
	})
}

// POST /api/mobile-profile/upload-photo
func (h *MobileProfileHandler) UploadPhoto(c *gin.Context) {
	uid, ok := mobileUserIDFromCtx(c)
	if !ok || uid <= 0 {
		c.JSON(http.StatusUnauthorized, gin.H{
			"status":  401,
			"message": "unauthorized: user id missing in token",
		})
		return
	}

	file, err := c.FormFile("file")
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "file foto wajib diupload",
		})
		return
	}

	if accountTypeFromContext(c) == "admin" {
		if h.adminRepo == nil {
			c.JSON(http.StatusInternalServerError, gin.H{
				"status":  500,
				"message": "repo admin tidak tersedia",
			})
			return
		}

		data, err := h.adminRepo.UploadPhoto(c.Request.Context(), uint(uid), file)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{
				"status":  400,
				"message": err.Error(),
			})
			return
		}

		c.JSON(http.StatusOK, gin.H{
			"status":  200,
			"message": "Upload foto berhasil",
			"data":    data,
		})
		return
	}

	data, err := h.repo.UploadPhoto(c.Request.Context(), uid, file)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": err.Error(),
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status":  200,
		"message": "Upload foto berhasil",
		"data":    data,
	})
}
