# Dokumentasi REST API Mobile — Jagat Absen

Dokumentasi resmi untuk **Laravel REST API (Laravel Sanctum Auth)** yang digunakan oleh aplikasi **Android Jagat Absen**.

- **Base URL**: `http://192.168.35.167:8080/api/` *(Atau IP/Domain Server Laravel Anda)*
- **Header Standar Request**:
  - `Accept`: `application/json`
  - `Authorization`: `Bearer <token>` *(Untuk endpoint terproteksi)*

---

## 🔑 1. Autentikasi & Sesi User

### 1.1 Login User (`POST /api/mobile/login`)
Digunakan untuk autentikasi awal pegawai, guru, atau wali kelas.

- **URL**: `/api/mobile/login`
- **Method**: `POST`
- **Auth**: Public
- **Body Request (JSON)**:
```json
{
  "email": "budi@gmail.com",
  "password": "password123"
}
```
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "message": "Login berhasil",
  "token": "1|abcdef1234567890sanctumtoken",
  "user": {
    "id": 5,
    "full_name": "Budi Santoso, S.Pd",
    "username": "budi",
    "email": "budi@gmail.com",
    "role": "Wali_kelas",
    "school_id": 1,
    "guru": {
      "id": 3,
      "nama": "Budi Santoso, S.Pd",
      "nip": "198501012010011002"
    }
  },
  "school": {
    "id": 1,
    "nama": "SMK Negeri 1 Jagat",
    "domain": "smkn1jagat.sch.id"
  }
}
```

---

### 1.2 Profil User (`GET /api/mobile/user`)
Mengambil data detail profil pengguna yang sedang login.

- **URL**: `/api/mobile/user`
- **Method**: `GET`
- **Auth**: Bearer Token
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "data": {
    "id": 5,
    "full_name": "Budi Santoso, S.Pd",
    "username": "budi",
    "email": "budi@gmail.com",
    "role": "Wali_kelas",
    "school_id": 1
  }
}
```

---

### 1.3 Logout (`POST /api/mobile/logout`)
Menghapus/revokasi token Sanctum aktif.

- **URL**: `/api/mobile/logout`
- **Method**: `POST`
- **Auth**: Bearer Token
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "message": "Logout berhasil"
}
```

---

## 🏢 2. Presensi Mandiri (Pegawai / Guru / Karyawan)

### 2.1 Status Presensi Hari Ini (`GET /api/mobile/today`)
Mengambil jam masuk, jam pulang, dan status presensi hari ini.

- **URL**: `/api/mobile/today`
- **Method**: `GET`
- **Auth**: Bearer Token
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "data": {
    "tanggal": "09-09-2026",
    "jam_masuk": "06:55:41",
    "jam_pulang": "15:40:40",
    "status_kehadiran": "Hadir",
    "is_checked_in": true,
    "is_checked_out": true
  }
}
```

---

### 2.2 Absen Masuk (`POST /api/mobile/checkin`)
Mencatat absen masuk dengan lokasi GPS dan foto selfie.

- **URL**: `/api/mobile/checkin`
- **Method**: `POST`
- **Content-Type**: `multipart/form-data`
- **Auth**: Bearer Token
- **Body Request**:
  - `latitude` (text): `-6.175392`
  - `longitude` (text): `106.827153`
  - `photo` (file image): `selfie_checkin.jpg`
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "message": "Absen masuk berhasil dicatat",
  "data": {
    "tanggal": "09-09-2026",
    "jam_masuk": "06:55:41",
    "status_kehadiran": "Hadir",
    "is_checked_in": true
  }
}
```

---

### 2.3 Absen Pulang (`POST /api/mobile/checkout`)
Mencatat jam pulang pegawai.

- **URL**: `/api/mobile/checkout`
- **Method**: `POST`
- **Content-Type**: `multipart/form-data`
- **Auth**: Bearer Token
- **Body Request**:
  - `latitude` (text): `-6.175392`
  - `longitude` (text): `106.827153`
  - `photo` (file image): `selfie_checkout.jpg`
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "message": "Absen pulang berhasil dicatat",
  "data": {
    "tanggal": "09-09-2026",
    "jam_pulang": "15:40:40",
    "is_checked_out": true
  }
}
```

---

### 2.4 Riwayat Presensi Individu (`GET /api/mobile/history`)
Mendapatkan log catatan kehadiran individu.

