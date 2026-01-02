<?php
// login_action.php
// Proses POST login: validasi CSRF, cek kredensial, set session

require_once __DIR__ . '/function.php';
require_once __DIR__ . '/db.php'; // pastikan file ini mendefinisikan $pdo (PDO instance)

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

// ambil dan verifikasi CSRF
$posted_csrf = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($posted_csrf)) {
    // debug log (hapus/disable di produksi jika perlu)
    error_log('CSRF mismatch. Session token: ' . ($_SESSION['csrf_token'] ?? '[none]') . ' | Posted: ' . ($posted_csrf ?: '[none]'));
    flash_set('error', 'Invalid request (CSRF)');
    header('Location: ../login.php');
    exit;
}

// ambil input
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    flash_set('error', 'Email dan password wajib diisi.');
    header('Location: ../login.php');
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, email, password_hash, role, full_name FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && isset($user['password_hash']) && password_verify($password, $user['password_hash'])) {
        // sukses: set session (gunakan helper)
        session_signin((int)$user['id'], $user['email'], $user['role'] ?? 'student', $user['full_name'] ?? '');
        flash_set('success', 'Login berhasil.');
        header('Location: ../pages/dashboard.php');
        exit;
    } else {
        // gagal
        // Opsional: increment failed attempts untuk protection
        flash_set('error', 'Email atau password salah.');
        header('Location: ../login.php');
        exit;
    }

} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    flash_set('error', 'Terjadi kesalahan server. Coba lagi nanti.');
    header('Location: ../login.php');
    exit;
}
