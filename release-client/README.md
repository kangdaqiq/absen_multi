# 📚 Sistem Absensi Sekolah

Sistem absensi berbasis web untuk sekolah dengan dukungan RFID dan Fingerprint, notifikasi WhatsApp otomatis, laporan kehadiran real-time, dan arsitektur **multi-tenant** (banyak sekolah dalam satu instalasi).

## ✨ Fitur Utama

### 🎯 Absensi Multi-Mode
- **RFID Card** — tap kartu untuk absensi
- **Fingerprint** — sidik jari via sensor ESP32
- **Mode Fleksibel** — 1x scan (masuk saja) atau 2x scan (masuk + pulang)
- **Gate Control** — buka/tutup gerbang dengan otorisasi guru

### 📱 Notifikasi WhatsApp
- Notifikasi real-time check-in/out ke siswa dan orang tua
- Laporan harian kehadiran ke grup kelas
- Laporan siswa bermasalah (Alpha/Bolos)
- Broadcast pesan ke kelas tertentu atau semua siswa

### 📊 Manajemen & Laporan
- Dashboard real-time kehadiran
- Laporan harian, mingguan, bulanan
- Export Excel/CSV dan PDF (kop surat sekolah)
- Import data siswa & guru dari Excel
- Deteksi otomatis Alpha dan Bolos

### 🏢 Multi-Tenant
- Satu instalasi untuk banyak sekolah
- Super Admin mengelola semua sekolah dari satu panel
- Setiap sekolah punya data, pengaturan, dan device sendiri
- **Kuota siswa & guru** per sekolah dapat dibatasi

### 🔑 Kelola Lisensi (Super Admin)
- Buat & kelola license key untuk client self-hosted
- Lock lisensi per hostname
- Monitor last ping dari setiap client
- Regenerate key dengan satu klik

### 🗂️ Auto Delete History Absen
- Kuota retensi history per sekolah (3/6/9/12/24/36 bulan atau tidak terbatas)
- Data otomatis dihapus setiap malam jam 01:00
- Dry-run mode untuk preview tanpa hapus data

### 🔐 Self-Hosted Mode (Deployment Client)
- Client deploy di server sendiri via **Docker** (1 perintah)
- Lisensi divalidasi ke server provider setiap hari
- Grace period 3 hari jika server tidak dapat dihubungi
- Super Admin panel otomatis diblokir di mode self-hosted

### ⚙️ Konfigurasi Fleksibel
- Toggle absen pulang (aktifkan/nonaktifkan)
- Konfigurasi toleransi keterlambatan per sekolah
- Pengaturan jadwal otomatis
- Mode aktif/nonaktif per kelas

---

## 🛠️ Teknologi

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 11.x |
| Frontend | Bootstrap 4 (SB Admin 2), jQuery |
| Database | MySQL 8.0 |
| Hardware | ESP8266/ESP32 (RFID/Fingerprint) |
| WhatsApp | **Go WhatsApp — GOWA** (aldinokemal/go-whatsapp-web-multidevice) |
| Scheduler | Laravel Task Scheduling + Supervisor |
| Deployment | Docker + Nginx + PHP-FPM |

---

## 📋 Persyaratan Sistem

### Hosted (Server Developer)
- Ubuntu/Debian VPS, PHP >= 8.2, Composer, MySQL 8.0
- Nginx/Apache, Node.js (aset kompilasi)
- GOWA binary (Go WhatsApp)

### Self-Hosted (Server Client via Docker)
- Docker Engine + Docker Compose
- Port 80 tersedia
- Koneksi internet (untuk validasi lisensi)
- RAM minimal 1GB (direkomendasikan 2GB)

---

## 🚀 Instalasi — Hosted (Server Developer)

### 1. Clone & Install

```bash
git clone https://github.com/kangdaqiq/absen_multi.git /var/www/absen
cd /var/www/absen
composer install --no-dev --optimize-autoloader
npm install && npm run build
```

