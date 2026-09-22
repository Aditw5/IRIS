# U-LAB Image Optimizer

Layanan internal ini menerima JPEG/PNG melalui `POST /v1/optimize`, melakukan koreksi orientasi EXIF, resize proporsional, lalu mengembalikan binary WebP. Layanan tidak menyimpan file dan sebaiknya hanya bind ke `127.0.0.1`.

## Menjalankan lokal

```bash
python -m venv .venv
.venv/bin/pip install -r requirements.txt
.venv/bin/python -m pytest -q
.venv/bin/uvicorn app.main:app --host 127.0.0.1 --port 8011
```

Di Windows gunakan `.venv\Scripts\python.exe` dan `.venv\Scripts\uvicorn.exe`.

Multipart field:

- `image`: file JPEG/PNG
- `profile`: `main` atau `thumbnail`

Health check tersedia di `GET /health`. Salin `.env.example` menjadi `.env` untuk mengatur quality, batas dimensi, dan batas upload.

## Production (systemd)

Contoh unit ada di `systemd/image-optimizer.service` dan sudah bind ke `127.0.0.1:8011` agar tidak terekspos langsung ke internet.

```bash
sudo cp systemd/image-optimizer.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now image-optimizer
curl http://127.0.0.1:8011/health
```

Tambahkan konfigurasi koneksi optimizer dari `.env.image-optimizer.example` ke environment Laravel dan Go API. Ukuran/quality pemrosesan dibaca oleh environment service Python.
