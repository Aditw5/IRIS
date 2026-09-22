package util

import (
	"crypto/sha1"
	"encoding/hex"
	"strings"

	"github.com/alexedwards/argon2id"
	"golang.org/x/crypto/bcrypt"
)

// CheckPassword membandingkan plaintext dengan hash Laravel.
// Mendukung:
// - bcrypt:   $2y$, $2b$, $2a$
// - argon2:   $argon2id$, $argon2i$
func CheckPassword(hashed, plain string) bool {
	hashed = strings.TrimSpace(hashed)

	// Argon2 (Laravel >=5.8 jika dikonfigurasi argon2)
	if strings.HasPrefix(hashed, "$argon2id$") || strings.HasPrefix(hashed, "$argon2i$") {
		ok, err := argon2id.ComparePasswordAndHash(plain, hashed)
		return err == nil && ok
	}

	// Bcrypt (default Laravel lama / banyak instalasi)
	if strings.HasPrefix(hashed, "$2y$") || strings.HasPrefix(hashed, "$2b$") || strings.HasPrefix(hashed, "$2a$") {
		return bcrypt.CompareHashAndPassword([]byte(hashed), []byte(plain)) == nil
	}

	if len(hashed) == 40 {
		sum := sha1.Sum([]byte(plain))
		return strings.EqualFold(hex.EncodeToString(sum[:]), hashed)
	}

	// Fallback terakhir: coba bcrypt
	return bcrypt.CompareHashAndPassword([]byte(hashed), []byte(plain)) == nil
}
