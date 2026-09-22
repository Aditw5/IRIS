package handlers

import (
	"crypto/rand"
	"crypto/subtle"
	"encoding/base64"
	"encoding/json"
	"errors"
	"fmt"
	"log"
	"net/http"
	"net/url"
	"strings"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/golang-jwt/jwt/v5"
	"github.com/jackc/pgx/v5/pgconn"
	"github.com/ulab/ulab-go-api/internal/domain"
	"github.com/ulab/ulab-go-api/internal/mailer"
	"github.com/ulab/ulab-go-api/internal/repo"
	"github.com/ulab/ulab-go-api/internal/util"
	"golang.org/x/crypto/bcrypt"
	"gorm.io/gorm"
)

type AuthHandler struct {
	Users          *repo.UserRepo
	AdminUsers     *repo.AdminUserRepo
	OTPS           *repo.OTPRepo
	RefreshTokens  *repo.RefreshTokenRepo
	Mailer         *mailer.Mailer
	JWTSecret      string
	JWTTtlMin      int
	RefreshTTLDays int
	OtpTTLMin      int
	OtpCooldownSec int
	OtpMaxAttempts int

	WAToken  string
	WASecret string
}

func NewAuthHandler(
	users *repo.UserRepo,
	adminUsers *repo.AdminUserRepo,
	otps *repo.OTPRepo,
	refreshTokens *repo.RefreshTokenRepo,
	mailer *mailer.Mailer,
	secret string,
	jwtTTL, refreshTTLDays, otpTTLMin, otpCooldownSec, otpMaxAttempts int,
	waToken, waSecret string,
) *AuthHandler {
	if refreshTTLDays <= 0 {
		refreshTTLDays = 90
	}

	return &AuthHandler{
		Users:          users,
		AdminUsers:     adminUsers,
		OTPS:           otps,
		RefreshTokens:  refreshTokens,
		Mailer:         mailer,
		JWTSecret:      secret,
		JWTTtlMin:      jwtTTL,
		RefreshTTLDays: refreshTTLDays,
		OtpTTLMin:      otpTTLMin,
		OtpCooldownSec: otpCooldownSec,
		OtpMaxAttempts: otpMaxAttempts,
		WAToken:        waToken,
		WASecret:       waSecret,
	}
}

func generateRefreshTokenString() (string, error) {
	b := make([]byte, 48)
	if _, err := rand.Read(b); err != nil {
		return "", err
	}
	return base64.RawURLEncoding.EncodeToString(b), nil
}

func (h *AuthHandler) buildExtraClaims(u *domain.User) map[string]any {
	mitrafk := 0
	if u.MitraFk != nil {
		mitrafk = int(*u.MitraFk)
	}

	isUserBaru := false
	if u.IsUserBaru != nil {
		isUserBaru = *u.IsUserBaru
	}

	var isEksternal any
	if u.IsEksternal != nil {
		isEksternal = *u.IsEksternal
	}

	return map[string]any{
		"email":        u.Email,
		"mitrafk":      mitrafk,
		"isuserbaru":   isUserBaru,
		"iseksternal":  isEksternal,
		"role":         "customer",
		"account_type": "customer",
		"kelompokuser": "customer",
	}
}

func (h *AuthHandler) issueAccessAndRefreshToken(u *domain.User) (string, string, error) {
	extra := h.buildExtraClaims(u)

	accessToken, err := util.GenerateJWT(h.JWTSecret, u.ID, h.JWTTtlMin, extra)
	if err != nil {
		return "", "", err
	}

	refreshToken, err := generateRefreshTokenString()
	if err != nil {
		return "", "", err
	}

	rt := &domain.RefreshToken{
		UserID:      u.ID,
		AccountType: "customer",
		Token:       refreshToken,
		ExpiresAt:   time.Now().Add(time.Duration(h.RefreshTTLDays) * 24 * time.Hour),
	}

	if err := h.RefreshTokens.Create(rt); err != nil {
		return "", "", err
	}

	return accessToken, refreshToken, nil
}

