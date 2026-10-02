# 📝 Catatan Rilis & Riwayat Pembaruan Proyek (Changelog)

Dokumen ini mencatat seluruh riwayat pembaruan, penambahan fitur penting, integrasi perangkat keras IoT, serta perbaikan sistem pada aplikasi **Sistem Absensi Multi-Sekolah** dari awal pengembangan hingga versi saat ini, disusun secara kronologis per tanggal rilis menggunakan Bahasa Indonesia.

---

## 📌 Ringkasan Perkembangan Proyek
- **Januari 2026**: Inisiasi sistem absensi sekolah, fondasi arsitektur multi-tenancy, integrasi WhatsApp Gateway, pengiriman laporan berkala ke grup kelas, notifikasi orang tua, dan penanganan auto-bolos.
- **April – Mei 2026**: Modernisasi antarmuka berbasis TailAdmin (Tailwind CSS), sistem lisensi client self-hosted, integrasi hardware ESP8266 (RFID & Fingerprint R307) dengan sinkronisasi antrean offline, mode layar penuh (kiosk) lobi sekolah, dan backup cloud Cloudflare R2.
- **Juni – Juli 2026**: Integrasi notifikasi bot Telegram, sistem perlindungan anti-blokir WhatsApp (spintax template & antrean adaptif), modul absensi kegiatan/ekstrakurikuler, dan fitur kartu gerbang izin kepulangan.
- **Agustus – September 2026**: Manajemen shift kerja dan rekapitulasi guru, portal pengajuan izin siswa mandiri, API mobile (Sanctum), notifikasi push FCM (Firebase), dan pembaruan firmware IoT secara nirkabel (OTA).
- **Oktober 2026**: Modul Super-Admin manajemen multi-sekolah, pencadangan otomatis Cloudflare R2 khusus self-hosted dengan retensi 2 hari, fitur pengurutan (Sorting ASC/DESC) interaktif pada Rekap Siswa & Kelas, serta restrukturisasi Rekap Guru menjadi format ringkasan per guru.

---

## 🗓️ Periode Januari 2026

### 📅 02 Januari 2026 (2026-01-02)

- Inisialisasi awal repositori dan pengunggahan struktur dasar proyek.
- Penambahan halaman pengaturan penjadwalan otomatis (scheduler) absensi berbasis antarmuka web.

---

### 📅 04 Januari 2026 (2026-01-04)

- Otomasi laporan harian diperbarui agar melewati hari Minggu dan hari libur sekolah.
- Implementasi fitur siaran pesan WhatsApp (Broadcast) per kelas dengan pilihan kotak centang (checkbox).
- Fix Select All JS not loading.
- Penerapan jeda waktu (delay 2 detik) pada antrean kirim WhatsApp untuk mencegah pemblokiran nomor.

---

### 📅 05 Januari 2026 (2026-01-05)

- Perbaikan kendala mixed-content HTTPS pada request AJAX.
- Perbaikan kendala mixed-content HTTPS pada halaman manajemen guru.
- Perbaikan URL aset dan request AJAX pada halaman jadwal, mapel, perangkat, dan kelas.
- Dukungan integrasi ID Grup WhatsApp pada setiap kelas dan pemilih grup otomatis dari akun terhubung.
- Penyederhanaan menu pengaturan dengan menghapus opsi grup laporan lama.
- Penambahan toggle pengiriman laporan kehadiran per kelas yang otomatis nonaktif jika libur.
- Penyelarasan label status laporan pada badge antarmuka.
- Penyelarasan format laporan harian WhatsApp dengan pengelompokan status siswa.
- Fitur rekapitulasi ketidakhadiran mingguan otomatis dengan pengiriman ke wali kelas dan orang tua.
- Pengembangan antarmuka laporan absensi dengan filter periode, tabel data, ekspor laporan, dan modal detail siswa.
- Penyederhanaan filter laporan absensi menggunakan pengaturan periode default sekolah.
- Penyederhanaan filter laporan dengan menggunakan ambang batas dari pengaturan umum.
- Pembersihan teks instruksi redundan pada filter laporan absensi.
- Optimalisasi pengiriman laporan mingguan hanya ke grup kelas dan grup guru.

---

### 📅 06 Januari 2026 (2026-01-06)

- Fitur penutupan gerbang manual untuk mengontrol dan memvalidasi absensi kepulangan siswa.
- Penyempurnaan kalkulasi menit keterlambatan siswa agar dihitung dari batas akhir waktu toleransi masuk.
- Perbaikan bug durasi negatif pada kalkulasi waktu checkout absensi pulang.

---

### 📅 07 Januari 2026 (2026-01-07)

