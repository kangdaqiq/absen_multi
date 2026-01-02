# QUICK START GUIDE - Teacher Authorization System

## Step 1: Run Database Migration

**Open phpMyAdmin** → Select `absen` database → Click **SQL** tab → Paste this:

```sql
-- Add uid_rfid column to guru table
ALTER TABLE `guru` 
ADD COLUMN `uid_rfid` VARCHAR(20) DEFAULT NULL AFTER `id_finger`,
ADD UNIQUE KEY `uk_guru_uid` (`uid_rfid`);

-- Create teacher checkout sessions table
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

Click **Go** to execute.

---

## Step 2: Register Teacher RFID Cards

For each teacher, update their UID in the database:

```sql
-- Example: Register Pak Budi's card
UPDATE guru SET uid_rfid = 'A1B2C3D4' WHERE id = 2;

-- Check all teachers
SELECT id, nama, uid_rfid FROM guru;
```

**To get the UID**: Have the teacher tap their card on the RFID reader. The UID will show in the error message if not registered.

---

## Step 3: Test the System

### Test 1: Teacher Authorization
1. Teacher taps RFID card
2. Should see: **"Guru authorized. Siswa dapat absen pulang."**
3. Check database: `SELECT * FROM teacher_checkout_sessions;`

### Test 2: Student Checkout (Success)
1. Teacher taps card (creates 30-min session)
2. Student taps card
3. Should see: **"Absen pulang berhasil"**

### Test 3: Student Checkout (Blocked)
1. NO teacher tap (or wait 31 minutes after teacher tap)
2. Student taps card
3. Should see: **"Belum ada izin guru. Minta guru tap kartu."**

---

## How It Works

**OLD SYSTEM**: Students could checkout after jam_pulang time
**NEW SYSTEM**: Students can ONLY checkout if a teacher tapped their card in the last 30 minutes

**Flow**:
```
Teacher taps → 30-min authorization window opens → Students can checkout → Window expires
```

---

## Troubleshooting

**Problem**: "Kartu belum terdaftar" for teacher
**Solution**: Register teacher's UID in database (see Step 2)

**Problem**: Student still can't checkout after teacher tap
**Solution**: Check `teacher_checkout_sessions` table for active sessions:
```sql
SELECT * FROM teacher_checkout_sessions WHERE expires_at > NOW();
```

**Problem**: Migration fails with "Duplicate column"
**Solution**: Column already exists, skip to Step 2

---

## Quick Database Queries

```sql
-- View all teacher cards
SELECT id, nama, uid_rfid FROM guru;

-- View active authorization sessions
SELECT * FROM teacher_checkout_sessions WHERE expires_at > NOW();

-- Clear all sessions (for testing)
DELETE FROM teacher_checkout_sessions;

-- Clear expired sessions
DELETE FROM teacher_checkout_sessions WHERE expires_at < NOW();
```
