package push

import (
	"context"
	"errors"
	"fmt"
	"strings"
	"time"

	firebase "firebase.google.com/go/v4"
	"firebase.google.com/go/v4/messaging"
	"google.golang.org/api/option"
	"gorm.io/gorm"
)

const adminNotificationChannel = "ulab_high_importance_channel"

var ErrNoAdminTokens = errors.New("token FCM admin aktif tidak ditemukan")

type NewRegistration struct {
	RegistrationIDs []string
	CustomerName    string
	ToolCount       int
	LocationID      int
	OrderTime       string
}

type AdminNotifier interface {
	NotifyNewRegistration(ctx context.Context, event NewRegistration) error
}

type multicastSender interface {
	SendEachForMulticast(context.Context, *messaging.MulticastMessage) (*messaging.BatchResponse, error)
	SendEachForMulticastDryRun(context.Context, *messaging.MulticastMessage) (*messaging.BatchResponse, error)
}

type FirebaseAdminNotifier struct {
	db     *gorm.DB
	client multicastSender
}

func NewFirebaseAdminNotifier(
	ctx context.Context,
	db *gorm.DB,
	projectID,
	credentialsFile string,
) (*FirebaseAdminNotifier, error) {
	if db == nil {
		return nil, errors.New("database FCM admin tidak tersedia")
	}

	config := &firebase.Config{}
	if strings.TrimSpace(projectID) != "" {
		config.ProjectID = strings.TrimSpace(projectID)
	}

	var opts []option.ClientOption
	if strings.TrimSpace(credentialsFile) != "" {
		opts = append(opts, option.WithCredentialsFile(strings.TrimSpace(credentialsFile)))
	}

	app, err := firebase.NewApp(ctx, config, opts...)
	if err != nil {
		return nil, fmt.Errorf("inisialisasi Firebase Admin gagal: %w", err)
	}

	client, err := app.Messaging(ctx)
	if err != nil {
		return nil, fmt.Errorf("inisialisasi Firebase Messaging gagal: %w", err)
	}

	return &FirebaseAdminNotifier{db: db, client: client}, nil
}

func (n *FirebaseAdminNotifier) NotifyNewRegistration(
	ctx context.Context,
	event NewRegistration,
) error {
	return n.sendNewRegistration(ctx, event, false)
}

// ValidateNewRegistration melakukan seluruh pemeriksaan produksi (database,
// kredensial Firebase, token admin, dan payload) tanpa mengirim notifikasi.
func (n *FirebaseAdminNotifier) ValidateNewRegistration(
	ctx context.Context,
	event NewRegistration,
) error {
	return n.sendNewRegistration(ctx, event, true)
}

func (n *FirebaseAdminNotifier) sendNewRegistration(
	ctx context.Context,
	event NewRegistration,
	dryRun bool,
) error {
	var tokens []string
	if err := n.db.WithContext(ctx).
		Table("mobile_push_tokens").
		Where("is_active = TRUE").
		Where("LOWER(TRIM(user_type)) IN ?", []string{"admin", "administrator"}).
		Where("NULLIF(TRIM(fcm_token), '') IS NOT NULL").
		Distinct().
		Pluck("fcm_token", &tokens).Error; err != nil {
		return fmt.Errorf("query token FCM admin gagal: %w", err)
	}

	if len(tokens) == 0 {
		return ErrNoAdminTokens
	}

	for start := 0; start < len(tokens); start += 500 {
		end := start + 500
		if end > len(tokens) {
			end = len(tokens)
		}

		message := buildAdminRegistrationMessage(tokens[start:end], event)
		var response *messaging.BatchResponse
		var err error
		if dryRun {
			response, err = n.client.SendEachForMulticastDryRun(ctx, message)
		} else {
			response, err = n.client.SendEachForMulticast(ctx, message)
		}
		if err != nil {
			return fmt.Errorf("kirim multicast FCM admin gagal: %w", err)
		}

		if !dryRun && response.FailureCount > 0 {
			n.deactivateInvalidTokens(ctx, tokens[start:end], response)
		}
		if response.FailureCount == len(tokens[start:end]) {
			return fmt.Errorf("semua %d target FCM admin gagal", response.FailureCount)
		}
	}

	return nil
}

func (n *FirebaseAdminNotifier) deactivateInvalidTokens(
	ctx context.Context,
	tokens []string,
	response *messaging.BatchResponse,
) {
	invalid := make([]string, 0)
	for index, result := range response.Responses {
		if result.Success || index >= len(tokens) || result.Error == nil {
			continue
		}
		if messaging.IsRegistrationTokenNotRegistered(result.Error) ||
			messaging.IsInvalidArgument(result.Error) {
			invalid = append(invalid, tokens[index])
		}
	}
	if len(invalid) == 0 {
		return
	}

	_ = n.db.WithContext(ctx).
		Table("mobile_push_tokens").
		Where("fcm_token IN ?", invalid).
		Updates(map[string]any{
			"is_active":  false,
			"updated_at": time.Now(),
		}).Error
}

func buildAdminRegistrationMessage(
	tokens []string,
	event NewRegistration,
) *messaging.MulticastMessage {
	customer := strings.TrimSpace(event.CustomerName)
	if customer == "" {
		customer = "Customer U-LAB"
	}

	registrationID := ""
	if len(event.RegistrationIDs) > 0 {
		registrationID = strings.TrimSpace(event.RegistrationIDs[0])
	}

	body := fmt.Sprintf(
		"%s mendaftarkan %d alat. Buka aplikasi untuk melakukan verifikasi.",
		customer,
		event.ToolCount,
	)

	return &messaging.MulticastMessage{
		Tokens: tokens,
		Notification: &messaging.Notification{
			Title: "Pendaftaran alat baru",
			Body:  body,
		},
		Data: map[string]string{
			"type":             "admin_registration",
			"screen":           "admin_notifications",
			"norec_registrasi": event.RegistrationIDsString(),
			"registration_id":  registrationID,
			"customer":         customer,
			"jumlah_alat":      fmt.Sprintf("%d", event.ToolCount),
			"lokasi_id":        fmt.Sprintf("%d", event.LocationID),
			"waktu_order":      event.OrderTime,
		},
		Android: &messaging.AndroidConfig{
			Priority: "high",
			Notification: &messaging.AndroidNotification{
				ChannelID: adminNotificationChannel,
				Sound:     "default",
				Color:     "#2563EB",
			},
		},
	}
}

func (e NewRegistration) RegistrationIDsString() string {
	clean := make([]string, 0, len(e.RegistrationIDs))
	for _, id := range e.RegistrationIDs {
		if text := strings.TrimSpace(id); text != "" {
			clean = append(clean, text)
		}
	}
	return strings.Join(clean, ",")
}
