package repo

import (
	"context"
	"errors"
	"fmt"
	"mime/multipart"
	"os"
	"path/filepath"
	"strings"
	"time"

	"github.com/google/uuid"
	"gorm.io/gorm"
	"gorm.io/gorm/clause"
)

var (
	ErrCheckoutItemsAlreadyProcessed = errors.New("item checkout sudah diproses")
	ErrCheckoutItemsInvalid          = errors.New("item checkout tidak valid")
	ErrCheckoutLocationInvalid       = errors.New("lokasi checkout tidak valid")
	ErrCheckoutPackageInvalid        = errors.New("paket kalibrasi tidak valid")
)

var wibLocation = time.FixedZone("WIB", 7*60*60)

type CheckoutRepo struct {
	db *gorm.DB
}

func NewCheckoutRepo(db *gorm.DB) *CheckoutRepo {
	return &CheckoutRepo{db: db}
}

type DropdownItem struct {
	Value string `json:"value" gorm:"column:value"`
	Label string `json:"label" gorm:"column:label"`
	Hari  *int   `json:"hari,omitempty" gorm:"column:hari"`
}

type CheckoutItem struct {
	Norec      string `json:"norec"`
	ID         int    `json:"id"`
	JenisOrder string `json:"jenisorder"`
}

type CheckoutCustomerProfile struct {
	ID         int    `gorm:"column:id"`
	Name       string `gorm:"column:name"`
	NoWA       string `gorm:"column:nowa"`
	Jabatan    string `gorm:"column:jabatan"`
	MitraFK    int    `gorm:"column:mitrafk"`
	IsExternal bool   `gorm:"column:iseksternal"`
}

type SaveCheckoutRequest struct {
	UserID      int
	NoMitraFK   string
	NamaPJ      string
	NoHPPJ      string
	JabatanPJ   string
	Catatan     string
	PaketID     int
	LokasiID    int
	RentangUkur string
	RentangKet  string
	FileAMS     *multipart.FileHeader
	FileTools   *multipart.FileHeader
	Items       []CheckoutItem
}

type SaveCheckoutResult struct {
	RegistrasiNorec []string `json:"registrasi_norec"`
	JumlahAlat      int      `json:"jumlah_alat"`
	NamaCustomer    string   `json:"nama_customer"`
	NoHPCustomer    string   `json:"nohp_customer"`
	WaktuOrder      string   `json:"waktu_order"`
	DetailAlat      []string `json:"detail_alat"`
	LokasiID        int      `json:"lokasi_id"`
	FileAMS         string   `json:"file_ams"`
	FileTools       *string  `json:"file_tools"`
}

func (r SaveCheckoutResult) DetailAlatText() string {
	if len(r.DetailAlat) == 0 {
		return "-"
	}
	var out strings.Builder
	for i, d := range r.DetailAlat {
		out.WriteString(fmt.Sprintf("%d. %s\n", i+1, d))
	}
	return strings.TrimSpace(out.String())
}

type AdminContact struct {
	ID                 any     `json:"id" gorm:"column:id"`
	NamaLengkap        string  `json:"namalengkap" gorm:"column:namalengkap"`
	NoHandphone        *string `json:"nohandphone" gorm:"column:nohandphone"`
	ObjectJenisPegawai *int    `json:"objectjenispegawaifk,omitempty" gorm:"column:objectjenispegawaifk"`
	LokasiKalibrasiFK  *int    `json:"lokasikalibrasifk,omitempty" gorm:"column:lokasikalibrasifk"`
}

