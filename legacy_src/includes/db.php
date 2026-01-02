<?php 
$DB_HOST = '127.0.0.1';
$DB_NAME = 'absen';
$DB_USER = 'root';
$DB_PASS = '';


try {
$pdo = new PDO("mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4", $DB_USER, $DB_PASS, [
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
PDO::ATTR_EMULATE_PREPARES => false,
]);
} catch (Exception $e) {
// Jangan tampilkan detail error di produksi
error_log($e->getMessage());
http_response_code(500);
exit('Database connection failed');
}?>