- Perbaikan fungsi pengiriman pesan massal WhatsApp Broadcast.
- Integrasi notifikasi WhatsApp otomatis ke nomor orang tua/wali murid saat siswa melakukan absensi.
- Penambahan modul diagnostik untuk pengujian sistem notifikasi.
- Peningkatan kehandalan pengiriman notifikasi ketika nomor telepon siswa/ortu kosong.
- Penambahan salam pembuka islami standar pada pesan notifikasi absensi.
- Penyederhanaan format pesan notifikasi kepulangan siswa via WhatsApp.
- Peningkatan validasi format nomor telepon, perbaikan halaman login, dan opsi hapus data absensi.
- Penyediaan tombol hapus catatan absensi untuk seluruh status (Hadir, Sakit, Izin, Alpha, Bolos).
- Fitur unggah logo sekolah dan pengaturan posisi logo pada kop surat laporan.
- Pembaruan tata letak halaman pengaturan dan perbaikan perilaku notifikasi alert.
- Penyesuaian tampilan kotak informasi agar tetap tampil statis tanpa hilang otomatis.
- Penyederhanaan tata letak formulir pengaturan menjadi satu kolom responsif.
- Dukungan integrasi ID Grup WhatsApp pada setiap kelas dan pemilih grup otomatis dari akun terhubung.
- Penyesuaian layer modal pemilih grup WhatsApp agar tidak tertutup elemen lain.
- Penegakan protokol aman HTTPS secara otomatis pada lingkungan server produksi.
- Konfigurasi penegakan HTTPS untuk domain produksi.
- Penerapan perlindungan keamanan Content Security Policy (CSP) dan penegakan koneksi aman HTTPS.
- Penyempurnaan parsing respon data grup WhatsApp dari gateway.
- Penanganan format respon JSON bersarang dari WhatsApp Gateway.

---

### 📅 08 Januari 2026 (2026-01-08)

- Penerapan nama sekolah dan logo yang dinamis di seluruh dashboard dan laporan resmi.
- Peralihan metode pemindaian absensi abnormal ke pengecekan berkala harian.
- Pengecualian penegakan HTTPS saat dijalankan pada lingkungan lokal/localhost.
- Penyesuaian pembuatan URL agar fleksibel mendukung protokol HTTP maupun HTTPS.
- Otomasi perpanjangan status absensi Izin dan Sakit selama 2 hari tanpa perlu input manual ulang.
- Penyesuaian perpanjangan otomatis hanya berlaku untuk status Sakit, tidak untuk Izin.
- Pencegahan perulangan otomatis perpanjangan status sakit melebihi batas 2 hari.
- Penambahan kolom pelacakan status absensi yang diperpanjang otomatis di database.

---

### 📅 09 Januari 2026 (2026-01-09)

- Perbaikan tampilan format jam pada modal ubah data absensi.

---

### 📅 10 Januari 2026 (2026-01-10)

- Penambahan pengaturan untuk mengaktifkan atau menonaktifkan kewajiban absensi pulang (checkout).
- Penyusunan dokumentasi komprehensif sistem absensi sekolah pada file README.
- Perbaikan penyimpanan status kotak centang (checkbox) pada halaman pengaturan.
- Pengiriman laporan grup kelas yang sepenuhnya spesifik sesuai data kelas bersangkutan.
- Pusat kustomisasi template pesan WhatsApp untuk berbagai jenis notifikasi (masuk, pulang, terlambat, izin/sakit).
- Penambahan variabel nama kelas pada template pesan notifikasi kehadiran masuk.

---

### 📅 11 Januari 2026 (2026-01-11)

- Implementasi sistem Multi-Tenancy penuh untuk pemisahan data antar sekolah secara aman disertai cascading delete.
- Perbaikan penanganan duplikasi username akun dan isolasi branding laporan antar sekolah.

---

## 🗓️ Periode April 2026

### 📅 25 April 2026 (2026-04-25)

- Pembaruan fitur integrasi WhatsApp Gateway, pengumuman sekolah, dan peningkatan performa.
- Pembaruan dokumentasi panduan teknis terkait fitur-fitur baru.

---

### 📅 29 April 2026 (2026-04-29)

- Migrasi menyeluruh antarmuka pengguna ke TailAdmin (Tailwind CSS) dengan dukungan tema gelap (Dark Mode).
- Perbaikan berkas migrasi database agar mendukung instalasi bersih dari dump SQL awal.
- Https mixed content and ui errors.
- Penambahan batas waktu jadwal eksplisit, auto checkout, dan tombol coba ulang API.
- Formulir uji coba kirim pesan WhatsApp dan pemasangan favicon ikon sekolah.
- Perbaikan referensi tabel antrean pesan pada pemroses WhatsApp.
- Pembaruan auto-discovery dan auto-create device WhatsApp pada gateway REST API.
- Peningkatan kehandalan pengiriman data saat registrasi perangkat WhatsApp baru.
- Penyesuaian endpoint pembuatan device sesuai spesifikasi dokumentasi API.

---

### 📅 30 April 2026 (2026-04-30)

