<?php
// cron/auto_bolos.php
// Script ini dijalankan oleh worker pada jam 13:00
// Fungsinya: Menandai siswa yang sudah absen masuk tapi belum absen pulang sebagai 'B' (Bolos)

if (php_sapi_name() !== 'cli') {
    die("Access denied. CLI only.");
}

require __DIR__ . '/../includes/db.php';

date_default_timezone_set('Asia/Jakarta');
$today = date('Y-m-d');

echo "[AutoBolos] Starting check for date: $today...\n";

try {
    // Cari siswa yang:
    // 1. Punya record attendance hari ini
    // 2. Jam masuk tidak NULL (sudah hadir)
    // 3. Jam pulang NULL (belum pulang)
    // 4. Status awalnya 'H' (Hadir) atau 'T' (Terlambat) -> Asumsi kita ubah jadi B
    //    Kita update semua yang belum pulang, terlepas status awalnya apa (selama bukan Izin/Sakit yang mungkin tidak perlu absen pulang?)
    //    Biasanya 'H' (Hadir) yang perlu diubah.
    
    // UPDATE LOGIC:
    // Set status = 'B'
    // Tambahkan keterangan
    
    $sql = "UPDATE attendance 
            SET status = 'B', 
                keterangan = CONCAT(IFNULL(keterangan, ''), ' [Auto: Tidak Absen Pulang]') 
            WHERE tanggal = ? 
              AND jam_pulang IS NULL 
              AND jam_masuk IS NOT NULL
              AND status NOT IN ('I', 'S') -- Hindari mengubah Izin/Sakit jika mereka kebetulan punya jam masuk (unlikely but safe)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$today]);
    
    $count = $stmt->rowCount();
    
    echo "[AutoBolos] Success. Updated $count records to 'B'.\n";
    
} catch (PDOException $e) {
    echo "[AutoBolos] Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "[AutoBolos] Error: " . $e->getMessage() . "\n";
    exit(1);
}
