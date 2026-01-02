<?php
// enroll-publish.php (final - supports 'siswa' and 'guru')
// Pastikan includes/db.php & includes/function.php tersedia
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/function.php';
require_login('../login.php');

// optional: require role — aktifkan jika hanya role tertentu boleh publish
// if (!in_array(current_user()['role'] ?? '', ['admin','operator'])) {
//    http_response_code(403);
//    echo json_encode(['ok'=>false, 'error'=>'Forbidden']);
//    exit;
//}

require __DIR__ . '/../vendor/autoload.php';
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false, 'error' => 'Method not allowed']);
    exit;
}

// Accept token either from token_siswa or token_guru for compatibility
$token = $_POST['token_siswa'] ?? ($_POST['token_guru'] ?? '');

// CSRF validation: try common function names used in projects
$csrf_ok = false;
if (function_exists('validate_csrf_token')) {
    $csrf_ok = validate_csrf_token($token);
} elseif (function_exists('verify_csrf_token')) {
    $csrf_ok = verify_csrf_token($token);
} elseif (function_exists('generate_csrf_token')) {
    // if only generator exists, we can't verify; fail safe
    $csrf_ok = false;
}

if (!$csrf_ok) {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'error' => 'Invalid CSRF token']);
    exit;
}

// Read and sanitize inputs
$raw_action = $_POST['action'] ?? 'start_enroll';
$action = ($raw_action === 'delete') ? 'delete' : 'start_enroll';

$absen_id = 0;
if (isset($_POST['absen_id']) && $_POST['absen_id'] !== '') $absen_id = intval($_POST['absen_id']);
elseif (isset($_POST['siswa_id']) && $_POST['siswa_id'] !== '') $absen_id = intval($_POST['siswa_id']);

$mode = ($_POST['mode'] ?? 'auto') === 'manual' ? 'manual' : 'auto';
$id_finger = (isset($_POST['id_finger']) && $_POST['id_finger'] !== '') ? intval($_POST['id_finger']) : null;
$type = isset($_POST['type']) ? strtolower(trim($_POST['type'])) : 'siswa';
$type = ($type === 'guru') ? 'guru' : 'siswa';

if ($absen_id <= 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'error' => 'Invalid absen_id']);
    exit;
}

$allowed_actions = ['start_enroll', 'delete'];
if (!in_array($action, $allowed_actions, true)) {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'error' => 'Invalid action']);
    exit;
}

if ($action === 'start_enroll') {
    if ($mode === 'manual' && $id_finger === null) {
        http_response_code(400);
        echo json_encode(['ok'=>false, 'error' => 'ID Finger wajib diisi untuk mode manual']);
        exit;
    }
}

// For delete: if id_finger not provided, try to read from DB (from appropriate table)
if ($action === 'delete' && ($id_finger === null || $id_finger <= 0)) {
    try {
        $table = ($type === 'guru') ? 'guru' : 'siswa';
        $stmt = $pdo->prepare("SELECT id_finger FROM {$table} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $absen_id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($r && $r['id_finger'] !== null && $r['id_finger'] !== '') {
            $id_finger = intval($r['id_finger']);
        }
    } catch (Exception $e) {
        error_log("DB fetch id_finger failed: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['ok'=>false, 'error' => 'Server DB error']);
        exit;
    }
}

if ($action === 'delete' && ($id_finger === null || $id_finger <= 0)) {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'error' => 'Tidak ada id_finger yang dapat dihapus untuk absen_id ini']);
    exit;
}

// Build payload
$payload = [
    'action' => $action,
    'absen_id' => $absen_id,
    'type' => $type,
    'request_by' => current_user()['username'] ?? (current_user()['id'] ?? 'web'),
    'ts' => time(),
];

if ($action === 'start_enroll') {
    $payload['mode'] = $mode;
    if ($mode === 'manual' && $id_finger !== null) $payload['id_finger'] = $id_finger;
} else { // delete
    $payload['id_finger'] = $id_finger;
}

// MQTT publish settings - sesuaikan bila perlu
$broker = '127.0.0.1';
$port   = 1883;
$topic  = 'school/enroll/command';

try {
    $clientId = 'php-publisher-'.bin2hex(random_bytes(4));
    $client = new MqttClient($broker, $port, $clientId, MqttClient::MQTT_3_1);
    $settings = (new ConnectionSettings())->setKeepAliveInterval(60);
    $client->connect($settings, true);

    $client->publish($topic, json_encode($payload), 0);
    $client->disconnect();

    // update DB initial status (on correct table)
    $status = ($action === 'start_enroll') ? 'requested' : 'delete-requested';
    try {
        $table = ($type === 'guru') ? 'guru' : 'siswa';
        $stmt = $pdo->prepare("UPDATE {$table} SET enroll_status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $absen_id]);
    } catch (Exception $e) {
        error_log("DB update failed after publish: " . $e->getMessage());
        // don't fail the whole request just because DB update failed; respond with warning
        echo json_encode(['ok' => true, 'warning' => 'Published but DB update failed', 'payload' => $payload]);
        exit;
    }

    echo json_encode(['ok' => true, 'message' => ($action === 'start_enroll') ? 'Perintah enroll dikirim ke perangkat' : 'Perintah hapus ID dikirim ke perangkat', 'payload' => $payload]);
    exit;

} catch (Exception $e) {
    error_log('MQTT Publish Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Gagal mengirim ke perangkat', 'detail' => $e->getMessage()]);
    exit;
}
