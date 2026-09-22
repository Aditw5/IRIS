package handlers

import (
	"net/http"
	"strconv"
	"strings"

	"github.com/gin-gonic/gin"
	"github.com/google/uuid"
	"github.com/ulab/ulab-go-api/internal/repo"
)

type KeranjangHandler struct {
	keranjangRepo *repo.KeranjangRepo
}

func NewKeranjangHandler(r *repo.KeranjangRepo) *KeranjangHandler {
	return &KeranjangHandler{keranjangRepo: r}
}

type saveKeranjangReq struct {
	KeranjangCustomer struct {
		IDAlat     int    `json:"idalat"`
		JenisOrder string `json:"jenisorder"`
	} `json:"keranjangcustomer"`
}

type deleteKeranjangReq struct {
	Norec []string `json:"norec"`
}

func anyToInt(v any) (int, bool) {
	switch x := v.(type) {
	case float64:
		return int(x), true
	case int:
		return x, true
	case int64:
		return int(x), true
	case string:
		i, err := strconv.Atoi(x)
		return i, err == nil
	default:
		return 0, false
	}
}

func (h *KeranjangHandler) SaveKeranjangCustomer(c *gin.Context) {
	customerId := c.GetInt("user_id")
	if customerId <= 0 {
		c.JSON(http.StatusUnauthorized, gin.H{
			"status":  401,
			"message": "unauthorized",
		})
		return
	}

	var req saveKeranjangReq
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  400,
			"message": "payload tidak valid",
			"error":   err.Error(),
		})
		return
	}

	idalat := req.KeranjangCustomer.IDAlat
	jenis := strings.ToLower(strings.TrimSpace(req.KeranjangCustomer.JenisOrder))

	if idalat <= 0 {
		c.JSON(http.StatusUnprocessableEntity, gin.H{
			"status":  422,
			"message": "idalat wajib diisi",
		})
		return
	}

	if jenis != "kalibrasi" && jenis != "repair" {
		c.JSON(http.StatusUnprocessableEntity, gin.H{
			"status":  422,
			"message": "jenisorder harus 'kalibrasi' atau 'repair'",
		})
		return
	}

	tx := h.keranjangRepo.DB.Begin()
	if tx.Error != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": "db begin failed",
			"error":   tx.Error.Error(),
		})
		return
	}
	defer func() { tx.Rollback() }()

	mitraFk := 0
	if v, ok := c.Get("mitrafk"); ok && v != nil {
		if i, ok2 := anyToInt(v); ok2 {
			mitraFk = i
		}
	}
	if mitraFk <= 0 {
		type row struct {
			MitraFK *int `gorm:"column:mitrafk"`
		}
		var r row
		if err := tx.Raw(`select mitrafk from users where id = ?`, customerId).Scan(&r).Error; err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{
				"status":  500,
				"message": "gagal ambil mitrafk",
				"error":   err.Error(),
			})
			return
		}
		if r.MitraFK == nil || *r.MitraFK <= 0 {
			c.JSON(http.StatusUnprocessableEntity, gin.H{
				"status":  422,
				"message": "mitrafk user tidak ditemukan",
			})
			return
		}
		mitraFk = *r.MitraFK
	}

	ownerCheck, err := h.keranjangRepo.CheckAlatOwnedByUserUnit(tx, customerId, idalat)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": "cek kepemilikan alat gagal",
			"error":   err.Error(),
		})
		return
	}

	if ownerCheck == nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": "cek kepemilikan alat gagal",
		})
		return
	}

	if !ownerCheck.IsOwner {
		c.JSON(http.StatusForbidden, gin.H{
			"status":  403,
			"message": "Alat ini bukan milik unit Anda, sehingga ditolak dan tidak dapat dimasukkan ke keranjang.",
			"data": gin.H{
				"is_owner":         false,
				"blocked":          true,
				"idalat":           idalat,
				"customerid":       customerId,
				"user_mitrafk":     ownerCheck.UserMitraFK,
				"alat_objectmitra": ownerCheck.ObjectMitraFK,
				"namaproduk":       ownerCheck.NamaProduk,
			},
		})
		return
	}

	var dup bool
	if err := tx.Raw(`
		select exists(
			select 1
			from keranjangcustomer_t
			where statusenabled = true
			  and customerid = ?
			  and idalat = ?
		)
	`, customerId, idalat).Scan(&dup).Error; err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": "cek duplikat gagal",
			"error":   err.Error(),
		})
		return
	}

	if dup {
		c.JSON(http.StatusConflict, gin.H{
			"status":  409,
			"message": "Barang sudah ada di keranjang",
		})
		return
	}

	mitraFkStr := strconv.Itoa(mitraFk)
	type orderBlockerRow struct {
		JenisOrder string `gorm:"column:jenisorder"`
	}

	var inProgress orderBlockerRow
	progressQuery := tx.Raw(`
		select lower(coalesce(h.jenisorder, '')) as jenisorder
		from mitraregistrasidetail_t d
		join mitraregistrasi_t h on h.norec = d.noregistrasifk
		where d.statusenabled = true
		  and h.statusenabled = true
		  and d.namaalatfk = ?
		  and h.nomitrafk = ?
		  and (
			case
				when lower(coalesce(h.jenisorder, '')) = 'repair'
					then d.tglsetujumanagerlaporanrepair
				else d.tglsetujumanagerlembarkerja
			end
		  ) is null
		order by h.tglregistrasi desc nulls last
		limit 1
	`, idalat, mitraFkStr).Scan(&inProgress)
	if progressQuery.Error != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": "cek progress gagal",
			"error":   progressQuery.Error.Error(),
		})
		return
	}

	if progressQuery.RowsAffected > 0 {
		documentName := "Sertifikat kalibrasi"
		if inProgress.JenisOrder == "repair" {
			documentName = "Laporan repair"
		}
		message := "Alat masih dikerjakan. " + documentName + " belum disetujui Manager."

		c.JSON(http.StatusConflict, gin.H{
			"status":       409,
			"message":      message,
			"block_reason": "work_in_progress",
			"jenisorder":   inProgress.JenisOrder,
		})
		return
	}

	var ratingPending bool
	if err := tx.Raw(`
		select exists(
			select 1
			from mitraregistrasidetail_t d
			join mitraregistrasi_t h on h.norec = d.noregistrasifk
			where d.statusenabled = true
			  and h.statusenabled = true
			  and d.namaalatfk = ?
			  and h.nomitrafk = ?
			  and (
					case
						when lower(coalesce(h.jenisorder, '')) = 'repair'
							then d.tglsetujumanagerlaporanrepair
						else d.tglsetujumanagerlembarkerja
					end
				  ) is not null
			  and coalesce(d.isireviewalat, false) = false
		)
	`, idalat, mitraFkStr).Scan(&ratingPending).Error; err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": "cek rating gagal",
			"error":   err.Error(),
		})
		return
	}

	if ratingPending {
		c.JSON(http.StatusConflict, gin.H{
			"status":       409,
			"message":      "Pekerjaan alat sudah selesai, tetapi rating layanan belum diberikan. Berikan rating terlebih dahulu sebelum mendaftarkan alat kembali.",
			"block_reason": "rating_required",
		})
		return
	}

	norec := uuid.NewString()
	if err := tx.Exec(`
		insert into keranjangcustomer_t
			(norec, statusenabled, idalat, jenisorder, customerid, created_at, updated_at)
		values
			(
				?, true, ?, ?, ?,
				date_trunc('second', now() at time zone 'Asia/Jakarta'),
				date_trunc('second', now() at time zone 'Asia/Jakarta')
			)
	`, norec, idalat, jenis, customerId).Error; err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": "insert gagal",
			"error":   err.Error(),
		})
		return
	}

	if err := tx.Commit().Error; err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": "commit gagal",
			"error":   err.Error(),
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status":  200,
		"message": "Simpan Keranjang Customer Sukses",
		"data": gin.H{
			"norec":      norec,
			"is_owner":   true,
			"blocked":    false,
			"idalat":     idalat,
			"jenisorder": jenis,
			"customerid": customerId,
			"mitrafk":    mitraFk,
		},
	})
}

