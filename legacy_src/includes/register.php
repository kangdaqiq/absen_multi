<?php
// includes/register.php
// Halaman pendaftaran admin sekali pakai
// Pastikan file db.php dan functions.php berada di folder yang sama (includes/) atau sesuaikan path.

require_once __DIR__ . '/db.php';        // harus menyediakan $pdo (PDO instance)
require_once __DIR__ . '/function.php'; // harus menyediakan flash_get, flash_set, generate_csrf_token, redirect

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Jika sudah login -> redirect ke dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
    exit;
}

// Cek apakah sudah ada admin (jika terjadi error, blok pendaftaran untuk keamanan)
$adminCount = 1;
try {
    $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM users WHERE role = 'admin'");
    $row = $stmt->fetch();
    $adminCount = (int)($row['cnt'] ?? 0);
} catch (Exception $e) {
    error_log('DB error checking admin: ' . $e->getMessage());
    // keep $adminCount = 1 to block form on DB error
}

// Ambil pesan flash jika ada
$success = flash_get('success');
$error = flash_get('error');

$csrf = generate_csrf_token();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register Admin - Sistem Absensi</title>
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../css/sb-admin-2.min.css" rel="stylesheet">
  <style>
    .logo-wrapper { width:120px;height:120px;background:#ffffffee;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 15px;box-shadow:0 4px 15px rgba(0,0,0,0.15);padding:15px;}
  </style>
</head>
<body class="bg-gradient-primary">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-xl-5 col-lg-6 col-md-8 col-sm-10">
        <div class="card o-hidden border-0 shadow-lg my-5">
          <div class="card-body p-4">
            <div class="logo-wrapper">
              <img src="../img/logo.png" alt="Logo Sekolah" style="width:85%;height:auto">
            </div>
            <div class="text-center mb-3">
              <div class="h5 font-weight-bold text-primary">Pendaftaran Admin (Sekali Pakai)</div>
              <div class="small text-muted">Hapus atau nonaktifkan file ini setelah pendaftaran.</div>
            </div>

            <?php if ($success): ?>
              <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
              <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
            <?php endif; ?>

            <?php if ($adminCount > 0): ?>
              <div class="alert alert-info">
                <strong>Akun admin sudah ada.</strong><br>
                Pendaftaran admin baru tidak diperbolehkan.
              </div>
            <?php else: ?>
              <form action="register_action.php" method="post" class="user" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
                <div class="form-group">
                  <input type="email" class="form-control form-control-user" name="email" placeholder="Email..." required>
                </div>
                <div class="form-group">
                  <input type="text" class="form-control form-control-user" name="full_name" placeholder="Nama lengkap..." required>
                </div>
                <div class="form-group">
                  <input type="password" class="form-control form-control-user" name="password" placeholder="Password..." required>
                </div>
                <div class="form-group">
                  <input type="password" class="form-control form-control-user" name="password_confirm" placeholder="Konfirmasi Password..." required>
                </div>
                <button type="submit" class="btn btn-primary btn-user btn-block">Daftarkan Admin</button>
              </form>
              <hr>
              <div class="small text-muted">Setelah sukses, hapus file ini (register.php & register_action.php).</div>
            <?php endif; ?>

          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
