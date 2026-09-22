package push

import (
	"context"
	"os"
	"strings"
	"testing"
	"time"

	firebase "firebase.google.com/go/v4"
	"github.com/ulab/ulab-go-api/internal/config"
	"github.com/ulab/ulab-go-api/internal/db"
	"google.golang.org/api/option"
)

// TestFirebaseAdminNotifierDryRun sengaja opt-in karena memakai database dan
// kredensial Firebase nyata. Dry-run memvalidasi target tanpa mengirim pesan.
func TestFirebaseAdminNotifierDryRun(t *testing.T) {
	if os.Getenv("FCM_INTEGRATION_TEST") != "1" {
		t.Skip("set FCM_INTEGRATION_TEST=1 untuk validasi Firebase nyata")
	}

	cfg := config.Load()
	database := db.Connect(cfg.PostgresDSN())
	notifier, err := NewFirebaseAdminNotifier(
		context.Background(),
		database,
		cfg.Firebase.ProjectID,
		cfg.Firebase.CredentialsFile,
	)
	if err != nil {
		t.Fatalf("inisialisasi notifier gagal: %v", err)
	}

	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	defer cancel()
	err = notifier.ValidateNewRegistration(ctx, NewRegistration{
		RegistrationIDs: []string{"dry-run-admin-notification"},
		CustomerName:    "FCM Integration Test",
		ToolCount:       1,
		LocationID:      1,
		OrderTime:       time.Now().Format(time.RFC3339),
	})
	if err != nil {
		t.Fatalf("dry-run FCM admin gagal: %v", err)
	}
}

// TestFirebaseAdminMessageDryRun memvalidasi kredensial, payload, dan satu
// registration token nyata tanpa membutuhkan koneksi database serta tanpa
// menampilkan token ke log pengujian.
func TestFirebaseAdminMessageDryRun(t *testing.T) {
	if os.Getenv("FCM_INTEGRATION_TEST") != "1" {
		t.Skip("set FCM_INTEGRATION_TEST=1 untuk validasi Firebase nyata")
	}

	projectID := strings.TrimSpace(os.Getenv("FIREBASE_PROJECT_ID"))
	credentialsFile := strings.TrimSpace(os.Getenv("FIREBASE_CREDENTIALS_FILE"))
	token := strings.TrimSpace(os.Getenv("FCM_TEST_TOKEN"))
	if projectID == "" || credentialsFile == "" || token == "" {
		t.Fatal("FIREBASE_PROJECT_ID, FIREBASE_CREDENTIALS_FILE, dan FCM_TEST_TOKEN wajib diisi")
	}

	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	defer cancel()
	app, err := firebase.NewApp(
		ctx,
		&firebase.Config{ProjectID: projectID},
		option.WithCredentialsFile(credentialsFile),
	)
	if err != nil {
		t.Fatalf("inisialisasi Firebase gagal: %v", err)
	}
	client, err := app.Messaging(ctx)
	if err != nil {
		t.Fatalf("inisialisasi Messaging gagal: %v", err)
	}

	response, err := client.SendEachForMulticastDryRun(
		ctx,
		buildAdminRegistrationMessage([]string{token}, NewRegistration{
			RegistrationIDs: []string{"dry-run-admin-notification"},
			CustomerName:    "FCM Integration Test",
			ToolCount:       1,
			LocationID:      1,
			OrderTime:       time.Now().Format(time.RFC3339),
		}),
	)
	if err != nil {
		t.Fatalf("dry-run Firebase gagal: %v", err)
	}
	if response.SuccessCount != 1 || response.FailureCount != 0 {
		var targetErr error
		if len(response.Responses) > 0 {
			targetErr = response.Responses[0].Error
		}
		t.Fatalf(
			"dry-run ditolak oleh target: success=%d failure=%d error=%v",
			response.SuccessCount,
			response.FailureCount,
			targetErr,
		)
	}
}
