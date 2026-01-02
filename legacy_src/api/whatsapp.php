<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

// -------------------------------------------------------------
// KONFIGURASI JADWAL (Default fallback jika DB jadwal kosong)
// -------------------------------------------------------------
// Logic jadwal akan diambil dari tabel jadwal (index_hari)
date_default_timezone_set('Asia/Jakarta');

/* 
  INPUT DARI REMOTE BOT
  Method: POST
  Params: 
    - number (ex: 628123456789)
    - message (ex: "1", "halo")
*/

$sender = $_POST['number'] ?? '';
$msg    = trim($_POST['message'] ?? '');

if (empty($sender)) {
    echo json_encode(['status' => 'error', 'message' => 'No number provided']);
    exit;
}

// 1. NORMALIZE NUMBER
// Pastikan format sesuai database (misal di DB 08..., di WA 628...)
// Kita coba cari parsial atau convert 62 -> 0
function normalize_wa($num) {
    $num = preg_replace('/[^0-9]/', '', $num);
    // Jika user simpan 08..., tapi bot kirim 628...
    if (substr($num, 0, 2) == '62') {
        return '0' . substr($num, 2);
    }
    return $num;
}
$db_number = normalize_wa($sender);

// 2. IDENTIFIKASI USER (SISWA / GURU)
$user_type = null;
$user_data = null;

// Cek Siswa
$stmt = $pdo->prepare("SELECT * FROM siswa WHERE no_wa LIKE ? LIMIT 1");
$stmt->execute([$db_number]);
$siswa = $stmt->fetch(PDO::FETCH_ASSOC);

if ($siswa) {
    $user_type = 'siswa';
    $user_data = $siswa;
} else {
    // Cek Guru
    $stmt = $pdo->prepare("SELECT * FROM guru WHERE no_wa LIKE ? LIMIT 1");
    $stmt->execute([$db_number]);
    $guru = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($guru) {
        $user_type = 'guru';
        $user_data = $guru;
    }
}

// 3. FILTER: JIKA TIDAK TERDAFTAR -> DIAM (SILENT)
if (!$user_type) {
    // Return flag 'ignore' agar bot tidak membalas apa-apa
    echo json_encode(['reply' => false, 'reason' => 'unregistered']);
    exit;
}

// 4. PROSES PESAN (MENU)
$reply = "";
$nama  = $user_data['nama'];
$cmd   = strtolower($msg);

