<?php
// esp-enroll-callback.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/function.php';
// If you want to restrict who can call this endpoint, implement an API key check or restrict to local network.

header('Content-Type: application/json; charset=utf-8');

// Read JSON body
$body = file_get_contents('php://input');
if (!$body) {
    echo json_encode(['success'=>false,'message'=>'No body']); exit;
}
$data = json_decode($body, true);
if (!$data) {
    echo json_encode(['success'=>false,'message'=>'Invalid JSON']); exit;
}

$siswa_id = isset($data['siswa_id']) ? (int)$data['siswa_id'] : 0;
$fid = isset($data['fid']) ? (int)$data['fid'] : 0;
$status = isset($data['status']) ? $data['status'] : '';
$message = isset($data['message']) ? $data['message'] : '';

if ($siswa_id <= 0 || $fid <= 0 || !$status) {
    echo json_encode(['success'=>false,'message'=>'Missing fields']); exit;
}

// log status in DB (optional)
try {
    $stmt = $pdo->prepare("INSERT INTO enroll_log (siswa_id, fid, status, message, created_at) VALUES (:s,:f,:st,:msg, NOW())");
    $stmt->execute([':s'=>$siswa_id, ':f'=>$fid, ':st'=>$status, ':msg'=>$message]);
} catch (Exception $e) {
    // ignore logging failure
}

// handle status
if ($status === 'done') {
    // Mark enroll completed
    try {
        $stmt = $pdo->prepare("UPDATE siswa SET id_finger = :fid, enroll_status = 'done' WHERE id = :id");
        $stmt->execute([':fid'=>$fid, ':id'=>$siswa_id]);
        // optionally return extra data or commands for ESP
        echo json_encode(['success'=>true,'message'=>'Saved']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>'DB save failed: '.$e->getMessage()]);
        exit;
    }
} elseif ($status === 'fail') {
    // rollback pending id_finger if you set it before
    try {
        $stmt = $pdo->prepare("UPDATE siswa SET id_finger = NULL, enroll_status = 'error' WHERE id = :id");
        $stmt->execute([':id' => $siswa_id]);
    } catch (Exception $e) { /* ignore */ }
    echo json_encode(['success'=>true,'message'=>'Recorded fail']);
    exit;
} else {
    // progress / action messages
    // you can forward these to UI via WebSocket / push / polling. For now just ack.
    echo json_encode(['success'=>true,'message'=>'OK']);
    exit;
}
