<?php
// public/fix_users_timestamps.php
// Script to add missing timestamp columns to users table
// Access via: http://localhost/absen/public/fix_users_timestamps.php

// Database configuration
$host = 'localhost';
$dbname = 'absen';
$username = 'root';
$password = '';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Users Table Timestamps</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
        .box { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>";

echo "<h2>🔧 Adding Timestamp Columns to Users Table</h2>";

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<div class='box'>";
    echo "<p class='success'>✓ Connected to database: <strong>$dbname</strong></p>";
    echo "</div>";
    
    // Check current table structure
    echo "<div class='box'>";
    echo "<h3>Step 1: Checking current table structure...</h3>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    $hasCreatedAt = false;
    $hasUpdatedAt = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'created_at') $hasCreatedAt = true;
        if ($column['Field'] === 'updated_at') $hasUpdatedAt = true;
    }
    
    echo "<p>Has created_at: " . ($hasCreatedAt ? "✓ Yes" : "✗ No") . "</p>";
    echo "<p>Has updated_at: " . ($hasUpdatedAt ? "✓ Yes" : "✗ No") . "</p>";
    echo "</div>";
    
    // Add missing columns
    echo "<div class='box'>";
    echo "<h3>Step 2: Adding missing timestamp columns...</h3>";
    
    if (!$hasCreatedAt) {
        $sql = "ALTER TABLE users ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP";
        $pdo->exec($sql);
        echo "<p class='success'>✓ Added created_at column</p>";
    } else {
        echo "<p>created_at already exists, skipping...</p>";
    }
    
    if (!$hasUpdatedAt) {
        $sql = "ALTER TABLE users ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        $pdo->exec($sql);
        echo "<p class='success'>✓ Added updated_at column</p>";
    } else {
        echo "<p>updated_at already exists, skipping...</p>";
    }
    echo "</div>";
    
    // Show final table structure
    echo "<div class='box'>";
    echo "<h3>Step 3: Final table structure</h3>";
    echo "<pre>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    echo str_pad("Field", 20) . str_pad("Type", 20) . str_pad("Null", 8) . str_pad("Key", 8) . "Extra\n";
    echo str_repeat("-", 80) . "\n";
    
    foreach ($columns as $column) {
        echo str_pad($column['Field'], 20) . 
             str_pad($column['Type'], 20) . 
             str_pad($column['Null'], 8) . 
             str_pad($column['Key'], 8) . 
             $column['Extra'] . "\n";
    }
    echo "</pre>";
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h2 class='success'>✅ Migration Completed Successfully!</h2>";
    echo "<p><a href='/absen/public/siswa' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Go to Data Siswa →</a></p>";
    echo "<p><a href='/absen/public/' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>← Back to Home</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='box'>";
    echo "<h2 class='error'>❌ Database Error</h2>";
    echo "<p><strong>Error Message:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<p><strong>Error Code:</strong> " . $e->getCode() . "</p>";
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h3>Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Make sure XAMPP MySQL is running</li>";
    echo "<li>Check database name is 'absen'</li>";
    echo "<li>Check MySQL username is 'root' with empty password</li>";
    echo "<li>If credentials are different, edit this file and update lines 6-9</li>";
    echo "</ul>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='box'>";
    echo "<h2 class='error'>❌ Error</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div>";
}

echo "</body></html>";
