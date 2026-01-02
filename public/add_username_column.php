<?php
// public/add_username_column.php
// Script to add username column to users table using native MySQL PDO
// Access via: http://localhost/absen/public/add_username_column.php

// Database configuration - adjust if needed
$host = 'localhost';
$dbname = 'absen';
$username = 'root';
$password = '';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Add Username Column Migration</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
        .box { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>";

echo "<h2>🔧 Adding Username Column to Users Table</h2>";

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
    
    // Check if column already exists
    echo "<div class='box'>";
    echo "<h3>Step 1: Checking if username column exists...</h3>";
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'username'");
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        echo "<p class='success'>✓ Username column already exists!</p>";
        echo "<pre>";
        print_r($columnExists);
        echo "</pre>";
    } else {
        echo "<p>Column doesn't exist yet. Adding now...</p>";
        
        // Add username column
        $sql = "ALTER TABLE users ADD COLUMN username VARCHAR(255) NULL UNIQUE AFTER full_name";
        $pdo->exec($sql);
        
        echo "<p class='success'>✓ Username column added successfully!</p>";
        echo "<p>SQL executed: <code>$sql</code></p>";
    }
    echo "</div>";
    
    // Show current table structure
    echo "<div class='box'>";
    echo "<h3>Step 2: Current users table structure</h3>";
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
    
    // Show current users count
    echo "<div class='box'>";
    echo "<h3>Step 3: Users table info</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch();
    echo "<p>Total users in database: <strong>{$result['total']}</strong></p>";
    
    if ($result['total'] > 0) {
        echo "<p class='error'>⚠️ Note: Existing users have NULL username. You need to set username for each user via the edit form.</p>";
    }
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h2 class='success'>✅ Migration Completed Successfully!</h2>";
    echo "<p><a href='/absen/public/' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>← Back to Home</a></p>";
    echo "<p><a href='/absen/public/users' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Manage Users →</a></p>";
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