func (h *AuthHandler) buildAdminExtraClaims(u *repo.AdminUser) map[string]any {
	var pegawaiID any
	if u.PegawaiID != nil {
		pegawaiID = *u.PegawaiID
	}

	var lokasiKalibrasiFK any
	if u.LokasiKalibrasiFK != nil {
		lokasiKalibrasiFK = *u.LokasiKalibrasiFK
	}

	return map[string]any{
		"role":              "admin",
		"account_type":      "admin",
		"username":          u.Username,
		"name":              u.NamaLengkap,
		"email":             "",
		"mitrafk":           0,
		"isuserbaru":        false,
		"kelompokuser":      u.KelompokUser,
		"kelompokuser_id":   u.KelompokUserID,
		"pegawaifk":         pegawaiID,
		"lokasikalibrasifk": lokasiKalibrasiFK,
	}
}

func (h *AuthHandler) issueAccessAndRefreshTokenForAdmin(u *repo.AdminUser) (string, string, error) {
	extra := h.buildAdminExtraClaims(u)

	accessToken, err := util.GenerateJWT(h.JWTSecret, u.ID, h.JWTTtlMin, extra)
	if err != nil {
		return "", "", err
	}

	refreshToken, err := generateRefreshTokenString()
	if err != nil {
		return "", "", err
	}

	rt := &domain.RefreshToken{
		UserID:      u.ID,
		AccountType: "admin",
		Token:       refreshToken,
		ExpiresAt:   time.Now().Add(time.Duration(h.RefreshTTLDays) * 24 * time.Hour),
	}

	if err := h.RefreshTokens.Create(rt); err != nil {
		return "", "", err
	}

	return accessToken, refreshToken, nil
}

type registerDTO struct {
	Name     string `json:"name" binding:"required,min=2"`
	Email    string `json:"email" binding:"required,email"`
	Password string `json:"password" binding:"required,min=6"`
	NoWa     string `json:"nowa" binding:"required"`
	Channel  string `json:"channel"`
}

func (h *AuthHandler) Register(c *gin.Context) {
	var in registerDTO
	if err := c.ShouldBindJSON(&in); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid payload", "detail": err.Error()})
		return
	}

	in.Name = strings.TrimSpace(in.Name)
	in.Email = strings.ToLower(strings.TrimSpace(in.Email))
	in.NoWa = normalizePhone(in.NoWa)
	in.Channel = normalizeOTPChannel(in.Channel)

	if !isValidWhatsAppNumber(in.NoWa) {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid whatsapp number; enter the number after country code 62 starting with 8"})
		return
	}

	if u, err := h.Users.FindByEmail(in.Email); err == nil {
		if u.EmailVerifiedAt == nil {
			if err := h.sendOTP(u.ID, u.Email, in.NoWa, u.Name, "register", in.Channel); err != nil {
				log.Printf("sendOTP error existing-user: %v (user id=%d)", err, u.ID)

				if err.Error() == "too_soon" {
					c.JSON(http.StatusTooManyRequests, gin.H{"error": "please wait before requesting again"})
					return
				}

				c.JSON(http.StatusInternalServerError, gin.H{"error": "failed to send otp via " + in.Channel})
				return
			}

			c.JSON(http.StatusOK, gin.H{
				"message": "email already registered but not verified; OTP resent via " + in.Channel,
				"email":   u.Email,
				"nowa":    in.NoWa,
				"channel": in.Channel,
			})
			return
		}

		c.JSON(http.StatusConflict, gin.H{"error": "email already exists"})
		return
	} else if !errors.Is(err, gorm.ErrRecordNotFound) {
		log.Printf("FindByEmail error: %v", err)
		c.JSON(http.StatusInternalServerError, gin.H{"error": "server error"})
		return
	}

	hash, err := bcrypt.GenerateFromPassword([]byte(in.Password), bcrypt.DefaultCost)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "hashing failed"})
		return
	}

	se := true
	kp := int16(1)
	nb := true

	u := &domain.User{
		Name:          in.Name,
		Email:         in.Email,
		Password:      string(hash),
		StatusEnabled: &se,
		KdProfile:     &kp,
		IsUserBaru:    &nb,
		NoWa:          &in.NoWa,
	}

	if err := h.Users.Create(u); err != nil {
		var pgErr *pgconn.PgError
		if errors.As(err, &pgErr) && pgErr.Code == "23505" && isEmailDuplicateConstraint(pgErr) {
			c.JSON(http.StatusConflict, gin.H{"error": "email already exists"})
			return
		}
		log.Printf("Users.Create error: %v", err)
		c.JSON(http.StatusBadRequest, gin.H{"error": "could not create user"})
		return
	}

	if err := h.sendOTP(u.ID, u.Email, in.NoWa, u.Name, "register", in.Channel); err != nil {
		log.Printf("sendOTP error new-user: %v (rollback user id=%d)", err, u.ID)
		_ = h.Users.DeleteByID(u.ID)

		if err.Error() == "too_soon" {
			c.JSON(http.StatusTooManyRequests, gin.H{"error": "please wait before requesting again"})
			return
		}

		c.JSON(http.StatusInternalServerError, gin.H{"error": "failed to send otp via " + in.Channel})
		return
	}

	c.JSON(http.StatusCreated, gin.H{
		"message": "registered. OTP sent via " + in.Channel,
		"email":   u.Email,
		"nowa":    in.NoWa,
		"channel": in.Channel,
	})
}

