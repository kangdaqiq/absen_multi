# Dokumentasi REST API Mobile — Jagat Absen

Dokumentasi resmi untuk **Laravel REST API (Laravel Sanctum Auth)** yang digunakan oleh aplikasi **Android Jagat Absen**.

- **Base URL**: `http://absen.jagattech.my.id/api/` *(Atau IP/Domain Server Laravel Anda)*
- **Header Standar Request**:
  - `Accept`: `application/json`
  - `Authorization`: `Bearer <token>` *(Untuk endpoint terproteksi)*

---

## 🔑 1. Autentikasi & Sesi User

### 1.1 Login User (`POST /api/mobile/login`)
Digunakan untuk autentikasi awal pegawai, guru, wali kelas, maupun **siswa**.

- **URL**: `/api/mobile/login`
- **Method**: `POST`
- **Auth**: Public
- **Body Request (JSON)**:
```json
{
  "email": "1001", 
  "password": "password123",
  "fcm_token": "fcm_device_token_opsional"
}
```
*Keterangan:* 
- Field `email` dapat diisi dengan **Email**, **Username**, maupun **NIS / NISN** siswa.
- Field `fcm_token` (opsional): Jika disertakan saat login, sistem akan otomatis mendaftarkan token ke user aktif dan **menghapus token tersebut dari akun user/siswa sebelumnya** pada perangkat tersebut guna mencegah notifikasi terkirim dobel.

- **Response Success (200 OK) — Contoh Login Guru / Pegawai**:
```json
{
  "success": true,
  "message": "Login berhasil",
  "token": "1|abcdef1234567890sanctumtoken",
  "user": {
    "id": 2,
    "full_name": "Budi Santoso, S.Pd",
    "username": "budisantoso",
    "email": "budi@sekolah.sch.id",
    "role": "teacher",
    "nip": "198507152010011012",
    "tgl_lahir": "1985-07-15",
    "no_wa": "081234567890",
    "no_wa_ortu": null,
    "wa_ortu": null,
    "school_id": 1,
    "is_wali_kelas": true,
    "kelas_id": 3,
    "kelas_name": "XII RPL 1",
    "wali_kelases": [
      {
        "id": 3,
        "nama": "XII RPL 1",
        "nama_kelas": "XII RPL 1",
        "peran": "Wali Kelas 1",
        "is_utama": true
      }
    ],
    "guru": {
      "id": 10,
      "nama": "Budi Santoso, S.Pd",
      "nip": "198507152010011012",
      "tgl_lahir": "1985-07-15",
      "no_wa": "081234567890",
      "foto_url": "http://absen.jagattech.my.id/storage/guru/budi.jpg",
      "is_wali_kelas": true,
      "kelas_id": 3,
      "kelas_name": "XII RPL 1",
      "wali_kelases": [
        {
          "id": 3,
          "nama": "XII RPL 1",
          "nama_kelas": "XII RPL 1",
          "peran": "Wali Kelas 1",
          "is_utama": true
        }
      ]
    }
  },
  "school": {
    "id": 1,
    "name": "SMK Negeri 1 Jagat",
    "domain": "smkn1jagat.sch.id",
    "logo_url": "http://absen.jagattech.my.id/storage/schools/logos/logo.png",
    "latitude": -6.175392,
    "longitude": 106.827153,
    "radius": 100,
    "geofence_enabled": true
  }
}
```

- **Response Success (200 OK) — Contoh Login Siswa**:
```json
{
  "success": true,
  "message": "Login siswa berhasil",
  "token": "2|fedcba0987654321sanctumtoken",
  "user": {
    "id": 5,
    "full_name": "Ahmad Rizky",
    "username": "1001",
    "email": "1001@siswa.local",
    "role": "student",
    "nis": "1001",
    "tgl_lahir": "2008-05-15",
    "no_wa": "081234567890",
    "no_wa_ortu": "081987654321",
    "wa_ortu": "081987654321",
    "school_id": 1,
    "student": {
      "id": 15,
      "nama": "Ahmad Rizky",
      "nis": "1001",
      "tgl_lahir": "2008-05-15",
      "no_wa": "081234567890",
      "no_wa_ortu": "081987654321",
      "wa_ortu": "081987654321",
      "foto_url": "http://absen.jagattech.my.id/storage/siswa/1001.jpg",
      "kelas_id": 3,
      "kelas_name": "XII RPL 1"
    }
  },
  "school": {
    "id": 1,
    "name": "SMK Negeri 1 Jagat",
    "domain": "smkn1jagat.sch.id",
    "logo_url": "http://absen.jagattech.my.id/storage/schools/logos/logo.png",
    "latitude": -6.175392,
    "longitude": 106.827153,
    "radius": 100,
    "geofence_enabled": true
  }
}
```

- **Response Error (401 Unauthorized)**:
```json
{
  "success": false,
  "message": "NISN/Email atau Password/Tanggal Lahir salah."
}
```

---

### 1.2 Profil User (`GET /api/mobile/user`)
Mengambil data detail profil pengguna yang sedang login (baik Guru maupun Siswa).

- **URL**: `/api/mobile/user`
- **Method**: `GET`
- **Auth**: Bearer Token
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "data": {
    "id": 2,
    "full_name": "Budi Santoso, S.Pd",
    "username": "budisantoso",
    "email": "budi@sekolah.sch.id",
    "role": "teacher",
    "nip": "198507152010011012",
    "nis": null,
    "tgl_lahir": "1985-07-15",
    "no_wa": "081234567890",
    "no_wa_ortu": null,
    "wa_ortu": null,
    "school_id": 1,
    "guru": {
      "id": 10,
      "nama": "Budi Santoso, S.Pd",
      "nip": "198507152010011012",
      "foto_url": "http://absen.jagattech.my.id/storage/guru/budi.jpg",
      "is_wali_kelas": true,
      "kelas_id": 3,
      "kelas_name": "XII RPL 1"
    },
    "student": null,
    "school": {
      "id": 1,
      "name": "SMK Negeri 1 Jagat",
      "domain": "smkn1jagat.sch.id",
      "logo_url": "http://absen.jagattech.my.id/storage/schools/logos/logo.png",
      "latitude": -6.175392,
      "longitude": 106.827153,
      "radius": 100,
      "geofence_enabled": true
    }
  }
}
```

---

### 1.3 Update FCM Token (`POST /api/mobile/fcm-token`)
Memperbarui FCM Device Registration Token untuk push notification.

- **URL**: `/api/mobile/fcm-token`
- **Method**: `POST`
- **Auth**: Bearer Token
- **Body Request (JSON)**:
```json
{
  "fcm_token": "fcm_device_token_string_here"
}
```
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "message": "FCM Token berhasil diperbarui"
}
```
*Catatan:* Jika token FCM yang dikirimkan sebelumnya masih tersimpan di user atau siswa lain (misal berganti akun di perangkat yang sama), token pada akun lama tersebut akan otomatis dihapus (`null`) agar notifikasi tidak terkirim ganda.

