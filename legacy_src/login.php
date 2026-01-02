<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Sistem Absensi SMK Assuniyah Tumijajar</title>

    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">

    <style>
        .logo-wrapper {
            width: 120px;
            height: 120px;
            background: #ffffffee;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 15px auto;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            padding: 15px;
        }

        .school-title {
            font-weight: 700;
            font-size: 1.25rem;
            color: #4e73df;
        }

        .school-subtitle {
            font-weight: 600;
            color: #2e2e3f;
            font-size: 1.05rem;
        }

        /* RESPONSIVE IMPROVEMENT */
        @media (max-width: 480px) {
            .card-body {
                padding: 1.8rem !important;
            }
            .logo-wrapper {
                width: 100px;
                height: 100px;
            }
            .school-title {
                font-size: 1.1rem;
            }
            .school-subtitle {
                font-size: 1rem;
            }
        }
    </style>
</head>
<?php
require_once __DIR__ . '/includes/function.php'; // path sesuaikan
$csrf = generate_csrf_token();
?>
<body class="bg-gradient-primary">

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-xl-5 col-lg-6 col-md-8 col-sm-10">

                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-4">

                        <!-- ALERT AREA (AKTIFKAN DARI PHP) -->
                        <!-- Contoh PHP:
                             if($error) { echo '<div class="alert alert-danger">Email atau password salah!</div>'; }
                        -->
                        <div id="alert-area">
                            <?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once 'includes/function.php';

$success = flash_get('success');
$error   = flash_get('error');

if ($success) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">'
        . htmlspecialchars($success, ENT_QUOTES) .
        '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
     </div>';
}

if ($error) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">'
        . htmlspecialchars($error, ENT_QUOTES) .
        '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
     </div>';
}
?>

                        </div>

                        <!-- LOGO -->
                        <div class="logo-wrapper">
                            <img src="img/logo.png" alt="Logo Sekolah" style="width:85%; height:auto;">
                        </div>

                        <!-- TITLE -->
                        <div class="text-center mb-3">
                            <div class="school-title">Sistem Absensi</div>
                            <div class="school-subtitle">SMK Assuniyah Tumijajar</div>
                        </div>

                        <!-- LOGIN FORM -->
                        <form action="includes/login-action.php" method="POST" class="user">
 <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
                            <div class="form-group">
                                <input 
                                    type="email"
                                    class="form-control form-control-user"
                                    name="email"
                                    placeholder="Email..."
                                    required>
                            </div>

                            <div class="form-group">
                                <input 
                                    type="password"
                                    class="form-control form-control-user"
                                    name="password"
                                    placeholder="Password..."
                                    required>
                            </div>

                            <button type="submit" class="btn btn-primary btn-user btn-block">
                                Login
                            </button>

                        </form>

                        <hr>
                   

                    </div>
                </div>

            </div>

        </div>

    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
<script>
setTimeout(() => {
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(a => a.classList.remove('show'));
}, 4000);
</script>

</body>
</html>
