# Sistem Absensi Sekolah — Dokumentasi Teknis

## 📋 Deskripsi Project

Sistem Absensi Sekolah adalah aplikasi berbasis web Laravel untuk mengelola absensi siswa dan guru secara otomatis menggunakan RFID dan Fingerprint. Sistem ini mendukung arsitektur **multi-tenant** (banyak sekolah dalam satu instalasi), terintegrasi dengan WhatsApp via **GOWA (Go WhatsApp)**, dan dapat di-deploy secara hosted maupun self-hosted (manual) dengan proteksi sistem lisensi online.

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
| Web Server | Nginx atau Apache + PHP-FPM |
| Process Manager | Supervisor (queue worker + scheduler) |

### Mode Deployment

| | **Hosted** | **Self-Hosted** |
|---|---|---|
| Dikelola oleh | Developer (kamu) | Client di server sendiri |
| `APP_MODE` | `hosted` | `self_hosted` |
| Install | Manual (PHP, Nginx/Apache, Composer) | Manual (PHP, Apache/Nginx, Composer) |
| Super Admin | ✅ Aktif | ❌ Diblokir (404) |
| License check | ❌ Tidak perlu | ✅ Ping harian ke server developer |
| Source code | Full source code | Full source code (diterima dari provider) |
| Update | `git pull` + `migrate` | Scp/rsync dari provider + `migrate` |

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

> **[SECURITY v2.4.0]** Deteksi mode kini berbasis **integrity file**, bukan `APP_MODE`.
> Client tidak bisa bypass dengan mengubah `APP_MODE=hosted` di `.env`-nya sendiri.