---

### 1.4 Ganti Password Akun (`POST /api/mobile/change-password`)
Mengubah kata sandi akun pengguna yang sedang login (berlaku untuk Guru, Pegawai, maupun Siswa).

- **URL**: `/api/mobile/change-password` atau `/api/mobile/ganti-password`
- **Method**: `POST`
- **Auth**: Bearer Token
- **Body Request (JSON / Form-Data)**:
```json
{
  "current_password": "passwordLama123",
  "new_password": "passwordBaru456",
  "new_password_confirmation": "passwordBaru456"
}
```
*Catatan:* Parameter juga mendukung penamaan bahasa Indonesia (`password_lama`, `password_baru`, `konfirmasi_password`).

- **Response Success (200 OK)**:
```json
{
  "success": true,
  "message": "Password akun berhasil diubah. Silakan gunakan password baru ini untuk login berikutnya."
}
```

- **Response Error (422 Unprocessable Entity - Password Lama Salah)**:
```json
{
  "success": false,
  "message": "Password saat ini (lama) yang Anda masukkan salah."
}
```

- **Response Error (422 - Konfirmasi Tidak Cocok / Kurang dari 6 Karakter)**:
```json
{
  "success": false,
  "message": "Password baru minimal terdiri dari 6 karakter."
```

---

### 1.5 Update Profil Pengguna (Siswa & Guru) (`POST /api/mobile/update-profile`)
Endpoint terpadu untuk memperbarui **Tanggal Lahir**, **Email**, serta **Nomor WhatsApp**:
- **Siswa**: Dapat langsung mengupdate datanya sendiri (`tgl_lahir`, `email`, `no_wa`, `wa_ortu`) tanpa mengirim `student_id`.
- **Guru / Pegawai**: Dapat mengupdate data profilnya sendiri (`tgl_lahir`, `email`, `no_wa`) tanpa mengirim `student_id`.
- **Guru / Wali Kelas / Admin (Edit Siswa)**: Dapat mengupdate profil siswa tertentu dengan menyertakan parameter `student_id`.

- **URL**: `/api/mobile/update-profile` *(Alias: `/api/mobile/student/profile`, `/api/mobile/teacher/profile`, `/api/mobile/guru/profile`, `/api/mobile/student/contact`, `/api/mobile/update-contact`)*
- **Method**: `POST`
- **Auth**: Bearer Token
- **Body Request (JSON / Form-Data) — Contoh Update Siswa**:
```json
{
  "student_id": 15,
  "tgl_lahir": "2008-05-15",
  "email": "ahmad.rizky@gmail.com",
  "no_wa": "081234567890",
  "wa_ortu": "081987654321"
}
```

- **Body Request (JSON / Form-Data) — Contoh Update Guru / Pegawai**:
```json
{
  "tgl_lahir": "1985-07-15",
  "email": "budi.santoso@gmail.com",
  "no_wa": "081234567890"
}
```

*Catatan Parameter:*
- `student_id` (opsional): ID siswa. Wajib disertakan hanya jika akun guru/admin ingin mengedit siswa tertentu.
- `tgl_lahir` (opsional): Format `YYYY-MM-DD`, `DD-MM-YYYY`, atau `DD/MM/YYYY`. (Mendukung alias: `tanggal_lahir`, `birth_date`).
- `email` (opsional): Alamat email baru (divalidasi keunikannya di database).
- `no_wa` (opsional): Nomor WhatsApp (`08xxx` / `628xxx`). (Mendukung alias: `nomor_wa`, `phone`, `wa_siswa`, `wa_guru`).
- `wa_ortu` (opsional): Nomor WhatsApp orang tua (khusus data siswa). (Mendukung alias: `nomor_wa_ortu`, `no_wa_ortu`, `wa_orang_tua`).
- Minimal salah satu field (`tgl_lahir`, `email`, `no_wa`, atau `wa_ortu`) harus dikirimkan.

- **Response Success (200 OK) — Response Siswa**:
```json
{
  "success": true,
  "message": "Data profil siswa berhasil diperbarui.",
  "data": {
    "student_id": 15,
    "nama": "Ahmad Rizky",
    "nis": "1001",
    "tgl_lahir": "2008-05-15",
    "email": "ahmad.rizky@gmail.com",
    "no_wa": "081234567890",
    "wa_ortu": "081987654321"
  }
}
```

- **Response Success (200 OK) — Response Guru**:
```json
{
  "success": true,
  "message": "Data profil guru berhasil diperbarui.",
  "data": {
    "guru_id": 10,
    "nama": "Budi Santoso, S.Pd",
    "nip": "198507152010011012",
    "tgl_lahir": "1985-07-15",
    "email": "budi.santoso@gmail.com",
    "no_wa": "081234567890"
  }
}
```

- **Response Error (422 Unprocessable Entity - Email Sudah Terdaftar)**:
```json
{
  "success": false,
  "message": "Email sudah digunakan oleh akun lain. Silakan gunakan email lain."
}
```

- **Response Error (422 Unprocessable Entity - Format Tanggal / WA Tidak Valid)**:
```json
{
  "success": false,
  "message": "Format tanggal lahir tidak valid (gunakan format YYYY-MM-DD, contoh: 2008-05-15)."
}
```

- **Response Error (403 Forbidden - Akses Ditolak)**:
```json
{
  "success": false,
  "message": "Akses ditolak: Anda hanya dapat mengubah profil Anda sendiri."
}
```

---

