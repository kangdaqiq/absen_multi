<?php
require 'includes/db.php';
try {
    $k = $pdo->query('SELECT api_key FROM api_keys LIMIT 1')->fetchColumn();
    // Cari siswa dengan WA
    $s = $pdo->query('SELECT id, nama, no_wa FROM siswa WHERE no_wa IS NOT NULL LIMIT 1')->fetch();
    
    if ($s) {
        // Set UID dummy
        $testUID = '11223344';
        $pdo->prepare("UPDATE siswa SET uid_rfid = ? WHERE id = ?")->execute([$testUID, $s['id']]);
        
        echo "API_KEY=" . $k . "\n";
        echo "UID=" . $testUID . "\n"; 
    } else {
        echo "NO_USER_FOUND\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
