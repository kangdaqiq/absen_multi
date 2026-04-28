# Sistem Absensi Sekolah — Dokumentasi Teknis

## 📋 Deskripsi Project

Sistem Absensi Sekolah adalah aplikasi berbasis web Laravel untuk mengelola absensi siswa dan guru secara otomatis menggunakan RFID dan Fingerprint. Sistem ini mendukung arsitektur **multi-tenant** (banyak sekolah dalam satu instalasi), terintegrasi dengan WhatsApp via **GOWA (Go WhatsApp)**, dan dapat di-deploy secara self-hosted menggunakan Docker dengan proteksi sistem lisensi online.

---

## 🏗️ Arsitektur Sistem

### Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 11.x |
| Frontend | Blade, Bootstrap 4 (SB Admin 2), jQuery |
| Database | MySQL 8.0 |
| Queue | Database Queue (Laravel) |
| Hardware | ESP8266/ESP32 + RFID RC522 + Fingerprint Sensor |
| WhatsApp | **GOWA** (aldinokemal/go-whatsapp-web-multidevice) — Go binary, ~20MB RAM |
| Deployment Hosted | PHP-FPM + Nginx + Supervisor (manual) |
| Deployment Client | Docker (app + mysql + gowa dalam docker-compose) |

### Mode Deployment

| | **Hosted** | **Self-Hosted** |
|---|---|---|
| Dikelola oleh | Developer (kamu) | Client di server sendiri |
| `APP_MODE` | `hosted` | `self_hosted` |
| Install | Manual (PHP, Nginx, Composer) | `docker compose up -d` |
| Super Admin | ✅ Aktif | ❌ Diblokir (404) |
| License check | ❌ Tidak perlu | ✅ Ping harian ke server developer |
| Source code | Di server langsung | Tersembunyi dalam Docker image |
| Update | `git pull` + `migrate` | `docker compose pull` |

---

## 🎯 Fitur Detail

### 1. Manajemen Data Master

- **Siswa** — CRUD, import/export Excel, enroll RFID & Fingerprint, WA siswa & ortu
- **Guru** — CRUD, import/export Excel, enroll RFID & Fingerprint, WA pribadi
- **Kelas** — Manajemen kelas, wali kelas, toggle absensi & laporan, grup WA
- **Jadwal** — Jam masuk/pulang per hari per sekolah
- **Pengumuman** — Manajemen & tampilan interaktif di dashboard

### 2. Sistem Absensi

#### Status Siswa
| Kode | Status | Keterangan |
|------|--------|-----------|
| H | Hadir | Tepat waktu |
| T | Terlambat | Melewati batas toleransi |
| A | Alpha | Tidak hadir (auto) |
| B | Bolos | Masuk tapi tidak pulang (auto) |
| I | Izin | Input manual |
| S | Sakit | Input manual |

#### Mode Absensi
- **2x Scan (Aktif):** masuk + pulang dengan otorisasi guru (buka gerbang 15 menit)
- **1x Scan (Nonaktif):** hanya masuk, tidak ada proses checkout

### 3. Kuota Siswa & Guru

Super Admin dapat membatasi jumlah siswa dan guru per sekolah:

| Field | Keterangan |
|-------|-----------|
| `student_limit` | Maks siswa. null/0 = unlimited |
| `teacher_limit` | Maks guru/staff. null/0 = unlimited |

**Titik pengecekan:**
- Tambah manual (form) → cek sebelum insert
- Import Excel → cek per baris, stop + pesan error saat penuh

**Pesan error:**
```
Gagal: Kuota siswa/guru sudah penuh (500 siswa). Hubungi Super Admin.
Import dihentikan: Kuota penuh. Berhasil diimpor: 43 data.
```

### 4. Auto Delete History Absen

- Field `history_quota_months` di tabel `schools`
- Pilihan: `3`, `6`, `9`, `12`, `24`, `36` bulan, atau null (tidak terbatas)
- Command: `absen:auto-delete-history` — dijalankan tiap malam 01:00
- `--dry-run` untuk preview tanpa hapus
- Menghapus `attendance` (siswa) dan `absensi_guru` yang melewati cutoff date

### 5. Notifikasi WhatsApp (GOWA)

GOWA (Go WhatsApp) dijalankan sebagai:
- **Hosted:** binary `/usr/local/bin/gowa rest --port=3000`
- **Self-Hosted:** Docker service `whatsapp` dalam `docker-compose.yml`

Endpoint yang dipakai Laravel: `POST http://[host]:3000/send/message`

Basic Auth dari `.env`: `WA_API_USER` dan `WA_API_PASS`

### 6. Multi-Tenant

| Role | Akses |
|------|-------|
| `super_admin` | Semua sekolah, Super Admin panel, kelola lisensi |
| `admin` | Hanya sekolahnya sendiri |
| `teacher` | View + limited edit |

Setiap data di-scope per `school_id`. Super Admin tidak terpengaruh scope.

### 7. Sistem Lisensi (Self-Hosted)

#### Komponen

