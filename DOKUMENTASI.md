# Sistem Absensi Sekolah

## 📋 Deskripsi Project

Sistem Absensi Sekolah adalah aplikasi berbasis web yang dibangun menggunakan Laravel untuk mengelola absensi siswa dan guru secara otomatis menggunakan teknologi RFID dan Fingerprint. Sistem ini terintegrasi dengan WhatsApp untuk notifikasi real-time dan dilengkapi dengan berbagai fitur pelaporan dan monitoring.

## 🎯 Fitur Utama

### 1. **Manajemen Data Master**
- **Data Siswa**
  - CRUD (Create, Read, Update, Delete) data siswa
  - Import/Export data siswa via CSV/Excel
  - Generate akun login otomatis untuk siswa
  - Enroll RFID Card dan Fingerprint
  - Manajemen nomor WhatsApp orang tua

- **Data Guru**
  - CRUD data guru
  - Import/Export data guru via CSV/Excel
  - Enroll RFID Card dan Fingerprint
  - Manajemen nomor WhatsApp pribadi

- **Data Kelas**
  - Manajemen kelas dengan wali kelas
  - Toggle status absensi aktif/non-aktif per kelas
  - Toggle status laporan aktif/non-aktif per kelas
  - Integrasi grup WhatsApp per kelas

- **Jadwal Absensi**
  - Jadwal jam masuk dan pulang

- **Pengumuman & Informasi**
  - Manajemen pengumuman sekolah
  - Tampilan pengumuman interaktif di dashboard

### 2. **Sistem Absensi**

#### Absensi Siswa
- **Metode Absensi:**
  - RFID Card (Tap kartu)
  - Fingerprint (Sidik jari)
  
- **Jenis Status:**
  - ✅ **H (Hadir)** - Siswa hadir tepat waktu
  - ⏰ **T (Terlambat)** - Siswa hadir terlambat
  - ❌ **A (Alpha)** - Siswa tidak hadir tanpa keterangan
  - 🏃 **B (Bolos)** - Siswa absen masuk tapi tidak absen pulang
  - 📝 **I (Izin)** - Siswa izin (input manual)
  - 🤒 **S (Sakit)** - Siswa sakit (input manual)

- **Fitur:**
  - Auto-detect terlambat berdasarkan toleransi
  - Auto-mark Alpha untuk siswa yang tidak hadir
  - Auto-mark Bolos untuk siswa yang tidak absen pulang
  - Perhitungan durasi kehadiran otomatis
  - Edit dan hapus data absensi manual

#### Absensi Guru
- **Metode Absensi:**
  - RFID Card
  - Fingerprint
  
- **Fitur:**
  - Absensi masuk dan pulang
  - Kontrol gerbang otomatis (buka/tutup)
  - Auto-close gerbang setelah 15 menit
  - Rekap kehadiran guru

### 3. **Sistem Pelaporan**

#### Rekap Absensi Siswa
- Filter berdasarkan:
  - Tanggal (range)
  - Kelas
  - Status kehadiran
- Export ke:
  - Excel/CSV
  - PDF (dengan kop surat sekolah)
- Detail per siswa dengan statistik lengkap

#### Rekap Absensi Guru
- Filter berdasarkan tanggal
- Export Excel dan PDF
- Statistik kehadiran per guru

#### Laporan Siswa Bermasalah
- **Deteksi Otomatis:**
  - Threshold ketidakhadiran (default: 3 hari)
  - Periode pengecekan (max: 180 hari / 1 semester)
  - Filter: Hanya siswa yang **tidak hadir hari ini** DAN melebihi threshold
  
- **Fitur:**
  - Laporan harian otomatis (default jam 16:00)
  - Waktu laporan dapat dikustomisasi
  - Export ke CSV
  - Detail breakdown Alpha dan Bolos per siswa

### 4. **Notifikasi WhatsApp**

#### Notifikasi Real-time
- **Untuk Orang Tua:**
  - Notifikasi saat siswa tap masuk
  - Notifikasi saat siswa tap pulang
  - Informasi status (Hadir/Terlambat)
  - Durasi kehadiran
  - Lokasi tap (jika tersedia)

- **Untuk Guru:**
  - Laporan kehadiran kelas harian
  - Laporan siswa bermasalah

#### Laporan Terjadwal
- **Laporan Harian Final** (setelah proses auto-bolos)
  - Daftar siswa Alpha
  - Daftar siswa Bolos
  - Daftar siswa Izin/Sakit
  - Dikirim ke grup kelas dan grup guru

- **Laporan Siswa Bermasalah** (harian, customizable)
  - Siswa dengan akumulasi ketidakhadiran tinggi
  - Hanya yang tidak hadir hari ini
  - Dikirim ke grup kelas dan wali kelas

