package mailer

import (
	"strings"
	"testing"
)

func TestBuildOTPEmailRegistration(t *testing.T) {
	subject, plainBody, htmlBody := buildOTPEmail("Adit <Test>", "503753", "register", 10)

	if subject != "Kode OTP Pendaftaran U-LAB" {
		t.Fatalf("unexpected subject: %q", subject)
	}
	for _, expected := range []string{
		"503 753",
		"https://www.ulabumro.id",
		"© 2026 UMRO Laboratory. All rights reserved.",
	} {
		if !strings.Contains(plainBody, expected) {
			t.Errorf("plain body does not contain %q", expected)
		}
	}
	for _, expected := range []string{
		"https://www.ulabumro.id/UMRO.png",
		"UMRO LABORATORY",
		"503 753",
		"&lt;Test&gt;",
		"&copy; 2026 UMRO Laboratory. All rights reserved.",
	} {
		if !strings.Contains(htmlBody, expected) {
			t.Errorf("HTML body does not contain %q", expected)
		}
	}
	if strings.Contains(htmlBody, "Adit <Test>") {
		t.Fatal("recipient name must be HTML-escaped")
	}
}

func TestBuildOTPEmailForgotPassword(t *testing.T) {
	subject, _, htmlBody := buildOTPEmail("", "123456", "forgot_password", 5)

	if subject != "Kode OTP Reset Password U-LAB" {
		t.Fatalf("unexpected subject: %q", subject)
	}
	for _, expected := range []string{"Reset Password", "Pengguna U-LAB", "123 456", "5 menit"} {
		if !strings.Contains(htmlBody, expected) {
			t.Errorf("HTML body does not contain %q", expected)
		}
	}
}

func TestBuildOTPEmailUsesSafeDefaults(t *testing.T) {
	_, plainBody, _ := buildOTPEmail("", "ABC", "register", 0)
	if !strings.Contains(plainBody, "ABC") || !strings.Contains(plainBody, "10 menit") {
		t.Fatalf("safe defaults were not applied: %q", plainBody)
	}
}