| Komponen | Lokasi | Fungsi |
|----------|--------|--------|
| `LicenseService` | `app/Services/LicenseService.php` | Logika validasi, cache, grace period |
| `CheckLicense` | `app/Http/Middleware/CheckLicense.php` | Global web middleware |
| `SelfHostedGuard` | `app/Http/Middleware/SelfHostedGuard.php` | Blokir `/super-admin/*` |
| `ValidateLicenseCommand` | `app/Console/Commands/` | `php artisan license:validate` |
| `LicenseController` | `app/Http/Controllers/SuperAdmin/` | CRUD lisensi di panel |
| `LicenseValidateController` | `app/Http/Controllers/Api/` | Public API endpoint |
| `License` model | `app/Models/License.php` | Model tabel licenses |
| Tabel `licenses` | Database | Menyimpan semua license key |

#### Alur Validasi

```
Request web masuk
    ↓ CheckLicense middleware (global)
    ├── APP_MODE = hosted? → skip, lanjut
    ├── Route api/*, login, license.*? → bypass
    └── Self-hosted:
        ├── Baca cache (storage/app/license_cache.json)
        │   ├── Cache fresh < 24 jam → gunakan cache
        │   └── Cache expired → ping server
        │         ├── Server respond → update cache
        │         └── Unreachable:
        │               ├── Grace < 3 hari → lanjut (warn)
        │               └── Grace > 3 hari → redirect /license/invalid
        ├── valid=true → lanjut ke app
        ├── expired=true → redirect /license/expired
        └── valid=false → redirect /license/invalid
```

#### Cache File

`storage/app/license_cache.json`:
```json
{
  "cached_at": "2026-04-26T01:30:00+07:00",
  "grace_started_at": null,
  "result": {
    "valid": true,
    "expired": false,
    "client_name": "SMA Negeri 1 Jakarta",
    "expired_at": "2026-12-31",
    "max_schools": 1,
    "max_students": 500,
    "message": "Lisensi aktif.",
    "grace_remaining_days": 0
  }
}
```

#### API Endpoint (Public)

```
POST /api/license/validate
Body: { "license_key": "XXXXXX-XXXXXX-XXXXXX-XXXXXX", "hostname": "client-server" }

Response OK:
{
  "valid": true,
  "client_name": "SMA Negeri 1 Jakarta",
  "max_schools": 1,
  "max_students": 500,
  "expired_at": "2026-12-31",
  "message": "Lisensi aktif."
}
```

#### Tabel `licenses`

| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| `license_key` | VARCHAR(32) | Key unik (format XXXXXX-XXXXXX-XXXXXX-XXXXXX) |
| `client_name` | VARCHAR(255) | Nama client/sekolah |
| `max_schools` | SMALLINT | 0 = unlimited |
| `max_students` | SMALLINT | 0 = unlimited |
| `expired_at` | DATE | null = selamanya |
| `is_active` | BOOLEAN | Toggle aktif/nonaktif |
| `allowed_hostname` | VARCHAR(255) | Lock per hostname. null = semua hostname |
| `last_ping_at` | TIMESTAMP | Terakhir client validasi |
| `notes` | TEXT | Catatan internal |

### 8. Docker (Self-Hosted)

#### Services dalam `docker-compose.yml`

| Service | Image | RAM | Keterangan |
|---------|-------|-----|-----------|
| `app` | ghcr.io/kangdaqiq/absen-multi | ~350MB | PHP-FPM + Nginx + Scheduler + Queue |
| `mysql` | mysql:8.0 | ~180MB | Database (tuned 128M buffer) |
| `whatsapp` | aldinokemal2104/go-whatsapp-web-multidevice | ~20MB | GOWA WhatsApp API |

**Total RAM: ~550MB** — cocok untuk hardware 2GB RAM.

#### Volumes

| Volume | Isi |
|--------|-----|
| `mysql_data` | Data MySQL |
| `app_storage` | File upload, backup, license cache |
| `app_logs` | Laravel logs |
| `wa_data` | Session WhatsApp (jangan dihapus!) |

#### Entrypoint Flow

```
docker compose up
    ↓ Container start
    ↓ 1. Tunggu MySQL ready (healthcheck)
    ↓ 2. php artisan migrate --force
    ↓ 3. php artisan config:cache + route:cache + view:cache
    ↓ 4. php artisan license:validate (jika self_hosted)
    ↓ 5. supervisord start:
         ├── nginx
         ├── php-fpm
         ├── laravel-scheduler (loop 60 detik)
         └── laravel-queue (queue:work)
```

#### Build & Push Image

```powershell
# Build (jalankan npm run build dulu!)
npm run build
docker build -t ghcr.io/kangdaqiq/absen-multi:latest `
             -t ghcr.io/kangdaqiq/absen-multi:v2.3.0 .

