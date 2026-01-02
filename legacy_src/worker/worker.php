<?php
// worker.php
// Script ini berjalan di background untuk mengirim pesan WhatsApp dari antrian

// Pastikan hanya dijalankan via CLI
if (php_sapi_name() !== 'cli') {
    die("Access denied. CLI only.");
}

require_once __DIR__ . '/../includes/db.php';

// Konfigurasi dari rfid.php
// Sebaiknya dipindah ke file config terpisah, tapi untuk sekarang kita samakan
define('WA_API_URL', 'http://192.168.100.67:3000/send/message');
define('WA_API_USER', 'admin');
define('WA_API_PASS', '04112000');

echo "[Worker] Starting WhatsApp Queue Worker...\n";
echo "[Worker] Press Ctrl+C to stop.\n";

// Set timezone (penting untuk scheduler)
date_default_timezone_set('Asia/Jakarta');

// Loop selamanya
while (true) {
    try {
        // --- 1. SCHEDULER (Check every loop) ---
        $currentHour = date('H:i');
        if ($currentHour === '08:30' || $currentHour === '13:00') {
             checkAndRunScheduler($pdo);
        }

        // --- 2. MESSAGE QUEUE PROCESSOR ---
        
        // Cek koneksi DB. Jika putus, reconnect (PDO handle otomatis, tapi di loop panjang perlu hati-hati)
        // Kita gunakan try-catch di dalam loop agar worker tidak mati jika DB glitch

        // 1. Ambil pesan pending (LIMIT 5 per batch)
        // Kita lock row agar tidak diambil worker lain (jika ada multiple worker)
        // Note: MySQL transaction required for FOR UPDATE to be effective usually, 
        // tapi untuk simple worker single instance, ini cukup aman.
        
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            SELECT * FROM message_queue 
            WHERE status = 'pending' 
            ORDER BY created_at ASC 
            LIMIT 5 
            FOR UPDATE
        ");
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($messages)) {
            $pdo->commit();
            // Tidak ada pesan, tidur sebentar
            sleep(2); 
            continue;
        }

        // Tandai processing
        $ids = array_column($messages, 'id');
        $inQuery = implode(',', array_fill(0, count($ids), '?'));
        $updateStmt = $pdo->prepare("UPDATE message_queue SET status = 'processing' WHERE id IN ($inQuery)");
        $updateStmt->execute($ids);
        
        $pdo->commit();

        echo "[Worker] Found " . count($messages) . " messages. Processing...\n";

        // 2. Proses pengiriman
        foreach ($messages as $msg) {
            $success = processMessage($msg);
            
            // Update status final
            $status = $success ? 'sent' : 'failed';
            $error = $success ? null : 'API Error'; // Bisa diperdetail jika fungsi return error msg
            
            // Update individual row
            // (Note: kita buat koneksi baru/query baru per update untuk simplicity)
            $stmt = $pdo->prepare("
                UPDATE message_queue 
                SET status = ?, last_error = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$status, $error, $msg['id']]);
            
            echo "[Worker] Message ID {$msg['id']} -> {$status}\n";
        }

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "[Worker] DB Error: " . $e->getMessage() . "\n";
        sleep(5); // Tunggu sebelum retry jika DB error
    } catch (Exception $e) {
        echo "[Worker] Error: " . $e->getMessage() . "\n";
        sleep(5);
    }
}

/**
 * Fungsi kirim WA (Copy dari rfid.php dengan sedikit modifikasi)
 */
function processMessage($msg) {
    $phone = $msg['phone_number'];
    $message = $msg['message'];
    
    // Kirim REQUEST ke API
    $data = [
        'phone' => $phone,
        'message' => $message
    ];
    
    $ch = curl_init(WA_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_USERPWD => WA_API_USER . ':' . WA_API_PASS,
        CURLOPT_TIMEOUT => 20, // Timeout lebih santai karena background
        CURLOPT_CONNECTTIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if (isset($result['code']) && $result['code'] === 'SUCCESS') {
            return true;
        } else {
            // Log api error content
            return false;
        }
    } else {
        return false;
    }
}

/**
 * Scheduler Checker
 */
function checkAndRunScheduler($pdo) {
    echo "[Scheduler] Checking daily report...\n";
    
    // Cek apakah sudah run hari ini?
    $today = date('Y-m-d');
    
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'last_daily_report_date'");
    $stmt->execute();
    $lastRun = $stmt->fetchColumn();
    
    if ($lastRun !== $today) {
        echo "[Scheduler] Running Daily Report for $today...\n";
        
        // Ambil Target JID
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'report_target_jid'");
        $stmt->execute();
        $targetJid = $stmt->fetchColumn();
        
        if ($targetJid) {
            // Run script (Assuming relative path is correct)
            // Windows/Linux compatible path resolution
            $scriptPath = realpath(__DIR__ . '/../cron/daily_report.php');
            $phpBinary = PHP_BINARY; // Get current PHP executable
            $cmd = "\"$phpBinary\" \"$scriptPath\" \"$targetJid\"";
            
            echo "[Scheduler] Executing: $cmd\n";
            $output = shell_exec($cmd);
            echo "[Scheduler] Output: $output\n";
            
            // Mark as done
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('last_daily_report_date', ?) ON DUPLICATE KEY UPDATE setting_value = ?")
                ->execute([$today, $today]);
                
            echo "[Scheduler] Done.\n";
        } else {
            echo "[Scheduler] Skipped: No target JID configured.\n";
        }
    } else {
        // Sudah jalan hari ini, skip
        // echo "[Scheduler] Already run for today.\n";
    }

    // --- 2. AUTO BOLOS (13:00) ---
    if (date('H:i') === '13:00') {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'last_auto_bolos_date'");
        $stmt->execute();
        $lastRunBolos = $stmt->fetchColumn();

        if ($lastRunBolos !== $today) {
            echo "[Scheduler] Running Auto Bolos for $today...\n";
            
            $scriptPath = realpath(__DIR__ . '/../cron/auto_bolos.php');
            $phpBinary = PHP_BINARY;
            $cmd = "\"$phpBinary\" \"$scriptPath\"";
            
            echo "[Scheduler] Executing: $cmd\n";
            $output = shell_exec($cmd);
            echo "[Scheduler] Output: $output\n";
            
            // Mark as done
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('last_auto_bolos_date', ?) ON DUPLICATE KEY UPDATE setting_value = ?")
                ->execute([$today, $today]);
                
            echo "[Scheduler] Auto Bolos Done.\n";
        }
    }
}