// Helper Jadwal
function get_jadwal_hari_ini($pdo) {
    $n = date('N'); // 1=Senin, 7=Minggu
    $stmt = $pdo->prepare("SELECT * FROM jadwal WHERE index_hari = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$n]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- LOGIC MENU ---

if ($cmd == '1' || strpos($cmd, 'masuk') !== false) {
    // === ABSEN MASUK ===
    $jadwal = get_jadwal_hari_ini($pdo);
    if (!$jadwal) {
        $reply = "📅 Tidak ada jadwal absensi hari ini.";
    } else {
        $now = new DateTime();
        $jam_masuk = new DateTime($jadwal['jam_masuk']);
        $jam_pulang = new DateTime($jadwal['jam_pulang']);
        
        // Cek apakah sudah absen?
        if ($user_type == 'siswa') {
            $stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? AND tanggal = ?");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM attendance_guru WHERE guru_id = ? AND tanggal = ?");
        }
        $stmt->execute([$user_data['id'], date('Y-m-d')]);
        $absen = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($absen) {
            $reply = "❌ Kamu sudah melakukan absen masuk hari ini pukul " . substr($absen['jam_masuk'], 11, 5);
        } else {
            // Cek Toleransi / Status
            // Sederhana: Status H (Hadir) jika < jam_pulang. Logic detail bisa disamakan dengan rfid.php
            // Disini kita anggap Hadir, Keterangan hitung telat.
            
            $status = 'H';
            $keterangan = null;
            
            // Hitung telat
            // Toleransi
            $toleransi = (int)($jadwal['toleransi'] ?? 0);
            $batas_telat = (clone $jam_masuk)->modify("+$toleransi minutes");
            
            if ($now > $batas_telat) {
                // Telat
                $diff = $now->diff($jam_masuk);
                $keterangan = "Telat " . $diff->h . " jam " . $diff->i . " menit";
                // Status tetap H atau T? Default sistem H tapi ada note.
            }

            // Insert DB
            try {
                if ($user_type == 'siswa') {
                    $sql = "INSERT INTO attendance (student_id, tanggal, jam_masuk, status, keterangan, source, created_at) VALUES (?, ?, NOW(), ?, ?, 'whatsapp', NOW())";
                    $pdo->prepare($sql)->execute([$user_data['id'], date('Y-m-d'), $status, $keterangan]);
                } else {
                    $sql = "INSERT INTO attendance_guru (guru_id, tanggal, jam_masuk, total_seconds, keterangan, created_at) VALUES (?, ?, NOW(), 0, ?, NOW())";
                    $pdo->prepare($sql)->execute([$user_data['id'], date('Y-m-d'), $keterangan]);
                }
                $reply = "✅ Berhasil Absen Masuk!\n\nNama: $nama\nJam: " . date('H:i') . "\nStatus: " . ($keterangan ? "Telat ($keterangan)" : "Tepat Waktu");
            } catch (Exception $e) {
                $reply = "❌ Gagal menyimpan absen. Error sistem.";
            }
        }
    }

} elseif ($cmd == '2' || strpos($cmd, 'pulang') !== false) {
    // === ABSEN PULANG ===
    if ($user_type == 'siswa') {
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? AND tanggal = ?");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM attendance_guru WHERE guru_id = ? AND tanggal = ?");
    }
    $stmt->execute([$user_data['id'], date('Y-m-d')]);
    $absen = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$absen) {
        $reply = "⚠️ Kamu belum absen masuk hari ini. Silakan absen masuk (ketik 1) terlebih dahulu.";
    } elseif ($absen['jam_pulang']) {
        $reply = "ℹ️ Kamu sudah absen pulang pukul " . substr($absen['jam_pulang'], 11, 5);
    } else {
        // Update pulang
        try {
            if ($user_type == 'siswa') {
                $sql = "UPDATE attendance SET jam_pulang = NOW(), updated_at = NOW() WHERE id = ?";
            } else {
                $sql = "UPDATE attendance_guru SET jam_pulang = NOW(), updated_at = NOW() WHERE id = ?";
            }
            $pdo->prepare($sql)->execute([$absen['id']]);
            $reply = "👋 Hati-hati di jalan!\n\nBerhasil Absen Pulang.\nNama: $nama\nJam: " . date('H:i');
        } catch (Exception $e) {
            $reply = "❌ Gagal update absen pulang.";
        }
    }

} elseif ($cmd == '3' || strpos($cmd, 'status') !== false) {
    // === CEK STATUS ===
    if ($user_type == 'siswa') {
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? AND tanggal = ?");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM attendance_guru WHERE guru_id = ? AND tanggal = ?");
    }
    $stmt->execute([$user_data['id'], date('Y-m-d')]);
    $absen = $stmt->fetch(PDO::FETCH_ASSOC);

    $reply = "📊 *Status Absensi Hari Ini*\n\nNama: $nama\nTanggal: " . date('d-m-Y') . "\n";
    if ($absen) {
        $masuk = substr($absen['jam_masuk'], 11, 5);
        $pulang = $absen['jam_pulang'] ? substr($absen['jam_pulang'], 11, 5) : "--:--";
        $ket = $absen['keterangan'] ?? '-';
        $reply .= "----------------\nMasuk: $masuk\nPulang: $pulang\nKeterangan: $ket";
    } else {
        $reply .= "----------------\nStatus: Belum Absen";
    }

} else {
    // === MAIN MENU ===
    $reply = "Halo, *$nama*! 👋\nSelamat datang di Bot Absensi.\n\nSilakan balas dengan angka:\n\n"
           . "1️⃣ *Absen Masuk*\n"
           . "2️⃣ *Absen Pulang*\n"
           . "3️⃣ *Cek Status*\n\n"
           . "_(Pastikan nomor Anda terdaftar)_";
}

// RETURN RESPONSE
echo json_encode(['reply' => $reply]);
?>
