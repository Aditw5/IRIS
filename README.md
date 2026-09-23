# ULAB Kalibrasi

Monorepo ULAB Kalibrasi berisi aplikasi web, backend utama, server realtime, aplikasi mobile, dan beberapa service pendukung. Stack utama yang dipakai:

- **Backend:** Laravel 8
- **Frontend:** Vue 3 + Vite
- **Socket:** Node.js + Express + Socket.IO
- **Mobile:** Flutter
- **Services:** Go Gin API dan Python FastAPI chatbot

## Struktur Project

| Path | Stack | Fungsi |
| --- | --- | --- |
| `backend/` | Laravel 8, PHP, Composer | Backend utama web/API, service legacy, laporan, dokumen, auth, integrasi socket, PDF, dan fitur operasional ULAB. |
| `frontend-v2/` | Vue 3, Vite, Pinia, Bulma/Vuero | Dashboard web ULAB untuk admin, customer, manager, pelaksana, penyelia, dan modul kalibrasi. |
| `iris-socket/` | Node.js, Express, Socket.IO | Server realtime IRIS untuk notifikasi, chat pegawai, presence pegawai, room kamera, dan WebRTC signaling. |
| `mobile/flutter_app/` | Flutter | Aplikasi mobile customer untuk login, OTP, profil, master alat, keranjang, checkout, history order, scan QR, laporan, dan push notification. |
| `services/go-api/` | Go, Gin, GORM, PostgreSQL | API mobile/customer baru untuk auth, OTP, profil, alat, mitra, keranjang, checkout, history, dan report. |
| `services/chatbot-python/` | Python, FastAPI | Chatbot ULAB berbasis manual DOCX dan data customer dari backend. |

## Prasyarat

Pastikan tool berikut tersedia di mesin development:

- PHP `^7.3` atau `^8.0`
- Composer
- Node.js `>=12.17.1` dan npm `>=7`
- Go sesuai `services/go-api/go.mod`
- Flutter SDK sesuai `mobile/flutter_app/pubspec.yaml`
- Python 3.10+ dan pip
- Database sesuai konfigurasi masing-masing service
- PostgreSQL untuk `services/go-api`
- Optional untuk fitur dokumen/PDF backend: `wkhtmltopdf`, LibreOffice, dan Ghostscript

## Environment

Setiap modul punya konfigurasi sendiri. Jangan simpan nilai rahasia di dokumentasi atau commit baru.

### Backend Laravel

File: `backend/.env`

Kunci penting:

- `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `MOBILE_DB_CONNECTION`, `MOBILE_DB_HOST`, `MOBILE_DB_PORT`, `MOBILE_DB_DATABASE`, `MOBILE_DB_USERNAME`, `MOBILE_DB_PASSWORD`
- `MAIL_*`
- `JWT_ALG`, `JWT_KEY`, `JWT_EXPIRED_MINUTE`
- `SOCKET_SERVER_URL`, `SOCKET_SERVER_SECRET`
- `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`
- `FIREBASE_PROJECT_ID`, `FIREBASE_SERVICE_ACCOUNT_JSON`
- `WKHTML_PDF_BINARY`, `LIBREOFFICE_PATH`, `GS_PATH`

### Frontend Vue

File: `frontend-v2/.env`

Kunci penting:

- `VITE_API_BASE_URL` untuk base URL Laravel, contoh local: `http://localhost:8001/service/`
- `VITE_SOCKET_URL` untuk URL Socket.IO. Kosongkan pada production jika memakai reverse proxy `/socket.io/` pada origin yang sama.
- `VITE_SOCKET_ON` untuk menyalakan/mematikan socket
- `VITE_CHATBOT_URL` untuk endpoint chatbot Python
- `VITE_PROJECT`, `VITE_NAVIGASI`, `VITE_FOOTER`, `VITE_FOOTER_BY`
- `VITE_MAPBOX_ACCESS_TOKEN`, `VITE_TURNSTILE_SITE_KEY`

### Socket Server

File: `iris-socket/.env`

Kunci penting:

- `PORT`, default aplikasi: `3001`
- `CORS_ORIGIN`, pisahkan dengan koma jika lebih dari satu origin
- `SOCKET_SERVER_SECRET`, harus sama dengan secret yang dipakai backend saat memanggil endpoint emit socket

### Go API

File: `services/go-api/.env`

Kunci penting:

- `APP_ENV`, `APP_PORT`, `APP_JWT_SECRET`, `APP_JWT_TTL_MIN`
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_SSLMODE`
- `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`
- `OTP_TTL_MIN`, `OTP_RESEND_COOLDOWN_SEC`, `OTP_MAX_VERIFY_ATTEMPTS`
- `WA_TOKEN`, `WA_SECRET`

### Chatbot Python

File: `services/chatbot-python/.env`

Kunci penting:

- `APP_NAME`, `APP_ENV`, `APP_PORT`
- `BACKEND_BASE_URL`, contoh local Laravel: `http://127.0.0.1:8000`

### Mobile Flutter

Konfigurasi endpoint ada di `mobile/flutter_app/lib/config.dart`.

Untuk development Android emulator, localhost host machine biasanya diakses via `10.0.2.2`, contoh:

```dart
static const String baseUrl = 'http://10.0.2.2:8080';
```

## Menjalankan Project

Urutan umum saat development lokal:

1. Jalankan backend Laravel.
2. Jalankan Go API jika sedang mengerjakan mobile/customer API.
3. Jalankan socket server untuk notifikasi/chat/kamera.
4. Jalankan chatbot Python jika fitur chatbot dipakai.
5. Jalankan frontend Vue.
6. Jalankan mobile Flutter jika sedang mengerjakan aplikasi mobile.