func isEmailDuplicateConstraint(pgErr *pgconn.PgError) bool {
	constraint := strings.ToLower(strings.TrimSpace(pgErr.ConstraintName))
	detail := strings.ToLower(pgErr.Detail)

	return strings.Contains(constraint, "email") || strings.Contains(detail, "(email)")
}

func normalizeOTPChannel(channel string) string {
	if strings.ToLower(strings.TrimSpace(channel)) == "wa" {
		return "wa"
	}

	return "email"
}

func otpCodesMatch(expected, actual string) bool {
	return subtle.ConstantTimeCompare([]byte(expected), []byte(actual)) == 1
}

func (h *AuthHandler) sendOTP(userID uint, email, noWa, name, purpose, channel string) error {
	latest, err := h.OTPS.Latest(email, purpose)
	if err == nil && time.Since(latest.CreatedAt).Seconds() < float64(h.OtpCooldownSec) {
		return errors.New("too_soon")
	}

	code, err := util.Random6()
	if err != nil {
		return err
	}

	o := &domain.EmailOTP{
		UserID:    userID,
		Email:     email,
		Code:      code,
		Purpose:   purpose,
		ExpiresAt: time.Now().Add(time.Duration(h.OtpTTLMin) * time.Minute),
	}

	if err := h.OTPS.Create(o); err != nil {
		return err
	}

	if normalizeOTPChannel(channel) == "wa" {
		return h.sendWhatsAppOTP(noWa, code, purpose, h.OtpTTLMin)
	}

	if h.Mailer == nil {
		return errors.New("email service is unavailable")
	}

	return h.Mailer.SendOTP(email, name, code, purpose, h.OtpTTLMin)
}

func (h *AuthHandler) sendWhatsAppOTP(noWa, code, purpose string, expiresMinutes int) error {
	noWa = normalizePhone(noWa)
	if noWa == "" {
		return errors.New("invalid whatsapp number")
	}

	if strings.TrimSpace(h.WAToken) == "" || strings.TrimSpace(h.WASecret) == "" {
		return errors.New("whatsapp gateway config is missing")
	}

	purposeLabel := strings.ToUpper(strings.TrimSpace(purpose))
	spacedCode := formatOTPCode(code)

	msg := fmt.Sprintf(
		"🔐 U-LAB — Kode OTP %s\nKode: %s\nBerlaku %d menit.\n\nJangan bagikan kode ini kepada siapa pun.\nJika Anda tidak merasa meminta kode ini, abaikan pesan ini.",
		purposeLabel,
		spacedCode,
		expiresMinutes,
	)

	fullToken := fmt.Sprintf("%s.%s", h.WAToken, h.WASecret)
	endpoint := fmt.Sprintf(
		"https://sby.wablas.com/api/send-message?token=%s&phone=%s&message=%s",
		url.QueryEscape(fullToken),
		url.QueryEscape(noWa),
		url.QueryEscape(msg),
	)

	resp, err := http.Get(endpoint)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	var result map[string]any
	if err := json.NewDecoder(resp.Body).Decode(&result); err != nil {
		return fmt.Errorf("failed to decode wa response: %w", err)
	}

	statusVal, ok := result["status"]
	if !ok {
		return fmt.Errorf("invalid wa response: %v", result)
	}

	success := false
	switch v := statusVal.(type) {
	case bool:
		success = v
	case string:
		success = strings.ToLower(v) == "true" || strings.ToLower(v) == "success"
	case float64:
		success = v == 1
	}

	if !success {
		msgResp := "unknown error"
		if m, ok := result["message"].(string); ok && m != "" {
			msgResp = m
		}
		return fmt.Errorf("failed to send whatsapp otp: %s", msgResp)
	}

	return nil
}

