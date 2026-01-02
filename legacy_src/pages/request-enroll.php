<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/function.php';

require_login('../login.php');
header('Content-Type: application/json');

$id = (int)($_POST['siswa_id'] ?? 0);
if ($id === 0) {
    echo json_encode(['ok'=>false, 'message'=>'ID siswa tidak valid']);
    exit;
}

/* pastikan hanya 1 request aktif */
$pdo->exec("UPDATE siswa SET enroll_status = NULL");

/* set request enroll */
$stmt = $pdo->prepare("
    UPDATE siswa 
    SET enroll_status = 'requested'
    WHERE id = :id
");
$stmt->execute([':id' => $id]);

echo json_encode([
    'ok' => true,
    'message' => 'Silakan tempelkan kartu RFID'
]);
