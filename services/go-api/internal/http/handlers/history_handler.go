package handlers

import (
	"net/http"
	"strconv"
	"strings"

	"github.com/gin-gonic/gin"
	"github.com/ulab/ulab-go-api/internal/repo"
)

type HistoryHandler struct {
	repo *repo.HistoryRepo
}

func NewHistoryHandler(r *repo.HistoryRepo) *HistoryHandler {
	return &HistoryHandler{repo: r}
}

func (h *HistoryHandler) ListHistoryOrderKelompok(c *gin.Context) {
	uid, ok := userIDFromCtx(c)
	if !ok || uid <= 0 {
		c.JSON(http.StatusUnauthorized, gin.H{
			"status":  401,
			"message": "unauthorized",
		})
		return
	}

	mitraFK, err := h.repo.GetUserMitraFK(c.Request.Context(), uid)
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

	page, _ := strconv.Atoi(c.DefaultQuery("page", "1"))
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "10"))
	search := c.Query("search")
	if page <= 0 {
		page = 1
	}
	if limit <= 0 {
		limit = 10
	}
	offset := (page - 1) * limit

	rows, total, err := h.repo.ListHistoryOrderKelompok(c.Request.Context(), mitraFK, search, limit, offset)
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
		"total":  total,
	})
}

// =====================================
// NEW HANDLER: HISTORY ALAT HASIL SCAN
// =====================================
func (h *HistoryHandler) ListHistoryAlatScanGrouped(c *gin.Context) {
	uid, ok := userIDFromCtx(c)
	if !ok || uid <= 0 {
		c.JSON(http.StatusUnauthorized, gin.H{
			"status":  401,
			"message": "unauthorized",
		})
		return
	}

	idAlat := strings.TrimSpace(c.Query("id_alat"))
	if idAlat == "" {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "id_alat wajib diisi",
		})
		return
	}

	page, _ := strconv.Atoi(c.DefaultQuery("page", "1"))
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "10"))
	if page <= 0 {
		page = 1
	}
	if limit <= 0 {
		limit = 10
	}
	offset := (page - 1) * limit

	if accountTypeFromContext(c) == "admin" {
		rows, total, err := h.repo.ListHistoryAlatScanGrouped(c.Request.Context(), idAlat, "", limit, offset)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{
				"status":  500,
				"message": err.Error(),
			})
			return
		}

		c.JSON(http.StatusOK, gin.H{
			"status":   200,
			"is_owner": true,
			"data":     rows,
			"total":    total,
			"length":   len(rows),
		})
		return
	}

	mitraFK, err := h.repo.GetUserMitraFK(c.Request.Context(), uid)
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

	isOwner, ownerMessage, err := h.repo.ValidateAlatOwnershipForHistory(c.Request.Context(), idAlat, mitraFK)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": err.Error(),
		})
		return
	}
	if !isOwner {
		c.JSON(http.StatusOK, gin.H{
			"status":   200,
			"is_owner": false,
			"message":  ownerMessage,
			"data":     []repo.HistoryAlatScanGroupItem{},
			"total":    0,
			"length":   0,
		})
		return
	}

	rows, total, err := h.repo.ListHistoryAlatScanGrouped(c.Request.Context(), idAlat, mitraFK, limit, offset)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": err.Error(),
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status":   200,
		"is_owner": true,
		"data":     rows,
		"total":    total,
		"length":   len(rows),
	})
}

func (h *HistoryHandler) ListVersiTerima(c *gin.Context) {
	uid, ok := userIDFromCtx(c)
	if !ok || uid <= 0 {
		c.JSON(http.StatusUnauthorized, gin.H{
			"status":  401,
			"message": "unauthorized",
		})
		return
	}

	norecPD := strings.TrimSpace(c.Query("norec"))
	if norecPD == "" {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "norec wajib diisi",
		})
		return
	}

	mitraFK, err := h.repo.GetUserMitraFK(c.Request.Context(), uid)
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

	rows, err := h.repo.ListVersiTerima(c.Request.Context(), norecPD, mitraFK)
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
		"length": len(rows),
	})
}

