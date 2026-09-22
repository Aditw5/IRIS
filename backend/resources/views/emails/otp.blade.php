@php
    $purposeKey = strtolower($purpose ?? 'register');
    $purposeLabel = in_array($purposeKey, ['reset_password', 'forgot_password'])
        ? 'RESET PASSWORD'
        : 'VERIFIKASI PENDAFTARAN';
    $minutes = $expiresMinutes ?? 10;
    $formattedCode = strlen($code) === 6 ? substr($code, 0, 3) . ' ' . substr($code, 3) : $code;
@endphp
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light">
  <title>Kode OTP U-LAB</title>
</head>
<body style="margin:0;padding:0;background:#eef4fb;font-family:Arial,'Helvetica Neue',sans-serif;color:#0f172a;">
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
    Kode verifikasi U-LAB Anda berlaku selama {{ $minutes }} menit.
  </div>
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#eef4fb;">
    <tr>
      <td align="center" style="padding:32px 12px;">
        <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 14px 40px rgba(15,23,42,.10);">
          <tr>
            <td style="height:7px;background:#0e7490;font-size:0;line-height:0;">&nbsp;</td>
          </tr>
          <tr>
            <td align="center" style="padding:28px 28px 22px;background:#f8fbff;border-bottom:1px solid #e2e8f0;">
              <img src="https://www.ulabumro.id/UMRO.png" width="106" alt="U-LAB" style="display:block;width:106px;max-width:106px;height:auto;border:0;margin:0 auto 12px;">
              <div style="font-size:22px;line-height:29px;font-weight:800;letter-spacing:.7px;color:#0f172a;">UMRO LABORATORY</div>
              <div style="margin-top:5px;font-size:12px;line-height:19px;color:#64748b;letter-spacing:.3px;">Calibration, Testing &amp; Repair</div>
            </td>
          </tr>
          <tr>
            <td style="padding:32px 32px 20px;">
              <span style="display:inline-block;padding:7px 12px;border-radius:999px;background:#e0f2fe;color:#0369a1;font-size:11px;line-height:15px;font-weight:700;letter-spacing:.5px;">{{ $purposeLabel }}</span>
              <h1 style="margin:18px 0 9px;font-size:24px;line-height:32px;color:#0f172a;">Kode verifikasi Anda</h1>
              <p style="margin:0;font-size:15px;line-height:24px;color:#475569;">Gunakan kode OTP berikut untuk melanjutkan proses Anda di U-LAB.</p>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:8px 32px 20px;">
              <div style="display:inline-block;min-width:250px;padding:21px 20px;border:1px solid #bae6fd;border-radius:18px;background:#f0f9ff;color:#0f172a;font-size:35px;line-height:42px;font-weight:800;letter-spacing:9px;text-align:center;">{{ $formattedCode }}</div>
              <div style="margin-top:11px;font-size:13px;line-height:20px;color:#64748b;">Kode berlaku selama <strong style="color:#0f172a;">{{ $minutes }} menit</strong>.</div>
            </td>
          </tr>
          <tr>
            <td style="padding:8px 32px 25px;">
              <div style="padding:16px 18px;border-radius:13px;background:#fff7ed;border:1px solid #fed7aa;font-size:13px;line-height:21px;color:#9a3412;">
                <strong>Jaga kerahasiaan kode ini.</strong><br>
                U-LAB tidak pernah meminta OTP melalui telepon, chat, atau media sosial. Jika Anda tidak meminta kode ini, abaikan email ini.
              </div>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:0 32px 32px;">
              <a href="https://www.ulabumro.id" target="_blank" style="display:inline-block;padding:12px 22px;border-radius:11px;background:#0e7490;color:#ffffff;font-size:14px;line-height:20px;font-weight:700;text-decoration:none;">Kunjungi Website U-LAB</a>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:22px 28px;background:#0f172a;color:#cbd5e1;">
              <div style="font-size:13px;line-height:20px;font-weight:700;color:#ffffff;">UMRO Laboratory</div>
              <div style="margin-top:5px;font-size:12px;line-height:19px;"><a href="https://www.ulabumro.id" target="_blank" style="color:#67e8f9;text-decoration:none;">www.ulabumro.id</a></div>
              <div style="margin-top:12px;font-size:11px;line-height:18px;color:#94a3b8;">&copy; 2026 UMRO Laboratory. All rights reserved.</div>
              <div style="margin-top:4px;font-size:10px;line-height:16px;color:#64748b;">Email otomatis ini dikirim untuk keamanan akun Anda. Mohon tidak membalas email ini.</div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
