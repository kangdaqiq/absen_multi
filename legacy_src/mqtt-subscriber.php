<?php
// mqtt-subscriber.php
// PHP MQTT subscriber (ditulis ulang, lengkap)
// Jalankan: php mqtt-subscriber.php
// Pastikan: composer autoload & includes/db.php tersedia (meng-set $pdo)

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/db.php'; // file ini harus mengisi $pdo (PDO instance)

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

date_default_timezone_set('Asia/Jakarta');

$broker = '127.0.0.1';
$port = 1883;
$clientId = 'php-subscriber-' . bin2hex(random_bytes(4));
$reconnectDelay = 5; // detik

// -----------------------------
// Helper functions
// -----------------------------
function logmsg(string $msg): void
{
    echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
}

function safe_json_decode($raw): ?array
{
    if (!is_string($raw)) return null;
    $txt = trim($raw);
    // remove newlines that may break json
    $txt = preg_replace("/[\r\n]+/", '', $txt);
    // tolerate trailing commas
    $txt = preg_replace('/,\s*}/', '}', $txt);
    $txt = preg_replace('/,\s*\]/', ']', $txt);

    $data = json_decode($txt, true);
    if ($data !== null) return $data;

    // try to extract first JSON object
    if (preg_match('/\{.*\}/s', $txt, $m)) {
        $try = $m[0];
        $data = json_decode($try, true);
        if ($data !== null) return $data;
    }

    return null;
}

function normalize_type($t): string
{
    $t = strtolower(trim((string)$t));
    return ($t === 'guru') ? 'guru' : 'siswa';
}


function hasColumn(PDO $pdo, string $table, string $column): bool
{
    // portable check via information_schema where available
    try {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if (in_array($driver, ['mysql', 'mysqli'])) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl AND COLUMN_NAME = :col');
            $db = $pdo->query('select database()')->fetchColumn();
            $stmt->execute([':db' => $db, ':tbl' => $table, ':col' => $column]);
            return (bool)$stmt->fetchColumn();
        }
    } catch (Throwable $t) {
        // fall back to DESCRIBE
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM {$table} LIMIT 1");
        $stmt->execute();
        $cols = array_keys($stmt->fetch(PDO::FETCH_ASSOC) ?? []);
        return in_array($column, $cols, true);
    } catch (Throwable $t) {
        return false;
    }
}

// Normalizer for time formats in jadwal
$normalize_time = function ($raw) {
    if ($raw === null || $raw === '') return null;
    $t = trim($raw);
    $t = str_replace('.', ':', $t);
    if (preg_match('/^\d{1,2}:\d{1,2}$/', $t)) $t .= ':00';
    if (preg_match('/^\d{1,2}:\d{1,2}:\d{1,2}$/', $t)) {
        $parts = explode(':', $t);
        $parts = array_map(function ($p) {
            return str_pad($p, 2, '0', STR_PAD_LEFT);
        }, $parts);
        return implode(':', $parts);
    }
    return null;
};