func (h *HistoryHandler) HistoryOrderDetail(c *gin.Context) {
	norecPD := strings.TrimSpace(c.Query("norec_pd"))
	if norecPD == "" {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "norec_pd wajib diisi",
		})
		return
	}

	rows, err := h.repo.ListHistoryOrderDetail(c.Request.Context(), norecPD)
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
		"length": len(rows),
	})
}

func (h *HistoryHandler) GetSurveyPelanggan(c *gin.Context) {
	norecPD := strings.TrimSpace(c.Query("norec_pd"))
	if norecPD == "" {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "norec_pd wajib diisi",
		})
		return
	}

	data, err := h.repo.GetSurveyPelanggan(c.Request.Context(), norecPD)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": err.Error(),
		})
		return
	}

	hasCompletedTool, err := h.repo.HasCompletedTool(c.Request.Context(), norecPD)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": err.Error(),
		})
		return
	}

	surveySudahDiisi := len(data) > 0

	c.JSON(http.StatusOK, gin.H{
		"status":             200,
		"data":               data,
		"has_completed_tool": hasCompletedTool,
		"survey_sudah_diisi": surveySudahDiisi,
		"survey_required":    hasCompletedTool && !surveySudahDiisi,
	})
}

func (h *HistoryHandler) SavePenilaianPelanggan(c *gin.Context) {
	var req struct {
		Penilaian struct {
			Norec       string `json:"norec"`
			NorecDetail string `json:"norec_detail"`
			NoOrderAlat string `json:"noorderalat"`
			Bintang     int    `json:"bintang"`
			Ulasan      string `json:"ulasan"`
		} `json:"penilaian"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "body json tidak valid",
		})
		return
	}

	if strings.TrimSpace(req.Penilaian.Norec) == "" {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "norec wajib diisi",
		})
		return
	}
	if strings.TrimSpace(req.Penilaian.NorecDetail) == "" {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "norec_detail wajib diisi",
		})
		return
	}
	if req.Penilaian.Bintang < 1 || req.Penilaian.Bintang > 5 {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "bintang harus 1 sampai 5",
		})
		return
	}

	if err := h.repo.SavePenilaianPelanggan(c.Request.Context(), repo.SavePenilaianPayload{
		Norec:       req.Penilaian.Norec,
		NorecDetail: req.Penilaian.NorecDetail,
		NoOrderAlat: req.Penilaian.NoOrderAlat,
		Bintang:     strconv.Itoa(req.Penilaian.Bintang),
		Ulasan:      req.Penilaian.Ulasan,
	}); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": err.Error(),
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status":  200,
		"message": "Simpan Penilaian Pelanggan Sukses",
	})
}

func validateSurveyPelangganPayload(s repo.SaveSurveyPayload) string {
	if strings.TrimSpace(s.NamaResponden) == "" {
		return "namaresponden wajib diisi"
	}
	if strings.TrimSpace(s.UnitDivisiKerja) == "" {
		return "unitdivisikerja wajib diisi"
	}
	if strings.TrimSpace(s.JabatanResponden) == "" {
		return "jabatanresponden wajib diisi"
	}
	if strings.TrimSpace(s.NoTelpon) == "" {
		return "notelpon wajib diisi"
	}
	if len(s.AtributList) == 0 {
		return "atribut kepuasan wajib diisi"
	}
	for _, attr := range s.AtributList {
		if attr.Harapan == nil || attr.Kepuasan == nil {
			return "harapan dan kepuasan pada seluruh atribut kepuasan wajib diisi"
		}
	}

	return ""
}

func (h *HistoryHandler) SaveSurveyPelanggan(c *gin.Context) {
	var req struct {
		Survey repo.SaveSurveyPayload `json:"survey"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "body json tidak valid",
		})
		return
	}

	if strings.TrimSpace(req.Survey.RegistrasiFK) == "" {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "registrasifk wajib diisi",
		})
		return
	}

	if msg := validateSurveyPelangganPayload(req.Survey); msg != "" {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": msg,
		})
		return
	}

	if err := h.repo.SaveSurveyPelanggan(c.Request.Context(), req.Survey); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": err.Error(),
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status":  200,
		"message": "Simpan Survey Pelanggan Sukses",
	})
}