### 1.6 Logout (`POST /api/mobile/logout`)
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

## 🏢 2. Presensi Mandiri (Siswa / Pegawai / Guru)

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
    "tanggal": "10-09-2026",
    "jam_masuk": "06:55:41",
    "jam_pulang": "15:40:40",
    "jadwal_masuk": "07:00:00",
    "jadwal_pulang": "15:30:00",
    "shift_name": "Reguler Pagi",
    "status_kehadiran": "Hadir",
    "is_checked_in": true,
    "is_wali_kelas": true,
    "wali_kelas": true,
    "kelas_name": "Kelas X IPA 1",
    "kelas_id": 1,
    "wali_kelases": [
      {
        "id": 1,
        "nama": "X IPA 1",
        "nama_kelas": "X IPA 1",
        "peran": "Wali Kelas 1",
        "is_utama": true
      },
      {
        "id": 4,
        "nama": "XI IPS 2",
        "nama_kelas": "XI IPS 2",
        "peran": "Wali Kelas 2",
        "is_utama": false
      }
    ]
  }
}
```
*Catatan Multi-Wali Kelas: Jika guru menjadi wali di lebih dari 1 kelas (misal Wali Kelas 1 di X IPA 1 dan Wali Kelas 2 di XI IPS 2), array `wali_kelases` akan berisi seluruh kelas yang diampu, sedangkan `kelas_id` dan `kelas_name` tetap berisi kelas utama untuk backward-compatibility aplikasi.*

---

### 2.2 Absen Masuk (`POST /api/mobile/checkin`)
Mencatat absen masuk berbasis verifikasi koordinat GPS.

- **URL**: `/api/mobile/checkin`
- **Method**: `POST`
- **Content-Type**: `multipart/form-data`
- **Auth**: Bearer Token
- **Body Request**:
  - `latitude` (text): `-6.175392`
  - `longitude` (text): `106.827153`
  - `photo` (optional, file image): `selfie_checkin.jpg`
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "message": "Absen masuk berhasil dicatat",
  "data": {
    "tanggal": "10-09-2026",
    "jam_masuk": "06:55:41",
    "status_kehadiran": "Hadir",
    "is_checked_in": true
  }
}
```

#### ⚠️ Respon Gagal / Validasi Absen Masuk:

1. **Gagal: Di Luar Radius Sekolah (400 Bad Request / 422)**:
```json
{
  "success": false,
  "message": "Gagal absen: Posisi Anda berada di luar radius lokasi sekolah (Jarak: 250 meter, Maksimal: 100 meter)."
}
```

2. **Gagal: Di Luar Jam Operasional / Belum Jam Masuk (400 Bad Request)**:
```json
{
  "success": false,
  "message": "Gagal absen: Jam absen masuk belum dibuka. Jam masuk dibuka mulai pukul 06:00 WIB."
}
```

3. **Gagal: Sudah Absen Masuk Hari Ini (400 Bad Request)**:
```json
{
  "success": false,
  "message": "Gagal absen: Anda sudah melakukan absen masuk hari ini pada pukul 06:55:41 WIB."
}
```

4. **Gagal: Fake GPS / Lokasi Palsu Terdeteksi (400 Bad Request)**:
```json
{
  "success": false,
  "message": "Gagal absen: Lokasi palsu / Fake GPS terdeteksi. Mohon matikan aplikasi lokasi palsu."
}
```

5. **Gagal: Verifikasi Wajah Tidak Cocok / Tidak Terdeteksi (400 Bad Request)**:
```json
{
  "success": false,
  "message": "Absen ditolak: Wajah Anda tidak cocok dengan foto terdaftar (Kemiripan: 24%)."
}
```

---

### 2.3 Absen Pulang (`POST /api/mobile/checkout`)
Mencatat jam pulang pengguna.

