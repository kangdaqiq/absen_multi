<?php
require_once __DIR__ . '/../includes/function.php';
require_once __DIR__ . '/../includes/db.php'; // jika butuh query db

// Pastikan user login; jika tidak akan redirect ke index.php
require_login('../login.php');

// (opsional) Jika hanya admin yang boleh, gunakan:
// require_role('admin', 'index.php');


// Logic Statistik Hari Ini
$today = date('Y-m-d');

// 1. Total Siswa
$stmt = $pdo->query("SELECT COUNT(*) FROM siswa");
$totalSiswa = $stmt->fetchColumn();

// 2. Siswa Hadir (status = 'H' hari ini)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE tanggal = :tgl AND status = 'H'");
$stmt->execute([':tgl' => $today]);
$siswaHadir = $stmt->fetchColumn();

// 3. Siswa Tidak Hadir
$siswaTidakHadir = $totalSiswa - $siswaHadir;

// 4. Total Guru
$stmt = $pdo->query("SELECT COUNT(*) FROM guru");
$totalGuru = $stmt->fetchColumn();

// 5. Guru Hadir (cek record attendance_guru hari ini)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_guru WHERE tanggal = :tgl AND jam_masuk IS NOT NULL");
$stmt->execute([':tgl' => $today]);
$guruHadir = $stmt->fetchColumn();

// 6. Guru Tidak Hadir
$guruTidakHadir = $totalGuru - $guruHadir;

// 7. Data Chart per Kelas
// Ambil daftar kelas
$chartLabels = [];
$chartDataHadir = [];
$chartDataTidakHadir = [];

// Query aggregate
$sqlChart = "
    SELECT 
        k.nama_kelas,
        COUNT(s.id) as total_murid,
        SUM(CASE WHEN a.id IS NOT NULL AND a.status = 'H' THEN 1 ELSE 0 END) as jml_hadir
    FROM kelas k
    JOIN siswa s ON s.kelas_id = k.id
    LEFT JOIN attendance a ON a.student_id = s.id AND a.tanggal = :tgl
    GROUP BY k.id, k.nama_kelas
    ORDER BY k.nama_kelas ASC
";

$stmt = $pdo->prepare($sqlChart);
$stmt->execute([':tgl' => $today]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    $chartLabels[] = $r['nama_kelas'];
    $h = (int)$r['jml_hadir'];
    $t = (int)$r['total_murid'];
    $th = $t - $h;
    
    $chartDataHadir[] = $h;
    $chartDataTidakHadir[] = $th;
}

// Encode ke JSON untuk JS
$jsonLabels = json_encode($chartLabels);
$jsonHadir = json_encode($chartDataHadir);
$jsonTidakHadir = json_encode($chartDataTidakHadir);

?>
<?php $pageTitle = "Dashboard"; ?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?></title>

    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">

<div id="wrapper">

    <!-- Sidebar -->
    <?php include "sidebar.php"; ?>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <div id="content">

            <!-- Topbar -->
            <?php include "topbar.php"; ?>

            <div class="container-fluid">

                <!-- Header -->
<div id="alert-area">
                    <?php
                    if (function_exists('flash_get')) {
                        $s = flash_get('success');
                        $e = flash_get('error');
                        if ($s) echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($s, ENT_QUOTES) . '<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
                        if ($e) echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($e, ENT_QUOTES) . '<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
                    } elseif (!empty($_SESSION['flash'])) {
                        if (!empty($_SESSION['flash']['success'])) {
                            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['flash']['success'], ENT_QUOTES) . '<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
                            unset($_SESSION['flash']['success']);
                        }
                        if (!empty($_SESSION['flash']['error'])) {
                            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['flash']['error'], ENT_QUOTES) . '<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
                            unset($_SESSION['flash']['error']);
                        }
                    }
                    ?>
                </div>
         <!-- Page Heading -->
<h1 class="h3 mb-4 text-gray-800">Dashboard</h1>

<div class="row">

    <!-- Siswa Hadir -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Siswa Hadir
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= $siswaHadir ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Siswa Tidak Hadir -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            Siswa Tidak Hadir
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= $siswaTidakHadir ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-times fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Guru Hadir -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Guru Hadir
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= $guruHadir ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Guru Tidak Hadir -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Guru Tidak Hadir
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= $guruTidakHadir ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-slash fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- Chart Kehadiran per Kelas -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Chart Kehadiran Siswa per Kelas</h6>
            </div>
            <div class="card-body">
                <div class="chart-bar">
                    <canvas id="chartKehadiranKelas"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>


            </div>

        </div>

        <!-- Footer -->
        <?php include "footer.php"; ?>

    </div>

</div>
<script src="../vendor/chart.js/chart.js"></script>
<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>
<script>
var ctx = document.getElementById("chartKehadiranKelas");

var chartKehadiranKelas = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= $jsonLabels ?>,
        datasets: [
            {
                label: "Hadir",
                backgroundColor: "#1cc88a",
                borderColor: "#17a673",
                data: <?= $jsonHadir ?>,
            },
            {
                label: "Tidak Hadir",
                backgroundColor: "#e74a3b",
                borderColor: "#c0392b",
                data: <?= $jsonTidakHadir ?>,
            }
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            xAxes: [{
                gridLines: {
                    display: false
                }
            }],
            yAxes: [{
                ticks: {
                    beginAtZero: true
                }
            }]
        }
    }
	
});
</script>

</body>
</html>