- **URL**: `/api/mobile/history`
- **Method**: `GET`
- **Auth**: Bearer Token
- **Query Parameters**:
  - `start_date` (optional): `01-09-2026`
  - `end_date` (optional): `09-09-2026`
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 102,
      "tanggal": "09-09-2026",
      "jam_masuk": "06:55:41",
      "jam_pulang": "15:40:40",
      "status_kehadiran": "Hadir",
      "keterangan": "Tepat Waktu"
    }
  ]
}
```

---

### 2.5 Rekapitulasi Presensi Individu (`GET /api/mobile/rekap`)
Mendapatkan akumulasi persentase dan rincian kehadiran individu.

- **URL**: `/api/mobile/rekap`
- **Method**: `GET`
- **Auth**: Bearer Token
- **Query Parameters**:
  - `months` (optional): `1`, `3`, `6`, `12`
  - `start_date` (optional): `01-09-2026`
  - `end_date` (optional): `09-09-2026`
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "data": {
    "total_hadir": 23,
    "total_terlambat": 2,
    "total_izin": 1,
    "total_sakit": 0,
    "total_alpha": 0,
    "total_hari_kerja": 26,
    "persentase_kehadiran": 96.2
  }
}
```

---

## 👨‍🏫 3. Fitur Dewan Guru & Wali Kelas

### 3.1 Cari & Tampilkan Siswa untuk Absensi (`GET /api/mobile/students-attendance`)
Cari siswa di seluruh sekolah berdasarkan kata kunci Nama / NISN untuk diisi absensinya.

- **URL**: `/api/mobile/students-attendance`
- **Method**: `GET`
- **Auth**: Bearer Token
- **Query Parameters**:
  - `tanggal` (required): `09-09-2026`
  - `q` (required for search, min 2 chars): `Ahmad`
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "data": [
    {
      "student_id": 15,
      "nama": "Ahmad Rizky",
      "nis": "1001",
      "kelas_name": "Kelas X IPA 1",
      "status": "H",
      "keterangan": null
    }
  ]
}
```

---

### 3.2 Simpan Absensi Per Siswa (`POST /api/mobile/student-attendance-single`)
Menyimpan atau mengubah status absensi satu orang siswa saat tombol "Simpan Absen" ditekan.

- **URL**: `/api/mobile/student-attendance-single`
- **Method**: `POST`
- **Auth**: Bearer Token
- **Body Request (JSON)**:
```json
{
  "tanggal": "09-09-2026",
  "student_id": 15,
  "status": "H",
  "keterangan": "Hadir tepat waktu"
}
```
*Catatan kode status:* `H` (Hadir), `T` (Terlambat), `I` (Izin), `S` (Sakit), `A` (Alpha).

- **Response Success (200 OK)**:
```json
{
  "success": true,
  "message": "Absensi siswa tersimpan secara realtime"
}
```

---

### 3.3 Rekap Absensi Siswa Kelas Wali Kelas (`GET /api/mobile/rekap-kelas`)
Mendapatkan rekapitulasi kehadiran seluruh siswa di kelas tempat guru tersebut menjadi wali kelas.

- **URL**: `/api/mobile/rekap-kelas`
- **Method**: `GET`
- **Auth**: Bearer Token
- **Query Parameters**:
  - `start_date` (optional): `01-09-2026`
  - `end_date` (optional): `09-09-2026`
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "data": {
    "kelas_name": "Kelas X IPA 1 (Wali Kelas)",
    "total_siswa": 32,
    "start_date": "01-09-2026",
    "end_date": "09-09-2026",
    "students": [
      {
        "student_id": 15,
        "nama": "Ahmad Rizky",
        "nis": "1001",
        "hadir": 7,
        "terlambat": 1,
        "izin": 0,
        "sakit": 0,
        "alpha": 0
      }
    ]
  }
}
```

---

## 📊 4. Fitur Waka Kurikulum & Manajemen Sekolah

### 4.1 Daftar Kelas Sekolah (`GET /api/mobile/kelases`)
Mendapatkan daftar kelas yang terdaftar di sekolah.

- **URL**: `/api/mobile/kelases`
- **Method**: `GET`
- **Auth**: Bearer Token
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "data": [
    { "id": 1, "nama": "X IPA 1" },
    { "id": 2, "nama": "X IPA 2" },
    { "id": 3, "nama": "XI IPS 1" }
  ]
}
```

---

### 4.2 Akumulasi Rekap Absensi Siswa Sekolah (`GET /api/mobile/rekap-siswa-sekolah`)
Mendapatkan statistik akumulasi absensi siswa tingkat sekolah atau per kelas untuk Waka Kurikulum.

- **URL**: `/api/mobile/rekap-siswa-sekolah`
- **Method**: `GET`
- **Auth**: Bearer Token
- **Query Parameters**:
  - `kelas_id` (optional): `1` *(Kosongkan untuk akumulasi seluruh kelas)*
  - `months` (optional): `1`, `3`, `6`, `12`
  - `start_date` (optional): `01-09-2026`
  - `end_date` (optional): `09-09-2026`
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "data": {
    "total_hadir": 580,
    "total_terlambat": 25,
    "total_izin": 12,
    "total_sakit": 8,
    "total_alpha": 3,
    "total_hari_kerja": 628,
    "persentase_kehadiran": 96.3
  }
}
```