```
Request web masuk
    ↓ CheckLicense middleware (global)
    ├── Cek: storage/app/license_integrity.json ada?
    │   ├── TIDAK ADA → environment developer (hosted server)
    │   │   └── Fallback: APP_MODE != self_hosted → skip, lanjut
    │   └── ADA → client release (self-hosted)
    │       └── [SECURITY] Jika APP_MODE diubah ke 'hosted' → LOG WARNING,
    │                      tetap enforce license check
    ├── Route api/*, login, license.*? → bypass
    └── Self-hosted (integrity file terdeteksi):
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

#### File Integrity

`storage/app/license_integrity.json` — dibuat otomatis oleh `build_release.php` saat
build paket client. **Tidak boleh diedit manual.** Keberadaan file ini adalah penanda
bahwa instalasi adalah self-hosted client.

```json
{
  "build_at": "2026-05-01T10:00:00+07:00",
  "checksum": "sha256-hash-..."
}
```

#### Cache File

`storage/app/license_cache.json`:
```json
{
  "cached_at": "2026-05-01T10:00:00+07:00",
  "grace_started_at": null,
  "result": {
    "valid": true,
    "expired": false,
    "client_name": "SMA Negeri 1 Jakarta",
    "expired_at": "2026-12-31",
    "max_schools": 1,
    "max_students": 500,
    "max_teachers": 10,
    "max_bot_users": 5,
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
  "max_teachers": 10,
  "max_bot_users": 5,
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
| `max_teachers` | SMALLINT | 0 = unlimited (kolom baru v2.4.0) |
| `max_bot_users` | SMALLINT | 0 = unlimited — maks user yang boleh pakai bot WA (kolom baru v2.4.0) |
| `expired_at` | DATE | null = selamanya |
| `is_active` | BOOLEAN | Toggle aktif/nonaktif |
| `allowed_hostname` | VARCHAR(255) | Lock per hostname. null = semua hostname |
| `last_ping_at` | TIMESTAMP | Terakhir client validasi |
| `notes` | TEXT | Catatan internal |

### 8. Persyaratan Server

#### Minimum Spesifikasi

| Kebutuhan | Minimum | Rekomendasi |
|-----------|---------|-------------|
| OS | Ubuntu 20.04 | Ubuntu 22.04 LTS |
| RAM | 1 GB | 2 GB |
| Storage | 10 GB | 20 GB |
| PHP | 8.2+ | 8.3 |
| MySQL | 8.0 | 8.0 |
| Web Server | Nginx atau Apache | Nginx |

#### PHP Extensions yang Dibutuhkan

```
pdo_mysql, mbstring, bcmath, gd, zip, opcache, intl, pcntl, xml, curl
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

### A. Hosted — Server Developer (Ubuntu/Debian)

```bash
# ── 1. Install dependensi sistem ──────────────────────────────────────────
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server supervisor curl git unzip

# PHP 8.3
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-bcmath \
  php8.3-gd php8.3-zip php8.3-intl php8.3-xml php8.3-curl php8.3-pcntl php8.3-opcache

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js (untuk build assets)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# ── 2. Setup MySQL ────────────────────────────────────────────────────────
sudo mysql -e "CREATE DATABASE absen_sell CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'absen_user'@'localhost' IDENTIFIED BY 'passwordKuat';"
sudo mysql -e "GRANT ALL PRIVILEGES ON absen_sell.* TO 'absen_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# ── 3. Clone & install app ────────────────────────────────────────────────
git clone <repo> /var/www/absen && cd /var/www/absen
composer install --no-dev --optimize-autoloader
npm install && npm run build

# ── 4. Konfigurasi .env ───────────────────────────────────────────────────
cp .env.example .env
nano .env
# Isi: APP_MODE=hosted, APP_URL, DB_HOST=127.0.0.1, DB_DATABASE, DB_USERNAME, DB_PASSWORD, WA_*
php artisan key:generate

# ── 5. Database migration ─────────────────────────────────────────────────
php artisan migrate --force
php artisan db:seed --class=SuperAdminSeeder
php artisan storage:link
php artisan optimize

# ── 6. Permissions ───────────────────────────────────────────────────────
sudo chown -R www-data:www-data /var/www/absen/storage /var/www/absen/bootstrap/cache
sudo chmod -R 775 /var/www/absen/storage /var/www/absen/bootstrap/cache

# ── 7. Nginx vhost ────────────────────────────────────────────────────────
# Buat file: /etc/nginx/sites-available/absen
# (lihat konfigurasi Nginx di bawah)
sudo ln -s /etc/nginx/sites-available/absen /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# ── 8. Crontab (Laravel Scheduler) ───────────────────────────────────────
# Tambahkan ke crontab (sudo crontab -e -u www-data):
# * * * * * cd /var/www/absen && php artisan schedule:run >> /dev/null 2>&1

# ── 9. Supervisor (Queue Worker) ─────────────────────────────────────────
# Buat file: /etc/supervisor/conf.d/absen-queue.conf
# (lihat konfigurasi Supervisor di bawah)
sudo supervisorctl reread && sudo supervisorctl update

# ── 10. GOWA (WhatsApp API) ───────────────────────────────────────────────
# Download binary dari: https://github.com/aldinokemal/go-whatsapp-web-multidevice/releases
wget https://github.com/aldinokemal/go-whatsapp-web-multidevice/releases/latest/download/gowa-linux-amd64
chmod +x gowa-linux-amd64 && sudo mv gowa-linux-amd64 /usr/local/bin/gowa
# Jalankan via Supervisor atau systemd
gowa rest --port=3000 --basic-auth=admin:changeme
```

#### Konfigurasi Nginx (`/etc/nginx/sites-available/absen`)

```nginx
server {
    listen 80;
    server_name yourdomain.com;  # ganti dengan domain/IP
    root /var/www/absen/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### Konfigurasi Supervisor (`/etc/supervisor/conf.d/absen-queue.conf`)

```ini
[program:absen-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/absen/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/absen/storage/logs/queue.log
```

---

### B. Self-Hosted Client (Ubuntu/Debian)

> Client menerima source code dari provider via ZIP/SCP, lalu install sendiri di server.

```bash
# ── 1. Install dependensi sistem ──────────────────────────────────────────
sudo apt update && sudo apt upgrade -y
sudo apt install -y apache2 mysql-server supervisor curl unzip

# PHP 8.3
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3 php8.3-mysql php8.3-mbstring php8.3-bcmath \
  php8.3-gd php8.3-zip php8.3-intl php8.3-xml php8.3-curl php8.3-pcntl \
  php8.3-opcache libapache2-mod-php8.3

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Enable Apache mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2

# ── 2. Setup MySQL ────────────────────────────────────────────────────────
sudo mysql -e "CREATE DATABASE absen_sell CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'absen_user'@'localhost' IDENTIFIED BY 'passwordKuat';"
sudo mysql -e "GRANT ALL PRIVILEGES ON absen_sell.* TO 'absen_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# ── 3. Extract & install app ──────────────────────────────────────────────
sudo mkdir -p /var/www/absen
sudo unzip absensi.zip -d /var/www/absen
cd /var/www/absen
composer install --no-dev --optimize-autoloader

# ── 4. Konfigurasi .env ───────────────────────────────────────────────────
cp .env.example .env
nano .env
# Wajib diisi:
#   APP_MODE=self_hosted
#   APP_URL=http://IP-SERVER
#   DB_HOST=127.0.0.1
#   DB_DATABASE=absen_sell
#   DB_USERNAME=absen_user
#   DB_PASSWORD=passwordKuat
#   LICENSE_KEY=XXXX-XXXX-XXXX-XXXX   ← dari provider
#   LICENSE_SERVER_URL=https://absen.kangdaqiq.com
#   WA_API_BASE_URL=http://localhost:3000
#   WA_API_USER=admin
#   WA_API_PASS=changeme
php artisan key:generate

# ── 5. Database migration ─────────────────────────────────────────────────
php artisan migrate --force
php artisan storage:link
php artisan optimize

# ── 6. Permissions ───────────────────────────────────────────────────────
sudo chown -R www-data:www-data /var/www/absen/storage /var/www/absen/bootstrap/cache
sudo chmod -R 775 /var/www/absen/storage /var/www/absen/bootstrap/cache

# ── 7. Apache vhost ───────────────────────────────────────────────────────
# Buat file /etc/apache2/sites-available/absen.conf (lihat di bawah)
sudo a2ensite absen.conf
sudo systemctl reload apache2

# ── 8. Crontab (Scheduler) ───────────────────────────────────────────────
(sudo crontab -u www-data -l 2>/dev/null; echo "* * * * * cd /var/www/absen && php artisan schedule:run >> /dev/null 2>&1") | sudo crontab -u www-data -

# ── 9. Supervisor (Queue Worker) ─────────────────────────────────────────
sudo nano /etc/supervisor/conf.d/absen-queue.conf
# (isi sama seperti config di atas, sesuaikan path)
sudo supervisorctl reread && sudo supervisorctl update

# ── 10. GOWA (WhatsApp API) ───────────────────────────────────────────────
wget https://github.com/aldinokemal/go-whatsapp-web-multidevice/releases/latest/download/gowa-linux-amd64
chmod +x gowa-linux-amd64 && sudo mv gowa-linux-amd64 /usr/local/bin/gowa
# Jalankan:
gowa rest --port=3000 --basic-auth=admin:changeme
# Buka browser: http://IP-SERVER:3000 → scan QR WhatsApp
```

#### Konfigurasi Apache (`/etc/apache2/sites-available/absen.conf`)

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/absen/public

    <Directory /var/www/absen/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/absen_error.log
    CustomLog ${APACHE_LOG_DIR}/absen_access.log combined
</VirtualHost>
```

#### Update Aplikasi (Client)

```bash
cd /var/www/absen

# Terima file baru dari provider (ZIP)
sudo unzip -o absensi-update.zip -d /var/www/absen

# Update dependencies & migrate
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize

# Restart queue
sudo supervisorctl restart absen-queue:*
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

### v2.4.0 (2026-05-01)
- 🔐 **[SECURITY] CheckLicense berbasis integrity file** — deteksi mode self-hosted tidak lagi bergantung `APP_MODE`, mencegah bypass client
- 🆕 **`max_teachers`** di tabel `licenses` — batasi kuota guru per lisensi (terpisah dari `teacher_limit` di `schools`)
- 🆕 **`max_bot_users`** di tabel `licenses` — batasi jumlah user yang boleh menggunakan bot WhatsApp
- 🆕 API response `/api/license/validate` kini menyertakan `max_teachers` dan `max_bot_users`
- 🔐 Log warning otomatis jika terdeteksi bypass attempt (`license_integrity.json` ada tapi `APP_MODE` diubah)

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