#### Broadcast Manual
- Kirim pesan ke:
  - Semua siswa
  - Siswa per kelas (pilih multiple)
  - Custom message

### 5. **Otomatisasi (Scheduler)**

Sistem menjalankan task otomatis setiap hari:

| Waktu | Task | Deskripsi |
|-------|------|-----------|
| 08:15 | Laporan Pagi | Kirim rekap kehadiran ke grup |
| 13:30 | Proses Harian | Mark Alpha/Bolos otomatis |
| 16:00* | Cek Abnormal | Laporan siswa bermasalah |
| 23:59 | Backup DB | Backup database otomatis |

*Waktu dapat dikustomisasi via Settings

**Catatan:** Scheduler tidak berjalan pada:
- Hari Minggu

### 6. **Manajemen Perangkat**

- **Device Management:**
  - Registrasi perangkat ESP8266
  - Monitor status online/offline
  - Konfigurasi per device
  - API Key management

- **Enroll RFID & Fingerprint:**
  - Request enroll dari web
  - Proses enroll di device
  - Auto-sync ke database
  - Cancel enroll
  - Delete UID/Fingerprint

### 7. **Pengaturan Sistem**

#### Konfigurasi Umum
- Nama sekolah (dinamis)
- Logo sekolah (upload)
- Alamat sekolah (untuk kop surat)
- Toleransi checkout guru (menit)

#### Konfigurasi Jadwal Otomatis
- Waktu proses harian
- Waktu laporan harian
- Waktu backup database
- Waktu laporan siswa bermasalah

#### Konfigurasi Notifikasi
- Target grup WhatsApp untuk laporan
- Toggle aktif/non-aktif notifikasi
- Threshold ketidakhadiran
- Periode pengecekan (1-180 hari)

### 8. **Manajemen User & Keamanan**

- **Role-based Access Control:**
  - **Admin:** Full access
  - **Teacher:** View & limited edit
  - **Student:** View dashboard pribadi

- **User Management:**
  - CRUD users
  - Bulk delete
  - Password management

### 9. **Backup & Restore**

- **Backup Database:**
  - Manual backup
  - Auto backup harian (23:59)
  - Download file backup (.sql)
  - Delete backup lama

- **Restore:**
  - Upload file backup
  - Restore database

### 10. **Logging & Monitoring**

- **WhatsApp Logs:**
  - History pesan terkirim
  - Status pengiriman
  - Filter dan search

- **API Logs:**
  - Log request dari ESP8266
  - Debugging device issues
  - Monitor aktivitas



## 🏗️ Arsitektur Sistem

### Tech Stack
- **Backend:** Laravel 11.x
- **Frontend:** Blade Templates, Bootstrap 4 (SB Admin 2)
- **Database:** MySQL
- **Queue:** Database Queue
- **Hardware:** ESP8266 + RFID RC522 + Fingerprint Sensor
- **Integration:** WhatsApp Multi-Device API (Node.js Baileys)

### Struktur Database

**Tabel Utama:**
- `users` - Data user login
- `siswa` - Data siswa
- `guru` - Data guru
- `kelas` - Data kelas
- `attendance` - Data absensi siswa
- `absensi_guru` - Data absensi guru
- `settings` - Konfigurasi sistem
- `devices` - Perangkat ESP8266
- `message_queue` - Antrian pesan WhatsApp
- `jadwal` - Jam masuk/pulang
- `announcements` - Data pengumuman sekolah
- `whatsapp_devices` - Data Multi-Device WhatsApp

### Flow Absensi

```
1. Siswa/Guru tap RFID/Fingerprint di device
2. ESP8266 kirim data ke API Laravel
3. Laravel validasi dan simpan ke database
4. Laravel queue notifikasi WhatsApp
5. Background job kirim notifikasi
6. Orang tua/Guru terima notifikasi
```

### Flow Scheduler

```
1. Cron job trigger Laravel Scheduler (setiap menit)
2. Scheduler cek waktu dan task
3. Jalankan command sesuai jadwal
4. Command proses data dan queue notifikasi
5. Background job kirim notifikasi
```

## 🚀 Instalasi

### Requirements
- PHP >= 8.1
- Composer
- MySQL/MariaDB
- Node.js & NPM (untuk asset compilation)
- ESP8266 dengan RFID RC522 dan/atau Fingerprint Sensor
- Node.js (untuk WhatsApp Multi-Device API)

### Langkah Instalasi