func (h *KeranjangHandler) ListKeranjangCustomer(c *gin.Context) {
	customerId := c.GetInt("user_id")
	if customerId <= 0 {
		c.JSON(http.StatusUnauthorized, gin.H{"status": 401, "message": "unauthorized"})
		return
	}

	search := strings.TrimSpace(c.Query("search"))
	searchTerm := ""
	if search != "" {
		searchTerm = "%" + search + "%"
	}

	type row struct {
		Norec        string `json:"norec" gorm:"column:norec"`
		JenisOrder   string `json:"jenisorder" gorm:"column:jenisorder"`
		ID           int    `json:"id" gorm:"column:id"`
		NamaProduk   string `json:"namaproduk" gorm:"column:namaproduk"`
		NamaMerk     string `json:"namamerk" gorm:"column:namamerk"`
		NamaTipe     string `json:"namatipe" gorm:"column:namatipe"`
		SerialNumber string `json:"namaserialnumber" gorm:"column:namaserialnumber"`
		FotoProduk   string `json:"fotoproduk" gorm:"column:fotoproduk"`
	}

	var out []row

	sql := `
		select
			kc.norec,
			kc.jenisorder,
			mmp.id,
			mmp.namaproduk,
			mmp.namamerk,
			mmp.namatipe,
			mmp.namaserialnumber,
			mmp.fotoproduk
		from keranjangcustomer_t as kc
		left join mapunittoalat_m as mmp on mmp.id = kc.idalat
		where kc.customerid = ?
		  and mmp.statusenabled = true
		  and kc.statusenabled = true
	`

	args := []any{customerId}

	if searchTerm != "" {
		sql += `
		  and (
			   mmp.namaproduk ilike ?
			or cast(mmp.id as text) ilike ?
			or mmp.namamerk ilike ?
			or mmp.namatipe ilike ?
			or mmp.namaserialnumber ilike ?
		  )
		`
		args = append(args, searchTerm, searchTerm, searchTerm, searchTerm, searchTerm)
	}

	sql += ` order by kc.jenisorder `

	if err := h.keranjangRepo.DB.Raw(sql, args...).Scan(&out).Error; err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  500,
			"message": "gagal ambil keranjang",
			"error":   err.Error(),
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status":  200,
		"message": "OK",
		"data":    out,
	})
}