- **URL**: `/api/mobile/checkout`
- **Method**: `POST`
- **Content-Type**: `multipart/form-data`
- **Auth**: Bearer Token
- **Body Request**:
  - `latitude` (text): `-6.175392`
  - `longitude` (text): `106.827153`
  - `photo` (optional, file image): `selfie_checkout.jpg`
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "message": "Absen pulang berhasil dicatat",
  "data": {
    "tanggal": "10-09-2026",
    "jam_pulang": "15:40:40",
    "is_checked_out": true
  }
}
```

#### ⚠️ Respon Gagal / Validasi Absen Pulang:

1. **Gagal: Belum Absen Masuk Hari Ini (400 Bad Request)**:
```json
{
  "success": false,
  "message": "Gagal absen pulang: Anda belum melakukan absen masuk hari ini."
}
```

2. **Gagal: Belum Waktunya Jam Pulang (400 Bad Request)**:
```json
{
  "success": false,
  "message": "Gagal absen pulang: Jam pulang dibuka mulai pukul 15:00 WIB."
}
```

3. **Gagal: Sudah Absen Pulang Hari Ini (400 Bad Request)**:
```json
{
  "success": false,
  "message": "Gagal absen pulang: Anda sudah melakukan absen pulang hari ini pada pukul 15:40:40 WIB."
}
```

4. **Gagal: Di Luar Radius Sekolah (400 Bad Request)**:
```json
{
  "success": false,
  "message": "Gagal absen pulang: Posisi Anda berada di luar radius sekolah."
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
  - `end_date` (optional): `10-09-2026`
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 102,
      "tanggal": "10-09-2026",
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
  - `end_date` (optional): `10-09-2026`
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
Cari siswa di seluruh sekolah berdasarkan kelas atau kata kunci Nama / NIS / NISN untuk diisi absensinya.

- **URL**: `/api/mobile/students-attendance`
- **Method**: `GET`
- **Auth**: Bearer Token (Role: `guru`, `waka`, `kurikulum`, `admin`, `super_admin`)
- **Query Parameters**:
  - `tanggal` (optional, default hari ini): `10-09-2026`
  - `kelas_id` (optional): `1` *(Untuk memfilter siswa dalam satu kelas tertentu tanpa kata kunci pencarian)*
  - `q` (optional): `Ahmad` *(Kata kunci pencarian nama atau NIS/NISN, minimal 2 karakter)*
  - `strict_class` (optional boolean, default `false`): Jika diset `true`, pencarian tetap dibatasi hanya di dalam `kelas_id` yang dipilih.
- **Hak Akses & Batasan Pencarian**:
  - **Waka Kurikulum & Admin**: Dapat mencari seluruh siswa di sekolah secara global lintas kelas.
  - **Wali Kelas (Walas)**: Akses dibatasi secara ketat **hanya pada kelas yang diwalikannya**. Jika walas memiliki 1 kelas, hanya siswa di kelas tersebut yang tampil/dicari. Jika walas memiliki 2 kelas, maka siswa dari 2 kelas tersebut yang tampil dan dapat dicari. Walas tidak dapat mengakses atau mencari siswa di kelas lain.
- **Contoh Request**:
  - Walas (otomatis ke kelas walinya): `GET /api/mobile/students-attendance?tanggal=10-09-2026`
  - Walas pilih salah satu kelas walinya: `GET /api/mobile/students-attendance?tanggal=10-09-2026&kelas_id=1`
  - Waka/Admin cari global satu sekolah: `GET /api/mobile/students-attendance?tanggal=10-09-2026&q=Ahmad`
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "data": [
    {
      "student_id": 15,
      "nama": "Ahmad Rizky",
      "nis": "1001",
      "kelas_id": 1,
      "kelas_name": "Kelas X IPA 1",
      "status": "H",
      "status_kehadiran": "Hadir",
      "keterangan": null,
      "jam_masuk": "06:55:00",
      "jam_pulang": null
    }
  ]
}
```

---

### 3.2 Simpan Absensi Siswa Massal / Sekaligus (`POST /api/mobile/students-attendance`)
Menyimpan atau memperbarui data absensi seluruh siswa sekaligus (bulk submit) dalam satu kelas.

- **URL**: `/api/mobile/students-attendance`
- **Method**: `POST`
- **Auth**: Bearer Token
- **Body Request (JSON)**:
```json
{
  "tanggal": "10-09-2026",
  "attendances": [
    { "student_id": 15, "status": "H", "keterangan": null },
    { "student_id": 16, "status": "I", "keterangan": "Izin acara keluarga" },
    { "student_id": 17, "status": "S", "keterangan": "Demam / Surat Dokter" }
  ]
}
```
*Catatan kode status:* `H` (Hadir), `T` (Terlambat), `I` (Izin), `S` (Sakit), `A` (Alpha).

- **Response Success (200 OK)**:
```json
{
  "success": true,
  "message": "Absensi seluruh siswa berhasil disimpan",
  "total_updated": 3
}
```

---

### 3.3 Simpan Absensi Per Siswa Realtime (`POST /api/mobile/student-attendance-single`)
Menyimpan atau mengubah status absensi satu orang siswa saat tombol status individual ditekan.

- **URL**: `/api/mobile/student-attendance-single`
- **Method**: `POST`
- **Auth**: Bearer Token
- **Body Request (JSON)**:
```json
{
  "tanggal": "10-09-2026",
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

### 3.4 Rekap Absensi Siswa Kelas Wali Kelas (`GET /api/mobile/rekap-kelas`)
Mendapatkan rekapitulasi kehadiran seluruh siswa di kelas tempat guru tersebut menjadi wali kelas atau kelas yang dipilih.

- **URL**: `/api/mobile/rekap-kelas`
- **Method**: `GET`
- **Auth**: Bearer Token
- **Hak Akses & Batasan**:
  - **Wali Kelas (Walas)**: Hanya dapat mengambil rekap kelas yang diwalikannya (1 atau 2 kelas miliknya). Jika walas mengampu 2 kelas, ia dapat memilih salah satu dari 2 kelas tersebut via parameter `kelas_id`. Upaya mengakses rekap kelas guru lain akan ditolak (`403 Forbidden`).
  - **Waka Kurikulum & Admin**: Dapat mengambil rekap untuk seluruh kelas di sekolah.
- **Query Parameters**:
  - `kelas_id` (optional): `1` *(Opsional jika walas mengampu lebih dari 1 kelas atau diakses oleh Waka/Admin)*
  - `start_date` (optional): `01-09-2026`
  - `end_date` (optional): `10-09-2026`
- **Contoh Request**:
  - `GET /api/mobile/rekap-kelas?start_date=01-09-2026&end_date=10-09-2026&kelas_id=1`
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "data": {
    "kelas_id": 1,
    "kelas_name": "Kelas X IPA 1 (Wali Kelas)",
    "total_siswa": 32,
    "start_date": "01-09-2026",
    "end_date": "10-09-2026",
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
Mendapatkan daftar pilihan kelas.

- **URL**: `/api/mobile/kelases` (atau `/api/mobile/kelas`)
- **Method**: `GET`
- **Auth**: Bearer Token
- **Hak Akses & Batasan**:
  - **Wali Kelas (Walas)**: Otomatis hanya mengembalikan kelas-kelas tempat guru tersebut menjadi wali kelas (jika 1 kelas maka 1 kelas yang tampil, jika 2 kelas maka 2 kelas tersebut yang tampil) beserta status perannya (`Wali Kelas 1` / `Wali Kelas 2`).
  - **Waka Kurikulum & Admin**: Mengembalikan seluruh kelas yang ada di sekolah.
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
  - `end_date` (optional): `10-09-2026`
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

---

## 👤 5. Biometrik Face ID (Universal Guru & Siswa)

Sistem Face ID menggunakan ekstraksi vektor 512-dimensi (FaceNet deep learning). Endpoint berikut berlaku universal baik untuk akun **Guru/Pegawai** maupun **Siswa**.

### 5.1 Cek Status Registrasi Face ID (`GET /api/mobile/face-status`)
Digunakan oleh aplikasi mobile untuk mengecek apakah pengguna yang sedang login sudah memiliki master Face ID terdaftar.

- **URL**: `/api/mobile/face-status`
- **Method**: `GET`
- **Auth**: Bearer Token
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "data": {
    "has_face_registered": true,
    "is_verified": true,
    "sample_photo_url": "http://absen.jagattech.my.id/storage/faces/sample_123.jpg",
    "registered_at": "2026-10-12 08:30:00"
  }
}
```
*Jika belum terdaftar, `has_face_registered` bernilai `false` dan `sample_photo_url` bernilai `null`.*

---

### 5.2 Registrasi Master Wajah Face ID (`POST /api/mobile/face-register`)
Mendaftarkan foto wajah asli pengguna sebagai referensi embedding biometrik saat absen.

- **URL**: `/api/mobile/face-register`
- **Method**: `POST`
- **Content-Type**: `multipart/form-data`
- **Auth**: Bearer Token
- **Body Request**:
  - `photo` (file, required): File gambar wajah menghadap lurus ke kamera (maks 10MB, format JPG/PNG).
- **Aturan Proteksi Anti-Duplikasi**:
  - Jika wajah **sudah pernah didaftarkan**, sistem akan **menolak** pendaftaran ulang dengan status `422 Unprocessable Entity`.
  - Pendaftaran ulang hanya dapat dilakukan jika admin/operator sekolah telah mereset Face ID melalui dashboard web.

- **Response Success (200 OK)**:
```json
{
  "success": true,
  "message": "Registrasi wajah master berhasil disimpan.",
  "data": {
    "is_verified": true,
    "sample_photo_url": "http://absen.jagattech.my.id/storage/faces/abc123xyz.jpg",
    "registered_at": "2026-10-12 08:35:12"
  }
}
```

- **Response Error (422 - Wajah Sudah Pernah Terdaftar)**:
```json
{
  "success": false,
  "message": "Pendaftaran ditolak: Wajah Anda sudah terdaftar di sistem. Silakan hubungi admin sekolah jika ingin mereset/mendaftarkan ulang data Face ID."
}
```

- **Response Error (400 - Wajah Tidak Terdeteksi / Buram)**:
```json
{
  "success": false,
  "message": "Wajah tidak terdeteksi pada foto. Pastikan wajah terlihat jelas menghadap kamera."
}
```

---

### 5.3 Scan Wajah Absensi Siswa 1:N (`POST /api/mobile/student/scan-face`)
Endpoint pencarian 1:N untuk melakukan absensi otomatis (Masuk atau Pulang) siswa berdasarkan tangkapan kamera mobile. Server mencocokkan vektor embedding wajah dengan seluruh master Face ID siswa di unit sekolah tersebut tanpa perlu menyimpan file foto hasil scan ke disk storage server.

- **URL**: `/api/mobile/student/scan-face`
- **Method**: `POST`
- **Content-Type**: `multipart/form-data`
- **Auth**: Bearer Token
- **Body Request**:
  - `photo` (file, required): File foto hasil tangkapan kamera dari aplikasi mobile.
  - `tanggal` (string, optional, contoh: `"13-10-2026"` atau `"2026-10-13"`): Tanggal absensi (default: tanggal hari ini).

- **Response Success (200 OK) — Absen Masuk**:
```json
{
  "success": true,
  "message": "Absen Masuk Berhasil (07:15 WIB)",
  "data": {
    "student_id": 102,
    "nama": "Ahmad Rizky",
    "nis": "20240012",
    "kelas_name": "XII IPA 1",
    "confidence": 0.985,
    "status": "H",
    "jam_masuk": "07:15:30",
    "message": "Absen Masuk Berhasil (07:15 WIB)"
  }
}
```

- **Response Success (200 OK) — Absen Pulang**:
```json
{
  "success": true,
  "message": "Absen Pulang Berhasil (15:30 WIB)",
  "data": {
    "student_id": 102,
    "nama": "Ahmad Rizky",
    "nis": "20240012",
    "kelas_name": "XII IPA 1",
    "confidence": 0.985,
    "status": "H",
    "jam_pulang": "15:30:00",
    "message": "Absen Pulang Berhasil (15:30 WIB)"
  }
}
```

- **Response Error (404 Not Found — Wajah Tidak Dikenali / Belum Terdaftar)**:
```json
{
  "success": false,
  "message": "Wajah siswa tidak terdaftar atau tidak cocok dalam database",
  "data": null
}
```

- **Response Error (422 Unprocessable Entity — Di Luar Jadwal Sekolah)**:
  - *Belum Buka Absen Masuk*: `{"success": false, "message": "Absen masuk belum dibuka (Dibuka pukul 05:30)", "data": null}`
  - *Tutup Absen Masuk*: `{"success": false, "message": "Batas waktu absen masuk sudah ditutup (Pukul 09:00)", "data": null}`
  - *Belum Buka Absen Pulang*: `{"success": false, "message": "Belum masuk waktu absen pulang (Dibuka pukul 15:30)", "data": null}`
  - *Tutup Absen Pulang*: `{"success": false, "message": "Batas waktu absen pulang sudah ditutup (Pukul 17:00)", "data": null}`
  - *Hari Libur*: `{"success": false, "message": "Hari ini adalah hari libur sekolah (tidak ada jadwal aktif)", "data": null}`
  - *Belum Pernah Masuk Saat Hendak Pulang*: `{"success": false, "message": "Siswa Ahmad Rizky belum melakukan absen masuk hari ini", "data": null}`

---

## 📸 6. Upload Foto Profil (Siswa & Guru)

Endpoint untuk memperbarui foto profil identitas di sistem. Otomatis melakukan kompresi cerdas GD (maks. 600x800 px) dan menghapus file foto fisik lama dari disk storage server saat diperbarui.

### 6.1 Upload Foto Siswa (`POST /api/mobile/student/photo` atau `/mobile/student/upload-photo`)
Dapat dipanggil oleh Siswa yang sedang login (untuk fotonya sendiri) atau oleh Guru / Admin (dengan menyertakan `student_id`).

- **URL**: `/api/mobile/student/photo` atau `/api/mobile/student/upload-photo`
- **Method**: `POST`
- **Content-Type**: `multipart/form-data`
- **Auth**: Bearer Token
- **Body Request**:
  - `photo` / `foto` (file, required): File gambar profil (maks 10MB).
  - `student_id` (integer, optional): Wajib diisi jika diunggah oleh akun guru/admin.
  - `sync_face` (boolean, optional, default: `true`): Jika bernilai `true` dan wajah terdeteksi, otomatis didaftarkan sebagai master Face ID (selama Face ID belum pernah terdaftar sebelumnya).

- **Response Success (200 OK)**:
```json
{
  "success": true,
  "message": "Foto siswa berhasil diperbarui dan didaftarkan untuk Face ID.",
  "data": {
    "student_id": 15,
    "nama": "Ahmad Rizky",
    "nis": "1001",
    "foto": "siswa/foto_1001_1728723948.jpg",
    "foto_url": "http://absen.jagattech.my.id/storage/siswa/foto_1001_1728723948.jpg",
    "face_registered": true,
    "face_message": "Wajah berhasil dideteksi dan didaftarkan sebagai master Face ID."
  }
}
```

---

### 6.2 Upload Foto Guru (`POST /api/mobile/teacher/photo` atau `/mobile/guru/photo`)
Dapat dipanggil oleh Guru yang sedang login (untuk fotonya sendiri) atau oleh Admin sekolah (dengan menyertakan `guru_id` / `teacher_id`).

- **URL**: `/api/mobile/teacher/photo` atau `/api/mobile/guru/photo`
- **Method**: `POST`
- **Content-Type**: `multipart/form-data`
- **Auth**: Bearer Token
- **Body Request**:
  - `photo` / `foto` (file, required): File gambar profil (maks 10MB).
  - `guru_id` / `teacher_id` (integer, optional): ID guru jika diunggah oleh admin sekolah.
  - `sync_face` (boolean, optional, default: `true`): Jika `true`, otomatis mendaftarkan master Face ID jika wajah terdeteksi dan belum pernah terdaftar.

- **Response Success (200 OK)**:
```json
{
  "success": true,
  "message": "Foto guru berhasil diperbarui dan didaftarkan untuk Face ID.",
  "data": {
    "guru_id": 8,
    "nama": "Budi Santoso, S.Pd",
    "nip": "198501012010011002",
    "foto": "guru/foto_guru_8_1728723948.jpg",
    "foto_url": "http://absen.jagattech.my.id/storage/guru/foto_guru_8_1728723948.jpg",
    "face_registered": true,
    "face_message": "Wajah berhasil dideteksi dan didaftarkan sebagai master Face ID."
  }
}
```

---

## 🛠️ 7. Tombol Reset / Hapus Face ID (Web Dashboard Admin)

Untuk menjaga keamanan agar siswa atau guru tidak dapat sembarangan mendaftarkan wajah orang lain, Face ID yang sudah terdaftar hanya dapat dihapus melalui Web Dashboard Admin:

- **Menu Data Siswa**: Terdapat tombol aksi **Hapus Face ID** (`POST /siswa/{id}/delete-face`).
- **Menu Data Guru**: Terdapat tombol aksi **Hapus Face ID** (`POST /guru/{id}/delete-face`).
- **Validasi Keamanan**: Diperiksa dengan validasi multi-tenant (`school_id`). Menghapus record biometrik embedding sekaligus menghapus file foto sample master di folder storage.

---

## 📚 8. Fitur Jadwal Pembelajaran & Absensi Siswa Per Jam Pelajaran (Apps Guru Pengajar)

Modul ini digunakan oleh Guru Pengajar pada aplikasi mobile untuk melihat jadwal mengajar hari ini/mingguan, mengisi jurnal KBM, dan melakukan absensi siswa per jam pelajaran.

### 8.1 Jadwal Mengajar Hari Ini (`GET /api/mobile/jadwal-pelajaran/hari-ini`)
Mengambil seluruh jadwal mengajar guru yang sedang login untuk hari ini (atau tanggal tertentu).
> **Fitur Kelas Gabungan (Multi-Kelas):** Jika guru mengajar jam dan mata pelajaran yang sama pada 2 atau lebih kelas paralel, sistem otomatis menggabungkannya menjadi 1 item sesi mengajar dengan akumulasi data statistik dan daftar kelas gabungan.

- **URL**: `/api/mobile/jadwal-pelajaran/hari-ini`
- **Method**: `GET`
- **Auth**: Bearer Token
- **Query Params**:
  - `tanggal` *(opsional, format `YYYY-MM-DD`, default: hari ini)*
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "data": {
    "tanggal": "2026-10-14",
    "hari": "Rabu",
    "guru": {
      "id": 8,
      "nama": "Budi Santoso, S.Pd",
      "nip": "198501012010011002"
    },
    "jadwals": [
      {
        "id": 12,
        "hari": "Rabu",
        "jam_ke": "1-2",
        "jam_mulai": "07:15",
        "jam_selesai": "08:35",
        "jam_format": "07:15 - 08:35",
        "ruangan": "Lab Komputer 1",
        "kelas_id": 3,
        "kelas_nama": "X-RPL 1, X-RPL 2",
        "is_multi_kelas": true,
        "parallel_count": 2,
        "parallel_kelases": ["X-RPL 1", "X-RPL 2"],
        "parallel_ids": [12, 13],
        "mapel_id": 5,
        "mapel_nama": "Pemrograman Web",
        "is_attended": true,
        "jurnal": {
          "id": 4,
          "materi": "Pengenalan HTML5 dan Semantic Tags",
          "catatan": "Siswa mengerjakan latihan modul 1"
        },
        "stats": {
          "total": 64,
          "h": 60,
          "s": 2,
          "i": 1,
          "a": 1,
          "t": 0,
          "d": 0
        }
      }
    ]
  }
}
```

---

### 8.2 Jadwal Mengajar Mingguan (`GET /api/mobile/jadwal-pelajaran/mingguan`)
Mengambil seluruh jadwal mengajar guru selama satu minggu, dikelompokkan per hari. Mendukung sesi kelas gabungan (`is_multi_kelas: true`).

- **URL**: `/api/mobile/jadwal-pelajaran/mingguan`
- **Method**: `GET`
- **Auth**: Bearer Token
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "data": {
    "Senin": [ ... ],
    "Rabu": [ ... ]
  }
}
```

---

### 8.3 Detail Presensi & Daftar Siswa Kelas (`GET /api/mobile/jadwal-pelajaran/{id}/presensi`)
Mengambil info jadwal, data jurnal KBM, dan daftar seluruh siswa beserta status absensi saat ini.
> **Dukungan Multi-Kelas:** Jika jadwal merupakan sesi paralel/kelas gabungan, daftar `students` otomatis memuat seluruh siswa dari semua kelas gabungan tersebut (disertai `kelas_id` dan `kelas_nama` pada tiap siswa), dan data `parallel_classes` disediakan untuk memfasilitasi tab atau filter per kelas di mobile app.

- **URL**: `/api/mobile/jadwal-pelajaran/{id}/presensi`
- **Method**: `GET`
- **Auth**: Bearer Token
- **Query Params**:
  - `tanggal` *(opsional, format `YYYY-MM-DD`, default: hari ini)*
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "data": {
    "jadwal": {
      "id": 12,
      "hari": "Rabu",
      "jam_ke": "1-2",
      "jam_mulai": "07:15",
      "jam_selesai": "08:35",
      "jam_format": "07:15 - 08:35",
      "ruangan": "Lab Komputer 1",
      "kelas": { "id": 3, "nama": "X-RPL 1, X-RPL 2" },
      "is_multi_kelas": true,
      "parallel_count": 2,
      "parallel_kelases": ["X-RPL 1", "X-RPL 2"],
      "parallel_classes": [
        { "jadwal_id": 12, "kelas_id": 3, "kelas_nama": "X-RPL 1" },
        { "jadwal_id": 13, "kelas_id": 4, "kelas_nama": "X-RPL 2" }
      ],
      "mapel": { "id": 5, "nama": "Pemrograman Web" },
      "guru": { "id": 8, "nama": "Budi Santoso, S.Pd" }
    },
    "tanggal": "2026-10-14",
    "jurnal": {
      "id": 4,
      "materi": "Pengenalan HTML5",
      "catatan": "Latihan modul 1"
    },
    "summary": {
      "total": 64,
      "h": 60,
      "s": 2,
      "i": 1,
      "a": 1,
      "t": 0,
      "d": 0
    },
    "students": [
      {
        "id": 15,
        "nama": "Ahmad Rizky",
        "nis": "1001",
        "foto": "http://domain.com/storage/siswa/1001.jpg",
        "kelas_id": 3,
        "kelas_nama": "X-RPL 1",
        "status": "H",
        "status_label": "Hadir",
        "keterangan": "",
        "is_saved": true
      },
      {
        "id": 45,
        "nama": "Budi Pratama",
        "nis": "2001",
        "foto": "http://domain.com/storage/siswa/2001.jpg",
        "kelas_id": 4,
        "kelas_nama": "X-RPL 2",
        "status": "H",
        "status_label": "Hadir",
        "keterangan": "",
        "is_saved": true
      }
    ]
  }
}
```

