package mailer

import (
	"crypto/tls"
	"fmt"
	"html/template"
	"strconv"
	"strings"
	"time"

	gomail "gopkg.in/gomail.v2"
)

type Mailer struct {
	Host     string
	Port     int
	User     string
	Pass     string
	FromAddr string
	FromName string
}

func New(host string, port int, user, pass, fromAddr, fromName string) *Mailer {
	return &Mailer{host, port, user, pass, fromAddr, fromName}
}

func (m *Mailer) SendOTP(toEmail, toName, code, purpose string, minutes int) error {
	if strings.TrimSpace(m.Host) == "" || strings.TrimSpace(m.FromAddr) == "" {
		return fmt.Errorf("smtp configuration is incomplete")
	}

	subject, plainBody, htmlBody := buildOTPEmail(toName, code, purpose, minutes)

	msg := gomail.NewMessage()
	msg.SetHeader("Date", time.Now().Format(time.RFC1123Z))
	msg.SetAddressHeader("From", m.FromAddr, m.FromName)
	msg.SetAddressHeader("To", toEmail, toName)
	msg.SetHeader("Subject", subject)
	msg.SetHeader("X-Entity-Ref-ID", fmt.Sprintf("ulab-otp-%d", time.Now().UnixNano()))
	msg.SetBody("text/plain", plainBody)
	msg.AddAlternative("text/html", htmlBody)

	d := gomail.NewDialer(m.Host, m.Port, m.User, m.Pass)
	// Pastikan STARTTLS/TLS jalan dengan benar
	d.TLSConfig = &tls.Config{ServerName: m.Host, MinVersion: tls.VersionTLS12}

	if err := d.DialAndSend(msg); err != nil {
		return fmt.Errorf("smtp send failed: %w", err)
	}
	return nil
}

type otpEmailData struct {
	Name          string
	Code          string
	Action        string
	PurposeLabel  string
	Minutes       int
	Preheader     string
	WebsiteURL    string
	WebsiteLabel  string
	CopyrightYear string
}

func buildOTPEmail(toName, code, purpose string, minutes int) (string, string, string) {
	subject := "Kode OTP Pendaftaran U-LAB"
	action := "menyelesaikan pendaftaran akun Anda"
	purposeLabel := "Verifikasi Pendaftaran"

	switch strings.ToLower(strings.TrimSpace(purpose)) {
	case "forgot_password":
		subject = "Kode OTP Reset Password U-LAB"
		action = "mengatur ulang password akun Anda"
		purposeLabel = "Reset Password"
	case "test_email":
		subject = "Tes Email OTP U-LAB"
		action = "menguji layanan pengiriman email U-LAB"
		purposeLabel = "Pengujian Email"
	}

	name := strings.TrimSpace(toName)
	if name == "" {
		name = "Pengguna U-LAB"
	}
	if minutes < 1 {
		minutes = 10
	}

	data := otpEmailData{
		Name:          name,
		Code:          formatCode(code),
		Action:        action,
		PurposeLabel:  purposeLabel,
		Minutes:       minutes,
		Preheader:     fmt.Sprintf("Kode verifikasi U-LAB Anda berlaku selama %d menit.", minutes),
		WebsiteURL:    "https://www.ulabumro.id",
		WebsiteLabel:  "www.ulabumro.id",
		CopyrightYear: "2026",
	}

	plainBody := fmt.Sprintf(
		"Halo %s,\n\nGunakan kode OTP berikut untuk %s:\n\n%s\n\nKode berlaku selama %d menit. Jangan bagikan kode ini kepada siapa pun, termasuk petugas U-LAB.\n\nJika Anda tidak meminta kode ini, abaikan email ini dan pastikan akun Anda tetap aman.\n\nKunjungi %s\n\n© %s UMRO Laboratory. All rights reserved.",
		data.Name,
		data.Action,
		data.Code,
		data.Minutes,
		data.WebsiteURL,
		data.CopyrightYear,
	)

	var htmlBuilder strings.Builder
	tmpl := template.Must(template.New("otp-email").Parse(otpEmailHTML))
	if err := tmpl.Execute(&htmlBuilder, data); err != nil {
		return subject, plainBody, plainBody
	}

	return subject, plainBody, htmlBuilder.String()
}

func formatCode(code string) string {
	code = strings.TrimSpace(code)
	if len(code) != 6 {
		return code
	}
	if _, err := strconv.Atoi(code); err != nil {
		return code
	}
	return code[:3] + " " + code[3:]
}

