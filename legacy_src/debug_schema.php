<?php
require 'includes/db.php';
try {
    $stmt = $pdo->query("DESCRIBE siswa");
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