---

### 8.4 Simpan Presensi Batch & Jurnal KBM (`POST /api/mobile/jadwal-pelajaran/{id}/presensi`)
Menyimpan materi jurnal KBM dan seluruh daftar kehadiran siswa dalam satu request secara serentak.
> **Auto-Sync Multi-Kelas:** Jika jadwal merupakan sesi paralel/gabungan, materi & catatan Jurnal KBM **otomatis disinkronkan ke seluruh jadwal kelas paralel**, dan kehadiran siswa otomatis disimpan ke masing-masing jadwal kelas asalnya.

- **URL**: `/api/mobile/jadwal-pelajaran/{id}/presensi`
- **Method**: `POST`
- **Auth**: Bearer Token
- **Body Request (JSON)**:
```json
{
  "tanggal": "2026-10-14",
  "materi": "Bab 3: Pembuatan Layout dengan Flexbox dan CSS Grid",
  "catatan": "Seluruh siswa berhasil menyelesaikan tugas latihan praktikum",
  "attendances": [
    {
      "student_id": 15,
      "status": "H",
      "keterangan": ""
    },
    {
      "student_id": 45,
      "status": "S",
      "keterangan": "Surat dokter menyusul"
    }
  ]
}
```
*Keterangan Status:* `H` = Hadir, `S` = Sakit, `I` = Izin, `A` = Alpa, `T` = Terlambat, `D` = Dispen.