func (r *CheckoutRepo) ListAdminByLokasi(ctx context.Context, lokasiID int) ([]AdminContact, error) {
	var rows []AdminContact

	// Prioritas 1: admin sesuai role + lokasi
	err := r.db.WithContext(ctx).
		Table("pegawai_m").
		Select(`
			id,
			namalengkap,
			NULLIF(TRIM(nohandphone), '') as nohandphone,
			objectjenispegawaifk,
			lokasikalibrasifk
		`).
		Where("statusenabled = TRUE").
		Where("lokasikalibrasifk = ?", lokasiID).
		Where("objectjenispegawaifk = ?", 13).
		Order("namalengkap asc").
		Find(&rows).Error
	if err != nil {
		return nil, err
	}

	if len(rows) > 0 {
		return rows, nil
	}

	// Fallback 1: semua pegawai pada lokasi itu yang punya no hp
	rows = nil
	err = r.db.WithContext(ctx).
		Table("pegawai_m").
		Select(`
			id,
			namalengkap,
			NULLIF(TRIM(nohandphone), '') as nohandphone,
			objectjenispegawaifk,
			lokasikalibrasifk
		`).
		Where("statusenabled = TRUE").
		Where("lokasikalibrasifk = ?", lokasiID).
		Where("NULLIF(TRIM(nohandphone), '') IS NOT NULL").
		Order("namalengkap asc").
		Find(&rows).Error
	if err != nil {
		return nil, err
	}

	return rows, nil
}

func (r *CheckoutRepo) IsExternalCustomer(ctx context.Context, userID int) (bool, error) {
	var row struct {
		IsEksternal *bool `gorm:"column:iseksternal"`
	}

	if err := r.db.WithContext(ctx).
		Table("users").
		Select("iseksternal").
		Where("id = ?", userID).
		Take(&row).Error; err != nil {
		return false, err
	}

	return row.IsEksternal != nil && *row.IsEksternal, nil
}

func (r *CheckoutRepo) GetCheckoutCustomerProfile(ctx context.Context, userID int) (CheckoutCustomerProfile, error) {
	var profile CheckoutCustomerProfile
	err := r.db.WithContext(ctx).
		Table("users").
		Select(`
			id,
			COALESCE(name, '') AS name,
			COALESCE(nowa, '') AS nowa,
			COALESCE(jabatan, '') AS jabatan,
			COALESCE(mitrafk, 0) AS mitrafk,
			COALESCE(iseksternal, FALSE) AS iseksternal
		`).
		Where("id = ?", userID).
		Take(&profile).Error
	return profile, err
}

func (r *CheckoutRepo) ListLokasiKalibrasi(ctx context.Context, query string, limit int) ([]DropdownItem, error) {
	if limit <= 0 {
		limit = 20
	}

	q := r.db.WithContext(ctx).
		Table("lokasikalibrasi_m").
		Select(`
			CAST(id AS text) AS value,
			COALESCE(lokasi, '-') AS label
		`).
		Where("statusenabled = TRUE")

	if strings.TrimSpace(query) != "" {
		q = q.Where("lokasi ILIKE ?", "%"+strings.TrimSpace(query)+"%")
	}

	q = q.Order("lokasi asc").Limit(limit)

	var rows []DropdownItem
	err := q.Scan(&rows).Error
	return rows, err
}

func (r *CheckoutRepo) ListPaketKalibrasi(ctx context.Context, query string, limit int) ([]DropdownItem, error) {
	if limit <= 0 {
		limit = 20
	}

	q := r.db.WithContext(ctx).
		Table("paketkalibrasi_m").
		Select(`
			CAST(id AS text) AS value,
			COALESCE(namapaket, '-') AS label,
			hari
		`).
		Where("statusenabled = TRUE")

	if strings.TrimSpace(query) != "" {
		q = q.Where("namapaket ILIKE ?", "%"+strings.TrimSpace(query)+"%")
	}

	q = q.Order("namapaket asc").Limit(limit)

	var rows []DropdownItem
	err := q.Scan(&rows).Error
	return rows, err
}

func (r *CheckoutRepo) nextNorec() string {
	return fmt.Sprintf("%d", time.Now().UnixNano())
}

func checkoutNow() time.Time {
	return time.Now().In(wibLocation)
}

