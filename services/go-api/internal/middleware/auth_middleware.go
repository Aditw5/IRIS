package middleware

import (
	"net/http"
	"strconv"
	"strings"

	"github.com/gin-gonic/gin"
	"github.com/golang-jwt/jwt/v5"
)

func Auth(secret string) gin.HandlerFunc {
	keyFn := func(token *jwt.Token) (interface{}, error) {
		if _, ok := token.Method.(*jwt.SigningMethodHMAC); !ok {
			return nil, jwt.ErrSignatureInvalid
		}
		return []byte(secret), nil
	}

	return func(c *gin.Context) {
		// 1) Bearer token
		h := c.GetHeader("Authorization")
		if h == "" || !strings.HasPrefix(h, "Bearer ") {
			c.JSON(http.StatusUnauthorized, gin.H{"status": 401, "message": "missing bearer token"})
			c.Abort()
			return
		}
		raw := strings.TrimSpace(strings.TrimPrefix(h, "Bearer "))

		// 2) Parse & verify
		tok, err := jwt.Parse(raw, keyFn)
		if err != nil || !tok.Valid {
			c.JSON(http.StatusUnauthorized, gin.H{"status": 401, "message": "invalid token"})
			c.Abort()
			return
		}

		claims, ok := tok.Claims.(jwt.MapClaims)
		if !ok {
			c.JSON(http.StatusUnauthorized, gin.H{"status": 401, "message": "invalid claims"})
			c.Abort()
			return
		}

		// 3) Ambil uid, set ke context (dua nama: "uid" dan "user_id")
		var haveUID bool
		if v, ok := claims["uid"]; ok && v != nil {
			switch x := v.(type) {
			case float64:
				c.Set("uid", uint(x))
				c.Set("user_id", int(x))
				haveUID = true
			case int:
				c.Set("uid", uint(x))
				c.Set("user_id", x)
				haveUID = true
			case int64:
				c.Set("uid", uint(x))
				c.Set("user_id", int(x))
				haveUID = true
			case string:
				if i, err := strconv.Atoi(x); err == nil {
					c.Set("uid", uint(i))
					c.Set("user_id", i)
					haveUID = true
				}
			}
		}
		if !haveUID {
			c.JSON(http.StatusUnauthorized, gin.H{"status": 401, "message": "uid claim missing"})
			c.Abort()
			return
		}

		// 4) Opsional: turunkan mitrafk
		if v, ok := claims["mitrafk"]; ok {
			c.Set("mitrafk", v)
		}

		// 5) Simpan semua claims
		c.Set("claims", claims)

		c.Next()
	}
}
