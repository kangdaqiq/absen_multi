<?php
// register_action.php
require_once 'db.php';        // PDO $pdo
require_once 'function.php'; // flash_set, flash_get, redirect, verify_csrf_token

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.php');
}

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// CSRF check
$csrf = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf)) {
    flash_set('error', 'Invalid request (CSRF).');
    redirect('register.php');
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$full_name = trim($_POST['full_name'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

if (!$email) {
    flash_set('error', 'Masukkan email yang valid.');
    redirect('register.php');
}
if (empty($full_name)) {
    flash_set('error', 'Nama lengkap wajib diisi.');
    redirect('register.php');
}
if (!$password || strlen($password) < 8) {
    flash_set('error', 'Password minimal 8 karakter.');
    redirect('register.php');
}
if ($password !== $password_confirm) {
    flash_set('error', 'Konfirmasi password tidak cocok.');
    redirect('register.php');
}

try {
    // TRANSAKSI untuk mengurangi race condition
    $pdo->beginTransaction();

    // Periksa lagi apakah sudah ada admin
    $check = $pdo->query("SELECT COUNT(*) AS cnt FROM users WHERE role = 'admin' FOR UPDATE");
    $row = $check->fetch();
    $adminCount = (int)($row['cnt'] ?? 0);
    if ($adminCount > 0) {
        $pdo->rollBack();
        flash_set('error', 'Pendaftaran admin ditolak: admin sudah ada.');
        redirect('register.php');
    }

    // Pastikan email belum terdaftar (unik)
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $exists = $stmt->fetch();
    if ($exists) {
        $pdo->rollBack();
        flash_set('error', 'Email sudah terdaftar.');
        redirect('register.php');
    }

    // Simpan user dengan role = admin
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $insert = $pdo->prepare('INSERT INTO users (email, password_hash, full_name, role) VALUES (?, ?, ?, ?)');
    $insert->execute([$email, $password_hash, $full_name, 'admin']);

    $pdo->commit();

    flash_set('success', 'Pendaftaran admin berhasil. Silakan login.');
    // Opsional: redirect ke index/login
    redirect('index.php');

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Register error: ' . $e->getMessage());
    flash_set('error', 'Terjadi kesalahan pada server. Coba lagi atau hubungi admin.');
    redirect('register.php');
}
