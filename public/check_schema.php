<?php
$host = '127.0.0.1';
$db   = 'absen';
$user = 'root';
$pass = ''; 
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    $tables = ['guru', 'absensi_guru'];
    
    foreach ($tables as $table) {
        echo "Table: $table\n";
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM $table");
            $cols = $stmt->fetchAll();
            foreach ($cols as $col) {
                echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
            }
        } catch (PDOException $e) {
            echo "Error checking table $table: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

} catch (\PDOException $e) {
    echo "Connection Failed: " . $e->getMessage();
}