func formatCheckoutTimeWIB(value time.Time) string {
	months := [...]string{
		"Januari", "Februari", "Maret", "April", "Mei", "Juni",
		"Juli", "Agustus", "September", "Oktober", "November", "Desember",
	}
	local := value.In(wibLocation)
	return fmt.Sprintf(
		"%02d %s %04d %02d:%02d",
		local.Day(), months[local.Month()-1], local.Year(), local.Hour(), local.Minute(),
	)
}

func getCustomerUploadDir() string {
	if envDir := strings.TrimSpace(os.Getenv("CUSTOMER_UPLOAD_DIR")); envDir != "" {
		return envDir
	}
	return filepath.Clean(filepath.Join("..", "..", "backend", "public", "berkas-customer"))
}

func saveMultipartFile(file *multipart.FileHeader, dst string) error {
	src, err := file.Open()
	if err != nil {
		return err
	}
	defer src.Close()

	out, err := os.Create(dst)
	if err != nil {
		return err
	}
	defer out.Close()

	_, err = out.ReadFrom(src)
	return err
}

func saveUploadedFileToLaravelPublic(file *multipart.FileHeader) (string, error) {
	if file == nil {
		return "", nil
	}

	uploadDir := getCustomerUploadDir()
	if err := os.MkdirAll(uploadDir, 0755); err != nil {
		return "", err
	}

	filename := fmt.Sprintf("%s_%s", uuid.NewString(), safeUploadFilename(file.Filename))

	dst := filepath.Join(uploadDir, filename)
	if err := saveMultipartFile(file, dst); err != nil {
		return "", err
	}

	return filename, nil
}

func removeCustomerUpload(filename string) {
	filename = filepath.Base(strings.TrimSpace(filename))
	if filename == "" || filename == "." {
		return
	}
	_ = os.Remove(filepath.Join(getCustomerUploadDir(), filename))
}

type checkoutCartRow struct {
	Norec         string `gorm:"column:norec"`
	IDAlat        int    `gorm:"column:idalat"`
	JenisOrder    string `gorm:"column:jenisorder"`
	StatusEnabled bool   `gorm:"column:statusenabled"`
}

func lockAndValidateCheckoutItems(tx *gorm.DB, req SaveCheckoutRequest) error {
	norecs := make([]string, 0, len(req.Items))
	for _, item := range req.Items {
		norecs = append(norecs, strings.TrimSpace(item.Norec))
	}

	var rows []checkoutCartRow
	if err := tx.Table("keranjangcustomer_t").
		Select("norec, idalat, jenisorder, statusenabled").
		Where("customerid = ?", req.UserID).
		Where("norec IN ?", norecs).
		Order("norec ASC").
		Clauses(clause.Locking{Strength: "UPDATE"}).
		Find(&rows).Error; err != nil {
		return err
	}
	if len(rows) != len(req.Items) {
		return fmt.Errorf("%w: sebagian item bukan milik customer atau sudah tidak tersedia", ErrCheckoutItemsInvalid)
	}

	byNorec := make(map[string]checkoutCartRow, len(rows))
	for _, row := range rows {
		byNorec[row.Norec] = row
	}
	for _, item := range req.Items {
		row, exists := byNorec[strings.TrimSpace(item.Norec)]
		if !exists || row.IDAlat != item.ID || !strings.EqualFold(strings.TrimSpace(row.JenisOrder), strings.TrimSpace(item.JenisOrder)) {
			return fmt.Errorf("%w: data item tidak sesuai dengan keranjang", ErrCheckoutItemsInvalid)
		}
		if !row.StatusEnabled {
			return ErrCheckoutItemsAlreadyProcessed
		}
	}

	return nil
}