- **Response Success (200 OK)**:
```json
{
  "success": true,
  "message": "Presensi dan Jurnal KBM berhasil disimpan."
}
```

---

### 8.5 Update Presensi 1 Siswa Instan (`POST /api/mobile/jadwal-pelajaran/{id}/presensi-single`)
Digunakan untuk interaksi tap langsung / update cepat 1 siswa dari layar mobile app.

- **URL**: `/api/mobile/jadwal-pelajaran/{id}/presensi-single`
- **Method**: `POST`
- **Auth**: Bearer Token
- **Body Request (JSON)**:
```json
{
  "student_id": 15,
  "tanggal": "2026-10-14",
  "status": "S",
  "keterangan": "Demam"
}
```
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "message": "Status siswa berhasil diperbarui.",
  "data": {
    "id": 105,
    "student_id": 15,
    "status": "S",
    "status_label": "Sakit"
  }
}
```

---

### 8.6 Daftar Mata Pelajaran (`GET /api/mobile/mapels`)
Mengambil daftar mata pelajaran yang aktif untuk sekolah pengguna. Dapat difilter berdasarkan `kelas_id`.

- **URL**: `/api/mobile/mapels?kelas_id=12`
- **Method**: `GET`
- **Auth**: Bearer Token
- **Query Params**:
  - `kelas_id` (opsional): ID kelas untuk mengambil mapel yang relevan/ter-mapping.
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "data": [
    { "id": 1, "nama_mapel": "Matematika" },
    { "id": 2, "nama_mapel": "Bahasa Indonesia" }
  ]
}
```

