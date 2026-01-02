<?php
// debug_queue.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/plain');

echo "1. Testing DB Connection...\n";
if ($pdo) {
    echo "   [OK] Connected to DB.\n";
} else {
    echo "   [FAIL] No connection.\n";
    exit;
}

echo "\n2. Testing Table Existence (message_queue)...\n";
try {
    $stmt = $pdo->query("DESCRIBE message_queue");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   [OK] Table exists. Columns: " . implode(", ", $cols) . "\n";
} catch (PDOException $e) {
    echo "   [FAIL] Table error: " . $e->getMessage() . "\n";
    exit;
}

echo "\n3. Testing Helper Function...\n";
function formatWhatsAppNumber_Debug($noWa) {
    echo "   Input: '$noWa'\n";
    if (empty($noWa)) return null;
    $noWa = preg_replace('/[^0-9]/', '', $noWa);
    echo "   Cleaned: '$noWa'\n";
    
    if (empty($noWa)) return null;
    
    if (substr($noWa, 0, 1) === '0') {
        $noWa = '62' . substr($noWa, 1);
    } elseif (substr($noWa, 0, 2) !== '62') {
        $noWa = '62' . $noWa;
    }
    echo "   Formatted: '$noWa'\n";
    
    $length = strlen($noWa);
    if ($length < 10 || $length > 15) {
        echo "   [INVALID] Length $length\n";
        return null;
    }
    return $noWa . '@s.whatsapp.net';
}

$testPhone = "08123456789"; // Example valid phone
$res = formatWhatsAppNumber_Debug($testPhone);
echo "   Result: " . ($res ? $res : "NULL") . "\n";

echo "\n4. Testing Insert Queue...\n";
try {
    $stmt = $pdo->prepare("
        INSERT INTO message_queue (phone_number, message, status, created_at)
        VALUES (?, ?, 'pending', NOW())
    ");
    $formatted = $res;
    $msg = "Debug Test Message " . date('H:i:s');
    
    if ($stmt->execute([$formatted, $msg])) {
        echo "   [OK] Insert Success. ID: " . $pdo->lastInsertId() . "\n";
    } else {
        echo "   [FAIL] Insert Failed.\n";
        print_r($stmt->errorInfo());
    }
} catch (PDOException $e) {
    echo "   [FAIL] Insert Exception: " . $e->getMessage() . "\n";
}