- Pencatatan guru pemeriksa absensi dan penyesuaian batasan unik nama kelas per sekolah.

---

## 🗓️ Periode Mei 2026

### 📅 01 Mei 2026 (2026-05-01)

- Implementasi sistem lisensi sekolah, validasi kode lisensi, batas kuota, dan masa tenggang (grace period).
- Add license key input form on invalid license page for easier initial setup.
- Perbaikan kendala pembacaan kunci cache lisensi pada middleware CheckLicense.
- Kemudahan bagi admin sekolah mengatur lisensi self-hosted tanpa hak akses super-admin.
- Pembaruan DatabaseSeeder untuk pembuatan sekolah default dan akun administrator mandiri.
- Konfigurasi default database versi self-hosted dan penyesuaian tampilan kuota bot.
- Penambahan perintah artisan khusus `migrate:full` untuk instalasi skema PDO murni.
- Pembersihan data dummy dari dump skema database MySQL.
- Pembersihan seluruh data contoh (dummy data) dari template database awal.
- Perbaikan tampilan notifikasi kuota bot WhatsApp agar sesuai batas lisensi self-hosted.
- Integrasi bot WhatsApp ke dalam konfigurasi docker-compose untuk kemudahan deployment 1-klik.
- Penyelarasan konteks layanan bot WhatsApp internal.
- Ci: add github action to auto build and push docker image.
- Add libxml2-dev and remove default built-in PHP extensions to resolve docker build failure.
- Update docker-compose.yml.
- Use pre-built images for both web and bot to hide source code from clients.

---

### 📅 02 Mei 2026 (2026-05-02)

- Perbaikan validasi ekspresi reguler (regex) format nomor WhatsApp siswa dan orang tua.
- Penghapusan dependensi Docker yang tidak digunakan dan penguatan enkripsi lisensi.
- Penyempurnaan format teks notifikasi keterlambatan (menampilkan jam dan menit secara jelas).
- Peningkatan keamanan untuk mencegah bypass mode aplikasi pada instalasi client.
- Penyediaan skrip otomatisasi build paket rilis client self-hosted (`build_release.php`) dan petunjuk instalasi (`INSTALL.md`).
- Pengabaian folder dan file arsip rilis client pada repositori git.

---

### 📅 03 Mei 2026 (2026-05-03)

- Backup restore - remap jadwal_pelajaran_id, anti-collision email, restore teacher_checkout_sessions, remove dead code.
- Sinkronisasi variabel konfigurasi API WhatsApp ke standar GOWA_API.

---

### 📅 04 Mei 2026 (2026-05-04)

- Peningkatan sistem OTA firmware RFID v2, kalibrasi baterai scanner, dan penambahan tanda tangan kepala sekolah/wakil pada PDF rekap.
- Remove accidentally created migration file with incorrect controller code.
- Perbaikan rute pemeriksaan firmware OTA dan pembaruan antarmuka halaman OTA ke Tailwind CSS.
- Fix ParseError: unexpected character 0x00 in api.php and import DB facade.

---

### 📅 07 Mei 2026 (2026-05-07)

- Integrasi notifikasi WhatsApp otomatis ke nomor orang tua/wali murid saat siswa melakukan absensi.
- Penambahan perangkat WhatsApp super-admin khusus pengingat perpanjangan langganan.
- Penambahan tombol kontak WhatsApp bantuan langsung pada halaman dukungan teknis.
- Peningkatan alur langganan sekolah, perbaikan bug minor, dan penyesuaian tata letak.
- Remove non-functional search bar from header and update support page wording.
- Penambahan proteksi keamanan anti-brute force untuk mencegah percobaan login berulang yang mencurigakan.
- Integrasi pencadangan database otomatis ke cloud object storage Cloudflare R2.
- Fitur kustomisasi unggah gambar kop surat sekolah untuk dokumen cetak laporan resmi.
- Pencatatan kegagalan autentikasi API untuk audit SuperAdmin dan dukungan stempel waktu offline sync pada scanner.
- Peningkatan performa halaman log API dengan eager-loading relasi data.
- Fix syntax error (character 0x00) in RfidController.
- Konversi kode singkatan status kehadiran menjadi kata lengkap pada notifikasi WhatsApp.
- Implementasi dashboard pemantauan absensi realtime dan mode layar penuh (Fullscreen Kiosk) untuk monitor lobi gerbang sekolah.
- Complete Redesign of Fullscreen Monitoring for Premium Aesthetics.
- Sudah Tap = siswa yang sudah tap hari ini, Belum Tap = yang belum.
- Shrink stat cards so mini cards are no longer cut off in fullscreen.

---

### 📅 08 Mei 2026 (2026-05-08)

