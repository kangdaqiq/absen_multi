<?php
// public/force_add_columns.php

// DB Credentials matching .env usually, but hardcoded for local XAMPP defaults if checking .env is hard.
// User path says c:\xampp\htdocs\absen.
// I'll try to load .env or just use 'root' and matching DB name from previous context.
// DB Name is usually 'absen' or similar. I'll guess 'absen' based on path 'c:\xampp\htdocs\absen'.
// If password is set, I might fail. Default XAMPP is empty.

$host = '127.0.0.1';
$db   = 'absen';
$user = 'root';
$pass = ''; // Default XAMPP
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Connected to DB '$db'.\n";
    
    // 1. Add created_at
    try {
        $pdo->exec("ALTER TABLE guru ADD COLUMN created_at TIMESTAMP NULL DEFAULT NULL");
        echo "SUCCESS: Added created_at.\n";
    } catch (PDOException $e) {
        if ($e->getCode() == '42S21') { // Duplicate column
            echo "INFO: created_at already exists.\n";
        } else {
            echo "ERROR adding created_at: " . $e->getMessage() . "\n";
        }
    }

    // 2. Add updated_at
    try {
        $pdo->exec("ALTER TABLE guru ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL");
        echo "SUCCESS: Added updated_at.\n";
    } catch (PDOException $e) {
        if ($e->getCode() == '42S21') { 
            echo "INFO: updated_at already exists.\n";
        } else {
            echo "ERROR adding updated_at: " . $e->getMessage() . "\n";
        }
    }
    
    // 3. Verify
    $stmt = $pdo->query("SHOW COLUMNS FROM guru");
    $cols = $stmt->fetchAll();
    echo "\nCurrent Columns:\n";
    foreach ($cols as $col) {
        echo "- " . $col['Field'] . "\n";
    }

} catch (\PDOException $e) {
    echo "Connection Failed: " . $e->getMessage();
}
