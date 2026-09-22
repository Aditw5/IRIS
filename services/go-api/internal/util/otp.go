package util

import (
	"crypto/rand"
)

func Random6() (string, error) {
	// 6 digit numeric
	const n = 6
	b := make([]byte, n)
	if _, err := rand.Read(b); err != nil {
		return "", err
	}
	// map ke 0-9
	for i := 0; i < n; i++ {
		b[i] = '0' + (b[i] % 10)
	}
	return string(b), nil
}