- Optimalisasi estetika dan tata letak layar monitor absensi lobi fullscreen.
- Fitur impor data kelas dari Excel lengkap dengan template pilihan wali kelas.
- Feature: Configurable Sakit duration, NIP fields in PDF signature, and UI polishing for Rekap PDF.
- Filter log pemantauan realtime agar hanya menampilkan catatan aktivitas hari berjalan.
- Perbaikan perintah laporan harian untuk sekolah berbasis wali kelas dan penambahan opsi `--force`.
- Cleanup: Remove duplicated log line in AutoBolosCommand.

---

### 📅 09 Mei 2026 (2026-05-09)

- Kustomisasi branding dashboard sekolah dan penataan posisi tanda tangan dokumen cetak PDF.
- Penggunaan salam netral universal pada template notifikasi otomatis.

---

### 📅 10 Mei 2026 (2026-05-10)

- Pemisahan kode status keterlambatan ('T') dari kehadiran tepat waktu pada laporan rekapitulasi absensi.
- Tambah migration ubah kolom status attendance ke VARCHAR agar mendukung nilai T (Terlambat).
- Ubah jadwal AutoBolos ke everyMinute agar waktu dari UI setting berlaku.
- Mekanisme percobaan ulang otomatis (auto-retry hingga 3 kali) untuk pesan WhatsApp yang gagal terkirim.
- Penyempurnaan pemrosesan data koleksi absensi sebelum dikirimkan ke laporan akhir.
- Penyempurnaan tipe data fungsi laporan harian untuk mencegah kendala tipe data PHP.
- Pengiriman laporan absensi berkala spesifik per kelas langsung ke nomor WhatsApp wali kelas masing-masing.
- Laporan grup WA sekarang mengirim data per kelas spesifik, bukan data global seluruh sekolah.
- Tambah checkbox Report Global pada form Guru agar guru tertentu dapat menerima laporan global harian.
- Tambahkan total siswa hadir pada laporan absensi final harian.

---

### 📅 11 Mei 2026 (2026-05-11)

- Update: hapus garis tanda tangan PDF rekap & sesuaikan input UID RFID.
- Modul kartu akses gerbang (Gate Cards) untuk autentikasi izin pulang lebih awal bagi siswa oleh guru piket/satpam.
- Enhance RFID enrollment error handling for duplicate UIDs.
- Fix duplicate UID logic and prioritize enrollment in RfidController.
- Fix WA validation error and enroll DB truncation error.
- Add global report support for Guru in DailyReportCommand.
- Pengiriman notifikasi otomatis langsung ke nomor pribadi siswa dan orang tua saat terdeteksi Bolos atau Alpha.
- Penyelarasan perintah auto-bolos agar fokus mengirimkan notifikasi bolos tanpa redundansi.
- Dukungan integrasi ID Grup WhatsApp pada setiap kelas dan pemilih grup otomatis dari akun terhubung.
- Penerapan logika akses gerbang RFID dan absensi masuk/pulang guru pada kontroler disertai pembaruan firmware ESP8266.

---

### 📅 12 Mei 2026 (2026-05-12)

- Penambahan rincian status Terlambat di semua template laporan, ringkasan jumlah pada laporan global, dan opsi bypass waktu.
- Ubah label NIS menjadi NISN pada tabel dan modal edit siswa.
- Penyematan identitas/tanda tangan resmi nama sekolah secara otomatis di akhir setiap pesan WhatsApp yang terkirim.

---

### 📅 13 Mei 2026 (2026-05-13)

- Penyelesaian kendala sinkronisasi offline scanner dan peningkatan kolom pencarian menjadi server-side query cepat.
- Create initial blade view index files for teacher, class, attendance, and record modules.
- Fitur filter berdasarkan status kehadiran dan aksi pengubahan massal (bulk edit) pada absensi harian.
- Penambahan master data Jurusan / Bidang Keahlian dan penautannya pada data kelas.
- Add missing Jurusan column header in kelas table.
- Add stats per jurusan and kelas to global whatsapp report.
- Add stats per jurusan and kelas to final global whatsapp report.
- Fitur pengeditan massal data kelas (jurusan, status aktif absensi, dan status pengiriman laporan).
- Laporan harian menandai Alpha pada database dengan kemampuan scan offline menimpa status otomatis tersebut saat sinkron.
- Perekaman jam pulang secara senyap saat mode checkout dimatikan demi keakuratan laporan final.
- Tampilan konfirmasi kepulangan pada layar LCD scanner meskipun mode checkout nonaktif.
- Pengiriman notifikasi kepulangan ke WhatsApp orang tua saat siswa melakukan tap sore hari.
- Pencatatan riwayat checkout pada log API untuk audit kepulangan siswa.
- Perintah laporan harian multi-sekolah otomatis dan API handler untuk pembaca kartu RFID.

---

### 📅 15 Mei 2026 (2026-05-15)

- Firmware sensor sidik jari ESP8266 dengan antrean data offline mandiri dan integrasi lengkap dengan backend Laravel..

---

### 📅 19 Mei 2026 (2026-05-19)