func validateCheckoutReferences(tx *gorm.DB, req SaveCheckoutRequest) error {
	var locationCount int64
	if err := tx.Table("lokasikalibrasi_m").
		Where("id = ? AND statusenabled = TRUE", req.LokasiID).
		Count(&locationCount).Error; err != nil {
		return err
	}
	if locationCount != 1 {
		return ErrCheckoutLocationInvalid
	}

	hasCalibration := false
	toolIDs := make([]int, 0, len(req.Items))
	seenToolIDs := make(map[int]struct{}, len(req.Items))
	for _, item := range req.Items {
		if strings.EqualFold(strings.TrimSpace(item.JenisOrder), "kalibrasi") {
			hasCalibration = true
		}
		if _, exists := seenToolIDs[item.ID]; !exists {
			seenToolIDs[item.ID] = struct{}{}
			toolIDs = append(toolIDs, item.ID)
		}
	}

	if hasCalibration {
		var packageCount int64
		if err := tx.Table("paketkalibrasi_m").
			Where("id = ? AND statusenabled = TRUE", req.PaketID).
			Count(&packageCount).Error; err != nil {
			return err
		}
		if packageCount != 1 {
			return ErrCheckoutPackageInvalid
		}
	}

	var toolCount int64
	if err := tx.Table("mapunittoalat_m").
		Where("id IN ?", toolIDs).
		Where("statusenabled = TRUE").
		Where("CAST(objectmitrafk AS text) = ?", req.NoMitraFK).
		Count(&toolCount).Error; err != nil {
		return err
	}
	if toolCount != int64(len(toolIDs)) {
		return fmt.Errorf("%w: alat tidak ditemukan, tidak aktif, atau bukan milik unit customer", ErrCheckoutItemsInvalid)
	}

	return nil
}