func normalizePhone(phone string) string {
	phone = strings.TrimSpace(phone)
	phone = strings.ReplaceAll(phone, " ", "")
	phone = strings.ReplaceAll(phone, "-", "")
	phone = strings.ReplaceAll(phone, "+", "")

	var b strings.Builder
	for _, r := range phone {
		if r >= '0' && r <= '9' {
			b.WriteRune(r)
		}
	}
	phone = b.String()

	if phone == "" {
		return ""
	}

	if strings.HasPrefix(phone, "62") {
		return phone
	}

	if strings.HasPrefix(phone, "0") {
		return "62" + phone[1:]
	}

	if strings.HasPrefix(phone, "8") {
		return "62" + phone
	}

	return phone
}

func isValidWhatsAppNumber(phone string) bool {
	if len(phone) < 11 || len(phone) > 15 || !strings.HasPrefix(phone, "628") {
		return false
	}
	for _, r := range phone {
		if r < '0' || r > '9' {
			return false
		}
	}
	return true
}

func formatOTPCode(code string) string {
	if len(code) != 6 {
		return code
	}
	return code[:3] + " " + code[3:]
}

func accountTypeFromContext(c *gin.Context) string {
	rawClaims, ok := c.Get("claims")
	if !ok {
		return "customer"
	}

	claims, ok := rawClaims.(jwt.MapClaims)
	if !ok {
		return "customer"
	}

	accountType := strings.ToLower(strings.TrimSpace(fmt.Sprint(claims["account_type"])))
	if accountType == "" || accountType == "<nil>" {
		accountType = strings.ToLower(strings.TrimSpace(fmt.Sprint(claims["role"])))
	}
	if accountType == "admin" {
		return "admin"
	}

	return "customer"
}

type resendOTPDTO struct {
	Email   string `json:"email" binding:"required,email"`
	Channel string `json:"channel"`
}

func (h *AuthHandler) ResendOTP(c *gin.Context) {
	var in resendOTPDTO
	if err := c.ShouldBindJSON(&in); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid payload"})
		return
	}

	in.Email = strings.ToLower(strings.TrimSpace(in.Email))
	in.Channel = normalizeOTPChannel(in.Channel)

	u, err := h.Users.FindByEmail(in.Email)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "user not found"})
		return
	}

	if in.Channel == "wa" && (u.NoWa == nil || strings.TrimSpace(*u.NoWa) == "") {
		c.JSON(http.StatusBadRequest, gin.H{"error": "whatsapp number not found"})
		return
	}

	noWa := ""
	if u.NoWa != nil {
		noWa = *u.NoWa
	}
	if err := h.sendOTP(u.ID, u.Email, noWa, u.Name, "register", in.Channel); err != nil {
		if err.Error() == "too_soon" {
			c.JSON(http.StatusTooManyRequests, gin.H{"error": "please wait before requesting again"})
			return
		}
		c.JSON(http.StatusInternalServerError, gin.H{"error": "failed to send otp via " + in.Channel})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "OTP resent via " + in.Channel,
		"email":   u.Email,
		"channel": in.Channel,
	})
}

type verifyOTPDTO struct {
	Email string `json:"email" binding:"required,email"`
	Code  string `json:"code" binding:"required,len=6"`
}

