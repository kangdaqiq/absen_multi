<?php
require_once __DIR__ . '/includes/db.php';

try {
    $sql = "ALTER TABLE message_queue MODIFY phone_number VARCHAR(50) NOT NULL";
    $pdo->exec($sql);
    echo "Table message_queue altered successfully (phone_number -> VARCHAR(50)).\n";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
