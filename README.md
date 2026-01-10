# 📚 Sistem Absensi Sekolah

Sistem absensi berbasis web untuk sekolah dengan dukungan RFID dan Fingerprint, dilengkapi dengan notifikasi WhatsApp otomatis dan laporan kehadiran real-time.

## ✨ Fitur Utama

### 🎯 Absensi Multi-Mode
- **RFID Card** - Absensi menggunakan kartu RFID
- **Fingerprint** - Absensi menggunakan sidik jari
- **Mode Fleksibel** - Pilih antara absensi 1x (masuk saja) atau 2x (masuk + pulang)
- **Gate Control** - Sistem buka/tutup gerbang dengan otorisasi guru

### 📱 Notifikasi WhatsApp
- Notifikasi check-in otomatis ke siswa dan orang tua
- Notifikasi check-out dengan durasi kehadiran
- Laporan harian kehadiran ke grup kelas
- Laporan siswa bermasalah (Alpha/Bolos berlebihan)
- Broadcast pesan ke kelas tertentu atau semua siswa

### 📊 Manajemen & Laporan
- Dashboard real-time kehadiran
- Laporan harian, mingguan, dan bulanan
- Export data ke Excel/CSV
- Import data siswa dari Excel/CSV
- Deteksi otomatis siswa Alpha dan Bolos
- Laporan siswa dengan ketidakhadiran berlebihan

### 👥 Manajemen Pengguna
- Multi-role: Admin, Guru, Siswa
- Manajemen kelas dan mata pelajaran
- Jadwal pelajaran dan guru mengajar
- Wali kelas untuk laporan per kelas
- Registrasi siswa via WhatsApp Bot

### ⚙️ Konfigurasi Fleksibel
- **Toggle Absen Pulang** - Aktifkan/nonaktifkan absen pulang
- Pengaturan jadwal otomatis (scheduler)
- Konfigurasi toleransi keterlambatan
- Hari libur custom
- Mode aktif/nonaktif per kelas

### 🤖 WhatsApp Bot
- Registrasi siswa otomatis
- Cek rekap kehadiran
- Notifikasi otomatis
- Pesan broadcast

## 🛠️ Teknologi

- **Backend**: Laravel 11.x
- **Frontend**: Bootstrap 5, jQuery
- **Database**: MySQL/MariaDB
- **Hardware**: ESP8266/ESP32 (RFID/Fingerprint)
- **WhatsApp**: Evolution API
- **Scheduler**: Laravel Task Scheduling

## 📋 Persyaratan Sistem

- PHP >= 8.2
- Composer
- MySQL/MariaDB >= 5.7
- Node.js & NPM (untuk asset compilation)
- Web Server (Apache/Nginx)
- Evolution API untuk WhatsApp (opsional)

## 🚀 Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/kangdaqiq/absen_pro.git
cd absen_pro
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit file `.env` dan sesuaikan konfigurasi database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absen_db
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Import Database

Import file SQL ke database:

```bash
mysql -u root -p absen_db < database/migrations/absen.sql
```

Atau jalankan migration:

```bash
php artisan migrate
```

### 5. Seed Data (Opsional)

```bash
php artisan db:seed
```

### 6. Build Assets

```bash
npm run build
```

### 7. Jalankan Server

```bash
php artisan serve
```

Akses aplikasi di: `http://localhost:8000`

### 8. Setup Scheduler (Production)

Tambahkan cron job untuk menjalankan scheduler:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## 🔧 Konfigurasi Hardware

### ESP8266/ESP32 RFID

Upload kode dari folder `esp8266_code/` ke ESP8266/ESP32 dengan konfigurasi:

```cpp
const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";
const char* serverUrl = "http://your-server.com/api/rfid";
const char* apiKey = "YOUR_API_KEY";
```

### Fingerprint Scanner

Gunakan sensor fingerprint yang kompatibel dengan ESP32 dan upload kode yang sesuai.

## 📖 Penggunaan

### Login Default

