# TROUBLESHOOTING: Kartu Guru Tidak Terdeteksi

## Masalah
Ketika guru tap kartu, sistem malah masuk ke `handleScan()` (untuk siswa) bukan `handleTeacherScan()`.

## Penyebab

Ada 2 kemungkinan:

### 1. Migration Belum Dijalankan ❌
Kolom `uid_rfid` belum ada di tabel `guru`.

**Cek dengan query ini:**
```sql
DESCRIBE guru;
```

**Jika tidak ada kolom `uid_rfid`**, jalankan migration:
```sql
ALTER TABLE `guru` 
ADD COLUMN `uid_rfid` VARCHAR(20) DEFAULT NULL AFTER `id_finger`,
ADD UNIQUE KEY `uk_guru_uid` (`uid_rfid`);
```

### 2. UID Guru Belum Diisi ❌
Kolom sudah ada, tapi UID guru masih NULL.

**Cek dengan query ini:**
```sql
SELECT id, nama, uid_rfid FROM guru;
```

**Jika `uid_rfid` kosong/NULL**, isi dengan UID kartu guru:

#### Cara 1: Update Manual (Jika Tahu UID-nya)
```sql
UPDATE guru SET uid_rfid = 'A1B2C3D4E5F6' WHERE id = 2;
```

#### Cara 2: Tap Kartu Dulu untuk Dapat UID
1. Tap kartu guru ke RFID reader
2. Lihat response error: `"Kartu belum terdaftar"` 
3. Cek log atau response, akan ada UID-nya (contoh: `A1B2C3D4`)
4. Copy UID tersebut
5. Update database:
```sql
UPDATE guru SET uid_rfid = 'A1B2C3D4' WHERE nama = 'Nama Guru';
```

## Cara Cek Debug Log

Setelah tap kartu, cek file log PHP (biasanya di `C:\xampp\apache\logs\error.log`):

Cari baris seperti ini:
```
checkTeacherCard - UID: A1B2C3D4, Found: NO
```

- **Found: NO** = UID tidak ada di database guru
- **Found: YES (Pak Budi)** = UID ditemukan, seharusnya masuk handleTeacherScan

## Langkah-Langkah Perbaikan

### Step 1: Pastikan Migration Sudah Jalan
```sql
-- Cek struktur tabel
DESCRIBE guru;

-- Jika uid_rfid tidak ada, tambahkan:
ALTER TABLE `guru` 
ADD COLUMN `uid_rfid` VARCHAR(20) DEFAULT NULL AFTER `id_finger`,
ADD UNIQUE KEY `uk_guru_uid` (`uid_rfid`);

-- Buat tabel sessions juga:
CREATE TABLE `teacher_checkout_sessions` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `teacher_id` INT(10) UNSIGNED NOT NULL,
  `teacher_name` VARCHAR(255) NOT NULL,
  `uid_rfid` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_teacher` (`teacher_id`),
  FOREIGN KEY (`teacher_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Step 2: Daftarkan UID Kartu Guru

**Cara Mudah:**
1. Tap kartu guru
2. Sistem akan bilang "Kartu belum terdaftar"
3. Cek di `api_logs` untuk dapat UID:
```sql
SELECT uid, message FROM api_logs 
WHERE action = 'unknown_card' 
ORDER BY created_at DESC 
LIMIT 5;
```

4. Update guru dengan UID tersebut:
```sql
UPDATE guru SET uid_rfid = 'UID_DARI_LOG' WHERE id = 2;
```

### Step 3: Test Lagi
1. Tap kartu guru
2. Seharusnya muncul: **"Guru authorized. Siswa dapat absen pulang."**
3. Cek database:
```sql
SELECT * FROM teacher_checkout_sessions;
```

## Quick Check Commands

```sql
-- 1. Cek struktur tabel guru
DESCRIBE guru;

-- 2. Cek semua guru dan UID mereka
SELECT id, nama, uid_rfid FROM guru;

-- 3. Cek log tap terakhir
SELECT * FROM api_logs ORDER BY created_at DESC LIMIT 10;

-- 4. Cek session aktif
SELECT * FROM teacher_checkout_sessions WHERE expires_at > NOW();

-- 5. Update UID guru (ganti nilai sesuai kebutuhan)
UPDATE guru SET uid_rfid = 'MASUKKAN_UID_DISINI' WHERE id = 2;
```

## Contoh Flow yang Benar

```
1. Migration dijalankan ✓
2. Kolom uid_rfid ada di tabel guru ✓
3. UID guru sudah diisi (contoh: 'A1B2C3D4') ✓
4. Guru tap kartu dengan UID 'A1B2C3D4'
5. checkTeacherCard() menemukan guru ✓
6. handleTeacherScan() dipanggil ✓
7. Session dibuat ✓
8. Response: "Guru authorized. Siswa dapat absen pulang." ✓
```