// -----------------------------
// Main loop (auto-reconnect)
// -----------------------------
while (true) {
    try {
        $client = new MqttClient($broker, $port, $clientId);
        $connectionSettings = (new ConnectionSettings())->setKeepAliveInterval(60);

        logmsg("Connecting to MQTT broker {$broker}:{$port} ...");
        $client->connect($connectionSettings, true);
        logmsg('Connected to broker.');

        // ---------------------------
        // 1) school/enroll/result
        // ---------------------------
        $client->subscribe('school/enroll/result', function ($topic, $message) use ($pdo) {
            $raw = is_string($message) ? $message : (is_object($message) || is_array($message) ? json_encode($message) : strval($message));
            $payload = safe_json_decode($raw);
            if (!$payload) { logmsg("Invalid JSON in result: {$raw}"); return; }

            $absen_id = isset($payload['absen_id']) ? intval($payload['absen_id']) : (isset($payload['siswa_id']) ? intval($payload['siswa_id']) : 0);
            if ($absen_id <= 0) { logmsg('Result missing absen_id/siswa_id: ' . json_encode($payload)); return; }

            $status = isset($payload['status']) ? strtolower(trim($payload['status'])) : null;
            $type = normalize_type($payload['type'] ?? 'siswa');

            // parse id_finger (prefer numeric if can)
            $id_finger_db = null;
            if (array_key_exists('id_finger', $payload) && $payload['id_finger'] !== null && $payload['id_finger'] !== '') {
                if (is_numeric($payload['id_finger'])) {
                    $num = intval($payload['id_finger']);
                    if ($num > 0) $id_finger_db = $num;
                } else {
                    if (preg_match('/([0-9]+)/', (string)$payload['id_finger'], $m)) {
                        $num = intval($m[1]);
                        if ($num > 0) $id_finger_db = $num;
                    }
                }
            }

            $preferred = ($type === 'guru') ? 'guru' : 'siswa';
            $fallback = ($preferred === 'siswa') ? 'guru' : 'siswa';

            try {
                $doUpdate = function (string $table) use ($pdo, $absen_id, $status, $id_finger_db, $payload) {
                    if (in_array($status, ['ok', 'done'], true)) {
                        if ($id_finger_db === null) {
                            $stmt = $pdo->prepare("UPDATE {$table} SET id_finger = NULL, enroll_status = :st WHERE id = :id");
                            $stmt->execute([':st' => 'done', ':id' => $absen_id]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE {$table} SET id_finger = :fid, enroll_status = :st WHERE id = :id");
                            $stmt->execute([':fid' => (string)$id_finger_db, ':st' => 'done', ':id' => $absen_id]);
                        }
                        return $stmt->rowCount();
                    }

                    if (in_array($status, ['deleted', 'delete', 'removed'], true)) {
                        $stmt = $pdo->prepare("UPDATE {$table} SET id_finger = NULL, enroll_status = :st WHERE id = :id");
                        $stmt->execute([':st' => 'deleted', ':id' => $absen_id]);
                        return $stmt->rowCount();
                    }

                    // fallback: save status json (trimmed)
                    $rawstr = json_encode($payload);
                    $stmt = $pdo->prepare("UPDATE {$table} SET enroll_status = :st WHERE id = :id");
                    $stmt->execute([':st' => substr($rawstr, 0, 255), ':id' => $absen_id]);
                    return $stmt->rowCount();
                };

                $affected = $doUpdate($preferred);
                if ($affected > 0) {
                    logmsg("Updated {$preferred} {$absen_id} -> id_finger=" . ($id_finger_db ?? 'NULL') . " (type={$type})");
                } else {
                    $affected2 = $doUpdate($fallback);
                    if ($affected2 > 0) {
                        logmsg("Fallback: Updated {$fallback} {$absen_id} -> id_finger=" . ($id_finger_db ?? 'NULL') . " (orig_type={$type})");
                    } else {
                        logmsg("Result processed but no rows updated for id {$absen_id} (tried {$preferred} & {$fallback}). Payload: " . json_encode($payload));
                    }
                }
            } catch (Exception $e) {
                logmsg('DB update error (result): ' . $e->getMessage() . ' -- payload: ' . json_encode($payload));
            }
        }, 0);

        // ---------------------------
        // 2) school/enroll/status/#
        // ---------------------------
        $client->subscribe('school/enroll/status/#', function ($topic, $message) use ($pdo) {
            $raw = is_string($message) ? $message : (is_object($message) || is_array($message) ? json_encode($message) : strval($message));
            $payload = safe_json_decode($raw);
            if (!$payload) { logmsg("Invalid JSON in status: {$raw}"); return; }

            $parts = explode('/', $topic);
            $topic_id = intval(end($parts));
            $absen_id = isset($payload['absen_id']) ? intval($payload['absen_id']) : $topic_id;
            if ($absen_id <= 0) { logmsg('Status missing absen_id: ' . json_encode($payload)); return; }

            $statusMessage = isset($payload['message']) ? $payload['message'] : (isset($payload['status']) ? $payload['status'] : json_encode($payload));
            $type = normalize_type($payload['type'] ?? 'siswa');

            $preferred = ($type === 'guru') ? 'guru' : 'siswa';
            $fallback = ($preferred === 'siswa') ? 'guru' : 'siswa';

            try {
                $stmt = $pdo->prepare("UPDATE {$preferred} SET enroll_status = :st WHERE id = :id");
                $stmt->execute([':st' => substr($statusMessage, 0, 255), ':id' => $absen_id]);
                if ($stmt->rowCount() > 0) {
                    logmsg("Progress for {$preferred} {$absen_id} (type={$type}): " . $statusMessage);
                } else {
                    $stmt2 = $pdo->prepare("UPDATE {$fallback} SET enroll_status = :st WHERE id = :id");
                    $stmt2->execute([':st' => substr($statusMessage, 0, 255), ':id' => $absen_id]);
                    if ($stmt2->rowCount() > 0) {
                        logmsg("Fallback progress for {$fallback} {$absen_id} (orig_type={$type}): " . $statusMessage);
                    } else {
                        logmsg('Status update received but no rows matched for id ' . $absen_id . ' (tried ' . $preferred . ' & ' . $fallback . '). Payload: ' . json_encode($payload));
                    }
                }
            } catch (Exception $e) {
                logmsg('DB update error (status): ' . $e->getMessage() . ' -- payload: ' . json_encode($payload));
            }
        }, 0);

        // ---------------------------
        // 3) school/absensi/result
        // ---------------------------
        $client->subscribe('school/absensi/result', function ($topic, $message) use ($pdo, $client, $normalize_time) {
            try {
                $raw = is_string($message) ? $message : (is_object($message) || is_array($message) ? json_encode($message) : strval($message));
                logmsg('ABSENSI handler start - raw payload: ' . $raw);

                $payload = safe_json_decode($raw);
                if (!$payload) {
                    logmsg('Invalid JSON payload. Sending error response to device.');
                    $client->publish('school/absensi/response', json_encode(['status' => 'error', 'message' => 'invalid_json', 'raw' => $raw]));
                    return;
                }

                logmsg('Parsed payload: ' . json_encode($payload));

                if (!array_key_exists('id_finger', $payload) || $payload['id_finger'] === '') {
                    logmsg('Missing id_finger in payload. Publishing not_found.');
                    $client->publish('school/absensi/response', json_encode(['status' => 'error', 'message' => 'missing_id_finger', 'payload' => $payload]));
                    return;
                }

                $id_finger_raw = (string)$payload['id_finger'];
                logmsg("id_finger_raw: {$id_finger_raw}");

                $type = normalize_type($payload['type'] ?? 'siswa');
                logmsg('Detected type: ' . $type);

                // timestamp handling
                $use_time = time();
                $device_ts_ms = null;
                if (isset($payload['timestamp']) && is_numeric($payload['timestamp'])) {
                    $ts = (float)$payload['timestamp'];
                    if ($ts > 1600000000000.0) { // ms epoch
                        $use_time = (int)floor($ts / 1000.0);
                        logmsg("Timestamp looks like epoch ms -> use_time={$use_time}");
                    } elseif ($ts > 1000000000.0 && $ts < 1600000000000.0) { // s epoch
                        $use_time = (int)$ts;
                        logmsg("Timestamp looks like epoch s -> use_time={$use_time}");
                    } else {
                        $device_ts_ms = (int)$ts; // device uptime
                        logmsg("Timestamp looks like device uptime ms ({$device_ts_ms}); using server time");
                    }
                } else {
                    logmsg('No numeric timestamp provided; using server time');
                }

                $dt_now = new DateTime('@' . $use_time);
                $dt_now->setTimezone(new DateTimeZone(date_default_timezone_get()));
                $tanggal = $dt_now->format('Y-m-d');
                $datetime_now = $dt_now->format('Y-m-d H:i:s');
                logmsg('Event time (server): ' . $datetime_now);

                // lookup person by id_finger
                if ($type === 'guru') {
                    $sql = 'SELECT id, nama, id_finger FROM guru WHERE id_finger = :fid LIMIT 1';
                } else {
                    $sql = 'SELECT id, nama, nis, kelas, id_finger FROM siswa WHERE id_finger = :fid LIMIT 1';
                }
                logmsg('Executing person lookup SQL: ' . $sql . ' with fid=' . $id_finger_raw);
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':fid' => $id_finger_raw]);
                $person = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$person) {
                    logmsg('No record found for id_finger=' . $id_finger_raw . ' type=' . $type . '. Replying not_found.');
                    $resp = ['id_finger' => $id_finger_raw, 'person_id' => null, 'type' => $type, 'status' => 'not_found', 'nama' => null, 'message' => 'ID finger tidak ditemukan'];
                    $client->publish('school/absensi/response', json_encode($resp));
                    return;
                }

                logmsg('Person found: ' . json_encode($person));
                $person_id = intval($person['id']);
                $nama = $person['nama'] ?? '';
                $kelas = $person['kelas'] ?? null;

                // global jadwal lookup (first active for hari)
                $dayNames = [0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
                $w = (int)$dt_now->format('w');
                $hariNama = $dayNames[$w] ?? $dt_now->format('l');

                logmsg('Looking up global jadwal for hari=' . $hariNama);
                $stmt = $pdo->prepare('SELECT * FROM jadwal WHERE is_active = 1 AND hari = :hari ORDER BY index_hari ASC LIMIT 1');
                $stmt->execute([':hari' => $hariNama]);
                $jadwal = $stmt->fetch(PDO::FETCH_ASSOC);
                logmsg('Jadwal found: ' . json_encode($jadwal));

                // windows
                $MASUK_WINDOW_MIN = 120;
                $PULANG_WINDOW_MIN = 60;
                $MIN_PULANG_MIN = 15;

                $scheduled_masuk_dt = null;
                $scheduled_pulang_dt = null;
                if ($jadwal) {
                    $jm_raw = $normalize_time($jadwal['jam_masuk'] ?? null);
                    $jp_raw = $normalize_time($jadwal['jam_pulang'] ?? null);
                    if ($jm_raw) {
                        $scheduled_masuk_dt = DateTime::createFromFormat('Y-m-d H:i:s', $tanggal . ' ' . $jm_raw);
                        if ($scheduled_masuk_dt) $scheduled_masuk_dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
                    }
                    if ($jp_raw) {
                        $scheduled_pulang_dt = DateTime::createFromFormat('Y-m-d H:i:s', $tanggal . ' ' . $jp_raw);
                        if ($scheduled_pulang_dt) $scheduled_pulang_dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
                    }
                    logmsg('Parsed jadwal: jam_masuk=' . ($jm_raw ?? 'null') . ' jam_pulang=' . ($jp_raw ?? 'null'));
                }

                // evaluate windows
                $is_masuk = false;
                $is_pulang = false;
                $in_any_window = false;

                if ($scheduled_masuk_dt) {
                    $start = clone $scheduled_masuk_dt;
                    $end = clone $scheduled_masuk_dt;
                    $end->modify("+{$MASUK_WINDOW_MIN} minutes");
                    logmsg('Masuk window start=' . $start->format('Y-m-d H:i:s') . ' end=' . $end->format('Y-m-d H:i:s'));
                    if ($dt_now >= $start && $dt_now <= $end) { $is_masuk = true; $in_any_window = true; logmsg('Now is within masuk window'); }
                }

                if ($scheduled_pulang_dt) {
                    $start = clone $scheduled_pulang_dt;
                    $end = clone $scheduled_pulang_dt;
                    $end->modify("+{$PULANG_WINDOW_MIN} minutes");
                    logmsg('Pulang window start=' . $start->format('Y-m-d H:i:s') . ' end=' . $end->format('Y-m-d H:i:s'));
                    if ($dt_now >= $start && $dt_now <= $end) { $is_pulang = true; $in_any_window = true; logmsg('Now is within pulang window'); }
                }

                // guru bypass
                if ($type === 'guru') {
                    logmsg('Type is guru -> bypass windows and force masuk=true');
                    $is_masuk = true;
                    $is_pulang = false;
                    $in_any_window = true;
                }

                // If not in any window and siswa -> check last record
                if (!$in_any_window) {
                    logmsg('Not in any window. Checking last attendance record for possible "already" state.');
                    $tbl = ($type === 'siswa') ? 'attendance' : 'attendance_guru';
                    $fkcol = ($type === 'siswa') ? 'student_id' : 'guru_id';
                    $stmt = $pdo->prepare("SELECT id, jam_masuk, jam_pulang FROM {$tbl} WHERE {$fkcol} = :pid AND tanggal = :tgl ORDER BY id DESC LIMIT 1");
                    $stmt->execute([':pid' => $person_id, ':tgl' => $tanggal]);
                    $last = $stmt->fetch(PDO::FETCH_ASSOC);
                    logmsg('Last attendance row: ' . json_encode($last));

                    if ($last && $last['jam_masuk'] && ($last['jam_pulang'] === null || $last['jam_pulang'] === '')) {
                        $resp = ['id_finger' => $id_finger_raw, 'person_id' => $person_id, 'type' => $type, 'status' => 'already', 'nama' => $nama, 'kelas' => $kelas, 'message' => 'Sudah absen (di luar jam)'];
                        $client->publish('school/absensi/response', json_encode($resp));
                        logmsg('Published "already" response: ' . json_encode($resp));
                        return;
                    }

                    $resp = ['id_finger' => $id_finger_raw, 'person_id' => $person_id, 'type' => $type, 'status' => 'out_of_window', 'nama' => $nama, 'kelas' => $kelas, 'message' => 'Tidak dalam jam absen'];
                    $client->publish('school/absensi/response', json_encode($resp));
                    logmsg('Published "out_of_window" response: ' . json_encode($resp));
                    return;
                }

                // ------------------ SISWA masuk ------------------
                if ($type === 'siswa' && $is_masuk) {
                    logmsg('Processing SISWA masuk for person_id=' . $person_id);

                    $chk = $pdo->prepare('SELECT id FROM attendance WHERE student_id = :pid AND tanggal = :tgl AND jam_masuk IS NOT NULL LIMIT 1');
                    $chk->execute([':pid' => $person_id, ':tgl' => $tanggal]);
                    if ($chk->fetch(PDO::FETCH_ASSOC)) {
                        $resp = ['id_finger' => $id_finger_raw, 'student_id' => $person_id, 'type' => 'siswa', 'status' => 'already', 'nama' => $nama, 'kelas' => $kelas, 'message' => 'Sudah absen'];
                        $client->publish('school/absensi/response', json_encode($resp));
                        logmsg('Duplicate masuk detected. Published response: ' . json_encode($resp));
                        return;
                    }

                    // keterlambatan
                    $keterangan = null;
                    if ($scheduled_masuk_dt) {
                        $diff = $dt_now->getTimestamp() - $scheduled_masuk_dt->getTimestamp();
                        if ($diff > 0) {
                            $hours = intdiv($diff, 3600);
                            $mins = intdiv($diff % 3600, 60);
                            $keterangan = ($hours > 0) ? "Telat {$hours} jam {$mins} menit" : "Telat {$mins} menit";
                            logmsg('Keterangan telat: ' . $keterangan);
                        }
                    }

                    // device_ts_ms support?
                    $storeDeviceTs = false;
                    try { $storeDeviceTs = hasColumn($pdo, 'attendance', 'device_ts_ms'); } catch (Throwable $t) { logmsg('hasColumn check failed for attendance: ' . $t->getMessage()); }
                    logmsg('attendance.device_ts_ms available: ' . ($storeDeviceTs ? 'yes' : 'no'));

                    if ($storeDeviceTs) {
                        $ins = $pdo->prepare('INSERT INTO attendance (student_id, tanggal, jam_masuk, jam_pulang, total_seconds, status, keterangan, device_ts_ms, created_at, updated_at) VALUES (:pid, :tgl, :jm, NULL, 0, NULL, :ket, :dms, :ca, :ua)');
                        $ins->execute([':pid' => $person_id, ':tgl' => $tanggal, ':jm' => $datetime_now, ':ket' => $keterangan, ':dms' => $device_ts_ms, ':ca' => date('Y-m-d H:i:s'), ':ua' => date('Y-m-d H:i:s')]);
                    } else {
                        $ins = $pdo->prepare('INSERT INTO attendance (student_id, tanggal, jam_masuk, jam_pulang, total_seconds, status, keterangan, created_at, updated_at) VALUES (:pid, :tgl, :jm, NULL, 0, NULL, :ket, :ca, :ua)');
                        $ins->execute([':pid' => $person_id, ':tgl' => $tanggal, ':jm' => $datetime_now, ':ket' => $keterangan, ':ca' => date('Y-m-d H:i:s'), ':ua' => date('Y-m-d H:i:s')]);
                    }

                    $newId = $pdo->lastInsertId();
                    $resp = ['id_finger' => $id_finger_raw, 'student_id' => $person_id, 'type' => 'siswa', 'status' => 'ok', 'nama' => $nama, 'kelas' => $kelas, 'attendance_id' => $newId, 'jam_masuk' => $datetime_now, 'message' => 'Absen masuk berhasil'];
                    $client->publish('school/absensi/response', json_encode($resp));
                    logmsg('Inserted attendance id=' . $newId . '. Published response: ' . json_encode($resp));
                    return;
                }

                // ------------------ SISWA pulang ------------------
                if ($type === 'siswa' && $is_pulang) {
                    logmsg('Processing SISWA pulang for person_id=' . $person_id);

                    $stmt = $pdo->prepare('SELECT id, jam_masuk, jam_pulang FROM attendance WHERE student_id = :pid AND tanggal = :tgl ORDER BY id DESC LIMIT 1');
                    $stmt->execute([':pid' => $person_id, ':tgl' => $tanggal]);
                    $last = $stmt->fetch(PDO::FETCH_ASSOC);
                    logmsg('Last attendance for pulang: ' . json_encode($last));

                    if (!$last || ($last['jam_pulang'] !== null && $last['jam_pulang'] !== '')) {
                        $resp = ['id_finger' => $id_finger_raw, 'student_id' => $person_id, 'type' => 'siswa', 'status' => 'no_open_record', 'nama' => $nama, 'kelas' => $kelas, 'message' => 'Tidak ada catatan masuk'];
                        $client->publish('school/absensi/response', json_encode($resp));
                        logmsg('No open record for pulang. Published: ' . json_encode($resp));
                        return;
                    }

                    $jam_masuk_ts = $last['jam_masuk'] ? strtotime($last['jam_masuk']) : 0;
                    $elapsed_min = ($use_time - $jam_masuk_ts) / 60.0;
                    if ($elapsed_min < $MIN_PULANG_MIN) {
                        $resp = ['id_finger' => $id_finger_raw, 'student_id' => $person_id, 'type' => 'siswa', 'status' => 'too_soon', 'nama' => $nama, 'kelas' => $kelas, 'message' => "Pulang terlalu cepat (harus > {$MIN_PULANG_MIN} menit)"];
                        $client->publish('school/absensi/response', json_encode($resp));
                        logmsg('Pulang too soon. Published: ' . json_encode($resp));
                        return;
                    }

                    $total_seconds = max(0, $use_time - $jam_masuk_ts);
                    $upd = $pdo->prepare('UPDATE attendance SET jam_pulang = :jp, total_seconds = :tot, updated_at = :ua WHERE id = :id');
                    $upd->execute([':jp' => $datetime_now, ':tot' => $total_seconds, ':ua' => date('Y-m-d H:i:s'), ':id' => $last['id']]);

                    $resp = ['id_finger' => $id_finger_raw, 'student_id' => $person_id, 'type' => 'siswa', 'status' => 'ok', 'nama' => $nama, 'kelas' => $kelas,  'jam_pulang' => $datetime_now, 'message' => 'Absen pulang berhasil'];
                    $client->publish('school/absensi/response', json_encode($resp));
                    logmsg('Pulang updated id=' . $last['id'] . '. Published: ' . json_encode($resp));
                    return;
                }

                // ------------------ GURU masuk ------------------
                if ($type === 'guru') {
                    logmsg('Processing GURU masuk for guru_id=' . $person_id);

                    $chk = $pdo->prepare('SELECT id FROM attendance_guru WHERE guru_id = :pid AND tanggal = :tgl AND jam_masuk IS NOT NULL LIMIT 1');
                    $chk->execute([':pid' => $person_id, ':tgl' => $tanggal]);
                    if ($chk->fetch(PDO::FETCH_ASSOC)) {
                        $resp = ['id_finger' => $id_finger_raw, 'guru_id' => $person_id, 'type' => 'guru', 'status' => 'already', 'nama' => $nama, 'message' => 'Sudah absen'];
                        $client->publish('school/absensi/response', json_encode($resp));
                        logmsg('Guru duplicate masuk. Published: ' . json_encode($resp));
                        return;
                    }

                    $keterangan = null;
                    if ($scheduled_masuk_dt) {
                        $diff = $dt_now->getTimestamp() - $scheduled_masuk_dt->getTimestamp();
                        if ($diff > 0) {
                            $hours = intdiv($diff, 3600);
                            $mins = intdiv($diff % 3600, 60);
                            $keterangan = ($hours > 0) ? "Telat {$hours} jam {$mins} menit" : "Telat {$mins} menit";
                        }
                    }

                    $storeDeviceGuru = false;
                    try { $storeDeviceGuru = hasColumn($pdo, 'attendance_guru', 'device_ts_ms'); } catch (Throwable $t) { logmsg('hasColumn attendance_guru failed: ' . $t->getMessage()); }
                    logmsg('attendance_guru.device_ts_ms available: ' . ($storeDeviceGuru ? 'yes' : 'no'));

                    if ($storeDeviceGuru) {
                        $ins = $pdo->prepare('INSERT INTO attendance_guru (guru_id, tanggal, jam_masuk, jam_pulang, total_seconds, keterangan, device_ts_ms, created_at, updated_at) VALUES (:pid, :tgl, :jm, NULL, 0, :ket, :dms, :ca, :ua)');
                        $ins->execute([':pid' => $person_id, ':tgl' => $tanggal, ':jm' => $datetime_now, ':ket' => $keterangan, ':dms' => $device_ts_ms, ':ca' => date('Y-m-d H:i:s'), ':ua' => date('Y-m-d H:i:s')]);
                    } else {
                        $ins = $pdo->prepare('INSERT INTO attendance_guru (guru_id, tanggal, jam_masuk, jam_pulang, total_seconds, keterangan, created_at, updated_at) VALUES (:pid, :tgl, :jm, NULL, 0, :ket, :ca, :ua)');
                        $ins->execute([':pid' => $person_id, ':tgl' => $tanggal, ':jm' => $datetime_now, ':ket' => $keterangan, ':ca' => date('Y-m-d H:i:s'), ':ua' => date('Y-m-d H:i:s')]);
                    }

                    $newId = $pdo->lastInsertId();
                    $resp = ['id_finger' => $id_finger_raw, 'guru_id' => $person_id, 'type' => 'guru', 'status' => 'ok', 'nama' => $nama, 'attendance_id' => $newId, 'message' => 'Absen masuk berhasil'];
                    $client->publish('school/absensi/response', json_encode($resp));
                    logmsg('Guru masuk inserted id=' . $newId . '. Published: ' . json_encode($resp));
                    return;
                }

                // fallback - shouldn't happen
                $resp = ['id_finger' => $id_finger_raw, 'person_id' => $person_id, 'type' => $type, 'status' => 'ignored', 'nama' => $nama, 'kelas' => $kelas, 'message' => 'Ignored'];
                $client->publish('school/absensi/response', json_encode($resp));
                logmsg('Fallback published: ' . json_encode($resp));
                return;

            } catch (Exception $e) {
                logmsg('Exception in absensi handler: ' . $e->getMessage());
                try {
                    $client->publish('school/absensi/response', json_encode(['status' => 'error', 'message' => 'server_exception', 'error' => substr($e->getMessage(), 0, 200)]));
                } catch (Throwable $t) { logmsg('Failed to publish exception response: ' . $t->getMessage()); }
                return;
            } catch (Throwable $t) {
                logmsg('Fatal error in absensi handler: ' . $t->getMessage());
                try { $client->publish('school/absensi/response', json_encode(['status' => 'error', 'message' => 'fatal', 'error' => substr($t->getMessage(), 0, 200)])); } catch (Throwable $x) { logmsg('Failed to publish fatal response: ' . $x->getMessage()); }
                return;
            }
        }, 0);

        logmsg('Subscribed to topics. Menunggu pesan...');
        $client->loop(true);

        try { $client->disconnect(); } catch (Exception $e) {}
        logmsg("Disconnected, trying reconnect in {$reconnectDelay}s...");
        sleep($reconnectDelay);

    } catch (Throwable $ex) {
        logmsg('Exception: ' . $ex->getMessage());
        logmsg("Will retry in {$reconnectDelay}s...");
        sleep($reconnectDelay);
    }
}
