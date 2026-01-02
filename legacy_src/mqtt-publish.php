<?php
require 'vendor/autoload.php';

use PhpMqtt\Client\MqttClient;

$server = '127.0.0.1';
$port = 1883;
$clientId = 'php-xampp';

$mqtt = new MqttClient($server, $port, $clientId);

$mqtt->connect();
$mqtt->publish('enroll/request', '{"siswa_id": 12}', 0);
$mqtt->disconnect();

echo "MQTT sent!";