const otpEmailHTML = `<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light">
  <title>{{.PurposeLabel}} U-LAB</title>
</head>
<body style="margin:0;padding:0;background:#eef4fb;font-family:Arial,'Helvetica Neue',sans-serif;color:#0f172a;">
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">{{.Preheader}}</div>
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#eef4fb;">
    <tr>
      <td align="center" style="padding:32px 12px;">
        <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 14px 40px rgba(15,23,42,.10);">
          <tr>
            <td style="height:7px;background:linear-gradient(90deg,#0891b2 0%,#22c1cf 50%,#ef4444 100%);font-size:0;line-height:0;">&nbsp;</td>
          </tr>
          <tr>
            <td align="center" style="padding:30px 28px 20px;background:#f8fbff;border-bottom:1px solid #e2e8f0;">
              <img src="https://www.ulabumro.id/UMRO.png" width="106" alt="U-LAB" style="display:block;width:106px;max-width:106px;height:auto;border:0;margin:0 auto 14px;">
              <div style="font-size:23px;line-height:30px;font-weight:800;letter-spacing:.8px;color:#0f172a;">UMRO LABORATORY</div>
              <div style="margin-top:5px;font-size:13px;line-height:20px;color:#64748b;letter-spacing:.4px;">Calibration, Testing &amp; Repair</div>
            </td>
          </tr>
          <tr>
            <td style="padding:34px 34px 20px;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                  <td>
                    <span style="display:inline-block;padding:7px 12px;border-radius:999px;background:#e0f2fe;color:#0369a1;font-size:11px;line-height:15px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;">{{.PurposeLabel}}</span>
                    <h1 style="margin:20px 0 10px;font-size:25px;line-height:34px;color:#0f172a;">Kode verifikasi Anda</h1>
                    <p style="margin:0;font-size:15px;line-height:25px;color:#475569;">Halo <strong style="color:#0f172a;">{{.Name}}</strong>, gunakan kode berikut untuk {{.Action}}.</p>
                  </td>
                </tr>
                <tr>
                  <td align="center" style="padding:28px 0 20px;">
                    <div style="display:inline-block;min-width:250px;padding:22px 20px;border:1px solid #bae6fd;border-radius:18px;background:#f0f9ff;color:#0f172a;font-size:36px;line-height:42px;font-weight:800;letter-spacing:9px;text-align:center;">{{.Code}}</div>
                  </td>
                </tr>
                <tr>
                  <td align="center" style="padding-bottom:28px;">
                    <span style="font-size:13px;line-height:20px;color:#64748b;">Kode berlaku selama <strong style="color:#0f172a;">{{.Minutes}} menit</strong>.</span>
                  </td>
                </tr>
                <tr>
                  <td style="padding:17px 18px;border-radius:14px;background:#fff7ed;border:1px solid #fed7aa;">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                      <tr>
                        <td width="30" valign="top" style="font-size:20px;line-height:24px;">&#128274;</td>
                        <td style="font-size:13px;line-height:21px;color:#9a3412;"><strong>Jaga kerahasiaan kode ini.</strong><br>U-LAB tidak pernah meminta OTP melalui telepon, chat, atau media sosial.</td>
                      </tr>
                    </table>
                  </td>
                </tr>
                <tr>
                  <td style="padding-top:25px;font-size:13px;line-height:22px;color:#64748b;">Jika Anda tidak melakukan permintaan ini, abaikan email ini. Tidak ada perubahan pada akun Anda tanpa kode tersebut.</td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:8px 34px 34px;">
              <a href="{{.WebsiteURL}}" target="_blank" style="display:inline-block;padding:13px 24px;border-radius:12px;background:#0e7490;color:#ffffff;font-size:14px;line-height:20px;font-weight:700;text-decoration:none;">Kunjungi Website U-LAB</a>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:24px 28px;background:#0f172a;color:#cbd5e1;">
              <div style="font-size:13px;line-height:20px;font-weight:700;color:#ffffff;">UMRO Laboratory</div>
              <div style="margin-top:5px;font-size:12px;line-height:19px;"><a href="{{.WebsiteURL}}" target="_blank" style="color:#67e8f9;text-decoration:none;">{{.WebsiteLabel}}</a></div>
              <div style="margin-top:13px;font-size:11px;line-height:18px;color:#94a3b8;">&copy; {{.CopyrightYear}} UMRO Laboratory. All rights reserved.</div>
              <div style="margin-top:4px;font-size:10px;line-height:16px;color:#64748b;">Email otomatis ini dikirim untuk keamanan akun Anda. Mohon tidak membalas email ini.</div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>`
