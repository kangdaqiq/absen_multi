# Analisa Gap Integrasi Multi-Tenant

Berikut adalah hasil analisa komponen sistem yang belum terintegrasi dengan arsitektur Multi-Tenant (Multi-Sekolah).

## 1. RFID Controller (CRITICAL)
Logic Absensi via Scanner (`RfidController.php`) masih berjalan secara **Global**. Ini berbahaya jika ada dua sekolah yang menggunakan sistem ini bersamaan.

### Masalah yang Ditemukan:
*   **Pencarian Siswa Global:** Sistem mencari siswa berdasarkan `uid_rfid` di **seluruh database** tanpa mempedulikan sekolah.
    *   *Resiko:* Jika ada UID ganda atau kartu bekas dipakai di sekolah A lalu dipakai di sekolah B, bisa salah deteksi siswa.
*   **Jadwal Global:** `Jadwal::where('index_hari', ...)->first()` mengambil jadwal pertama yang ditemukan di DB.
    *   *Resiko:* Siswa Sekolah A bisa ter- absen menggunakan aturan jam masuk Sekolah B.
*   **Pengaturan Global:** Pengambilan setting (`enable_checkout_attendance`, dll) tidak memfilter berdasarkan sekolah.
    *   *Resiko:* Pengaturan Sekolah A akan menimpa Sekolah B (atau sebaliknya).
*   **Enrollment Global:** Fitur pendaftaran kartu mencari siswa/guru yang statusnya `requested` di seluruh DB.
    *   *Resiko:* Kartu yang discan di Sekolah A bisa malah mendaftarkan siswa dari Sekolah B yang sedang menunggu enroll.

### Solusi:
Logic harus scoping berdasarkan `school_id` dari **Device** yang mengirim request.
1.  Ambil `school_id` dari API Key device.
2.  Filter pencarian Siswa/Guru hanya di `school_id` tersebut.
3.  Filter query Jadwal & Setting menggunakan `school_id`.

## 2. Hari Libur (Validation Gap)
*   **Masalah:** Validasi Tanggal Unik (`unique:hari_libur,tanggal`) bersifat global.
*   **Dampak:** Jika Sekolah A meliburkan tanggal 1 Januari, Sekolah B **tidak bisa** menyimpan tanggal libur yang sama karena dianggap duplikat.
*   **Solusi:** Ubah validasi agar unik per `school_id` (kombinasi `tanggal` + `school_id`).

## 3. Device Management
*   **Status:** Tabel `api_keys` (Device) sudah memiliki `school_id`.
*   **Perlu Cek:** Pastikan saat Super Admin membuat Device baru, `school_id` wajib diisi atau dipilih.

## 4. WhatsApp Bot
*   **Status:** Saat ini menggunakan satu antrian global (`message_queue`).
*   **Dampak:** Satu nomor bot melayani semua sekolah. Pesan akan antri di satu jalur.
*   **Saran:** Untuk saat ini aman, asalkan format pesan sudah benar. Kedepannya jika ingin setiap sekolah punya nomor WA sendiri, perlu update struktur tabel queue.

---
## Rekomendasi Prioritas Perbaikan
1.  **Refactor `RfidController`** (Mendesak, menyangkut operasional harian).
2.  **Fix Validasi `HariLibur`** (Penting agar admin tidak bingung).