- Implementasi sistem rekapitulasi absensi lengkap dengan fitur ekspor data Excel dan cetak PDF resmi.
- Kontroler laporan absensi kelas dan tampilan cetak dokumen PDF rekap kelas.

---

### 📅 20 Mei 2026 (2026-05-20)

- Dukungan domain kustom (custom domain) dan logo tersendiri untuk setiap sekolah client.
- Replace left panel fingerprint icon with school logo & name.
- Remove redundant top-left school logo in login view.
- Replace school name header with Selamat Datang in right-panel login form.
- Resize school logo and hide left panel descriptions when school is loaded.
- Redesign school login page with a premium glassmorphic portal card layout.
- Clean minimal login panel - logo + nama sekolah, no nested cards.
- Premium dark gradient login panel with glow, dot grid, and feature pills.
- Redesign custom domain login page with premium glassmorphism.
- Include compiled assets in git so server has correct CSS/JS.

---

### 📅 21 Mei 2026 (2026-05-21)

- Fix school brand logo in dashboards & fix duplicate-tap/checkout bug in rfid/fingerprint APIs.
- Logo sidebar tertutup topbar di tampilan mobile.
- Logo sidebar mobile - pakai inline style agar tidak tertutup topbar.
- Penambahan pengaturan untuk mengaktifkan atau menonaktifkan kewajiban absensi pulang (checkout).
- Implementasi dashboard pemantauan absensi realtime dan mode layar penuh (Fullscreen Kiosk) untuk monitor lobi gerbang sekolah.
- Implement dashboard and live dashboard controllers for role-based attendance monitoring.
- Implement dynamic school branding and add class attendance reporting features.

---

### 📅 22 Mei 2026 (2026-05-22)

- Rebuild assets vite.
- Penambahan statistik jumlah siswa yang belum/tidak hadir pada monitor live lobi.
- Implementasi dashboard pemantauan absensi realtime dan mode layar penuh (Fullscreen Kiosk) untuk monitor lobi gerbang sekolah.

---

### 📅 26 Mei 2026 (2026-05-26)

- Pengembangan modul absensi kegiatan khusus / ekstrakurikuler dengan manajemen sesi dan jadwal.

---

### 📅 28 Mei 2026 (2026-05-28)

- Otomasi pemrosesan absensi harian dan manajemen status kehadiran siswa.
- Perintah laporan harian multi-sekolah dengan fitur perpanjangan otomatis status sakit siswa.

---

### 📅 31 Mei 2026 (2026-05-31)

- Implement WhatsApp queue processor, add activity session/attendance database schema, and update ESP8266 RFID firmware.

---

## 🗓️ Periode Juni 2026

### 📅 06 Juni 2026 (2026-06-06)

- Dukungan resmi hardware scanner Fingerprint V3 dan RFID V2 berbasis ESP8266 beserta pemroses antreannya.
- Halaman pengelolaan langganan sekolah dengan pelacakan kuota siswa dan alur perpanjangan.

---

### 📅 07 Juni 2026 (2026-06-07)

- Fitur pengiriman ucapan selamat ulang tahun otomatis kepada siswa dan guru melalui WhatsApp.

---

### 📅 14 Juni 2026 (2026-06-14)

- Layanan antrean pengiriman pesan WhatsApp dengan jeda adaptif dan penjadwalan.
- Create migrations for kegiatan_sessions and kegiatan_attendances tables.
- Implement WhatsApp message queue system with priority, rate limiting, and auto-retry logic.

---

### 📅 16 Juni 2026 (2026-06-16)

- Kontroler pendaftaran sidik jari dan firmware perangkat untuk integrasi multi-pengguna di mesin scanner.

---

### 📅 17 Juni 2026 (2026-06-17)

- Dukungan mode offline pada perangkat scanner ESP8266 dengan antrean data lokal dan sinkronisasi otomatis saat online.
- Ignore esp8266_code dir.
- Implementasi sistem lisensi sekolah, validasi kode lisensi, batas kuota, dan masa tenggang (grace period).
- Implementasi sistem Multi-Tenancy penuh untuk pemisahan data antar sekolah secara aman disertai cascading delete.
- Implement SiswaController with CRUD operations, school-scoped filtering, and Excel import functionality.

---

### 📅 18 Juni 2026 (2026-06-18)

- Implement AppServiceProvider for dynamic branding and HTTPS configuration.
- Halaman manajemen perangkat WhatsApp dengan fitur scan QR Code dan kode pairing autentikasi.

---

### 📅 19 Juni 2026 (2026-06-19)

- Penyediaan skrip otomatisasi build paket rilis client self-hosted (`build_release.php`) dan petunjuk instalasi (`INSTALL.md`).

---

### 📅 23 Juni 2026 (2026-06-23)

- Penyediaan skrip otomatisasi build paket rilis client self-hosted (`build_release.php`) dan petunjuk instalasi (`INSTALL.md`).
- Implementasi sistem lisensi sekolah, validasi kode lisensi, batas kuota, dan masa tenggang (grace period).

