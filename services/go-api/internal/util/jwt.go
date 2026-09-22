package util

import (
	"time"

	"github.com/golang-jwt/jwt/v5"
)

// GenerateJWT membuat token dengan claim wajib: uid, iat, exp
// plus claim tambahan dari parameter extra (mis. email, mitrafk, isuserbaru).
func GenerateJWT(secret string, uid uint, ttlMin int, extra map[string]any) (string, error) {
	claims := jwt.MapClaims{
		"uid": uid,
		"iat": time.Now().Unix(),
		"exp": time.Now().Add(time.Duration(ttlMin) * time.Minute).Unix(),
	}
	for k, v := range extra {
		claims[k] = v
	}
	token := jwt.NewWithClaims(jwt.SigningMethodHS256, claims)
	return token.SignedString([]byte(secret))
}