### Backend Laravel

```bash
cd backend
composer install
npm install
php artisan key:generate
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8001
```

Catatan:

- Jalankan `php artisan key:generate` hanya jika `APP_KEY` belum ada.
- Jalankan `php artisan migrate` setelah database benar dan memang perlu migrasi.
- Frontend memakai endpoint Laravel dari `VITE_API_BASE_URL`.

### Frontend Vue

```bash
cd frontend-v2
npm install
npm run dev
```

Default Vite berjalan di:

```text
http://localhost:5174
```

Build production:

```bash
npm run build
```

Preview build:

```bash
npm run preview
```

### Socket Server

```bash
cd iris-socket
npm install
npm start
```

Default server berjalan di:

```text
http://127.0.0.1:3001
```

Endpoint dan event utama:

- `GET /` untuk health/info socket server
- `POST /emit-notification` dengan header `x-socket-secret`
- `POST /emit-chat` dengan header `x-socket-secret`
- `POST /emit-chat-delete` dengan header `x-socket-secret`
- Event client: `join-pegawai`, `get-online-pegawai`, `notification`, `chat-message`, `chat-message-deleted`
- Event kamera: `join-camera-room`, `camera-signal`, `camera-participants`, `camera-role-replaced`, `leave-camera-room`

Konfigurasi development lokal:

```dotenv
# frontend-v2/.env
VITE_SOCKET_ON=true
VITE_SOCKET_URL=http://localhost:3001

# backend/.env
SOCKET_SERVER_URL=http://localhost:3001

# iris-socket/.env
PORT=3001
CORS_ORIGIN=http://localhost:5174
```

Untuk Docker, backend dapat memakai `SOCKET_SERVER_URL=http://iris-socket:3001`. Frontend production dapat mengosongkan `VITE_SOCKET_URL` agar koneksi menggunakan origin yang sama dan diteruskan Nginx melalui `/socket.io/`. `SOCKET_SERVER_SECRET` wajib diberikan ke backend dan socket melalui secret CI/CD dengan nilai yang sama.

Build image socket:

```bash
docker build -t iris-socket ./iris-socket
```

### Go API

```bash
cd services/go-api
go mod download
go run ./cmd/server
```

Default port dari konfigurasi adalah `8080`.

Health check:

```text
http://127.0.0.1:8080/api/health
```

Build binary:

```bash
go build -o server.exe ./cmd/server
```

Scope endpoint Go API:

- `/api/auth/*`
- `/api/alat`
- `/api/profile/setup`
- `/api/mobile-profile/*`
- `/api/mitra`
- `/api/mitra-eksternal`
- `/api/customer/*`
- `/api/lokasi-kalibrasi`
- `/api/paket-kalibrasi`
- `/api/registrasi/*`

### Chatbot Python

```bash
cd services/chatbot-python
python -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
python -m uvicorn app.main:app --host 127.0.0.1 --port 8001 --reload
```

Endpoint utama:

- `GET /`
- `GET /manual/check`
- `GET /manual/debug`
- `POST /ask`

Manual chatbot disimpan di:

```text
services/chatbot-python/data/manuals/
```

Chatbot bisa menjawab dari manual DOCX atau mengambil data aktual customer lewat `BACKEND_BASE_URL`. Untuk data customer yang butuh login, chatbot meneruskan header `Authorization`, `Cookie`, atau token dari request frontend.

### Mobile Flutter

```bash
cd mobile/flutter_app
flutter pub get
flutter run
```

Build APK release:

```bash
flutter build apk --release
```

Perhatikan `mobile/flutter_app/lib/config.dart` sebelum menjalankan mobile:

- `baseUrl` mengarah ke Go API atau endpoint mobile production.
- `webBase` mengarah ke Laravel untuk file, print, dan service legacy.
- Untuk Android emulator, gunakan `10.0.2.2` jika API berjalan di host machine.

## Testing dan Validasi

Backend Laravel:

```bash
cd backend
php artisan test
```

Frontend Vue:

```bash
cd frontend-v2
npm run build
```

Go API:

```bash
cd services/go-api
go test ./...
```

Mobile Flutter:

```bash
cd mobile/flutter_app
flutter analyze
flutter test
```

Socket server:

```bash
cd iris-socket
npm start
```

Lalu cek `GET /` di port socket.

Chatbot Python:

```bash
cd services/chatbot-python
python -m uvicorn app.main:app --host 127.0.0.1 --port 8001 --reload
```

Lalu cek `GET /manual/check`.

## Troubleshooting

- Jika frontend tidak bisa memanggil backend, cek `frontend-v2/.env` bagian `VITE_API_BASE_URL` dan CORS backend.
- Jika realtime tidak jalan, cek `VITE_SOCKET_URL`, `VITE_SOCKET_ON`, `iris-socket/.env`, dan pastikan `CORS_ORIGIN` mengizinkan origin frontend.
- Jika backend tidak bisa emit notifikasi/chat, pastikan `SOCKET_SERVER_URL` mengarah ke service `iris-socket` dan `SOCKET_SERVER_SECRET` di backend sama dengan `iris-socket/.env`.
- Jika mobile emulator tidak bisa akses API local, ganti host `localhost` menjadi `10.0.2.2`.
- Jika chatbot tidak bisa mengambil data customer, cek `BACKEND_BASE_URL` dan pastikan request membawa token/login yang valid.
- Jika fitur PDF/dokumen gagal di backend, cek path binary `WKHTML_PDF_BINARY`, `LIBREOFFICE_PATH`, dan `GS_PATH`.