---

### 📅 24 Juni 2026 (2026-06-24)

- Add daily report automation with school-specific scheduling and auto-extension of student sick leave status.

---

### 📅 25 Juni 2026 (2026-06-25)

- Implementasi sistem lisensi sekolah, validasi kode lisensi, batas kuota, dan masa tenggang (grace period).
- Relax custom timestamp constraints and pass is_simulator flag.

---

### 📅 26 Juni 2026 (2026-06-26)

- Implementasi sistem lisensi sekolah, validasi kode lisensi, batas kuota, dan masa tenggang (grace period).
- Halaman manajemen perangkat WhatsApp dengan fitur scan QR Code dan kode pairing autentikasi.
- Add settings controller for multi-tenant configuration and update login view with dynamic branding support.
- Integrasi pencadangan database otomatis ke cloud object storage Cloudflare R2.

---

### 📅 27 Juni 2026 (2026-06-27)

- Fitur pengiriman ucapan selamat ulang tahun otomatis kepada siswa dan guru melalui WhatsApp.

---

### 📅 28 Juni 2026 (2026-06-28)

- Implement dynamic school branding and domain-based configuration via AppServiceProvider and remove legacy utility scripts..
- Implementasi sistem lisensi sekolah, validasi kode lisensi, batas kuota, dan masa tenggang (grace period).
- Compile assets for release v2.0.0.
- Implement scheduled artisan commands and update vbs script to show command window.
- Add view for displaying WhatsApp message delivery logs.

---

## 🗓️ Periode Juli 2026

### 📅 13 Juli 2026 (2026-07-13)

- Modul kartu akses gerbang (Gate Cards) untuk autentikasi izin pulang lebih awal bagi siswa oleh guru piket/satpam.
- Use raw sql column comparison in checkout query to prevent laravel binding issues.
- Resolve collation mismatch between gate_cards and teacher_checkout_sessions tables.
- Add comprehensive logging to ApiLog for fingerprint scan exceptions and errors.
- Implement IP blocking for IP addresses with more than 10 auth failures in 24 hours.
- Integrasi saluran notifikasi alternatif via Telegram Bot lengkap dengan pencatatan log pengiriman pada dashboard admin.
- Move telegram settings to dedicated menu with user guide.
- Change endsection to endpush in telegram view.

---

### 📅 14 Juli 2026 (2026-07-14)

- Bypass failed auth logging for non-POST requests to prevent IP blocking.
- Add self-healing mechanism for stuck processing messages.
- Display full API error details under failed status badge.
- Integrasi saluran notifikasi alternatif via Telegram Bot lengkap dengan pencatatan log pengiriman pada dashboard admin.
- Pusat kustomisasi template pesan WhatsApp untuk berbagai jenis notifikasi (masuk, pulang, terlambat, izin/sakit).

---

### 📅 15 Juli 2026 (2026-07-15)

- Add random closings with reply request CTA to improve sender reputation.
- Integrasi saluran notifikasi alternatif via Telegram Bot lengkap dengan pencatatan log pengiriman pada dashboard admin.

---

### 📅 20 Juli 2026 (2026-07-20)

- Penambahan dukungan multi-wali kelas (Wali Kelas 1 & Wali Kelas 2) pada masing-masing kelas.
- Remove temporary autologin route.
- Pencatatan waktu interaksi terakhir (last seen) pada data siswa dan guru untuk manajemen reputasi bot WhatsApp.

---

### 📅 22 Juli 2026 (2026-07-22)

- Implement automated daily attendance processing and reporting for students via CLI commands.
- Pengembangan modul absensi kegiatan khusus / ekstrakurikuler dengan manajemen sesi dan jadwal.
- Penyempurnaan RfidController untuk menangani izin palang gerbang dan absensi kerja guru.

---

### 📅 25 Juli 2026 (2026-07-25)

- Penerapan aturan batas interaksi 72 jam pada antrean pesan WhatsApp untuk keamanan nomor bot sekolah.

---

### 📅 28 Juli 2026 (2026-07-28)

- Pengembangan modul absensi kegiatan khusus / ekstrakurikuler dengan manajemen sesi dan jadwal.
- Optimize RfidController to handle GateCards, Guru, and active Kegiatan auto-attendance.
- Update RfidController.php.

---

## 🗓️ Periode Agustus 2026

### 📅 06 Agustus 2026 (2026-08-06)

- Perintah pengingat interaksi WhatsApp otomatis setiap pukul 07:00 pagi.
- Restrict last_seen reminder window between 48h and 72h max.
- Pengecekan berkala interaksi nomor WhatsApp setiap 2 jam untuk menjaga akun bot tetap aktif.
- Remove last_seen mutation in Laravel and use MessageQueue 24h deduplication for reminders.

---

### 📅 09 Agustus 2026 (2026-08-09)

