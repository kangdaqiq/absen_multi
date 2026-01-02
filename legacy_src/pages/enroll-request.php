<?php
// enroll-request.php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php'; // sesuaikan path
require_once __DIR__ . '/../includes/function.php';
require_login('../login.php'); // optional

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'Invalid request']); exit;
}

// CSRF check if you use it
$token = $_POST['token_siswa'] ?? '';
if (function_exists('verify_csrf_token') && !verify_csrf_token($token)) {
    echo json_encode(['success'=>false,'message'=>'CSRF invalid']); exit;
}

$siswa_id = isset($_POST['siswa_id']) ? (int)$_POST['siswa_id'] : 0;
if ($siswa_id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid siswa id']); exit; }

// get nama for informative purpose
$stmt = $pdo->prepare("SELECT nama FROM siswa WHERE id = :id");
$stmt->execute([':id'=>$siswa_id]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);
$nama = $r ? $r['nama'] : '';

// create txn
$txn = bin2hex(random_bytes(10));

// insert pending record
$stmt = $pdo->prepare("INSERT INTO enroll_pending (txn, siswa_id, status) VALUES (:txn, :s, 'pending')");
$stmt->execute([':txn'=>$txn, ':s'=>$siswa_id]);

// publish to MQTT
$server   = '127.0.0.1'; // broker host
$port     = 1883;
$clientId = 'php-web-' . bin2hex(random_bytes(4));
$mqtt = new MqttClient($server, $port, $clientId);
$settings = (new ConnectionSettings)->setUsername(null)->setPassword(null); // set if broker auth
$mqtt->connect($settings, true);

$payload = json_encode(['txn'=>$txn, 'siswa_id'=>$siswa_id, 'nama'=>$nama]);
// publish once (QoS 0)
$mqtt->publish('enroll/request', $payload, 0);

$mqtt->disconnect();

echo json_encode(['success'=>true, 'message'=>'Request sent', 'txn'=>$txn]);
exit;
