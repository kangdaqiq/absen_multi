# 📡 Panduan Integrasi REST API Backend — Perangkat RFIDv2 (ESP8266)

Dokumentasi ini ditujukan bagi **pengembang pihak ketiga / klien** yang ingin membangun **Backend Server sendiri** untuk berkomunikasi dengan perangkat mesin absensi **RFIDv2 (ESP8266 Firmware v4.3.0+)**.

---

## 📑 Daftar Isi
1. [Prinsip Komunikasi & Server-Driven](#1-prinsip-komunikasi--server-driven)
2. [Spesifikasi Endpoint](#2-spesifikasi-endpoint)
   - [2.1 Heartbeat / Ping Server (`GET /api/rfid/v2?ping=1`)](#21-heartbeat--ping-server-get-apirfidv2ping1)
   - [2.2 Transaksi Scan Kartu & Sinkronisasi (`POST /api/rfid/v2`)](#22-transaksi-scan-kartu--sinkronisasi-post-apirfidv2)
3. [Format Data yang Dikirim ESP8266 (Request)](#3-format-data-yang-dikirim-esp8266-request)
4. [Format Data yang Wajib Dibalas Server (Response)](#4-format-data-yang-wajib-dibalas-server-response)
   - [4.1 Kontrak Objek `display`](#41-kontrak-objek-display)
   - [4.2 Kontrak Parameter `sound` (Buzzer)](#42-kontrak-parameter-sound-buzzer)
5. [Contoh Kasus Respon JSON Server](#5-contoh-kasus-respon-json-server)
6. [Ketentuan HTTP Status Code & Penanganan Offline](#6-ketentuan-http-status-code--penanganan-offline)

---

## 1. Prinsip Komunikasi & Server-Driven

Perangkat RFIDv2 menganut arsitektur **Server-Driven**:
- **ESP8266 tidak menentukan teks atau logika absensi**. Perangkat hanya membaca UID kartu RFID fisik dan meneruskannya ke server Anda.
- **Server Anda yang menentukan segalanya**: Server membalas dengan teks yang akan dicetak pada layar LCD 16x2 (Baris 1 dan Baris 2) serta jenis bunyi buzzer yang harus dibunyikan.

---

## 2. Spesifikasi Endpoint

Pada menu konfigurasi perangkat, teknisi akan memasukkan URL Server Anda (misal: `https://api.domainanda.com`). Perangkat RFIDv2 akan memanggil dua endpoint berikut:

### 2.1 Heartbeat / Ping Server (`GET /api/rfid/v2?ping=1`)
Digunakan oleh ESP8266 untuk memeriksa apakah server Anda sedang online.

- **Method**: `GET`
- **Path**: `/api/rfid/v2?ping=1`
- **Timeout ESP**: 3.000 ms
- **Respon Minimal yang Diharapkan ESP**:
  - HTTP Status: `200 OK`
  - Body JSON:
  ```json
  {
    "ok": true,
    "message": "pong"
  }
  ```

---

### 2.2 Transaksi Scan Kartu & Sinkronisasi (`POST /api/rfid/v2`)
Dipanggil setiap kali kartu RFID di-tap pada perangkat, atau saat perangkat mengunggah antrean offline ke server.

- **Method**: `POST`
- **Path**: `/api/rfid/v2`
- **Header**:
  ```http
  Content-Type: application/json
  ```
- **Timeout ESP**: 8.000 ms

---

## 3. Format Data yang Dikirim ESP8266 (Request)

ESP8266 akan mengirimkan payload JSON dengan struktur berikut:

```json
{
  "api_key": "device_api_key_12345",
  "uid": "53A26B1E",
  "scanned_at": "2026-10-30 07:15:00"
}
```

### Penjelasan Parameter Request:
| Parameter | Tipe | Status | Deskripsi |
| :--- | :--- | :---: | :--- |
| `api_key` | `string` | **Wajib** | API Key unik perangkat yang telah didaftarkan teknisi di menu config alat. Gunakan ini untuk mengotentikasi keabsahan mesin. |
| `uid` | `string` | **Wajib** | Nomor UID kartu RFID fisik dalam format Hexadecimal Uppercase (contoh: `53A26B1E` atau `1A2B3C4D5E6F`). |
| `scanned_at` | `string` | *Opsional* | Timestamp kartu di-tap (format: `YYYY-MM-DD HH:mm:ss`). <br>• **Jika ada**: Data berasal dari antrean offline (LittleFS) saat internet sebelumnya sempat putus.<br>• **Jika tidak ada**: Kartu baru saja di-tap saat online secara real-time. |

---

## 4. Format Data yang Wajib Dibalas Server (Response)

Agar LCD 16x2 dan Buzzer perangkat menampilkan informasi dengan benar, backend Anda **wajib mengembalikan HTTP Status `200 OK`** dengan format JSON standar berikut:

```json
{
  "ok": true,
  "display": {
    "line1": "ABSEN MASUK",
    "line2": "Ahmad Dani",
    "duration_ms": 3000
  },
  "sound": "ok"
}
```

### 4.1 Kontrak Objek `display`
Objek ini mengontrol tampilan LCD 16x2 karakter pada mesin:

| Field | Tipe | Batas Karakter | Deskripsi |
| :--- | :--- | :---: | :--- |
| `line1` | `string` | **Maks 16 karakter** | Teks yang dicetak di **Baris 1 LCD**. Contoh: `"ABSEN MASUK"`, `"ABSEN GAGAL!"`, dll. |
| `line2` | `string` | **Maks 16 karakter** | Teks yang dicetak di **Baris 2 LCD**. Contoh: Nama user `"Ahmad Dani"`, rincian telat `"Dani (+15m)"`, dll. |
| `duration_ms`| `integer` | *Opsional* | Durasi teks tampil di layar dalam milidetik sebelum kembali ke tampilan jam standby. *(Default ESP: 3000 ms)*. |

> ⚠️ **Catatan Penting Panjang Karakter**: Layar LCD hanya memiliki 16 kolom. Pastikan backend server Anda memotong (*truncate*) string jika panjangnya melebihi 16 karakter agar tidak terpotong acak di layar.

---

### 4.2 Kontrak Parameter `sound` (Buzzer)
String ini menginstruksikan modul buzzer ESP8266 untuk membunyikan pola nada tertentu:

| Nilai `sound` | Pola Bunyi Buzzer | Rekomendasi Penggunaan |
| :--- | :--- | :--- |
| `"ok"` | **2x bip pendek** | Absen masuk / pulang berhasil. |
| `"short"` | **1x bip pendek** | Sudah absen / kartu sudah pernah di-tap. |
| `"triple"` | **3x bip cepat** | Registrasi kartu baru (enroll), buka akses khusus. |
| `"error"` | **1x bip panjang (600ms)** | Kartu ditolak, jadwal kosong, tidak terdaftar. |
| `"none"` | **Diam (tanpa suara)** | Notifikasi hening. |

---

## 5. Contoh Kasus Respon JSON Server

Berikut adalah contoh payload respon yang dapat dibuat oleh backend server Anda untuk berbagai kondisi:

#### 1. Presensi Masuk Berhasil (Tepat Waktu)
```json
{
  "ok": true,
  "display": {
    "line1": "ABSEN MASUK",
    "line2": "Ahmad Dani"
  },
  "sound": "ok"
}
```

#### 2. Presensi Masuk (Terlambat)
```json
{
  "ok": true,
  "display": {
    "line1": "MASUK (TELAT)",
    "line2": "Dani (+15m)"
  },
  "sound": "ok"
}
```

#### 3. Presensi Pulang Berhasil
```json
{
  "ok": true,
  "display": {
    "line1": "ABSEN PULANG",
    "line2": "Siti Nurhaliza"
  },
  "sound": "ok"
}
```

#### 4. Kartu Sudah Pernah Absen
```json
{
  "ok": true,
  "display": {
    "line1": "SUDAH ABSEN",
    "line2": "Ahmad Dani"
  },
  "sound": "short"
}
```

#### 5. Kartu Belum Terdaftar di Database
```json
{
  "ok": false,
  "display": {
    "line1": "KARTU TDK DIKENAL",
    "line2": "Hubungi Admin"
  },
  "sound": "error"
}
```

#### 6. Di Luar Jam Kerja / Shift
```json
{
  "ok": false,
  "display": {
    "line1": "DILUAR JADWAL",
    "line2": "Belum Jam Masuk"
  },
  "sound": "error"
}
```

#### 7. API Key Salah / Mesin Belum Aktif
```json
{
  "ok": false,
  "display": {
    "line1": "AUTH GAGAL",
    "line2": "API Key Invalid"
  },
  "sound": "error"
}
```

---

## 6. Ketentuan HTTP Status Code & Penanganan Offline

Perangkat ESP8266 mengevaluasi status transmisi data berdasarkan HTTP Response Code:

1. **HTTP Status `200 OK`**:
   - ESP8266 menganggap transaksi **sukses terkirim**.
   - ESP8266 langsung mem-parsing JSON, menampilkan baris teks LCD, dan membunyikan buzzer.
   - Jika transaksi ini adalah sinkronisasi antrean offline, entri kartu terkait akan langsung **dihapus dari antrean memori internal**.

2. **HTTP Status Selain `200` (4xx, 5xx) atau Request Timeout**:
   - ESP8266 menganggap jaringan/server mengalami gangguan.
   - Tampilan LCD lokal ESP8266 otomatis menampilkan `"OFFLINE - SIMPAN"` dan membunyikan 2x bip lokal.
   - UID kartu otomatis diamankan ke penyimpanan flash internal (`/queue.ndjson`) dan akan otomatis dikirim ulang saat server kembali online.