---

### 8.7 Daftar Master Jam Pelajaran (`GET /api/mobile/jam-pelajaran`)
Mengambil daftar master sesi jam pelajaran yang aktif (seperti master shift), sehingga aplikasi mobile dapat menampilkan pilihan sesi jam secara instan.

- **URL**: `/api/mobile/jam-pelajaran` (opsional `?hari=Senin`)
- **Method**: `GET`
- **Auth**: Bearer Token
- **Query Params**:
  - `hari` (opsional): Nama hari jika ingin filter sesi khusus hari tertentu (misal: "Jumat").
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nama_jam": "Jam Ke-1",
      "jam_ke": "1",
      "jam_mulai": "07:15",
      "jam_selesai": "08:00",
      "rentang": "07:15 - 08:00",
      "durasi": 45,
      "label": "Jam Ke-1 [Jam ke 1] (07:15 - 08:00)",
      "hari": null
    },
    {
      "id": 2,
      "nama_jam": "Jam Ke-2",
      "jam_ke": "2",
      "jam_mulai": "08:00",
      "jam_selesai": "08:45",
      "rentang": "08:00 - 08:45",
      "durasi": 45,
      "label": "Jam Ke-2 [Jam ke 2] (08:00 - 08:45)",
      "hari": null
    }
  ]
}
```

---

### 8.8 Jadwal Pelajaran Hari Ini & Status Presensi Mapel untuk Siswa (`GET /api/mobile/student/jadwal-pelajaran/hari-ini`)
Digunakan oleh aplikasi **Siswa (atau Orang Tua)** untuk melihat daftar mata pelajaran hari ini (berdasarkan kelas siswa), jam pelajaran, guru pengajar, materi KBM dari guru, serta **status kehadiran presensi siswa pada setiap jam pelajaran/mapel**.

> **Tips:** Endpoint polymorphic `GET /api/mobile/jadwal-pelajaran/hari-ini` secara otomatis mengenali peran akun. Jika user yang login adalah siswa, endpoint tersebut otomatis mengembalikan respon siswa di bawah ini. Respon menyediakan list pada properti `pelajaran` sekaligus alias `jadwals` beserta atribut flat (`mapel_nama`, `guru_nama`, `status`, `status_label`, `is_attended`) untuk memastikan kompatibilitas penuh dengan aplikasi mobile.

- **URL**: `/api/mobile/student/jadwal-pelajaran/hari-ini` *(alias: `/api/mobile/siswa/jadwal-pelajaran/hari-ini` atau `/api/mobile/jadwal-pelajaran/hari-ini`)*
- **Method**: `GET`
- **Auth**: Bearer Token
- **Query Params**:
  - `tanggal` *(opsional, format `YYYY-MM-DD`, default: hari ini)*
  - `student_id` *(opsional, untuk guru/wali kelas yang ingin mengecek siswa tertentu)*
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "data": {
    "tanggal": "2026-10-17",
    "hari": "Sabtu",
    "student": {
      "id": 15,
      "nama": "Ahmad Rizky Pratama",
      "nis": "1001",
      "foto_url": "http://domain.com/storage/siswa/1001.jpg",
      "kelas_id": 3,
      "kelas_nama": "XII RPL 1"
    },
    "summary": {
      "total": 4,
      "sudah_absen": 2,
      "h": 2,
      "s": 0,
      "i": 0,
      "a": 0,
      "t": 0,
      "d": 0,
      "belum_absen": 2
    },
    "pelajaran": [
      {
        "id": 12,
        "jadwal_pelajaran_id": 12,
        "hari": "Sabtu",
        "jam_ke": "1-2",
        "jam_mulai": "07:15",
        "jam_selesai": "08:35",
        "jam_format": "07:15 - 08:35",
        "ruangan": "Lab Komputer 1",
        "mapel": {
          "id": 5,
          "nama": "Pemrograman Web"
        },
        "guru": {
          "id": 8,
          "nama": "Budi Santoso, S.Pd",
          "nip": "198501012010011002",
          "foto_url": "http://domain.com/storage/guru/budi.jpg"
        },
        "presensi": {
          "is_attended": true,
          "status": "H",
          "status_label": "Hadir",
          "keterangan": "",
          "guru_pengabsen": "Budi Santoso, S.Pd",
          "waktu_absen": "2026-10-17 07:30:00"
        },
        "jurnal": {
          "id": 4,
          "materi": "Implementasi Responsive Layout dengan CSS Grid",
          "catatan": "Siswa mengerjakan modul latihan praktikum 2"
        }
      },
      {
        "id": 13,
        "jadwal_pelajaran_id": 13,
        "hari": "Sabtu",
        "jam_ke": "3-4",
        "jam_mulai": "08:35",
        "jam_selesai": "09:55",
        "jam_format": "08:35 - 09:55",
        "ruangan": "Ruang Teori 12",
        "mapel": {
          "id": 2,
          "nama": "Bahasa Indonesia"
        },
        "guru": {
          "id": 11,
          "nama": "Siti Nurhaliza, M.Pd",
          "nip": "199003122015022001",
          "foto_url": null
        },
        "presensi": {
          "is_attended": false,
          "status": null,
          "status_label": "Belum Presensi",
          "keterangan": "",
          "guru_pengabsen": null,
          "waktu_absen": null
        },
        "jurnal": null
      }
    ]
  }
}
```

