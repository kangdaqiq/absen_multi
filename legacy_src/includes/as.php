<?php
header('Content-Type: application/json');
require __DIR__ . '/../includes/db.php';

/* =========================
   KONFIGURASI
========================= */
date_default_timezone_set('Asia/Jakarta');

// Rate Limiting Config
define('MAX_REQUESTS_PER_MINUTE', 60);
define('SCAN_COOLDOWN_SECONDS', 2);

// WhatsApp API Config
define('WA_API_URL', 'http://localhost:3000/send/message'); // Sesuaikan dengan URL API Anda
define('WA_API_USER', 'username'); // Sesuaikan dengan username Anda
define('WA_API_PASS', 'password'); // Sesuaikan dengan password Anda

/* =========================
   HELPER FUNCTIONS
========================= */

/**
 * Standardized JSON Response
 */
function response($ok, $status, $message, $sound = 'ok', $extra = []) {
    $response = [
        'ok'        => $ok,
        'status'    => $status,
        'message'   => $message,
        'sound'     => $sound,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if (!empty($extra)) {
        $response = array_merge($response, $extra);
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Log API Request
 */
function logRequest($pdo, $apiKey, $action, $uid, $success, $message = '') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO api_logs 
            (api_key, action, uid, success, message, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $apiKey,
            $action,
            $uid,
            $success ? 1 : 0,
            $message,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        // Silent fail untuk logging
        error_log("Log error: " . $e->getMessage());
    }
}

/**
 * Rate Limiting Check
 */
function checkRateLimit($pdo, $apiKey) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM api_logs 
            WHERE api_key = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        ");
        $stmt->execute([$apiKey]);
        $count = $stmt->fetchColumn();
        
        if ($count > MAX_REQUESTS_PER_MINUTE) {
            logRequest($pdo, $apiKey, 'rate_limit', '', false, 'Rate limit exceeded');
            response(false, 'gagal', 'Terlalu banyak request. Tunggu sebentar.', 'error');
        }
    } catch (PDOException $e) {
        error_log("Rate limit check error: " . $e->getMessage());
        // Lanjutkan jika rate limit check gagal
    }
}

/**
 * Scan Cooldown Check (Race Condition Prevention)
 */
function checkScanCooldown($pdo, $uid) {
    try {
        $stmt = $pdo->prepare("
            SELECT created_at
            FROM scan_history
            WHERE uid = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$uid]);
        $lastScan = $stmt->fetchColumn();
        
        if ($lastScan) {
            $diff = time() - strtotime($lastScan);
            if ($diff < SCAN_COOLDOWN_SECONDS) {
                $remaining = SCAN_COOLDOWN_SECONDS - $diff;
                response(false, 'gagal', "Tunggu {$remaining} detik", 'warning', [
                    'type' => 'cooldown',
                    'remaining_seconds' => $remaining
                ]);
            }
        }
        
        // Record scan
        $pdo->prepare("
            INSERT INTO scan_history (uid, created_at) 
            VALUES (?, NOW())
        ")->execute([$uid]);
        
    } catch (PDOException $e) {
        error_log("Scan cooldown error: " . $e->getMessage());
        // Lanjutkan jika cooldown check gagal
    }
}

/**
 * Validate UID Format
 */
function validateUID($uid) {
    // UID harus hex (A-F, 0-9) dengan panjang 8-20 karakter
    if (!preg_match('/^[A-F0-9]{8,20}$/i', $uid)) {
        response(false, 'gagal', 'Format UID tidak valid', 'error', [
            'type' => 'validation_error',
            'field' => 'uid'
        ]);
    }
    return strtoupper($uid);
}

/**
 * Validate & Format WhatsApp Number
 * Convert Indonesian number to WhatsApp format
 */
