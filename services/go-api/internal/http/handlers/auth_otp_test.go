package handlers

import "testing"

func TestNormalizeOTPChannel(t *testing.T) {
	tests := []struct {
		input string
		want  string
	}{
		{input: "", want: "email"},
		{input: "email", want: "email"},
		{input: "EMAIL", want: "email"},
		{input: "wa", want: "wa"},
		{input: " WA ", want: "wa"},
		{input: "invalid", want: "email"},
	}

	for _, tt := range tests {
		if got := normalizeOTPChannel(tt.input); got != tt.want {
			t.Fatalf("normalizeOTPChannel(%q) = %q, want %q", tt.input, got, tt.want)
		}
	}
}

func TestOTPCodesMatch(t *testing.T) {
	if !otpCodesMatch("123456", "123456") {
		t.Fatal("matching OTP codes must be accepted")
	}
	if otpCodesMatch("123456", "123457") {
		t.Fatal("different OTP codes must be rejected")
	}
	if otpCodesMatch("123456", "12345") {
		t.Fatal("OTP codes with different lengths must be rejected")
	}
}

func TestNormalizePhone(t *testing.T) {
	tests := map[string]string{
		"0812-1000-0284":    "6281210000284",
		"81210000284":       "6281210000284",
		"+62 812-1000-0284": "6281210000284",
	}

	for input, want := range tests {
		if got := normalizePhone(input); got != want {
			t.Errorf("normalizePhone(%q) = %q, want %q", input, got, want)
		}
	}
}

func TestIsValidWhatsAppNumber(t *testing.T) {
	for _, valid := range []string{"6281210000284", "62812345678"} {
		if !isValidWhatsAppNumber(valid) {
			t.Errorf("expected %q to be valid", valid)
		}
	}
	for _, invalid := range []string{"", "81210000284", "62081210000284", "628123"} {
		if isValidWhatsAppNumber(invalid) {
			t.Errorf("expected %q to be invalid", invalid)
		}
	}
}
