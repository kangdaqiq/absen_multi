<?php
/**
 * Migration Script: Add Teacher RFID Support
 * Run this file once to add uid_rfid column to guru table
 * and create teacher_checkout_sessions table
 */

require_once __DIR__ . '/../includes/db.php';

echo "Starting migration...\n\n";

try {
    // 1. Add uid_rfid column to guru table
    echo "Adding uid_rfid column to guru table...\n";
    $pdo->exec("
        ALTER TABLE `guru` 
        ADD COLUMN `uid_rfid` VARCHAR(20) DEFAULT NULL AFTER `id_finger`
    ");
    echo "✓ Column added successfully\n\n";
    
    // 2. Add unique index
    echo "Adding unique index for uid_rfid...\n";
    $pdo->exec("
        ALTER TABLE `guru`
        ADD UNIQUE KEY `uk_guru_uid` (`uid_rfid`)
    ");
    echo "✓ Index added successfully\n\n";
    
    // 3. Create teacher_checkout_sessions table
    echo "Creating teacher_checkout_sessions table...\n";
    $pdo->exec("
        CREATE TABLE `teacher_checkout_sessions` (
          `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          `teacher_id` INT(10) UNSIGNED NOT NULL,
          `teacher_name` VARCHAR(255) NOT NULL,
          `uid_rfid` VARCHAR(20) NOT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `expires_at` TIMESTAMP NOT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_expires` (`expires_at`),
          KEY `idx_teacher` (`teacher_id`),
          FOREIGN KEY (`teacher_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "✓ Table created successfully\n\n";
    
    echo "=================================\n";
    echo "Migration completed successfully!\n";
    echo "=================================\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    
    // Check if error is because column/table already exists
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "\nNote: Column 'uid_rfid' already exists. Skipping...\n";
    } elseif (strpos($e->getMessage(), 'Table') !== false && strpos($e->getMessage(), 'already exists') !== false) {
        echo "\nNote: Table 'teacher_checkout_sessions' already exists. Skipping...\n";
    } else {
        echo "\nMigration failed. Please check the error above.\n";
        exit(1);
    }
}
