package config

import (
	"fmt"
	"strings"

	"github.com/spf13/viper"
)

type Config struct {
	App struct {
		Env       string
		Port      string
		JWTSecret string
		JWTTtlMin int
	}
	DB struct {
		Host, Port, User, Pass, Name, SSLMode string
	}
	Mail struct {
		Host     string
		Port     int
		User     string
		Pass     string
		FromAddr string
		FromName string
	}
	OTP struct {
		TtlMin            int
		ResendCooldownSec int
		MaxVerifyAttempts int
	}
	WA struct {
		Token  string
		Secret string
	}
	Firebase struct {
		ProjectID       string
		CredentialsFile string
	}
	MobileApp struct {
		LatestVersion string
		LatestBuild   int
		MinimumBuild  int
		ForceUpdate   bool
		PlayStoreURL  string
		UpdateMessage string
	}
	ImageOptimizer struct {
		Enabled        bool
		URL            string
		Timeout        int
		MaxUploadBytes int64
	}
}

func Load() *Config {
	viper.SetConfigFile(".env")
	_ = viper.ReadInConfig()
	viper.AutomaticEnv()
	viper.SetEnvKeyReplacer(strings.NewReplacer(".", "_"))

	viper.SetDefault("APP_PORT", "8080")
	viper.SetDefault("APP_JWT_TTL_MIN", 60)
	viper.SetDefault("DB_SSLMODE", "disable")

	viper.SetDefault("MAIL_PORT", 587)
	viper.SetDefault("OTP_TTL_MIN", 10)
	viper.SetDefault("OTP_RESEND_COOLDOWN_SEC", 60)
	viper.SetDefault("OTP_MAX_VERIFY_ATTEMPTS", 5)

	viper.SetDefault("WA_TOKEN", "")
	viper.SetDefault("WA_SECRET", "")
	viper.SetDefault("MOBILE_APP_LATEST_VERSION", "1.0.15")
	viper.SetDefault("MOBILE_APP_LATEST_BUILD", 16)
	viper.SetDefault("MOBILE_APP_MINIMUM_BUILD", 9)
	viper.SetDefault("MOBILE_APP_FORCE_UPDATE", true)
	viper.SetDefault("MOBILE_APP_PLAY_STORE_URL", "https://play.google.com/store/apps/details?id=id.ulabumro.mobile")
	viper.SetDefault("MOBILE_APP_UPDATE_MESSAGE", "Versi baru U-LAB Mobile tersedia. Silakan perbarui aplikasi untuk melanjutkan.")
	viper.SetDefault("IMAGE_OPTIMIZER_ENABLED", true)
	viper.SetDefault("IMAGE_OPTIMIZER_URL", "http://127.0.0.1:8011")
	viper.SetDefault("IMAGE_OPTIMIZER_TIMEOUT", 15)
	viper.SetDefault("IMAGE_MAX_UPLOAD_BYTES", 20*1024*1024)

	var c Config
	c.App.Env = viper.GetString("APP_ENV")
	c.App.Port = viper.GetString("APP_PORT")
	c.App.JWTSecret = viper.GetString("APP_JWT_SECRET")
	c.App.JWTTtlMin = viper.GetInt("APP_JWT_TTL_MIN")

	c.DB.Host = viper.GetString("DB_HOST")
	c.DB.Port = viper.GetString("DB_PORT")
	c.DB.User = viper.GetString("DB_USER")
	c.DB.Pass = viper.GetString("DB_PASS")
	c.DB.Name = viper.GetString("DB_NAME")
	c.DB.SSLMode = viper.GetString("DB_SSLMODE")

	c.Mail.Host = viper.GetString("MAIL_HOST")
	c.Mail.Port = viper.GetInt("MAIL_PORT")
	c.Mail.User = viper.GetString("MAIL_USERNAME")
	c.Mail.Pass = viper.GetString("MAIL_PASSWORD")
	c.Mail.FromAddr = viper.GetString("MAIL_FROM_ADDRESS")
	c.Mail.FromName = viper.GetString("MAIL_FROM_NAME")

	c.OTP.TtlMin = viper.GetInt("OTP_TTL_MIN")
	c.OTP.ResendCooldownSec = viper.GetInt("OTP_RESEND_COOLDOWN_SEC")
	c.OTP.MaxVerifyAttempts = viper.GetInt("OTP_MAX_VERIFY_ATTEMPTS")

	c.WA.Token = viper.GetString("WA_TOKEN")
	c.WA.Secret = viper.GetString("WA_SECRET")
	c.Firebase.ProjectID = viper.GetString("FIREBASE_PROJECT_ID")
	c.Firebase.CredentialsFile = viper.GetString("FIREBASE_CREDENTIALS_FILE")
	c.MobileApp.LatestVersion = viper.GetString("MOBILE_APP_LATEST_VERSION")
	c.MobileApp.LatestBuild = viper.GetInt("MOBILE_APP_LATEST_BUILD")
	c.MobileApp.MinimumBuild = viper.GetInt("MOBILE_APP_MINIMUM_BUILD")
	c.MobileApp.ForceUpdate = viper.GetBool("MOBILE_APP_FORCE_UPDATE")
	c.MobileApp.PlayStoreURL = viper.GetString("MOBILE_APP_PLAY_STORE_URL")
	c.MobileApp.UpdateMessage = viper.GetString("MOBILE_APP_UPDATE_MESSAGE")
	c.ImageOptimizer.Enabled = viper.GetBool("IMAGE_OPTIMIZER_ENABLED")
	c.ImageOptimizer.URL = viper.GetString("IMAGE_OPTIMIZER_URL")
	c.ImageOptimizer.Timeout = viper.GetInt("IMAGE_OPTIMIZER_TIMEOUT")
	c.ImageOptimizer.MaxUploadBytes = viper.GetInt64("IMAGE_MAX_UPLOAD_BYTES")

	return &c
}

func (c *Config) PostgresDSN() string {
	return fmt.Sprintf("host=%s port=%s user=%s password=%s dbname=%s sslmode=%s",
		c.DB.Host, c.DB.Port, c.DB.User, c.DB.Pass, c.DB.Name, c.DB.SSLMode)
}
