<?php
// mqtt_worker.php (verbose + fallback)
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php'; // must provide $pdo (PDO)
date_default_timezone_set('Asia/Jakarta');

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

$LOG_FILE = __DIR__ . '/logs/mqtt_worker.log';
if (!is_dir(dirname($LOG_FILE))) @mkdir(dirname($LOG_FILE), 0755, true);

function logMsg($msg) {
    global $LOG_FILE;
    $line = date('[Y-m-d H:i:s] ') . $msg . PHP_EOL;
    echo $line;
    @file_put_contents($LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}

// sanity: check $pdo
if (!isset($pdo) || !($pdo instanceof PDO)) {
    logMsg("FATAL: \$pdo not found or not a PDO instance. Check includes/db.php");
    exit(1);
}

// helper: check if table exists
function tableExists($pdo, $table) {
    try {
        $stmt = $pdo->prepare("SELECT 1 FROM `$table` LIMIT 1");
        $stmt->execute();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

$hasPending = tableExists($pdo, 'enroll_pending');
logMsg("DB check: enroll_pending exists? " . ($hasPending ? 'YES' : 'NO'));

// MQTT config
$server   = '127.0.0.1';
$port     = 1883;
$clientId = 'php-worker-' . bin2hex(random_bytes(4));
$mqtt = new MqttClient($server, $port, $clientId, MqttClient::MQTT_3_1);

$settings = (new ConnectionSettings)
    ->setKeepAliveInterval(60)
    ->setUseTls(false);

try {
    $mqtt->connect($settings, true);
} catch (Exception $e) {
    logMsg("MQTT CONNECT ERROR: " . $e->getMessage());
    exit(1);
}
logMsg("MQTT Worker connected to {$server}:{$port} as {$clientId}");

// message handler
$onMessage = function ($topic, $message, $retained) use ($pdo, $hasPending) {
    logMsg("Received topic: {$topic} payload: {$message}");
    $data = json_decode($message, true);

    // tolerant: if not JSON, try to parse permissive k:v pairs
    if ($data === null) {
        // permissive parse: {txn:tx123,siswa_id:1}
        $msg = trim($message);
        if (strlen($msg) > 0 && $msg[0] == '{' && substr($msg, -1) == '}') {
            $msg = substr($msg, 1, -1);
        }
        $pairs = preg_split('/\s*,\s*/', $msg);
        $data = [];
        foreach ($pairs as $p) {
            $parts = explode(':', $p, 2);
            if (count($parts) == 2) {
                $k = trim($parts[0], " \t\n\r\"'");
                $v = trim($parts[1], " \t\n\r\"'");
                if (is_numeric($v)) $v = (int)$v;
                $data[$k] = $v;
            }
        }
    }

    if (!$data || !is_array($data)) {
        logMsg("WARN: payload cannot be parsed as JSON or KV pairs. Ignoring.");
        return;
    }

    // determine type/txn
    $parts = explode('/', $topic);
    $type  = $parts[1] ?? null;   // 'response' or 'status'
    $txn   = $parts[2] ?? ($data['txn'] ?? null);
    $siswa_id = isset($data['siswa_id']) ? (int)$data['siswa_id'] : (isset($data['siswaId']) ? (int)$data['siswaId'] : null);
    $fid = isset($data['fid']) ? (int)$data['fid'] : null;
    $status = $data['status'] ?? null;
    $message_text = $data['message'] ?? null;

    logMsg("Parsed: type={$type}, txn={$txn}, siswa_id={$siswa_id}, fid={$fid}, status={$status}");

    // write to enroll_log if table exists
    try {
        if (tableExists($pdo, 'enroll_log')) {
            $stmt = $pdo->prepare("INSERT INTO enroll_log (txn, siswa_id, fid, status, message, created_at) VALUES (:txn,:s,:f,:st,:msg,NOW())");
            $stmt->execute([':txn'=>$txn, ':s'=>$siswa_id, ':f'=>$fid, ':st'=>$status ?: $type, ':msg'=>$message_text]);
            logMsg("Inserted enroll_log (txn={$txn})");
        }
    } catch (Exception $e) {
        logMsg("ERROR inserting enroll_log: " . $e->getMessage());
    }

    // handle response (ESP assigned fid)
    if ($type === 'response') {
        if ($txn && $fid) {
            if ($hasPending) {
                try {
                    $stmt = $pdo->prepare("UPDATE enroll_pending SET fid = :fid, updated_at = NOW() WHERE txn = :txn");
                    $stmt->execute([':fid'=>$fid, ':txn'=>$txn]);
                    logMsg("Updated enroll_pending fid={$fid} for txn={$txn}, rows=" . $stmt->rowCount());
                } catch (Exception $e) {
                    logMsg("DB error updating enroll_pending: " . $e->getMessage());
                }
            } else {
                // no pending table: optionally log or create a lightweight record
                logMsg("No enroll_pending table: response received txn={$txn} fid={$fid} (no DB update performed)");
            }
        } else {
            logMsg("response message missing txn or fid");
        }
        return;
    }

    // handle status messages
    if ($type === 'status') {
        if ($status === 'done') {
            // finalize: prefer enroll_pending if exists
            if ($hasPending) {
                try {
                    $stmt = $pdo->prepare("SELECT siswa_id, fid FROM enroll_pending WHERE txn = :txn LIMIT 1");
                    $stmt->execute([':txn'=>$txn]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $siswa_id_db = (int)$row['siswa_id'];
                        $fid_db = (int)$row['fid'] ?: $fid;
                        try {
                            $pdo->beginTransaction();
                            $stmt2 = $pdo->prepare("UPDATE siswa SET id_finger = :fid, enroll_status = 'done' WHERE id = :id");
                            $stmt2->execute([':fid'=>$fid_db, ':id'=>$siswa_id_db]);
                            logMsg("Updated siswa id={$siswa_id_db} set id_finger={$fid_db}, rows=" . $stmt2->rowCount());
                            $stmt3 = $pdo->prepare("UPDATE enroll_pending SET status='done', message=:msg WHERE txn=:txn");
                            $stmt3->execute([':msg'=>$message_text, ':txn'=>$txn]);
                            $pdo->commit();
                            logMsg("Enroll finalized for txn={$txn}");
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            logMsg("DB commit error finalizing enroll: " . $e->getMessage());
                        }
                    } else {
                        logMsg("No enroll_pending found for txn={$txn}");
                    }
                } catch (Exception $e) {
                    logMsg("DB error selecting enroll_pending: " . $e->getMessage());
                }
            } else {
                // fallback: directly update siswa if id_finger not set
                if (!$siswa_id) {
                    logMsg("No siswa_id provided in status and no pending table. Can't update.");
                } else {
                    try {
                        $stmt = $pdo->prepare("UPDATE siswa SET id_finger = :fid, enroll_status = 'done' WHERE id = :id AND (id_finger IS NULL OR id_finger = '')");
                        $stmt->execute([':fid'=>$fid, ':id'=>$siswa_id]);
                        $rc = $stmt->rowCount();
                        if ($rc == 1) {
                            logMsg("Fallback: Updated siswa id={$siswa_id} set id_finger={$fid}");
                        } else {
                            logMsg("Fallback: Update affected {$rc} rows (possible conflict) for siswa={$siswa_id}");
                        }
                    } catch (Exception $e) {
                        logMsg("DB error fallback update siswa: " . $e->getMessage());
                    }
                }
            }
            return;
        } elseif ($status === 'fail') {
            if ($hasPending) {
                try {
                    $stmt = $pdo->prepare("UPDATE enroll_pending SET status='error', message=:msg WHERE txn=:txn");
                    $stmt->execute([':msg'=>$message_text, ':txn'=>$txn]);
                    logMsg("Marked enroll_pending error for txn={$txn}");
                } catch (Exception $e) {
                    logMsg("DB error marking pending error: " . $e->getMessage());
                }
            } else {
                logMsg("Status=fail received for txn={$txn} (no pending table). Message: {$message_text}");
            }
            return;
        } else {
            logMsg("Progress/status message for txn={$txn}: status={$status} message={$message_text}");
            return;
        }
    }

    // unknown topic type
    logMsg("Unknown topic type '{$type}' for topic '{$topic}'");
    return;
};

// subscribe
$mqtt->subscribe('enroll/response/#', function ($topic, $message, $retained) use ($onMessage) {
    $onMessage($topic, $message, $retained);
}, 0);

$mqtt->subscribe('enroll/status/#', function ($topic, $message, $retained) use ($onMessage) {
    $onMessage($topic, $message, $retained);
}, 0);

logMsg("Subscriptions registered: enroll/response/#, enroll/status/#");

// loop forever
while (true) {
    try {
        $mqtt->loop(true);
    } catch (Exception $e) {
        logMsg("MQTT loop error: " . $e->getMessage());
        sleep(1);
    }
}