func (h *KeranjangHandler) DeleteKeranjangCustomer(c *gin.Context) {
	customerId := c.GetInt("user_id")
	if customerId <= 0 {
		c.JSON(http.StatusUnauthorized, gin.H{"status": 401, "message": "unauthorized"})
		return
	}

	var req deleteKeranjangReq
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"status": 400, "message": "payload tidak valid", "error": err.Error()})
		return
	}
	if len(req.Norec) == 0 {
		c.JSON(http.StatusBadRequest, gin.H{"status": 400, "message": "Data norec kosong/tidak valid"})
		return
	}

	tx := h.keranjangRepo.DB.Begin()
	if tx.Error != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"status": 500, "message": "db begin failed", "error": tx.Error.Error()})
		return
	}
	defer func() { tx.Rollback() }()

	for _, n := range req.Norec {
		norec := strings.TrimSpace(n)
		if norec == "" {
			continue
		}

		if err := tx.Exec(`
			update keranjangcustomer_t
			set statusenabled = false,
			    updated_at = date_trunc('second', now() at time zone 'Asia/Jakarta')
			where norec = ?
			  and customerid = ?
		`, norec, customerId).Error; err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"status": 500, "message": "hapus keranjang gagal", "error": err.Error()})
			return
		}
	}

	if err := tx.Commit().Error; err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"status": 500, "message": "commit gagal", "error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status":  200,
		"message": "Hapus Keranjang Sukses",
	})
}