### 2. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
nano .env
```

```env
APP_MODE=hosted          # ← WAJIB: hosted untuk server developer
APP_URL=https://absen.kangdaqiq.com
DB_DATABASE=absen_db
DB_USERNAME=absen_user
DB_PASSWORD=password_aman
WA_API_BASE_URL=http://localhost:3000
WA_API_USER=admin
WA_API_PASS=changeme
```

### 3. Migrasi & Setup

```bash
php artisan migrate
php artisan storage:link
php artisan optimize
```

### 4. Nginx Config

```nginx
server {
    listen 80;
    server_name absen.kangdaqiq.com;
    root /var/www/absen/public;
    index index.php;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 5. Scheduler & Queue (Supervisor)

```bash
# Crontab untuk scheduler
* * * * * cd /var/www/absen && php artisan schedule:run >> /dev/null 2>&1
```

```ini
# /etc/supervisor/conf.d/absen-queue.conf
[program:absen-queue]
command=php /var/www/absen/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
```

### 6. Install GOWA (Go WhatsApp)

```bash
# Download binary
wget https://github.com/aldinokemal/go-whatsapp-web-multidevice/releases/latest/download/whatsapp_linux_amd64 -O /usr/local/bin/gowa
chmod +x /usr/local/bin/gowa
```

```ini
# /etc/supervisor/conf.d/absen-gowa.conf
[program:absen-gowa]
command=/usr/local/bin/gowa rest --port=3000 --basic-auth=admin:changeme
autostart=true
autorestart=true
user=www-data
```

```bash
supervisorctl reread && supervisorctl update && supervisorctl start all
```

### 7. Update Versi

```bash
cd /var/www/absen
git pull origin main
composer install --no-dev --optimize-autoloader
npm run build
php artisan migrate --force
php artisan optimize
supervisorctl restart all
```

---

## 🐳 Build & Push Docker Image (untuk Client)

### Build Image

```powershell
# Di folder project (Windows PowerShell)
cd D:\ABSENSI\absen_multi

# Build
docker build -t ghcr.io/kangdaqiq/absen-multi:latest `
             -t ghcr.io/kangdaqiq/absen-multi:v2.3.0 .

# Push ke GitHub Container Registry
docker push ghcr.io/kangdaqiq/absen-multi:latest
docker push ghcr.io/kangdaqiq/absen-multi:v2.3.0
```

> **Pastikan `npm run build` dijalankan sebelum build Docker** agar aset CSS/JS sudah ter-compile.

---

## 🐳 Instalasi — Self-Hosted (Docker untuk Client)

Client hanya butuh **3 file** dari kamu:

| File | Keterangan |
|------|-----------|
| `docker-compose.yml` | Definisi semua service |
| `.env` | Sudah diisi `LICENSE_KEY` oleh developer |
| `INSTALL.txt` | Panduan singkat |

### Langkah Client

```bash
# 1. Edit .env (isi bagian yang belum diisi)
nano .env
# Wajib diisi: DB_PASSWORD, DB_ROOT_PASSWORD, APP_URL

# 2. Jalankan
docker compose up -d

# 3. Cek status
docker compose ps
docker compose logs app

# 4. Buka browser → APP_URL
```

### Setup WhatsApp (Sekali)

```bash
# Buka panel GOWA via browser (dari jaringan yang sama)
http://IP-server:3001

# Atau via SSH tunnel:
ssh -L 3001:localhost:3001 user@IP-server
# Lalu buka: http://localhost:3001
# Scan QR → selesai, session tersimpan permanen
```

### Update Versi (Client)

```bash
docker compose pull
docker compose up -d
```

---

## ⏰ Jadwal Otomatis (Scheduler)

| Waktu | Command | Keterangan |
|-------|---------|-----------|
| Setiap menit | `absen:process-daily` | Auto mark Alpha/Bolos |
| Setiap menit | `absen:daily-report` | Laporan harian WA |
| Setiap menit | `wa:process` | Proses antrian WA |
| Setiap menit | `absen:check-abnormal` | Cek siswa bermasalah |
| 00:30 | `license:validate` | Refresh cache lisensi (self-hosted) |
| 01:00 | `absen:auto-delete-history` | Hapus history melewati kuota |
| 02:00 | `db:backup` | Backup database SQL |

---

## 🔑 Sistem Lisensi (Self-Hosted)

Lisensi dikelola dari **Super Admin → Kelola Lisensi** di app hosted kamu.

API validasi: `POST https://absen.kangdaqiq.com/api/license/validate`

| Kondisi | Perilaku App Client |
|---------|-------------------|
| Lisensi valid | Berjalan normal ✅ |
| Server tidak bisa dihubungi | Grace period 3 hari ⚠️ |
| Grace period habis | App ditangguhkan ❌ |
| Lisensi expired | Halaman expired ❌ |
| Lisensi dinonaktifkan | Halaman invalid ❌ |

```bash
# Command manual di server client
php artisan license:validate          # cek status
php artisan license:validate --force  # paksa refresh cache
```

---

## 🔧 Konfigurasi Hardware (ESP8266/ESP32)

```cpp
const char* serverUrl = "http://your-server.com/api/rfid";
const char* apiKey    = "YOUR_API_KEY";
```

---

## 🐛 Troubleshooting

### App tidak bisa diakses (self-hosted)
```bash
docker compose ps
docker compose logs app
docker compose exec app php artisan license:validate
```

### Lisensi tidak valid padahal baru dibeli
```bash
php artisan license:validate --force
```

### WhatsApp tidak terkirim
```bash
php artisan queue:work              # pastikan worker jalan
# Cek tabel message_queues status='failed'
```

### Class not found / error autoload
```bash
composer dump-autoload
php artisan optimize:clear
```

---

## 📝 Changelog

### v2.3.0 (2026-04-26)
- 🆕 **Sistem Lisensi terintegrasi** di Super Admin panel (tidak perlu project terpisah)
- 🆕 **API validasi lisensi** built-in: `POST /api/license/validate`
- 🆕 **Lock lisensi per hostname**, regenerate key, monitor last ping
- 🆕 **Teacher/Staff Quota** — batasi jumlah guru per sekolah
- 🆕 **Auto Delete History Absen** — kuota retensi 3–36 bulan per sekolah
- 🐳 **GOWA (Go WhatsApp)** — ganti Node.js Baileys, hemat ~150MB RAM
- 🐳 Docker: MySQL memory tuning untuk hardware 2GB RAM
- 🐳 `.dockerignore` — `.env` tidak masuk image
- 🔐 `SelfHostedGuard` + `CheckLicense` middleware
- 🔐 Grace period 3 hari jika license server unreachable

### v2.2.0 (2026-04-24)
- ✨ Pengumuman sekolah
- ✨ Nomor Operator/PIC per sekolah
- 🔧 Drop tabel hari_libur

### v2.1.0 (2026-01-11)
- ✨ Arsitektur multi-tenant
- ✨ Super Admin panel
- ✨ WhatsApp Multi-Device per sekolah

### v2.0.0 (2026-01-10)
- ✨ Toggle absen pulang (1x/2x scan)
- 🔧 Auto-bolos menyesuaikan mode absensi

### v1.0.0 (2025-12-01)
- 🎉 Rilis awal sistem absensi
- ✨ RFID + Fingerprint, WhatsApp, Dashboard

---

## 👨‍💻 Developer

Dikembangkan oleh **Ahmad Daqiqi** ([@kangdaqiq](https://github.com/kangdaqiq))

📧 kangdaqiq@gmail.com | [GitHub Issues](https://github.com/kangdaqiq/absen_pro/issues)

---

⭐ Jika project ini membantu, jangan lupa beri star di GitHub!