1. **Clone Repository**
   ```bash
   git clone <repository-url>
   cd absen
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Setup Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Konfigurasi Database**
   Edit file `.env`:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=absensi
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Migrasi Database**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Setup Storage**
   ```bash
   php artisan storage:link
   ```

7. **Compile Assets**
   ```bash
   npm run build
   ```

8. **Setup Scheduler (Cron)**
   Tambahkan ke crontab:
   ```
   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
   ```

9. **Jalankan Queue Worker**
   ```bash
   php artisan queue:work --daemon
   ```

10. **Akses Aplikasi**
    ```
    http://localhost/absen/public
    ```

### Default Login
- **Username:** admin
- **Password:** password

## 📱 Integrasi ESP8266

### API Endpoints untuk Device

**Base URL:** `http://your-domain.com/api`

#### 1. Check-in (Tap Masuk)
```
POST /checkin
Content-Type: application/json

{
  "device_id": "ESP001",
  "uid": "A1B2C3D4",
  "timestamp": "2024-01-08 07:30:00"
}
```

#### 2. Check-out (Tap Pulang)
```
POST /checkout
Content-Type: application/json

{
  "device_id": "ESP001",
  "uid": "A1B2C3D4",
  "timestamp": "2024-01-08 14:30:00"
}
```

#### 3. Enroll Status Check
```
GET /enroll-status?device_id=ESP001
```

#### 4. Fingerprint Enroll Check
```
GET /finger-enroll-status?device_id=ESP001
```

### Kode ESP8266

Kode untuk ESP8266 tersedia di folder `esp8266_code/`.

## 🔧 Konfigurasi

### WhatsApp Multi-Device (Baileys)

Jalankan service WhatsApp API (Node.js):
```bash
cd restapi-wa
npm install
npm start
```
Konfigurasi device WhatsApp dilakukan melalui menu **Super Admin > WhatsApp Devices** di aplikasi web.

### Timezone

Edit `config/app.php`:
```php
'timezone' => 'Asia/Jakarta',
```

## 📊 Penggunaan

### 1. Setup Awal

1. Login sebagai admin
2. Buka **Konfigurasi > Pengaturan Umum**
3. Isi data sekolah (nama, logo, alamat)
4. Atur jadwal otomatis
5. Konfigurasi notifikasi WhatsApp

### 2. Input Data Master

1. **Kelas:** Buat kelas dan assign wali kelas
2. **Guru:** Input data guru dan enroll RFID/Fingerprint
3. **Siswa:** Input/Import data siswa dan enroll RFID/Fingerprint
4. **Jadwal:** Atur jam masuk/pulang

### 3. Monitoring Absensi

1. **Dashboard:** Lihat statistik real-time
2. **Absensi Siswa:** Monitor kehadiran harian
3. **Rekap:** Generate laporan periode tertentu
4. **Laporan Bermasalah:** Monitor siswa dengan ketidakhadiran tinggi

### 4. Notifikasi

Sistem akan otomatis mengirim notifikasi sesuai konfigurasi.

## 🛠️ Maintenance

### Backup Database

**Manual:**
```bash
php artisan db:backup
```

**Otomatis:**
Sudah terjadwal setiap hari jam 23:59

### Clear Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Update Sistem

```bash
git pull origin main
composer install
php artisan migrate
php artisan cache:clear
```

## 🐛 Troubleshooting

### Scheduler Tidak Jalan

1. Pastikan cron job sudah disetup
2. Cek log: `storage/logs/laravel.log`
3. Test manual: `php artisan schedule:run`

### Notifikasi WhatsApp Tidak Terkirim

1. Cek service Node.js Baileys
2. Cek queue worker: `php artisan queue:work`
3. Cek tabel `message_queue`

### Device ESP8266 Tidak Terhubung

1. Cek koneksi WiFi ESP8266
2. Cek API URL di kode ESP8266
3. Cek log API: Menu **Sistem > Log API**

## 📝 Changelog

### Version 2.0 (2026-01-08)
- ✅ Implementasi Dynamic School Name & Logo
- ✅ Refactor Weekly ke Daily Abnormal Attendance Check
- ✅ Tambah filter "Absent Today" untuk laporan
- ✅ Extend periode check hingga 180 hari (1 semester)
- ✅ Customizable report time via Settings
- ✅ Update UI labels dan dokumentasi

### Version 1.0
- ✅ Sistem absensi RFID & Fingerprint
- ✅ Notifikasi WhatsApp real-time
- ✅ Scheduler otomatis
- ✅ Rekap dan export laporan
- ✅ Multi-role access control

## 👨‍💻 Developer

**Developed with ❤️ by KangDaQiQ**

## 📄 License

Proprietary - All Rights Reserved

---

**© 2024-2026 SMK Assuniyah Tumijajar**
