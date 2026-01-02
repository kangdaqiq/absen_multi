<?php
require 'includes/db.php';
try {
    $k = $pdo->query('SELECT api_key FROM api_keys LIMIT 1')->fetchColumn();
    $s = $pdo->query('SELECT uid_rfid, nama, no_wa FROM siswa WHERE no_wa IS NOT NULL AND uid_rfid IS NOT NULL LIMIT 1')->fetch();
    
    echo "API_KEY=" . ($k ? $k : 'NOT_FOUND') . "\n";
    if ($s) {
        echo "UID=" . $s['uid_rfid'] . "\n";
        echo "NAMA=" . $s['nama'] . "\n";
        echo "WA=" . $s['no_wa'] . "\n";
    } else {
        echo "UID=NOT_FOUND\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
