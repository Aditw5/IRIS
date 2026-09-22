package main

import (
	"context"
	"log"
	"time"

	"github.com/ulab/ulab-go-api/internal/config"
	"github.com/ulab/ulab-go-api/internal/db"
	"github.com/ulab/ulab-go-api/internal/domain"
	httpTransport "github.com/ulab/ulab-go-api/internal/http"
	"github.com/ulab/ulab-go-api/internal/http/handlers"
	"github.com/ulab/ulab-go-api/internal/imageoptimizer"
	"github.com/ulab/ulab-go-api/internal/mailer"
	"github.com/ulab/ulab-go-api/internal/push"
	"github.com/ulab/ulab-go-api/internal/repo"
)

func main() {
	cfg := config.Load()
	d := db.Connect(cfg.PostgresDSN())

	db.SyncUsersIDSequence(d)
	_ = d.AutoMigrate(&domain.EmailOTP{}, &domain.RefreshToken{})

	// ===== Repos =====
	userRepo := repo.NewUserRepo(d)
	adminUserRepo := repo.NewAdminUserRepo(d)
	otpRepo := repo.NewOTPRepo(d)
	refreshTokenRepo := repo.NewRefreshTokenRepo(d)
	toolImageOptimizer := imageoptimizer.New(imageoptimizer.Config{
		Enabled:        cfg.ImageOptimizer.Enabled,
		URL:            cfg.ImageOptimizer.URL,
		Timeout:        time.Duration(cfg.ImageOptimizer.Timeout) * time.Second,
		MaxUploadBytes: cfg.ImageOptimizer.MaxUploadBytes,
	})
	alatRepo := repo.NewAlatRepo(d, toolImageOptimizer)
	userProfileRepo := repo.NewUserProfileRepo(d)
	mobileProfileRepo := repo.NewMobileProfileRepo(d)
	mitraRepo := repo.NewMitraRepo(d)
	keranjangRepo := repo.NewKeranjangRepo(d)

	// checkout
	checkoutRepo := repo.NewCheckoutRepo(d)

	// history
	historyRepo := repo.NewHistoryRepo(d)
	reportRepo := repo.NewReportRepo(d)
	adminRegistrationRepo := repo.NewAdminRegistrationRepo(d, toolImageOptimizer)
	mappingLayananRepo := repo.NewMappingLayananRepo(d)

	// ===== Mailer =====
	m := mailer.New(
		cfg.Mail.Host, cfg.Mail.Port,
		cfg.Mail.User, cfg.Mail.Pass,
		cfg.Mail.FromAddr, cfg.Mail.FromName,
	)

	// ===== Handlers =====
	ah := handlers.NewAuthHandler(
		userRepo,
		adminUserRepo,
		otpRepo,
		refreshTokenRepo,
		m,
		cfg.App.JWTSecret,
		cfg.App.JWTTtlMin,
		90, // refresh token 90 hari
		cfg.OTP.TtlMin,
		cfg.OTP.ResendCooldownSec,
		cfg.OTP.MaxVerifyAttempts,
		cfg.WA.Token,
		cfg.WA.Secret,
	)

	alatH := handlers.NewAlatHandler(alatRepo)
	profileH := handlers.NewProfileHandler(userProfileRepo)
	mobileProfileH := handlers.NewMobileProfileHandler(mobileProfileRepo, adminUserRepo)
	mitraH := handlers.NewMitraHandler(mitraRepo)
	keranjangH := handlers.NewKeranjangHandler(keranjangRepo)

	// Push admin diinisialisasi pada service checkout agar notifikasi dibuat
	// langsung setelah registrasi tersimpan. Checkout tetap berjalan jika FCM
	// belum dikonfigurasi atau sementara tidak tersedia.
	adminNotifier, pushErr := push.NewFirebaseAdminNotifier(
		context.Background(),
		d,
		cfg.Firebase.ProjectID,
		cfg.Firebase.CredentialsFile,
	)
	if pushErr != nil {
		log.Printf("[fcm-admin] disabled: %v", pushErr)
	}

	// checkout
	checkoutH := handlers.NewCheckoutHandler(
		checkoutRepo,
		cfg.WA.Token,
		cfg.WA.Secret,
		adminNotifier,
	)

	// history
	historyH := handlers.NewHistoryHandler(historyRepo)
	reportH := handlers.NewReportHandler(reportRepo, historyRepo)
	adminRegistrationH := handlers.NewAdminRegistrationHandler(adminRegistrationRepo)
	mappingLayananH := handlers.NewMappingLayananHandler(mappingLayananRepo)
	appVersionH := handlers.NewAppVersionHandler(handlers.AppVersionConfig{
		LatestVersion: cfg.MobileApp.LatestVersion,
		LatestBuild:   cfg.MobileApp.LatestBuild,
		MinimumBuild:  cfg.MobileApp.MinimumBuild,
		ForceUpdate:   cfg.MobileApp.ForceUpdate,
		PlayStoreURL:  cfg.MobileApp.PlayStoreURL,
		UpdateMessage: cfg.MobileApp.UpdateMessage,
	})

	// ===== Router =====
	r := httpTransport.SetupRouter(
		ah,
		alatH,
		profileH,
		mobileProfileH,
		mitraH,
		keranjangH,
		checkoutH,
		historyH,
		reportH,
		adminRegistrationH,
		mappingLayananH,
		appVersionH,
		cfg.App.JWTSecret,
	)

	log.Printf("env=%s | listening on :%s ...", cfg.App.Env, cfg.App.Port)
	if err := r.Run(":" + cfg.App.Port); err != nil {
		log.Fatal(err)
	}
}
