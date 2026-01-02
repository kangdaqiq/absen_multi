<?php
require_once __DIR__ . '/includes/db.php';

try {
    // 1. Create Settings Table
    $sql = "
    CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql);
    echo "Table settings created.\n";

    // 2. Insert/Update Config
    $groupId = '120363421672356407@g.us'; // Append @g.us for standard Group JID
    
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('report_target_jid', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$groupId, $groupId]);
    
    // 3. Insert last_run tracker if not exists
    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('last_daily_report_date', NULL)");
    $stmt->execute();

    echo "Configuration updated. Target Group: $groupId\n";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