func (r *CheckoutRepo) SaveCheckoutCustomer(ctx context.Context, req SaveCheckoutRequest) (SaveCheckoutResult, error) {
	var result SaveCheckoutResult

	filenameAMS, err := saveUploadedFileToLaravelPublic(req.FileAMS)
	if err != nil {
		return result, err
	}

	var filenameToolsPtr *string
	if req.FileTools != nil {
		filenameTools, err := saveUploadedFileToLaravelPublic(req.FileTools)
		if err != nil {
			removeCustomerUpload(filenameAMS)
			return result, err
		}
		filenameToolsPtr = &filenameTools
	}
	cleanupUploads := func() {
		removeCustomerUpload(filenameAMS)
		if filenameToolsPtr != nil {
			removeCustomerUpload(*filenameToolsPtr)
		}
	}

	var customer struct {
		Name string  `gorm:"column:name"`
		Nowa *string `gorm:"column:nowa"`
	}
	if err := r.db.WithContext(ctx).
		Table("users").
		Select("name, nowa").
		Where("id = ?", req.UserID).
		Take(&customer).Error; err != nil {
		cleanupUploads()
		return result, err
	}

	result.NamaCustomer = customer.Name
	if customer.Nowa != nil {
		result.NoHPCustomer = *customer.Nowa
	}
	now := checkoutNow()
	result.WaktuOrder = formatCheckoutTimeWIB(now)
	result.LokasiID = req.LokasiID
	result.FileAMS = filenameAMS
	result.FileTools = filenameToolsPtr

	grouped := map[string][]CheckoutItem{}
	for _, item := range req.Items {
		jenis := strings.ToLower(strings.TrimSpace(item.JenisOrder))
		grouped[jenis] = append(grouped[jenis], item)
	}

	err = r.db.WithContext(ctx).Transaction(func(tx *gorm.DB) error {
		if err := lockAndValidateCheckoutItems(tx, req); err != nil {
			return err
		}
		if err := validateCheckoutReferences(tx, req); err != nil {
			return err
		}

		for jenisOrder, items := range grouped {
			norec := r.nextNorec()

			header := map[string]any{
				"norec":                  norec,
				"statusenabled":          true,
				"nomitrafk":              req.NoMitraFK,
				"namapenanggungjawab":    req.NamaPJ,
				"nohppenanggungjawab":    req.NoHPPJ,
				"jabatanpenanggungjawab": req.JabatanPJ,
				"catatan":                req.Catatan,
				"tglregistrasi":          now,
				"customerfk":             req.UserID,
				"isregiscustomer":        true,
				"statusorder":            0,
				"statusordermanager":     0,
				"jenisorder":             jenisOrder,
				"filecustomerams":        nil,
				"filecustomertools":      nil,
				"created_at":             now,
				"updated_at":             now,
			}

			if filenameAMS != "" {
				header["filecustomerams"] = filenameAMS
			}

			if filenameToolsPtr != nil {
				header["filecustomertools"] = *filenameToolsPtr
			}

			if jenisOrder == "kalibrasi" {
				header["lokasikalibrasi"] = fmt.Sprintf("%d", req.LokasiID)
				header["rentangUkur"] = strings.TrimSpace(req.RentangUkur)
				header["rentangUkurketPermintaanPelanggan"] = strings.TrimSpace(req.RentangKet)
				if req.PaketID > 0 {
					header["paketkalibrasi"] = fmt.Sprintf("%d", req.PaketID)
				}
			} else {
				header["lokasirepair"] = fmt.Sprintf("%d", req.LokasiID)
			}

			if err := tx.Table("mitraregistrasi_t").Create(header).Error; err != nil {
				return err
			}

			var durasiKalibrasi *int
			if jenisOrder == "kalibrasi" && req.PaketID > 0 {
				var paket struct {
					Hari *int `gorm:"column:hari"`
				}
				if err := tx.Table("paketkalibrasi_m").
					Select("hari").
					Where("id = ?", req.PaketID).
					Take(&paket).Error; err == nil {
					durasiKalibrasi = paket.Hari
				}
			}

			for _, item := range items {
				detailNorec := r.nextNorec()

				detail := map[string]any{
					"norec":          detailNorec,
					"statusenabled":  true,
					"namaalatfk":     item.ID,
					"noregistrasifk": norec,
				}

				if durasiKalibrasi != nil {
					detail["durasikalbrasi"] = *durasiKalibrasi
				}

				if err := tx.Table("mitraregistrasidetail_t").Create(detail).Error; err != nil {
					return err
				}

				var alat struct {
					NamaProduk       *string `gorm:"column:namaproduk"`
					NamaMerk         *string `gorm:"column:namamerk"`
					NamaTipe         *string `gorm:"column:namatipe"`
					NamaSerialNumber *string `gorm:"column:namaserialnumber"`
				}
				if err := tx.Table("mapunittoalat_m").
					Select("namaproduk, namamerk, namatipe, namaserialnumber").
					Where("id = ?", item.ID).
					Take(&alat).Error; err == nil {
					detailAlat := fmt.Sprintf(
						"%s | Merk: %s | Tipe: %s | SN: %s",
						valueOrDash(alat.NamaProduk),
						valueOrDash(alat.NamaMerk),
						valueOrDash(alat.NamaTipe),
						valueOrDash(alat.NamaSerialNumber),
					)
					result.DetailAlat = append(result.DetailAlat, detailAlat)
					result.JumlahAlat++
				}

				if strings.TrimSpace(item.Norec) != "" {
					update := tx.Table("keranjangcustomer_t").
						Where("norec = ? AND customerid = ? AND statusenabled = TRUE", item.Norec, req.UserID).
						Updates(map[string]any{"statusenabled": false, "updated_at": now})
					if update.Error != nil {
						return update.Error
					}
					if update.RowsAffected != 1 {
						return ErrCheckoutItemsAlreadyProcessed
					}
				}
			}

			result.RegistrasiNorec = append(result.RegistrasiNorec, norec)
		}

		return nil
	})

	if err != nil {
		cleanupUploads()
		return result, err
	}

	return result, nil
}

func valueOrDash(s *string) string {
	if s == nil || strings.TrimSpace(*s) == "" {
		return "-"
	}
	return *s
}
