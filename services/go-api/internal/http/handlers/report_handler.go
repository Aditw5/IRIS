package handlers

import (
	"net/http"
	"strings"

	"github.com/gin-gonic/gin"
	"github.com/ulab/ulab-go-api/internal/repo"
)

type ReportHandler struct {
	reportRepo  *repo.ReportRepo
	historyRepo *repo.HistoryRepo
}

func NewReportHandler(reportRepo *repo.ReportRepo, historyRepo *repo.HistoryRepo) *ReportHandler {
	return &ReportHandler{reportRepo: reportRepo, historyRepo: historyRepo}
}

func (h *ReportHandler) UnitReport(c *gin.Context) {
	uid, ok := userIDFromCtx(c)
	if !ok || uid <= 0 {
		c.JSON(http.StatusUnauthorized, gin.H{
			"status":  401,
			"message": "unauthorized",
		})
		return
	}

	mitraFK, err := h.historyRepo.GetUserMitraFK(c.Request.Context(), uid)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": err.Error(),
		})
		return
	}
	if strings.TrimSpace(mitraFK) == "" {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "mitrafk user tidak ditemukan",
		})
		return
	}

	report, err := h.reportRepo.UnitReport(c.Request.Context(), mitraFK)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": err.Error(),
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status": 200,
		"data":   report,
	})
}