function formatWhatsAppNumber($noWa) {
    if (empty($noWa)) {
        return null;
    }
    
    // Hapus semua karakter non-digit
    $noWa = preg_replace('/[^0-9]/', '', $noWa);
    
    // Jika nomor kosong setelah dibersihkan
    if (empty($noWa)) {
        return null;
    }
    
    // Konversi format Indonesia ke format internasional
    if (substr($noWa, 0, 1) === '0') {
        // 08xx -> 628xx
        $noWa = '62' . substr($noWa, 1);
    } elseif (substr($noWa, 0, 2) !== '62') {
        // Jika tidak dimulai dengan 62, tambahkan 62
        $noWa = '62' . $noWa;
    }
    
    // Validasi panjang nomor (Indonesia: 10-13 digit setelah 62)
    $length = strlen($noWa);
    if ($length < 10 || $length > 15) {
        error_log("Invalid WA number length: $noWa (length: $length)");
        return null;
    }
    
    // Format akhir untuk API: 628xxx@s.whatsapp.net
    return $noWa . '@s.whatsapp.net';
}

/**
 * Send WhatsApp Notification
 */
function sendWhatsAppNotification($phone, $message) {
    try {
        // Validasi & format nomor
        $formattedPhone = formatWhatsAppNumber($phone);
        if (!$formattedPhone) {
            error_log("WA Notification skipped: Invalid phone number ($phone)");
            return false;
        }
        
        // Prepare data
        $data = [
            'phone' => $formattedPhone,
            'message' => $message
        ];
        
        // Initialize cURL
        $ch = curl_init(WA_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_USERPWD => WA_API_USER . ':' . WA_API_PASS,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5
        ]);
        
        // Execute
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Log hasil
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if (isset($result['code']) && $result['code'] === 'SUCCESS') {
                error_log("WA Notification sent successfully to $formattedPhone");
                return true;
            } else {
                error_log("WA API Error: " . json_encode($result));
                return false;
            }
        } else {
            error_log("WA API HTTP Error ($httpCode): $error - Response: $response");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("WA Notification Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if UID belongs to a teacher
 */
function checkTeacherCard($pdo, $uid) {
    try {
        // Log the exact UID being checked
        error_log("checkTeacherCard - Checking UID: '$uid' (length: " . strlen($uid) . ")");
        
        $stmt = $pdo->prepare("
            SELECT id, nama, uid_rfid FROM guru
            WHERE UPPER(uid_rfid) = UPPER(?)
            LIMIT 1
        ");
        $stmt->execute([$uid]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Detailed debug logging
        if ($result) {
            error_log("checkTeacherCard - FOUND! Teacher: " . $result['nama'] . " (ID: " . $result['id'] . ", UID in DB: " . $result['uid_rfid'] . ")");
        } else {
            error_log("checkTeacherCard - NOT FOUND. Checking all guru UIDs in database...");
            // Show all guru UIDs for debugging
            $allStmt = $pdo->query("SELECT id, nama, uid_rfid FROM guru WHERE uid_rfid IS NOT NULL");
            $allGuru = $allStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($allGuru as $g) {
                error_log("  - Guru ID " . $g['id'] . " (" . $g['nama'] . "): UID = '" . $g['uid_rfid'] . "' (length: " . strlen($g['uid_rfid']) . ")");
            }
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Check teacher card error: " . $e->getMessage());
        return false;
    }
}

/**
 * Create teacher checkout authorization session (30 minutes)
 */
function createTeacherSession($pdo, $teacherId, $teacherName, $uid) {
    try {
        // Clean up expired sessions first
        $pdo->prepare("
            DELETE FROM teacher_checkout_sessions
            WHERE expires_at < NOW()
        ")->execute();
        
        // Create new session (30 minutes)
        $stmt = $pdo->prepare("
            INSERT INTO teacher_checkout_sessions
            (teacher_id, teacher_name, uid_rfid, expires_at)
            VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))
        ");
        $stmt->execute([$teacherId, $teacherName, $uid]);
        return true;
    } catch (PDOException $e) {
        error_log("Create teacher session error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if there's a valid teacher authorization session
 */
function hasValidTeacherSession($pdo) {
    try {
        // Clean up expired sessions
        $pdo->prepare("
            DELETE FROM teacher_checkout_sessions
            WHERE expires_at < NOW()
        ")->execute();
        
        // Check for valid session
        $stmt = $pdo->prepare("
            SELECT teacher_name, created_at
            FROM teacher_checkout_sessions
            WHERE expires_at > NOW()
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Check teacher session error: " . $e->getMessage());
        return false;
    }
}

/* =========================
   AUTH API KEY
========================= */
$API_KEY = trim($_POST['api_key'] ?? '');

if ($API_KEY === '') {
    response(false, 'gagal', 'API key kosong', 'error');
}

try {
    // Cek API key di database
    $stmt = $pdo->prepare("
        SELECT id, name, last_used_at
        FROM api_keys
        WHERE api_key = ?
          AND active = 1
        LIMIT 1
    ");
    $stmt->execute([$API_KEY]);
    $device = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$device) {
        logRequest($pdo, $API_KEY, 'auth_failed', '', false, 'Invalid API key');
        response(false, 'gagal', 'API key tidak valid', 'error');
    }
    
    $deviceId   = $device['id'];
    
    // Update last used
    $pdo->prepare("UPDATE api_keys SET last_used_at = NOW() WHERE id = ?")
        ->execute([$deviceId]);
    
    // Rate Limiting Check
    checkRateLimit($pdo, $API_KEY);
    
} catch (PDOException $e) {
    error_log("Auth error: " . $e->getMessage());
    response(false, 'gagal', 'Terjadi kesalahan sistem1', 'error');
}

/* =========================
   INPUT VALIDATION
========================= */
$uid = trim($_POST['uid'] ?? '');

if ($uid === '') {
    logRequest($pdo, $API_KEY, 'validation_error', '', false, 'UID kosong');
    response(false, 'gagal', 'UID kosong', 'error');
}

// Validate & sanitize UID
$uid = validateUID($uid);

// Check scan cooldown (race condition prevention)
checkScanCooldown($pdo, $uid);

/* =========================
   MODE DETECTION
========================= */
try {
    // Check if this is a teacher card first
    $teacher = checkTeacherCard($pdo, $uid);
    if ($teacher) {
        handleTeacherScan($pdo, $uid, $teacher, $API_KEY);
    }
    
    // Check for enrollment mode
    $stmt = $pdo->query("
        SELECT id FROM siswa
        WHERE enroll_status = 'requested'
        LIMIT 1
    ");
    
    if ($stmt->fetch()) {
        handleEnroll($pdo, $uid, $API_KEY);
    } else {
        handleScan($pdo, $uid, $API_KEY);
    }
} catch (PDOException $e) {
    error_log("Mode detection error: " . $e->getMessage());
    logRequest($pdo, $API_KEY, 'error', $uid, false, $e->getMessage());
    response(false, 'gagal', 'Terjadi kesalahan sistem2', 'error');
}

/* =========================================================
   ENROLL RFID
========================================================= */
function handleEnroll($pdo, $uid, $apiKey) {
    try {
        // 1. Cek UID sudah terdaftar?
        $stmt = $pdo->prepare("
            SELECT nama FROM siswa
            WHERE uid_rfid = ?
            LIMIT 1
        ");
        $stmt->execute([$uid]);
        
        if ($row = $stmt->fetch()) {
            logRequest($pdo, $apiKey, 'enroll_duplicate', $uid, false, 'UID sudah ada');
            response(false, 'gagal', 'UID sudah ada', 'warning', [
                'type' => 'enroll_duplicate',
                'nama' => $row['nama']
            ]);
        }
        
        // 2. Cari siswa yang REQUEST enroll (with lock)
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            SELECT id, nama, no_wa FROM siswa
            WHERE enroll_status = 'requested'
            ORDER BY id DESC
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->execute();
        $siswa = $stmt->fetch();
        
        if (!$siswa) {
            $pdo->rollBack();
            logRequest($pdo, $apiKey, 'enroll_not_found', $uid, false, 'Tidak ada request enroll');
            response(false, 'gagal', 'Tidak ada permintaan enroll', 'warning');
        }
        
        // 3. Update UID & selesaikan enroll
        $stmt = $pdo->prepare("
            UPDATE siswa SET
                uid_rfid = ?,
                enroll_status = 'done'
            WHERE id = ?
        ");
        $stmt->execute([$uid, $siswa['id']]);
        
        $pdo->commit();
        
        // 4. Kirim notifikasi WhatsApp
        if (!empty($siswa['no_wa'])) {
            $waMessage = "✅ *Pendaftaran Kartu RFID Berhasil*\n\n";
            $waMessage .= "Halo *{$siswa['nama']}*,\n\n";
            $waMessage .= "Kartu RFID Anda telah berhasil didaftarkan dalam sistem absensi sekolah.\n\n";
            $waMessage .= "UID Kartu: `{$uid}`\n";
            $waMessage .= "Waktu: " . date('d/m/Y H:i:s') . "\n\n";
            $waMessage .= "Kartu Anda sekarang dapat digunakan untuk absensi.\n\n";
            $waMessage .= "_Terima kasih_";
            
            sendWhatsAppNotification($siswa['no_wa'], $waMessage);
        }
        
        logRequest($pdo, $apiKey, 'enroll_success', $uid, true, 'Enroll berhasil: ' . $siswa['nama']);
        
        response(true, 'success', 'Enroll berhasil', 'ok', [
            'type' => 'enroll_rfid',
            'nama' => $siswa['nama'],
            'uid'  => $uid
        ]);
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Enroll error: " . $e->getMessage());
        logRequest($pdo, $apiKey, 'enroll_error', $uid, false, $e->getMessage());
        response(false, 'gagal', 'Terjadi kesalahan sistem3', 'error');
    }
}

/* =========================================================
   TEACHER CARD SCAN
========================================================= */
function handleTeacherScan($pdo, $uid, $teacher, $apiKey) {
    try {
        $teacherId = $teacher['id'];
        $teacherName = $teacher['nama'];
        
        // Create authorization session
        if (createTeacherSession($pdo, $teacherId, $teacherName, $uid)) {
            logRequest($pdo, $apiKey, 'teacher_auth', $uid, true, 'Teacher authorized: ' . $teacherName);
            
            response(true, 'success', 'Guru authorized. Siswa dapat absen pulang.', 'ok', [
                'type' => 'teacher_authorization',
                'teacher_name' => $teacherName,
                'valid_until' => date('Y-m-d H:i:s', strtotime('+30 minutes'))
            ]);
        } else {
            logRequest($pdo, $apiKey, 'teacher_auth_failed', $uid, false, 'Failed to create session');
            response(false, 'gagal', 'Gagal membuat session authorization', 'error');
        }
    } catch (PDOException $e) {
        error_log("Teacher scan error: " . $e->getMessage());
        logRequest($pdo, $apiKey, 'teacher_error', $uid, false, $e->getMessage());
        response(false, 'gagal', 'Terjadi kesalahan sistem', 'error');
    }
}

/* =========================================================
   ABSENSI RFID
========================================================= */
function handleScan($pdo, $uid, $apiKey) {
    try {
        $today  = date('Y-m-d');
        $now    = new DateTime();
        $nowStr = $now->format('Y-m-d H:i:s');
        
        /* =========================
           CARI SISWA
        ========================= */
        $stmt = $pdo->prepare("
            SELECT id, nama, no_wa
            FROM siswa 
            WHERE uid_rfid = ? 
            LIMIT 1
        ");
        $stmt->execute([$uid]);
        $siswa = $stmt->fetch();
        
        if (!$siswa) {
            logRequest($pdo, $apiKey, 'unknown_card', $uid, false, 'Kartu tidak terdaftar');
            response(false, 'unknown', 'Kartu belum terdaftar', 'error', [
                'type' => 'unknown_card',
                'uid'  => $uid
            ]);
        }
        
        /* =========================
           AMBIL JADWAL
        ========================= */
        $indexHari = date('N');
        
        $stmt = $pdo->prepare("
            SELECT * FROM jadwal
            WHERE index_hari = ? AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$indexHari]);
        $jadwal = $stmt->fetch();
        
        if (!$jadwal) {
            logRequest($pdo, $apiKey, 'no_schedule', $uid, false, 'Tidak ada jadwal');
            response(false, 'gagal', 'Jadwal Kosong', 'warning', [
                'type' => 'no_schedule',
                'nama' => $siswa['nama']
            ]);
        }
        
        /* =========================
           WINDOW WAKTU
        ========================= */
        $toleransi = (int)($jadwal['toleransi'] ?? 0);
        
        $jamMasuk   = new DateTime("$today " . trim($jadwal['jam_masuk']));
        $jamPulang  = new DateTime("$today " . trim($jadwal['jam_pulang']));
        
        $batasTelat        = (clone $jamMasuk)->modify("+{$toleransi} minutes");
        $awalAbsenMasuk  = (clone $jamMasuk)->modify("-1 hour");
        $akhirAbsenMasuk = (clone $jamMasuk)->modify("+2 hour");
        $batasAbsenPulang  = (clone $jamPulang)->modify("+1 hour");
        
        /* =========================
           CEK ATTENDANCE HARI INI
        ========================= */
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            SELECT * FROM attendance
            WHERE student_id = ? AND tanggal = ?
            FOR UPDATE
        ");
        $stmt->execute([$siswa['id'], $today]);
        $att = $stmt->fetch();
        
        /* =====================================================
           CASE 1: SUDAH ABSEN MASUK & PULANG
        ===================================================== */
        if ($att && $att['jam_pulang']) {
            $pdo->rollBack();
            logRequest($pdo, $apiKey, 'already_complete', $uid, true, 'Absen Lengkap');
            
            response(true, 'success', 'Absen Lengkap', 'ok', [
                'type'        => 'sudah_lengkap',
                'nama'        => $siswa['nama'],
                'jam_masuk'   => $att['jam_masuk'],
                'jam_pulang'  => $att['jam_pulang']
            ]);
        }
        
        /* =====================================================
           CASE 2: SUDAH ABSEN MASUK, BELUM PULANG
        ===================================================== */
        if ($att && !$att['jam_pulang']) {

            // Cek sesi otorisasi guru
            $teacherSession = hasValidTeacherSession($pdo);

            // ===============================
            // 1. Masih dalam jam absen masuk
            // ===============================
            if (!$teacherSession && $now >= $awalAbsenMasuk && $now <= $akhirAbsenMasuk) {

                $pdo->rollBack();
                logRequest($pdo, $apiKey, 'sudah_absen_masuk', $uid, true, 'Sudah Absen Masuk');

                response(true, 'success', 'Sudah Absen Masuk', 'ok', [
                    'type'    => 'sudah_absen_masuk',
                    'nama'    => $siswa['nama'],
                    'message' => 'Sudah Absen Masuk'
                ]);
            }

            // ===============================
            // 2. Di luar jam masuk → wajib izin guru
            // ===============================
            if (!$teacherSession) {

                $pdo->rollBack();
                logRequest($pdo, $apiKey, 'no_teacher_auth', $uid, false, 'No teacher authorization');

                response(false, 'gagal', 'Belum ada izin guru. Minta guru tap kartu.', 'warning', [
                    'type'    => 'no_authorization',
                    'nama'    => $siswa['nama'],
                    'message' => 'Guru harus tap kartu terlebih dahulu'
                ]);
            }

            // ===============================
            // 3. Proses absen pulang (AUTHORIZED)
            // ===============================
            $masuk = new DateTime($att['jam_masuk']);
            $totalSeconds = $now->getTimestamp() - $masuk->getTimestamp();

            $stmt = $pdo->prepare("
                UPDATE attendance
                SET jam_pulang = ?, 
                    total_seconds = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$nowStr, $totalSeconds, $att['id']]);

            $pdo->commit();

            $hours   = floor($totalSeconds / 3600);
            $minutes = floor(($totalSeconds % 3600) / 60);

            // Kirim notifikasi WhatsApp untuk absen pulang
            if (!empty($siswa['no_wa'])) {
                $waMessage = "🏠 *Absen Pulang Berhasil*\n\n";
                $waMessage .= "Halo *{$siswa['nama']}*,\n\n";
                $waMessage .= "📍 Jam Masuk: " . date('H:i', strtotime($att['jam_masuk'])) . "\n";
                $waMessage .= "📍 Jam Pulang: " . date('H:i', strtotime($nowStr)) . "\n";
                $waMessage .= "⏱️ Durasi: {$hours} jam {$minutes} menit\n";
                $waMessage .= "👤 Diizinkan oleh: {$teacherSession['teacher_name']}\n\n";
                $waMessage .= "Terima kasih telah mengikuti kegiatan hari ini.\n\n";
                $waMessage .= "_Hati-hati di jalan!_ 🙏";
                
                sendWhatsAppNotification($siswa['no_wa'], $waMessage);
            }

            logRequest(
                $pdo,
                $apiKey,
                'checkout_success',
                $uid,
                true,
                'Absen pulang berhasil (authorized by: ' . $teacherSession['teacher_name'] . ')'
            );

            response(true, 'success', 'Absen pulang berhasil', 'ok', [
                'type'          => 'absen_pulang',
                'nama'          => $siswa['nama'],
                'jam_pulang'    => $nowStr,
                'durasi'        => "{$hours} jam {$minutes} menit",
                'authorized_by' => $teacherSession['teacher_name']
            ]);
        }
        
        /* =====================================================
           CASE 3: BELUM ADA ATTENDANCE → ABSEN MASUK
        ===================================================== */
        if (!$att) {
    
            // Cek window absen masuk
            if ($now < $awalAbsenMasuk) {
                $pdo->rollBack();
                logRequest($pdo, $apiKey, 'too_early', $uid, false, 'Belum dibuka');
                
                response(false, 'gagal', 'Absen Tutup', 'warning', [
                    'type' => 'too_early',
                    'nama' => $siswa['nama'],
                    'jam_buka' => $awalAbsenMasuk->format('H:i')
                ]);
            }
            if ($now > $akhirAbsenMasuk) {
                $pdo->rollBack();
                logRequest($pdo, $apiKey, 'checkin_closed', $uid, false, 'Absen masuk ditutup');

                response(false, 'gagal', 'Absen Tutup', 'warning', [
                    'type' => 'checkin_closed',
                    'nama' => $siswa['nama'],
                    'jam_tutup' => $akhirAbsenMasuk->format('H:i')
                ]);
            }
            // Tentukan status & keterangan
            $status = 'H';
            $keterangan = null;
            
            if ($now > $batasTelat) {
                $diff = $now->getTimestamp() - $awalAbsenMasuk->getTimestamp();
                $jam   = floor($diff / 3600);
                $menit = floor(($diff % 3600) / 60);
                
                $status = 'H';
                $keterangan = "Telat {$jam} jam {$menit} menit";
            }
            
            // Insert attendance
            $stmt = $pdo->prepare("
                INSERT INTO attendance
                (student_id, tanggal, jam_masuk, status, keterangan, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $siswa['id'],
                $today,
                $nowStr,
                $status,
                $keterangan
            ]);
            
            $pdo->commit();
      // Kirim notifikasi WhatsApp untuk absen masuk
            if (!empty($siswa['no_wa'])) {
                $emoji = ($status === 'H' && !$keterangan) ? '✅' : '⚠️';
                $statusText = ($status === 'H' && !$keterangan) ? 'Tepat Waktu' : 'Terlambat';
                
                $waMessage = "{$emoji} *Absen Masuk Berhasil*\n\n";
                $waMessage .= "Halo *{$siswa['nama']}*,\n\n";
                $waMessage .= "📅 Tanggal: " . date('d/m/Y', strtotime($today)) . "\n";
                $waMessage .= "🕐 Jam Masuk: " . date('H:i', strtotime($nowStr)) . "\n";
                $waMessage .= "📊 Status: {$statusText}\n";
                
                if ($keterangan) {
                    $waMessage .= "📝 Keterangan: {$keterangan}\n";
                }
                
                $waMessage .= "\nSelamat belajar! 📚\n\n";
                $waMessage .= "_Jangan lupa absen pulang ya!_";
                
                sendWhatsAppNotification($siswa['no_wa'], $waMessage);
            }
            
            logRequest($pdo, $apiKey, 'checkin_success', $uid, true, "Absen masuk: {$status}");
            
            response(true, 'success', 'Absen masuk berhasil', 'ok', [
                'type'         => 'absen_masuk',
                'nama'         => $siswa['nama'],
                'jam_masuk'    => $nowStr,
                'status_absen' => $status,
                'keterangan'   => $keterangan
            ]);
        }
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Scan error: " . $e->getMessage());
        logRequest($pdo, $apiKey, 'scan_error', $uid, false, $e->getMessage());
        response(false, 'gagal', 'Terjadi kesalahan sistem4', 'error');
    }
}