- Implement WhatsApp messaging service and automated message queuing with last-seen validation and template-based notifications.
- Add WhatsApp log management interface and controller with filtering and cleanup functionality.
- Implement WhatsApp message log viewer and management with search and bulk delete functionality.
- Add WhatsApp message processing command with rate limiting, jitter, and auto-reconnect logic.
- Add artisan commands for daily attendance processing and automated reporting.
- Create class management view with bulk actions and status toggles.

---

### 📅 10 Agustus 2026 (2026-08-10)

- Tambah pilihan hari untuk frekuensi kegiatan harian.
- Install and sync project dependencies in the vendor directory.
- Implement RfidController for teacher attendance and gate access management.

---

### 📅 12 Agustus 2026 (2026-08-12)

- Implement auth sessions, custom error handling, and modern login UI with RFID API controller support.
- Implement API controllers for RFID and fingerprint attendance tracking and gate management.

---

### 📅 14 Agustus 2026 (2026-08-14)

- Add super admin controller and view for managing device OTA firmware uploads.

---

### 📅 22 Agustus 2026 (2026-08-22)

- Tambah field alamat pada data siswa dan integrasi pembayaran qris subscription.
- Sembunyikan dan nonaktifkan fitur QRIS sementara, gunakan transfer manual dan WA.

---

### 📅 24 Agustus 2026 (2026-08-24)

- Integrasi sensor biometrik sidik jari R307 untuk pendaftaran dan pencatatan absensi siswa, guru, dan kartu gerbang.
- Add FingerprintController to manage device enrollment, authentication, and biometric data synchronization.
- Add fingerprint model classes for student, teacher, and gate card entities.
- Create SiswaController to manage student CRUD operations, search filtering, and quota validation..

---

### 📅 25 Agustus 2026 (2026-08-25)

- Implement CRUD and device enrollment controllers for teachers and students.
- Implement guru and siswa management modules with fingerprint and RFID enrollment capabilities.
- Integrasi sensor biometrik sidik jari R307 untuk pendaftaran dan pencatatan absensi siswa, guru, dan kartu gerbang.
- Implementasi sistem absensi dan manajemen shift kerja guru/staf beserta plotting jadwal penugasan shift.
- Antarmuka pemetaan jadwal shift guru dengan modal pengelolaan massal yang mudah.
- Add shift mapping management page to assign teachers to shift schedules.
- Create shift mapping page with teacher assignment management UI.
- Create index view for managing teacher and staff shifts.

---

### 📅 26 Agustus 2026 (2026-08-26)

- Modul kartu akses gerbang (Gate Cards) untuk autentikasi izin pulang lebih awal bagi siswa oleh guru piket/satpam.

---

### 📅 27 Agustus 2026 (2026-08-27)

- Implement teacher management system with fingerprint enrollment and quota-based WhatsApp bot access.

---

### 📅 28 Agustus 2026 (2026-08-28)

- Implement siswa and guru management controllers, subscription tracking, and school-specific validation logic.
- Sistem pembayaran langganan sekolah dengan dukungan kode QRIS otomatis dan transfer manual.

---

### 📅 30 Agustus 2026 (2026-08-30)

- Dukungan branding sekolah dinamis via View Composer dan antarmuka pengaturan sekolah terpadu.
- Integrasi sensor biometrik sidik jari R307 untuk pendaftaran dan pencatatan absensi siswa, guru, dan kartu gerbang.
- Create school configuration page with general settings and branding upload.
- Portal pengajuan izin dan sakit mandiri oleh siswa/wali murid secara online dengan persetujuan pihak sekolah.

---

## 🗓️ Periode September 2026

### 📅 01 September 2026 (2026-09-01)

- API terpadu untuk pendaftaran, sinkronisasi, dan manajemen perangkat scanner sidik jari dan RFID.
- Penerapan format template Spintax dan manajemen antrean pesan cerdas untuk menghindari pemblokiran nomor oleh WhatsApp.

---

### 📅 02 September 2026 (2026-09-02)

- Komponen modal interaktif Alpine.js yang dapat digunakan ulang pada halaman manajemen pengguna, guru, dan siswa.
- Kontroler pengaturan sekolah terpadu untuk upload file, konfigurasi lingkungan, dan identitas sekolah.
- Modul absensi kegiatan sekolah menyeluruh dilengkapi pelaporan otomatis dan integrasi notifikasi.

---

### 📅 03 September 2026 (2026-09-03)

- Sistem siaran pesan pengumuman terpadu dengan integrasi penuh pada sidebar dan menu navigasi dashboard.
- Fitur modal popup informasi pengumuman dan catatan rilis pada dashboard pengguna.
- Rapikan padding header date popup dan layout notification dropdown.
- Perbaiki tampilan checkbox pilihan kelas pada halaman broadcast pesan.
- Perbaiki tampilan line break dan styling preview pesan broadcast.
- Pratinjau visual pesan broadcast dengan dukungan format teks tebal (*bold*), miring (_italic_), dan coret (~strike~) khas WhatsApp.

