package handlers

import (
	"context"
	"log"
	"net/http"
	"strings"

	"github.com/gin-gonic/gin"
	"github.com/ulab/ulab-go-api/internal/models"
	"github.com/ulab/ulab-go-api/internal/repo"
)

type MappingLayananHandler struct {
	repo mappingLayananRepository
}

type mappingLayananRepository interface {
	ListByScope(context.Context, repo.ListMappingLayananParams) ([]models.MappingLayananItem, error)
}

func NewMappingLayananHandler(r mappingLayananRepository) *MappingLayananHandler {
	return &MappingLayananHandler{repo: r}
}

// GET /api/service/mapping-layanan?scope=kelistrikan&kategori=KAN&search=...
// Setara dengan NoAuthCtrl::getLandingMappingLayanan di Laravel, tapi datanya
// dilayani langsung oleh Go supaya mobile app tidak lagi bergantung ke Laravel.
func (h *MappingLayananHandler) GetMappingLayanan(c *gin.Context) {
	scope := strings.ToLower(strings.TrimSpace(c.Query("scope")))
	kategori := strings.TrimSpace(c.Query("kategori"))
	search := strings.TrimSpace(c.Query("search"))

	if _, ok := repo.ScopeTitles[scope]; !ok {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  http.StatusBadRequest,
			"message": "Ruang lingkup layanan tidak valid.",
		})
		return
	}

	if kategori != "" && kategori != "KAN" && kategori != "Non KAN" {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  http.StatusBadRequest,
			"message": "Kategori layanan tidak valid.",
		})
		return
	}

	if len([]rune(search)) > 100 {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  http.StatusBadRequest,
			"message": "Kata pencarian maksimal 100 karakter.",
		})
		return
	}

	items, err := h.repo.ListByScope(c.Request.Context(), repo.ListMappingLayananParams{
		Scope:    scope,
		Kategori: kategori,
		Search:   search,
	})
	if err != nil {
		log.Printf("[mapping-layanan] query gagal scope=%q: %v", scope, err)
		c.JSON(http.StatusServiceUnavailable, gin.H{
			"status":  http.StatusServiceUnavailable,
			"message": "Data layanan belum dapat dimuat.",
		})
		return
	}

	if items == nil {
		items = []models.MappingLayananItem{}
	}

	lingkupSeen := make(map[string]bool, len(items))
	lingkupList := make([]string, 0, len(items))
	kan, nonKan := 0, 0

	for _, item := range items {
		if item.Lingkup != "" && !lingkupSeen[item.Lingkup] {
			lingkupSeen[item.Lingkup] = true
			lingkupList = append(lingkupList, item.Lingkup)
		}

		switch item.Kategori {
		case "KAN":
			kan++
		case "Non KAN":
			nonKan++
		}
	}

	label := repo.ScopeTitles[scope]

	c.JSON(http.StatusOK, gin.H{
		"status":  200,
		"message": "Daftar mapping layanan",
		"data": gin.H{
			"scope": gin.H{
				"key":     scope,
				"label":   label,
				"lingkup": lingkupList,
			},
			"summary": gin.H{
				"total":   len(items),
				"kan":     kan,
				"non_kan": nonKan,
				"lingkup": lingkupList,
			},
			"items": items,
		},
	})
}
