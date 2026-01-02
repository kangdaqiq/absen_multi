<?php
// includes/functions.php
// Helper: session, flash, CSRF, auth

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* -------------------
   Flash messages
   ------------------- */
function flash_set(string $key, string $value): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION['flash'][$key] = $value;
}
function flash_get(string $key): ?string {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $val = $_SESSION['flash'][$key] ?? null;
    if (isset($_SESSION['flash'][$key])) {
        unset($_SESSION['flash'][$key]);
    }
    return $val;
}
function flash_has(string $key): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    return isset($_SESSION['flash'][$key]);
}

/* -------------------
   CSRF
   ------------------- */
function generate_csrf_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function verify_csrf_token(?string $token): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($token) || empty($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

/* -------------------
   Auth / session helpers
   ------------------- */
function session_signin(int $user_id, string $email, string $role = 'student', string $full_name = ''): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = $role;
    $_SESSION['user_name'] = $full_name;
    $_SESSION['last_activity'] = time();
    $_SESSION['created_at'] = $_SESSION['created_at'] ?? time();
}

function session_signout(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

function is_logged_in(): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'] ?? null,
        'role' => $_SESSION['user_role'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
    ];
}

/**
 * Require login. Redirects to $redirect_to if not logged.
 * Also enforces idle timeout.
 */
function require_login(string $redirect_to = 'index.php', int $idle_limit_seconds = 1800): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();

    // idle timeout
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $idle_limit_seconds)) {
        session_signout();
        flash_set('error', 'Sesi Anda habis. Silakan login kembali.');
        header('Location: ' . $redirect_to);
        exit;
    }

    // update last activity
    $_SESSION['last_activity'] = time();

    if (!is_logged_in()) {
        flash_set('error', 'Silakan login untuk mengakses halaman tersebut.');
        header('Location: ' . $redirect_to);
        exit;
    }
}

/**
 * Require specific role
 */
function require_role(string $role, string $redirect_to = 'index.php'): void {
    require_login($redirect_to);
    $user = current_user();
    if (!isset($user['role']) || $user['role'] !== $role) {
        flash_set('error', 'Anda tidak memiliki akses ke halaman ini.');
        header('Location: ' . $redirect_to);
        exit;
    }
}
function normalize_wa(?string $wa): ?string {
    if ($wa === null) return null;

    $wa = trim($wa);
    if ($wa === '') return null;

    // buang selain angka
    $wa = preg_replace('/[^0-9]/', '', $wa);

    // ubah ke format 62xxxxxxxxxx
    if (strpos($wa, '08') === 0) {
        $wa = '62' . substr($wa, 1);
    } elseif (strpos($wa, '62') === 0) {
        // sudah benar
    } else {
        return null;
    }

    // validasi panjang
    if (strlen($wa) < 10 || strlen($wa) > 15) {
        return null;
    }

    return $wa;
}