---

### 📅 06 September 2026 (2026-09-06)

- Implement Guru, Kelas, and Siswa Eloquent models with relationship definitions.
- Penyempurnaan pesan error validasi nomor WhatsApp pada formulir data siswa.
- Penambahan variabel `{alamat}` pada kustomisasi template pesan WhatsApp.

---

### 📅 07 September 2026 (2026-09-07)

- Implement siswa data management with index view and controller logic.

---

### 📅 09 September 2026 (2026-09-09)

- Implement real-time attendance monitoring dashboard and supporting API infrastructure.
- Alur otomatis perpanjangan lisensi sekolah dengan faktur tagihan QRIS dan pemberitahuan via WhatsApp.
- Add SubscriptionNotificationService to handle automated WhatsApp renewal invoices and create corresponding blade view for public invoice display.
- Add invoice view page with QRIS payment details.
- Add new invoice detail view page for subscription payments.
- Penyediaan RESTful API dan autentikasi mobile (Laravel Sanctum) untuk aplikasi Android/iOS guru dan siswa.

---

### 📅 10 September 2026 (2026-09-10)

- Add automated daily attendance processing commands and notification services for Telegram and WhatsApp.
- Otomasi penandaan status Bolos dan Alpha bagi siswa yang tidak hadir dengan pengiriman notifikasi terpadu.
- Penyediaan RESTful API dan autentikasi mobile (Laravel Sanctum) untuk aplikasi Android/iOS guru dan siswa.
- Implement multi-field auth support for students and restrict web access to student accounts.
- Add user_id column to siswa table migration.
- Implement WhatsApp and FCM notification services for student attendance and registration tracking.
- Integrasi layanan push notification Firebase Cloud Messaging (FCM HTTP v1) ke perangkat smartphone.
- Update fcm_token column type to text in users and siswa tables.
- Add FCM diagnostic and test command for troubleshooting notifications.
- Implement FCM and WhatsApp notification services along with fingerprint API controllers and automated scheduled tasks.
- Implement school settings configuration page with tabbed interface.
- Implement MobileAttendanceController for authentication and document attendance error response schemas.
- Integrasi sensor biometrik sidik jari R307 untuk pendaftaran dan pencatatan absensi siswa, guru, dan kartu gerbang.
- Implement daily attendance monitoring interface and controller logic.

---

### 📅 14 September 2026 (2026-09-14)

- Pencatatan riwayat interaksi API perangkat (API Logs) dan simulator tap RFID untuk pengujian sistem.
- Define readableStatus variable in WhatsAppService sendCheckIn.
- Penyederhanaan laporan harian dengan menyembunyikan bagian guru jika tidak ada jadwal kehadiran guru.
- Model data dan kontroler manajemen siswa, perangkat sekolah, serta integrasi biometrik.

---

### 📅 15 September 2026 (2026-09-15)

- Statistik dan riwayat status pengiriman notifikasi WhatsApp dan Telegram.

---

### 📅 17 September 2026 (2026-09-17)

- Manajemen pembaruan firmware perangkat scanner ESP8266 secara nirkabel (Over-the-Air / OTA).
- Add Guru, Kelas, and Siswa controllers and views with management and enrollment features.
- Add index views for guru and siswa management.
- Add SiswaController and student index view.

---

### 📅 20 September 2026 (2026-09-20)

- Add ClearDummyAttendanceSeeder and make DummyAttendanceSeeder manual.

---

## 🗓️ Periode Oktober 2026

### 📅 01 Oktober 2026 (2026-10-01)

- Modul Super-Admin untuk pemantauan seluruh sekolah, pelacakan masa aktif lisensi, dan manajemen langganan.
- Fitur backup basis data mandiri dan konfigurasi deployment client self-hosted.

---

### 📅 02 Oktober 2026 (2026-10-02)

- Pembaruan kontroler dan tampilan rekapitulasi kehadiran untuk kelas, guru, dan laporan umum.
- Penerapan backup database otomatis ke Cloudflare R2 khusus client self-hosted pada direktori `Client/{nama-klien}_{tgl-backup}.sql` dengan penghapusan otomatis file backup lokal dan cloud yang berusia lebih dari 2 hari.
- Penambahan fitur pengurutan data (Sorting ASC dan DESC) dengan ikon panah interaktif di setiap kolom pada halaman Rekap Absensi Siswa dan Rekap Absensi Kelas.
- Restrukturisasi halaman Rekap Absensi Guru menjadi format rekapitulasi ringkasan per guru (Nama, Hadir, Terlambat, Tidak Hadir, Izin, Sakit, Alpha, % Hadir) dilengkapi fitur Sorting interaktif serta penyesuaian ekspor file Excel dan cetak PDF.

---
