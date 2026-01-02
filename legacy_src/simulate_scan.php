<?php
$url = 'http://localhost/absen/api/rfid.php';

// Ambil argument dari CLI
$apiKey = $argv[1] ?? '';
$uid = $argv[2] ?? '';

if (!$apiKey || !$uid) {
    die("Usage: php simulate_scan.php <api_key> <uid>\n");
}

$data = ['api_key' => $apiKey, 'uid' => $uid];

$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data)
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "Response:\n";
echo $result;
