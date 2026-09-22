package handlers

import (
	"net/http"
	"strconv"

	"github.com/gin-gonic/gin"
	"github.com/ulab/ulab-go-api/internal/repo"
)

type MitraHandler struct {
	repo *repo.MitraRepo
}

func NewMitraHandler(r *repo.MitraRepo) *MitraHandler { return &MitraHandler{repo: r} }

// GET /api/mitra?search=&page=&limit=
// Untuk internal / semua mitra aktif
func (h *MitraHandler) List(c *gin.Context) {
	search := c.Query("search")
	page, _ := strconv.Atoi(c.DefaultQuery("page", "1"))
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))

	items, err := h.repo.ListMitra(c.Request.Context(), search, page, limit)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": err.Error(),
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"current_page": page,
		"data":         items,
	})
}

// GET /api/mitra-eksternal?search=&page=&limit=
// Untuk daftar institusi eksternal
func (h *MitraHandler) ListEksternal(c *gin.Context) {
	search := c.Query("search")
	page, _ := strconv.Atoi(c.DefaultQuery("page", "1"))
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))

	items, err := h.repo.ListMitraEksternal(c.Request.Context(), search, page, limit)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": err.Error(),
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"current_page": page,
		"data":         items,
	})
}
