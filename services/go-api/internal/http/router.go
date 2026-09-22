package http

import (
	"time"

	"github.com/gin-contrib/cors"
	"github.com/gin-gonic/gin"
	"github.com/ulab/ulab-go-api/internal/http/handlers"
	"github.com/ulab/ulab-go-api/internal/middleware"
)

func SetupRouter(
	ah *handlers.AuthHandler,
	alatH *handlers.AlatHandler,
	profileH *handlers.ProfileHandler,
	mobileProfileH *handlers.MobileProfileHandler,
	mitraH *handlers.MitraHandler,
	keranjangH *handlers.KeranjangHandler,
	checkoutH *handlers.CheckoutHandler,
	historyH *handlers.HistoryHandler,
	reportH *handlers.ReportHandler,
	adminRegistrationH *handlers.AdminRegistrationHandler,
	mappingLayananH *handlers.MappingLayananHandler,
	appVersionH *handlers.AppVersionHandler,
	jwtSecret string,
) *gin.Engine {
	r := gin.New()
	r.Use(gin.Logger(), gin.Recovery())
	r.Use(cors.New(cors.Config{
		AllowOrigins:  []string{"*"},
		AllowMethods:  []string{"GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS"},
		AllowHeaders:  []string{"Authorization", "Content-Type", "X-App-Version", "X-App-Build"},
		ExposeHeaders: []string{"Content-Length"},
		MaxAge:        12 * time.Hour,
	}))

	api := r.Group("/api")
	{
		api.GET("/health", func(c *gin.Context) { c.JSON(200, gin.H{"ok": true}) })
		api.GET("/mobile/app-version", appVersionH.Get)
		api.POST("/auth/register", ah.Register)
		api.POST("/auth/login", ah.Login)
		api.POST("/auth/refresh", ah.Refresh)
		api.POST("/auth/otp/resend", ah.ResendOTP)
		api.POST("/auth/verify-otp", ah.VerifyOTP)

		api.POST("/auth/forgot-password/request", ah.ForgotPasswordRequest)
		api.POST("/auth/forgot-password/verify", ah.ForgotPasswordVerify)
		api.POST("/auth/forgot-password/reset", ah.ForgotPasswordReset)

		// Layanan / mapping alat per ruang lingkup - publik, sama seperti NoAuthCtrl di Laravel
		api.GET("/service/mapping-layanan", mappingLayananH.GetMappingLayanan)
	}

	auth := r.Group("/api").Use(middleware.Auth(jwtSecret))
	{
		auth.GET("/auth/me", ah.Me)
		auth.POST("/auth/logout", ah.Logout)

		auth.GET("/alat", alatH.GetAlatCustomer)

		// MASTER ALAT CUSTOMER
		auth.POST("/customer/save-master-produk", alatH.SaveMasterProdukCustomer)
		auth.GET("/customer/master-produk/:id", alatH.GetMasterProdukCustomerByID)
		auth.POST("/customer/delete-master-produk", alatH.DeleteMasterProdukCustomer)

		auth.PATCH("/profile/setup", profileH.SetupNewUserProfile)

		auth.GET("/mobile-profile/detail", mobileProfileH.GetDetail)
		auth.PATCH("/mobile-profile/update", mobileProfileH.UpdateDetail)
		auth.POST("/mobile-profile/upload-photo", mobileProfileH.UploadPhoto)

		auth.GET("/mitra", mitraH.List)
		auth.GET("/mitra-eksternal", mitraH.ListEksternal)

		auth.POST("/customer/save-keranjang-customer", keranjangH.SaveKeranjangCustomer)
		auth.GET("/customer/keranjang-customer", keranjangH.ListKeranjangCustomer)
		auth.POST("/customer/hapus-keranjang-customer", keranjangH.DeleteKeranjangCustomer)

		auth.GET("/lokasi-kalibrasi", checkoutH.ListLokasiKalibrasi)
		auth.GET("/paket-kalibrasi", checkoutH.ListPaketKalibrasi)
		auth.POST("/customer/save-checkout", checkoutH.SaveCheckoutCustomer)

		auth.GET("/customer/history-order-kelompok", historyH.ListHistoryOrderKelompok)
		auth.GET("/customer/history-order-detail", historyH.HistoryOrderDetail)
		auth.GET("/customer/history-alat-scan", historyH.ListHistoryAlatScanGrouped)
		auth.GET("/customer/laporan-unit", reportH.UnitReport)

		auth.GET("/registrasi/list-versi-terima", historyH.ListVersiTerima)
		auth.GET("/registrasi/get-survey-pelanggan", historyH.GetSurveyPelanggan)
		auth.POST("/registrasi/save-penilaian-pelanggan", historyH.SavePenilaianPelanggan)
		auth.POST("/registrasi/save-survey-pelanggan", historyH.SaveSurveyPelanggan)

		auth.GET("/admin/registrasi/summary", adminRegistrationH.DashboardStats)
		auth.GET("/admin/registrasi/units", adminRegistrationH.ListUnits)
		auth.GET("/admin/registrasi/tools", adminRegistrationH.ListTools)
		auth.GET("/admin/registrasi/kaji-detail", adminRegistrationH.KajiDetails)
		auth.POST("/admin/registrasi/save", adminRegistrationH.SaveRegistration)
		auth.POST("/admin/registrasi/kaji-item", adminRegistrationH.SaveKajiItem)
		auth.POST("/admin/registrasi/kaji-header", adminRegistrationH.SaveKajiHeader)
		auth.POST("/admin/registrasi/upload-sertifikat-vendor", adminRegistrationH.UploadVendorCertificate)
		auth.POST("/admin/registrasi/upload-ams", adminRegistrationH.UploadAMS)
		auth.GET("/admin/master/lokasi", adminRegistrationH.ListLokasi)
		auth.GET("/admin/master/units", adminRegistrationH.SearchUnits)
		auth.GET("/admin/master/lab-units", adminRegistrationH.SearchLabUnits)
		auth.GET("/admin/master/employees", adminRegistrationH.SearchEmployees)
		auth.POST("/admin/master/unit", adminRegistrationH.SaveUnit)
		auth.GET("/admin/master/unit-tools", adminRegistrationH.SearchUnitTools)
		auth.POST("/admin/master/unit-tools", adminRegistrationH.SaveTool)
		auth.POST("/admin/master/unit-tools/delete", adminRegistrationH.DeleteTool)
		auth.GET("/admin/master/standard-tools", adminRegistrationH.SearchStandardTools)
		auth.GET("/admin/master/lingkup", adminRegistrationH.ListLingkupKalibrasi)
		auth.GET("/admin/master/paket", adminRegistrationH.ListPaketKalibrasi)
		auth.GET("/admin/master/status-surkes", adminRegistrationH.ListStatusSurkes)
		auth.GET("/admin/master/vendors", adminRegistrationH.ListVendors)
	}

	r.NoRoute(func(c *gin.Context) {
		c.JSON(404, gin.H{
			"status":  404,
			"message": "route not found",
			"path":    c.Request.URL.Path,
		})
	})

	return r
}