func (h *AuthHandler) VerifyOTP(c *gin.Context) {
	var in verifyOTPDTO
	if err := c.ShouldBindJSON(&in); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid payload"})
		return
	}

	in.Email = strings.ToLower(strings.TrimSpace(in.Email))

	u, err := h.Users.FindByEmail(in.Email)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "user not found"})
		return
	}

	now := time.Now()
	o, err := h.OTPS.LatestActive(in.Email, "register", now)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid or expired code"})
		return
	}

	if o.Attempts >= h.OtpMaxAttempts {
		c.JSON(http.StatusTooManyRequests, gin.H{"error": "too many attempts"})
		return
	}

	if !otpCodesMatch(o.Code, in.Code) {
		_ = h.OTPS.IncAttempt(o.ID)
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid or expired code"})
		return
	}

	if err := h.OTPS.Consume(o.ID); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "server error"})
		return
	}

	if err := h.Users.SetEmailVerified(u.ID); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "server error"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "account verified"})
}

type loginDTO struct {
	Email    string `json:"email" binding:"required"`
	Password string `json:"password" binding:"required"`
}

func (h *AuthHandler) Login(c *gin.Context) {
	var in loginDTO
	if err := c.ShouldBindJSON(&in); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid payload", "detail": err.Error()})
		return
	}

	in.Email = strings.ToLower(strings.TrimSpace(in.Email))

	u, err := h.Users.FindByEmail(in.Email)
	if err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			h.loginAdmin(c, in.Email, in.Password)
			return
		}
		c.JSON(http.StatusInternalServerError, gin.H{"error": "server error"})
		return
	}

	if u.EmailVerifiedAt == nil {
		c.JSON(http.StatusForbidden, gin.H{"error": "email not verified"})
		return
	}

	if !util.CheckPassword(u.Password, in.Password) {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "invalid password"})
		return
	}

	mitrafk := 0
	if u.MitraFk != nil {
		mitrafk = int(*u.MitraFk)
	}

	isUserBaru := false
	if u.IsUserBaru != nil {
		isUserBaru = *u.IsUserBaru
	}

	accessToken, refreshToken, err := h.issueAccessAndRefreshToken(u)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "token generation failed"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"token":         accessToken,
		"access_token":  accessToken,
		"refresh_token": refreshToken,
		"user": gin.H{
			"id":      u.ID,
			"name":    u.Name,
			"email":   u.Email,
			"mitrafk": mitrafk,
			"jabatan": func() string {
				if u.Jabatan != nil {
					return *u.Jabatan
				}
				return ""
			}(),
			"isuserbaru":   isUserBaru,
			"role":         "customer",
			"kelompokuser": "customer",
		},
	})
}

func (h *AuthHandler) loginAdmin(c *gin.Context, username, password string) {
	admin, err := h.AdminUsers.FindByLogin(c.Request.Context(), username)
	if err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "Email or username not registered"})
			return
		}
		c.JSON(http.StatusInternalServerError, gin.H{"error": "server error"})
		return
	}

	if !admin.IsRegistrationAdmin() {
		c.JSON(http.StatusForbidden, gin.H{"error": "admin group is not allowed for mobile registration app"})
		return
	}

	if !util.CheckPassword(admin.Password, password) {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "invalid password"})
		return
	}

	accessToken, refreshToken, err := h.issueAccessAndRefreshTokenForAdmin(admin)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "token generation failed"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"token":         accessToken,
		"access_token":  accessToken,
		"refresh_token": refreshToken,
		"role":          "admin",
		"kelompokuser":  admin.KelompokUser,
		"user": gin.H{
			"id":                admin.ID,
			"name":              admin.NamaLengkap,
			"email":             "",
			"username":          admin.Username,
			"mitrafk":           0,
			"jabatan":           "Admin Registrasi",
			"isuserbaru":        false,
			"role":              "admin",
			"kelompokuser":      admin.KelompokUser,
			"kelompokuser_id":   admin.KelompokUserID,
			"pegawaifk":         admin.PegawaiID,
			"lokasikalibrasifk": admin.LokasiKalibrasiFK,
			"nohp":              admin.NoHP,
			"nowa":              admin.NoHP,
			"unit":              admin.Unit,
			"fotopegawai":       admin.FotoPegawai,
			"foto_url":          admin.FotoURL,
		},
	})
}