- **Admin**: 
  - Email: `admin@example.com`
  - Password: `password`

### Fitur Toggle Absen Pulang

1. Login sebagai admin
2. Buka menu **Pengaturan Sekolah**
3. Di tab **Umum**, cari toggle **"Aktifkan Absen Pulang"**
4. **ON** = Sistem 2x scan (masuk + pulang dengan otorisasi guru)
5. **OFF** = Sistem 1x scan (hanya masuk)
6. Klik **Simpan Pengaturan**

### Cara Kerja Absensi

#### Mode 2x Scan (Absen Pulang Aktif)
1. Siswa scan RFID/fingerprint pagi → Absen masuk berhasil
2. Siswa scan lagi sebelum jam pulang → "Sudah Absen Masuk"
3. Guru tap kartu untuk buka gerbang → Gerbang dibuka (15 menit)
4. Siswa scan lagi → Absen pulang berhasil + notifikasi WhatsApp

#### Mode 1x Scan (Absen Pulang Nonaktif)
1. Siswa scan RFID/fingerprint pagi → Absen masuk berhasil
2. Siswa scan lagi kapan saja → "Absen Lengkap" (tidak perlu otorisasi guru)
3. Tidak ada proses check-out
4. Auto-bolos tidak akan menandai siswa sebagai "Bolos"

### WhatsApp Bot Commands

- `daftar` - Registrasi siswa baru
- `rekap` - Lihat rekap kehadiran
- `help` - Bantuan penggunaan bot

## 📁 Struktur Folder

```
absen_pro/
├── app/
│   ├── Console/Commands/      # Scheduled commands
│   ├── Http/Controllers/      # Controllers
│   ├── Models/                # Eloquent models
│   └── Services/              # Business logic services
├── database/
│   └── migrations/            # Database migrations & SQL
├── esp8266_code/              # ESP8266/ESP32 firmware
├── public/                    # Public assets
├── resources/
│   └── views/                 # Blade templates
└── routes/                    # Route definitions
```

## 🔄 Update & Maintenance

### Update Kode dari Git

```bash
git pull origin main
composer install
php artisan migrate
php artisan cache:clear
php artisan config:clear
```

### Backup Database

```bash
php artisan db:backup
```

Atau gunakan fitur backup otomatis yang sudah terjadwal.

## 🐛 Troubleshooting

### Error: "Class not found"
```bash
composer dump-autoload
php artisan optimize:clear
```

### Scheduler tidak berjalan
Pastikan cron job sudah ditambahkan dan berjalan:
```bash
crontab -l  # Lihat cron jobs
crontab -e  # Edit cron jobs
```

### WhatsApp tidak terkirim
1. Cek koneksi Evolution API
2. Pastikan nomor WhatsApp sudah terdaftar
3. Cek log di `storage/logs/laravel.log`

## 📝 Changelog

### v1.1.0 (2026-01-10)
- ✨ Fitur toggle absen pulang (1x atau 2x scan)
- 🔧 Auto-bolos menyesuaikan dengan mode absensi
- 📱 Update UI settings untuk toggle absen pulang
- 🐛 Fix berbagai bug minor

### v1.0.0 (2025-12-01)
- 🎉 Rilis awal sistem absensi
- ✨ Dukungan RFID dan Fingerprint
- 📱 Integrasi WhatsApp
- 📊 Dashboard dan laporan

## 🤝 Kontribusi

Kontribusi sangat diterima! Silakan buat pull request atau laporkan issue.

## 📄 Lisensi

Project ini dilisensikan di bawah [MIT License](LICENSE).

## 👨‍💻 Developer

Dikembangkan oleh **Ahmad Daqiqi** ([@kangdaqiq](https://github.com/kangdaqiq))

## 📞 Support

Jika ada pertanyaan atau butuh bantuan:
- Email: kangdaqiq@gmail.com
- GitHub Issues: [Create Issue](https://github.com/kangdaqiq/absen_pro/issues)

---

⭐ Jika project ini membantu, jangan lupa beri star di GitHub!
