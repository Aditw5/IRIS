package push

import "testing"

func TestBuildAdminRegistrationMessage(t *testing.T) {
	event := NewRegistration{
		RegistrationIDs: []string{"reg-1", "reg-2"},
		CustomerName:    "PT Contoh",
		ToolCount:       3,
		LocationID:      2,
		OrderTime:       "13 Juli 2026 10:00",
	}

	message := buildAdminRegistrationMessage([]string{"token-a"}, event)

	if len(message.Tokens) != 1 || message.Tokens[0] != "token-a" {
		t.Fatalf("target token tidak sesuai: %#v", message.Tokens)
	}
	if message.Notification == nil || message.Notification.Title != "Pendaftaran alat baru" {
		t.Fatalf("notification payload tidak lengkap: %#v", message.Notification)
	}
	if message.Data["screen"] != "admin_notifications" {
		t.Fatalf("target screen salah: %q", message.Data["screen"])
	}
	if message.Data["norec_registrasi"] != "reg-1,reg-2" {
		t.Fatalf("norec registrasi salah: %q", message.Data["norec_registrasi"])
	}
	if message.Android == nil || message.Android.Priority != "high" {
		t.Fatalf("konfigurasi Android tidak high priority: %#v", message.Android)
	}
}

func TestRegistrationIDsStringIgnoresEmptyValues(t *testing.T) {
	event := NewRegistration{RegistrationIDs: []string{" reg-1 ", "", "reg-2"}}
	if got := event.RegistrationIDsString(); got != "reg-1,reg-2" {
		t.Fatalf("RegistrationIDsString() = %q", got)
	}
}