# Push
docker push ghcr.io/kangdaqiq/absen-multi:latest
docker push ghcr.io/kangdaqiq/absen-multi:v2.3.0
```

---

## 📅 Jadwal Otomatis

| Waktu | Command | Keterangan |
|-------|---------|-----------|
| Setiap menit | `absen:process-daily` | Auto Alpha/Bolos per jadwal sekolah |
| Setiap menit | `absen:daily-report` | Laporan harian WA |
| Setiap menit | `wa:process` | Proses antrian WA |
| Setiap menit | `absen:check-abnormal` | Cek siswa bermasalah |
| 00:30 | `license:validate` | Refresh cache lisensi (self-hosted) |
| 01:00 | `absen:auto-delete-history` | Hapus history melewati kuota |
| 02:00 | `db:backup` | Backup database SQL |

---

## 📊 Struktur Database

### Tabel Utama

| Tabel | Keterangan |
|-------|-----------|
| `schools` | Sekolah (`student_limit`, `teacher_limit`, `history_quota_months`) |
| `users` | User login semua role |
| `siswa` | Data siswa per `school_id` |
| `guru` | Data guru per `school_id` |
| `kelas` | Data kelas per `school_id` |
| `attendance` | Absensi siswa |
| `absensi_guru` | Absensi guru |
| `settings` | Konfigurasi per sekolah (key-value) |
| `devices` | Perangkat ESP8266/ESP32 |
| `message_queues` | Antrian notifikasi WA |
| `jadwal` | Jam masuk/pulang per hari |
| `announcements` | Pengumuman per sekolah |
| `licenses` | License key untuk client self-hosted |

### Flow Absensi

```
Siswa/Guru tap RFID/Fingerprint
    ↓ ESP8266 POST ke /api/rfid atau /api/fingerprint
    ↓ Laravel validate → simpan attendance
    ↓ Queue notifikasi WA
    ↓ Queue worker kirim via GOWA
    ↓ Orang tua/Guru terima notifikasi
```

---

## 🚀 Panduan Instalasi Lengkap

### A. Hosted (Server Developer)

```bash
# 1. Clone
git clone <repo> /var/www/absen && cd /var/www/absen

# 2. Install
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 3. Env
cp .env.example .env && php artisan key:generate
# Edit .env: APP_MODE=hosted, DB_*, WA_*

# 4. Database
php artisan migrate && php artisan storage:link && php artisan optimize

# 5. Nginx + Scheduler crontab + Supervisor (queue + gowa)
# (lihat README.md untuk detail)
```

### B. Self-Hosted Client (Docker)

```bash
# Client terima: docker-compose.yml + .env + INSTALL.txt

# Edit .env
nano .env    # isi DB_PASSWORD, DB_ROOT_PASSWORD, APP_URL

# Jalankan
docker compose up -d

# Setup WA (sekali)
# Buka http://IP-server:3001 → scan QR
```

---

## 🐛 Troubleshooting

| Masalah | Solusi |
|---------|--------|
| App self-hosted tidak bisa diakses | `docker compose logs app` |
| Lisensi invalid | `php artisan license:validate --force` |
| WA tidak terkirim | Cek queue worker, cek `message_queues` status='failed' |
| Import gagal tanpa pesan | Cek storage writable, cek log Laravel |
| Class not found | `composer dump-autoload && php artisan optimize:clear` |
| Scheduler tidak jalan | Cek crontab / cek Supervisor status |

---

## 📝 Changelog

### v2.3.0 (2026-04-26)
- 🆕 **Kelola Lisensi di Super Admin** — tidak perlu project license server terpisah
- 🆕 **API `/api/license/validate`** — built-in di app utama
- 🆕 **Lock lisensi per hostname**, regenerate key, monitor `last_ping_at`
- 🆕 **Teacher/Staff Quota** (`teacher_limit` di tabel schools)
- 🆕 **Import siswa/guru**: stop + pesan error jelas saat kuota penuh
- 🆕 **Auto Delete History Absen** — kuota retensi per sekolah
- 🔄 **GOWA (Go WhatsApp)** — ganti Node.js Baileys, hemat ~150MB RAM
- 🐳 Docker: GOWA service, MySQL memory tuning, `.dockerignore`
- 🔐 `APP_MODE` config: `hosted` (default) / `self_hosted`
- 🔐 `CheckLicense` + `SelfHostedGuard` middleware
- 🔐 Grace period 3 hari, cache 24 jam di file JSON

### v2.2.0 (2026-04-24)
- ✨ Pengumuman sekolah
- ✨ `operator_phone` per sekolah
- 🔧 Drop tabel `hari_libur`

### v2.1.0 (2026-01-11)
- ✨ Multi-tenant architecture
- ✨ Super Admin panel
- ✨ WhatsApp Multi-Device per sekolah

### v2.0.0 (2026-01-10)
- ✨ Toggle absen pulang (1x/2x scan)
- ✨ Kustomisasi waktu laporan via Settings

### v1.0.0 (2025-12-01)
- 🎉 Rilis awal: RFID + Fingerprint + WhatsApp + Dashboard

---

**Developed with ❤️ by KangDaQiQ**
© 2024-2026 — Proprietary, All Rights Reserved