type refreshTokenDTO struct {
	RefreshToken string `json:"refresh_token" binding:"required"`
}

func (h *AuthHandler) Refresh(c *gin.Context) {
	var in refreshTokenDTO
	if err := c.ShouldBindJSON(&in); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid payload"})
		return
	}

	in.RefreshToken = strings.TrimSpace(in.RefreshToken)
	now := time.Now()

	rt, err := h.RefreshTokens.FindValid(in.RefreshToken, now)
	if err != nil {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "invalid refresh token"})
		return
	}

	if rt.AccountType == "admin" {
		admin, err := h.AdminUsers.FindByID(c.Request.Context(), rt.UserID)
		if err != nil {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "admin user not found"})
			return
		}

		_ = h.RefreshTokens.RevokeByToken(in.RefreshToken)

		accessToken, newRefreshToken, err := h.issueAccessAndRefreshTokenForAdmin(admin)
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "failed to refresh token"})
			return
		}

		c.JSON(http.StatusOK, gin.H{
			"token":         accessToken,
			"access_token":  accessToken,
			"refresh_token": newRefreshToken,
			"role":          "admin",
			"kelompokuser":  admin.KelompokUser,
		})
		return
	}

	u, err := h.Users.FindByID(rt.UserID)
	if err != nil {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "user not found"})
		return
	}

	_ = h.RefreshTokens.RevokeByToken(in.RefreshToken)

	accessToken, newRefreshToken, err := h.issueAccessAndRefreshToken(u)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "failed to refresh token"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"token":         accessToken,
		"access_token":  accessToken,
		"refresh_token": newRefreshToken,
		"role":          "customer",
		"kelompokuser":  "customer",
	})
}

func (h *AuthHandler) Logout(c *gin.Context) {
	uid := c.MustGet("uid").(uint)
	accountType := accountTypeFromContext(c)

	if err := h.RefreshTokens.RevokeAllByUserAndAccount(uid, accountType); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "failed to logout"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "logout success"})
}

func (h *AuthHandler) Me(c *gin.Context) {
	uid := c.MustGet("uid").(uint)

	if accountTypeFromContext(c) == "admin" {
		admin, err := h.AdminUsers.FindByID(c.Request.Context(), uid)
		if err != nil {
			c.JSON(http.StatusNotFound, gin.H{"error": "admin user not found"})
			return
		}

		c.JSON(http.StatusOK, gin.H{
			"id":                admin.ID,
			"name":              admin.NamaLengkap,
			"email":             "",
			"username":          admin.Username,
			"isuserbaru":        false,
			"mitrafk":           0,
			"jabatan":           "Admin Registrasi",
			"iseksternal":       nil,
			"role":              "admin",
			"kelompokuser":      admin.KelompokUser,
			"kelompokuser_id":   admin.KelompokUserID,
			"pegawaifk":         admin.PegawaiID,
			"lokasikalibrasifk": admin.LokasiKalibrasiFK,
			"fotopegawai":       admin.FotoPegawai,
			"nohp":              admin.NoHP,
			"nowa":              admin.NoHP,
			"unit":              admin.Unit,
			"foto_url":          admin.FotoURL,
		})
		return
	}

	u, err := h.Users.FindByID(uid)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "user not found"})
		return
	}

	isUserBaru := false
	if u.IsUserBaru != nil {
		isUserBaru = *u.IsUserBaru
	}

	mitrafk := 0
	if u.MitraFk != nil {
		mitrafk = int(*u.MitraFk)
	}

	jabatan := ""
	if u.Jabatan != nil {
		jabatan = *u.Jabatan
	}

	var isEksternal any
	if u.IsEksternal != nil {
		isEksternal = *u.IsEksternal
	}

	c.JSON(http.StatusOK, gin.H{
		"id":           u.ID,
		"name":         u.Name,
		"email":        u.Email,
		"isuserbaru":   isUserBaru,
		"mitrafk":      mitrafk,
		"jabatan":      jabatan,
		"iseksternal":  isEksternal,
		"role":         "customer",
		"kelompokuser": "customer",
	})
}