*Keterangan Nilai `status`:*
- `H`: Hadir
- `S`: Sakit
- `I`: Izin
- `A`: Alpa
- `T`: Terlambat
- `D`: Dispen (Dispensasi)
- `null`: Belum ada presensi yang diinput guru untuk sesi mapel ini.

---

### 8.9 Jadwal Pelajaran Mingguan untuk Siswa (`GET /api/mobile/student/jadwal-pelajaran/mingguan`)
Mengambil jadwal pelajaran lengkap selama satu minggu untuk kelas siswa, dikelompokkan berdasarkan hari (Senin s/d Minggu).

- **URL**: `/api/mobile/student/jadwal-pelajaran/mingguan` *(alias: `/api/mobile/siswa/jadwal-pelajaran/mingguan` atau `/api/mobile/jadwal-pelajaran/mingguan`)*
- **Method**: `GET`
- **Auth**: Bearer Token
- **Response Success (200 OK)**:
```json
{
  "success": true,
  "data": {
    "student": {
      "id": 15,
      "nama": "Ahmad Rizky Pratama",
      "nis": "1001",
      "kelas_id": 3,
      "kelas_nama": "XII RPL 1"
    },
    "jadwals": {
      "Senin": [
        {
          "id": 1,
          "hari": "Senin",
          "jam_ke": "1-2",
          "jam_mulai": "07:15",
          "jam_selesai": "08:35",
          "jam_format": "07:15 - 08:35",
          "ruangan": "Lab 1",
          "mapel": { "id": 5, "nama": "Pemrograman Web" },
          "guru": { "id": 8, "nama": "Budi Santoso, S.Pd", "nip": "198501012010011002", "foto_url": "..." }
        }
      ],
      "Selasa": [ ... ]
    }
  }
}
```



