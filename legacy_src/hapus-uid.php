<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/function.php';

require_login('../login.php');
header('Content-Type: application/json');

$id = (int)($_POST['siswa_id'] ?? 0);
if ($id === 0) {
    echo json_encode(['ok'=>false, 'message'=>'ID tidak valid']);
    exit;
}

$stmt = $pdo->prepare("
  UPDATE siswa SET
    uid_rfid = NULL,
    enroll_status = NULL
  WHERE id = :id
");
$stmt->execute([':id' => $id]);

echo json_encode([
  'ok' => true,
  'message' => 'UID RFID berhasil dihapus'
]);
