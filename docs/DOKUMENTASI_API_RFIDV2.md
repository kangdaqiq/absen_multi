# 📡 Dokumentasi Resmi API & Protokol Komunikasi Firmware RFIDv2

Dokumentasi ini menjelaskan spesifikasi antarmuka pemrograman (API), protokol komunikasi, format pertukaran data (JSON/NDJSON), serta arsitektur integrasi antara **Firmware RFIDv2 (ESP8266)** dan **Backend Server Jagat Absen (Laravel)**.

---

## 📑 Daftar Isi
1. [Arsitektur & Gambaran Umum](#1-arsitektur--gambaran-umum)
2. [Spesifikasi Hardware & Pinout](#2-spesifikasi-hardware--pinout)
3. [Local Web Server API (Embedded di ESP8266)](#3-local-web-server-api-embedded-di-esp8266)
   - [3.1 Halaman Dashboard & Web Konfigurasi (`GET /`)](#31-halaman-dashboard--web-konfigurasi-get-)
   - [3.2 Pemindaian Jaringan WiFi (`GET /scan-wifi`)](#32-pemindaian-jaringan-wifi-get-scan-wifi)
   - [3.3 Simpan Konfigurasi & Reboot (`POST /save`)](#33-simpan-konfigurasi--reboot-post-save)
   - [3.4 Trigger Sinkronisasi Antrean Offline (`GET /sync`)](#34-trigger-sinkronisasi-antrean-offline-get-sync)
   - [3.5 Trigger OTA Firmware Online (`GET /update-online` & `GET /ota-trigger`)](#35-trigger-ota-firmware-online-get-update-online--get-ota-trigger)
4. [Backend Cloud REST API (`/api/rfid`)](#4-backend-cloud-rest-api-apirfid)
   - [4.1 Ping & Heartbeat Server Check (`GET /api/rfid?ping=1`)](#41-ping--heartbeat-server-check-get-apirfidping1)
   - [4.2 Transaksi Scan Presensi (`POST /api/rfid`)](#42-transaksi-scan-presensi-post-apirfid)
   - [4.3 Kamus Respon `type`, Tampilan LCD, dan Nada Buzzer](#43-kamus-respon-type-tampilan-lcd-dan-nada-buzzer)
5. [Mekanisme Offline Queue & Sinkronisasi Data](#5-mekanisme-offline-queue--sinkronisasi-data)
   - [5.1 Format Penyimpanan NDJSON](#51-format-penyimpanan-ndjson)
   - [5.2 Batching & Anti-Duplikasi](#52-batching--anti-duplikasi)
   - [5.3 Silent Background Sync vs Full Sync](#53-silent-background-sync-vs-full-sync)
6. [Mekanisme Over-the-Air (OTA) Update](#6-mekanisme-over-the-air-ota-update)
   - [6.1 ArduinoOTA (Jaringan Lokal)](#61-arduinoota-jaringan-lokal)
   - [6.2 HTTP/HTTPS Remote Update (ESPhttpUpdate)](#62-httphttps-remote-update-esphttpupdate)
7. [Penanganan Gangguan & Fail-Safe (Watchdog & Recovery)](#7-penanganan-gangguan--fail-safe-watchdog--recovery)

---

## 1. Arsitektur & Gambaran Umum

Firmware **RFIDv2** berjalan pada mikrokontroler **ESP8266** dengan arsitektur komunikasi dua arah:
1. **Inbound (Embedded Web Server)**: Berjalan di Port 80 untuk manajemen perangkat, portal konfigurasi Wi-Fi (AP mode / STA mode), pemindaian Wi-Fi lokal, dan pemicuan OTA.
2. **Outbound (HTTP Client)**: Mengirimkan data pembacaan UID RFID ke endpoint backend Laravel (`/api/rfid`), menerima status presensi, mengecek heartbeat, dan mengunduh firmware biner (`.bin`).

```mermaid
flowchart TD
    User([Siswa / Guru]) -->|Tap Kartu RFID| RC522[Modul RFID RC522]
    RC522 -->|SPI| ESP8266[ESP8266 Firmware RFIDv2]
    
    ESP8266 -->|I2C| LCD[LCD 16x2 + I2C Backpack]
    ESP8266 -->|GPIO| BZ[Buzzer Aktif]
    ESP8266 -->|I2C| RTC[DS1307 RTC Module]
    ESP8266 -->|ADC A0| BAT[Monitoring Baterai]
    
    subgraph Fail-Safe Offline
        ESP8266 -->|Simpan jika Offline| FS[(LittleFS: queue.ndjson)]
    end
    
    subgraph Komunikasi Backend
        ESP8266 -->|POST /api/rfid (Online / Sync)| Cloud[Server Laravel Jagat Absen]
        ESP8266 -->|GET ?ping=1| Cloud
        Cloud -->|JSON Response| ESP8266
    end
    
    subgraph Local Access Point
        Admin[Teknisi / Admin Sekolah] -->|HTTP Web UI| ESP8266
    end
```

---

## 2. Spesifikasi Hardware & Pinout

| Komponen | Pin ESP8266 | Deskripsi / Alamat I2C |
| :--- | :--- | :--- |
| **RFID RC522 SDA/SS** | `D0` (GPIO 16) | Chip Select SPI |
| **RFID RC522 SCK** | `D5` (GPIO 14) | SPI Clock |
| **RFID RC522 MOSI** | `D7` (GPIO 13) | SPI Master Out |
| **RFID RC522 MISO** | `D6` (GPIO 12) | SPI Master In |
| **RFID RC522 RST** | `D3` (GPIO 0) | Reset Pin |
| **LCD 16x2 I2C** | `D1` (SCL), `D2` (SDA) | Alamat `0x27` |
| **RTC DS1307** | `D1` (SCL), `D2` (SDA) | Alamat `0x68` |
| **Buzzer** | `D8` (GPIO 15) | Active High |
| **Sensor Baterai** | `A0` (ADC0) | Pembagi tegangan rasio `2.29f` |

---

## 3. Local Web Server API (Embedded di ESP8266)

Web server internal berjalan di port **80**.
- **Mode AP (Access Point)**: SSID `ABSEN-RFID`, Password `12345678`, IP Default `192.168.4.1`.
- **Mode STA (Terkoneksi)**: Menggunakan IP DHCP lokal dari router Wi-Fi.

---

### 3.1 Halaman Dashboard & Web Konfigurasi (`GET /`)
Mengembalikan antarmuka web modern responsif untuk memantau status perangkat dan konfigurasi sistem.

- **URL**: `/`
- **Method**: `GET`
- **Content-Type**: `text/html`
- **Informasi yang Ditampilkan**:
  - Status koneksi Wi-Fi & Kualitas sinyal (RSSI dBm dan persentase kualitas).
  - Alamat IP Perangkat (Local IP / SoftAP IP).
  - Versi Firmware (contoh: `4.2.2`).
  - Zona Waktu terkonfigurasi (WIB / WITA / WIT).
  - Jumlah antrean offline yang belum tersinkronisasi.
  - Form pengaturan Wi-Fi, Nama Sekolah, API Key, API Server URL, dan OTA URL.

---

### 3.2 Pemindaian Jaringan WiFi (`GET /scan-wifi`)
Memindai jaringan Wi-Fi 2.4 GHz di sekitar perangkat dan mengembalikan daftar SSID berurutan berdasarkan sinyal terkuat.

- **URL**: `/scan-wifi`
- **Method**: `GET`
- **Header**:
  - `Cache-Control: no-cache, no-store, must-revalidate`
- **Format Response**: `application/json`

#### Contoh Response:
```json
[
  {
    "ssid": "SMK_JAGAT_OFFICE",
    "rssi": -58,
    "secure": true
  },
  {
    "ssid": "GURU_HOTSPOT",
    "rssi": -72,
    "secure": true
  },
  {
    "ssid": "SMK_PUBLIC_FREE",
    "rssi": -85,
    "secure": false
  }
]
```

---

### 3.3 Simpan Konfigurasi & Reboot (`POST /save`)
Menyimpan parameter konfigurasi ke EEPROM (kapasitas 512 byte) lalu me-restart mikrokontroler setelah jeda 1,5 detik.

- **URL**: `/save`
- **Method**: `POST`
- **Content-Type**: `application/x-www-form-urlencoded`
- **Parameter Form**:
  | Parameter | Tipe | Wajib | Keterangan |
  | :--- | :--- | :--- | :--- |
  | `ssid` | `string(32)` | Ya | Nama jaringan Wi-Fi target |
  | `pass` | `string(32)` | Tidak | Password Wi-Fi (jika kosong, mempertahankan password lama) |
  | `school` | `string(32)` | Ya | Nama sekolah yang muncul sebagai running text pada LCD |
  | `timezone` | `int` | Ya | `7` (WIB), `8` (WITA), `9` (WIT) |
  | `ota` | `string(128)`| Ya | URL binary firmware remote (`.bin`) |
  | `apikey` | `string(65)` | Ya | API Key perangkat terdaftar di Laravel |
  | `apiurl` | `string(128)`| Ya | Base URL server backend (contoh: `http://absen.jagattech.my.id`) |

#### Contoh Request:
```http
POST /save HTTP/1.1
Host: 192.168.4.1
Content-Type: application/x-www-form-urlencoded

ssid=SMK_JAGAT_OFFICE&pass=rahasia123&school=SMK+NEGERI+1+JAGAT&timezone=7&ota=http%3A%2F%2Fabsen.jagattech.my.id%2Ffirmware%2FRFIDV2.bin&apikey=dev_key_abc123xyz456&apiurl=http%3A%2F%2Fabsen.jagattech.my.id
```

#### Response (200 OK):
```html
<h3>Tersimpan! Restart 3 detik...</h3>
```

---

### 3.4 Trigger Sinkronisasi Antrean Offline (`GET /sync`)
Memicu pengiriman manual seluruh antrean presensi yang tersimpan di LittleFS (`/queue.ndjson`) ke backend server secara batch.

- **URL**: `/sync`
- **Method**: `GET`
- **Response**: `text/html` dengan pesan `"<h3>Sinkronisasi dimulai...</h3>"`.
- **Indikator LCD**: Menampilkan teks `SINKRONISASI` pada baris 1 dan counter `X/Total` pada baris 2.

---

### 3.5 Trigger OTA Firmware Online (`GET /update-online` & `GET /ota-trigger`)
Memicu pengunduhan firmware secara over-the-air dari URL yang telah ditentukan.

#### A. Trigger dari Konfigurasi EEPROM (`GET /update-online`)
- **URL**: `/update-online`
- **Method**: `GET`
- Menggunakan nilai `cfg.otaUrl` yang tersimpan di EEPROM.

#### B. Trigger Custom URL (`GET /ota-trigger`)
- **URL**: `/ota-trigger?url={binary_url}`
- **Method**: `GET`
- **Query Parameter**:
  - `url` (URL-encoded): Alamat direct download file `.bin` firmware.

#### Response:
```html
<h3>Memicu Update Kustom...</h3><p>Cek layar perangkat.</p>
```

---

## 4. Backend Cloud REST API (`/api/rfid`)

Komunikasi antara firmware RFIDv2 dengan backend Laravel Jagat Absen berlangsung melalui routing controller `App\Http\Controllers\Api\RfidController`.

---

### 4.1 Ping & Heartbeat Server Check (`GET /api/rfid?ping=1`)
Digunakan oleh firmware untuk mengecek apakah server aktif tanpa menyentuh database secara berat.

- **URL**: `{apiurl}/api/rfid?ping=1`
- **Method**: `GET`
- **Timeout Firmware**: 3000 ms
- **Response (200 OK)**:
```json
{
  "ok": true,
  "message": "pong"
}
```
*Catatan Perilaku*: Jika server offline, firmware menjalankan recovery ping tiap 2 detik sekali sampai server hidup kembali. Saat server online, ping periodik dihentikan untuk efisiensi jaringan.

---

### 4.2 Transaksi Scan Presensi (`POST /api/rfid`)
Dipanggil setiap kali kartu RFID didekatkan ke sensor atau saat sinkronisasi antrean offline.

- **URL**: `{apiurl}/api/rfid`
- **Method**: `POST`
- **Headers**:
  - `Content-Type: application/json`
- **Timeout Firmware**: 8000 ms (Online scan), 5000 ms (Batch sync), 3000 ms (Silent sync).

#### Schema Request Body:
```json
{
  "api_key": "string (wajib, max 64 karakter)",
  "uid": "string (wajib, 8-20 karakter hex uppercase, contoh: 'A1B2C3D4')",
  "scanned_at": "string (opsional, format 'YYYY-MM-DD HH:mm:ss')"
}
```
> **Catatan `scanned_at`**: Hanya dikirimkan saat sinkronisasi antrean offline (LittleFS). Jika diisi, backend menggunakan waktu tersebut sebagai waktu presensi nyata (dibatasi maksimal mundur 7 hari ke belakang). Jika tidak diisi, backend menggunakan waktu server saat ini (`now()`).

#### Contoh Request:
```bash
curl -X POST "http://absen.jagattech.my.id/api/rfid" \
  -H "Content-Type: application/json" \
  -d '{
    "api_key": "abc123devicekey",
    "uid": "53A26B1E",
    "scanned_at": "2026-10-30 07:15:22"
  }'
```

---

### 4.3 Kamus Respon `type`, Tampilan LCD, dan Nada Buzzer

Backend Laravel mengembalikan format standar berikut:
```json
{
  "ok": true,
  "status": "success",
  "message": "Pesan deskriptif",
  "sound": "ok",
  "timestamp": "2026-10-30 07:15:22",
  "type": "absen_masuk",
  "nama": "Ahmad Dani"
}
```

Firmware mem-parsing properti `ok`, `message`, `nama`, dan `type` untuk menentukan teks LCD 16x2 dan pola bunyi buzzer:

| Nilai `type` | `ok` | Baris 1 LCD | Baris 2 LCD | Pola Buzzer | Penjelasan Skenario |
| :--- | :---: | :--- | :--- | :--- | :--- |
| `absen_masuk` | `true` | `ABSEN MASUK` | Nama Siswa | `beepOK()` (2x pendek) | Siswa berhasil absen masuk sekolah. |
| `absen_pulang` | `true` | `ABSEN PULANG` | Nama Siswa | `beepOK()` (2x pendek) | Siswa berhasil absen pulang sekolah. |
| `absen_masuk_guru` | `true` | `ABSEN MASUK` | Nama Guru | `beepOK()` (2x pendek) | Guru berhasil absen masuk sesuai shift. |
| `absen_pulang_guru` | `true` | `ABSEN PULANG` | Nama Guru | `beepOK()` (2x pendek) | Guru berhasil absen pulang sesuai shift. |
| `sudah_absen_masuk` | `true` | `SUDAH ABSEN` | Nama Siswa | `beepShort()` (1x pendek) | Kartu di-tap kembali sebelum jam pulang dibuka. |
| `sudah_lengkap` | `true` | `ABSEN LENGKAP` | Nama Siswa | `beepShort()` (1x pendek) | Siswa sudah lengkap absen masuk dan pulang hari ini. |
| `absen_kegiatan` | `true` | `SUKSES` | Nama Siswa | `beepOK()` (2x pendek) | Kartu di-tap saat ada kegiatan ekskul/tambahan aktif. |
| `enroll_rfid` | `true` | `ENROLL SUKSES` | Nama User | `beepTriple()` (3x cepat) | Kartu baru berhasil didaftarkan ke siswa/guru. |
| `gate_opened` | `true` | `PULANG DIBUKA` | Nama Kartu Gerbang | `beepTriple()` (3x cepat) | Kartu gerbang di-tap, membuka izin pulang cepat 30 menit. |
| `gate_closed` | `true` | `PULANG DITUTUP`| Nama Kartu Gerbang | `beepTriple()` (3x cepat) | Sesi izin gerbang ditutup kembali. |
| `unknown_card` | `false`| `ABSEN GAGAL!` | Pesan Error | `beepError()` (1x panjang) | UID tidak terdaftar di database sekolah. |
| `class_disabled` | `false`| `ABSEN GAGAL!` | Pesan Error | `beepError()` (1x panjang) | Fitur presensi untuk kelas siswa dinonaktifkan. |
| `schedule_empty` | `false`| `ABSEN GAGAL!` | Pesan Error | `beepError()` (1x panjang) | Tidak ada jadwal aktif / hari libur sekolah. |
| `outside_checkin_window` | `false`| `ABSEN GAGAL!` | Pesan Error | `beepError()` (1x panjang) | Di luar jam scan masuk guru/shift. |
| `outside_checkout_window`| `false`| `ABSEN GAGAL!` | Pesan Error | `beepError()` (1x panjang) | Di luar jam scan pulang guru/shift. |
| `no_shift` | `false`| `ABSEN GAGAL!` | Pesan Error | `beepError()` (1x panjang) | Guru tidak memiliki shift aktif pada hari ini. |
| `invalid_uid` | `false`| `ABSEN GAGAL!` | Pesan Error | `beepError()` (1x panjang) | Format UID tidak sesuai pola Hex 8-20 digit. |
| `scan_cooldown` | `false`| `ABSEN GAGAL!` | Pesan Error | `beepError()` (1x panjang) | Menghindari tap ganda berturut-turut pada backend. |
| *(Koneksi Gagal / Timeout)* | `false`| `ERROR JARINGAN` | `Err:XXX` / `Conn Failed` | `beepNetwork()` (3x sedang) | Perangkat otomatis beralih menyimpan data ke LittleFS. |

---

## 5. Mekanisme Offline Queue & Sinkronisasi Data

Firmware dilengkapi sistem antrean offline berbasis flash memory LittleFS untuk menjamin tidak ada data presensi yang hilang saat Wi-Fi atau internet padam.

### 5.1 Format Penyimpanan NDJSON
Antrean disimpan di file `/queue.ndjson` dalam format **Newline Delimited JSON (NDJSON)**: 1 entri per baris tanpa array pembungkus, sehingga hemat RAM dan dapat ditambahkan secara append langsung (`mode: "a"`).

```json
{"uid":"53A26B1E","ts":"2026-10-30 07:15:00"}
{"uid":"7CA12B0F","ts":"2026-10-30 07:15:00"}
{"uid":"9A3F11E2","ts":"2026-10-30 07:16:15"}
```
- **Kapasitas Maksimal**: 2.000 entri (ukuran file ~100 KB di flash 1-4 MB).
- **Toleransi Flash**: Sangat aman untuk siklus hidup chip SPI flash ESP8266.

### 5.2 Batching & Anti-Duplikasi
- **Batching Jeda 30 Menit**: Setiap scan baru akan menggunakan timestamp aktif batch. Jika jeda scan berikutnya melebihi `30 menit` (`OFFLINE_BATCH_TIMEOUT = 1800000 ms`), batch baru dibentuk dari RTC.
- **Anti-Duplikasi**: Kartu yang sama di-tap berkali-kali dalam batch 30 menit yang sama tidak akan disimpan dobel ke antrean offline.

### 5.3 Silent Background Sync vs Full Sync
Firmware memiliki 2 mode sinkronisasi:
1. **Full Sync (`syncOfflineQueue`)**:
   - Berjalan saat boot pertama kali terkoneksi internet atau saat diakses via menu Web UI `/sync`.
   - Mengambil alih LCD untuk menampilkan progress `X/Total`.
   - Entri yang berhasil dihapus, entri yang gagal dituliskan ke file sementara `/queue_tmp.ndjson` untuk dicoba kembali nanti.
2. **Silent Sync (`syncOneOfflineRecordSilent`)**:
   - Berjalan otomatis di latar belakang saat kondisi:
     - Wi-Fi terhubung (`WL_CONNECTED`)
     - Server terkonfirmasi hidup (`serverOnline == true`)
     - Layar LCD sedang dalam status `LCD_STANDBY`
     - Terdapat antrean (`getQueueCount() > 0`)
   - Mengirimkan **1 entri setiap 3 detik** (`millis() - lastSilentSync > 3000`).
   - Tidak memblokir pembacaan RFID dan tidak mengubah tampilan LCD, sehingga alur absensi siswa di pagi hari tetap lancar tanpa jeda layar.

---

## 6. Mekanisme Over-the-Air (OTA) Update

### 6.1 ArduinoOTA (Jaringan Lokal)
Memungkinkan teknisi mengunggah firmware langsung dari Arduino IDE / PlatformIO / VS Code melalui jaringan Wi-Fi lokal.
- **Hostname**: `ABSEN-RFID` (mDNS responder aktif)
- **Port**: `8266`
- **Password**: `04112000`
- **Tampilan LCD**: Menampilkan progress persentase unduhan (`Prog: X%`) dan menyalakan backlight.

### 6.2 HTTP/HTTPS Remote Update (ESPhttpUpdate)
Firmware dapat mengunduh biner baru dari server terpusat secara otomatis.
- **Dukungan Protokol**:
  - `HTTP`: Menggunakan `WiFiClient`.
  - `HTTPS`: Menggunakan `WiFiClientSecure` dengan mode `setInsecure()` dan buffer `1024` byte (kompatibel dengan GitHub Releases / AWS S3 / Cloudflare).
- **Cache Buster**: Menambahkan parameter timestamp `?t={millis}` pada URL agar unduhan tidak tertahan cache proxy/CDN.
- **Auto-Reboot**: Jika kode update berhasil (`HTTP_UPDATE_OK`), perangkat otomatis me-reboot mikrokontroler ke firmware baru.

---

## 7. Penanganan Gangguan & Fail-Safe

1. **Hardware Watchdog Timer**:
   - Diatur dengan interval **30 detik** menggunakan `Ticker`.
   - Rutin `feedWatchdog()` dipanggil di seluruh loop proses pembacaan kartu, sync HTTP, dan scan Wi-Fi.
   - Jika terjadi *freeze* atau *deadlock*, mikrokontroler akan menampilkan `SYSTEM RESTART - Watchdog Trigger` lalu me-reboot secara otomatis.
2. **Koreksi Waktu Real-Time (RTC & NTP)**:
   - Jam utama dikelola oleh RTC DS1307 fisik dengan backup baterai koin CR2032.
   - Setiap kali terhubung ke internet, firmware melakukan query SNTP ke `pool.ntp.org` / `time.nist.gov` dengan offset zona waktu dinamis (WIB +7, WITA +8, WIT +9), lalu mengoreksi waktu RTC hardware secara otomatis.
3. **Card Cooldown**:
   - Proteksi kartu ganda (`CARD_COOLDOWN_TIME = 2000 ms`).
   - Mencegah kartu yang sama terbaca berulang kali dalam jeda di bawah 2 detik.
4. **Auto AP Fallback**:
   - Jika koneksi Wi-Fi terputus lebih dari 30 detik, firmware otomatis menghidupkan kembali SoftAP (`ABSEN-RFID`) agar teknisi dapat terhubung untuk troubleshooting.