type forgotPasswordRequestDTO struct {
	Email   string `json:"email" binding:"required,email"`
	Channel string `json:"channel"`
}

func (h *AuthHandler) ForgotPasswordRequest(c *gin.Context) {
	var in forgotPasswordRequestDTO
	if err := c.ShouldBindJSON(&in); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid payload"})
		return
	}

	in.Email = strings.ToLower(strings.TrimSpace(in.Email))
	in.Channel = normalizeOTPChannel(in.Channel)

	u, err := h.Users.FindByEmail(in.Email)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "user not found"})
		return
	}

	if in.Channel == "wa" && (u.NoWa == nil || strings.TrimSpace(*u.NoWa) == "") {
		c.JSON(http.StatusBadRequest, gin.H{"error": "whatsapp number not found"})
		return
	}

	noWa := ""
	if u.NoWa != nil {
		noWa = *u.NoWa
	}
	if err := h.sendOTP(u.ID, u.Email, noWa, u.Name, "forgot_password", in.Channel); err != nil {
		if err.Error() == "too_soon" {
			c.JSON(http.StatusTooManyRequests, gin.H{"error": "please wait before requesting again"})
			return
		}
		c.JSON(http.StatusInternalServerError, gin.H{"error": "failed to send otp via " + in.Channel})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "OTP sent via " + in.Channel,
		"email":   u.Email,
		"channel": in.Channel,
	})
}

type forgotPasswordVerifyDTO struct {
	Email string `json:"email" binding:"required,email"`
	Code  string `json:"code" binding:"required,len=6"`
}

func (h *AuthHandler) ForgotPasswordVerify(c *gin.Context) {
	var in forgotPasswordVerifyDTO
	if err := c.ShouldBindJSON(&in); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid payload"})
		return
	}

	in.Email = strings.ToLower(strings.TrimSpace(in.Email))
	in.Code = strings.TrimSpace(in.Code)

	u, err := h.Users.FindByEmail(in.Email)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "user not found"})
		return
	}

	now := time.Now()
	o, err := h.OTPS.LatestActive(in.Email, "forgot_password", now)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid or expired code"})
		return
	}

	if o.Attempts >= h.OtpMaxAttempts {
		c.JSON(http.StatusTooManyRequests, gin.H{"error": "too many attempts"})
		return
	}

	if !otpCodesMatch(o.Code, in.Code) {
		_ = h.OTPS.IncAttempt(o.ID)
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid or expired code"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "OTP valid",
		"email":   u.Email,
	})
}

type forgotPasswordResetDTO struct {
	Email       string `json:"email" binding:"required,email"`
	Code        string `json:"code" binding:"required,len=6"`
	NewPassword string `json:"new_password" binding:"required,min=6"`
}

func (h *AuthHandler) ForgotPasswordReset(c *gin.Context) {
	var in forgotPasswordResetDTO
	if err := c.ShouldBindJSON(&in); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid payload"})
		return
	}

	in.Email = strings.ToLower(strings.TrimSpace(in.Email))
	in.Code = strings.TrimSpace(in.Code)
	in.NewPassword = strings.TrimSpace(in.NewPassword)

	u, err := h.Users.FindByEmail(in.Email)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "user not found"})
		return
	}

	now := time.Now()
	o, err := h.OTPS.LatestActive(in.Email, "forgot_password", now)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid or expired code"})
		return
	}

	if o.Attempts >= h.OtpMaxAttempts {
		c.JSON(http.StatusTooManyRequests, gin.H{"error": "too many attempts"})
		return
	}

	if !otpCodesMatch(o.Code, in.Code) {
		_ = h.OTPS.IncAttempt(o.ID)
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid or expired code"})
		return
	}

	hash, err := bcrypt.GenerateFromPassword([]byte(in.NewPassword), bcrypt.DefaultCost)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "hashing failed"})
		return
	}

	if err := h.Users.SetPassword(u.ID, string(hash)); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "failed to update password"})
		return
	}

	if err := h.OTPS.Consume(o.ID); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "failed to consume otp"})
		return
	}

	if err := h.RefreshTokens.RevokeAllByUser(u.ID); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "failed to revoke old sessions"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"message": "password updated successfully",
	})
}
