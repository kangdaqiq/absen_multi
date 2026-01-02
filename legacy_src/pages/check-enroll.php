<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/function.php';

require_login('../login.php');
header('Content-Type: application/json');

$id = (int)($_GET['siswa_id'] ?? 0);
if ($id === 0) {
    echo json_encode(['ok'=>false]);
    exit;
}

$stmt = $pdo->prepare("
  SELECT enroll_status, uid_rfid
  FROM siswa
  WHERE id = :id
");
$stmt->execute([':id' => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['ok'=>false]);
    exit;
}

echo json_encode([
    'ok' => true,
    'status' => $row['enroll_status'],
    'uid' => $row['uid_rfid']
]);
