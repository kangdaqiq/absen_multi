<?php
// halaman: absensi.php (menggunakan attendance sebagai sumber)
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/function.php';

// Pastikan user login; jika tidak akan redirect ke index.php
require_login('../login.php');

// (opsional) Jika hanya admin yang boleh, gunakan:
// require_role('admin', 'index.php');

$user = current_user();
// baca parameter range tanggal
$from = $_GET['from'] ?? date('Y-m-d');
$to   = $_GET['to']   ?? date('Y-m-d');

// normalize tanggal
try {
    $d1 = new DateTime($from);
    $d2 = new DateTime($to);
    if ($d1 > $d2) {
        $tmp = $from; $from = $to; $to = $tmp;
    }
} catch (Exception $e) {
    $from = $to = date('Y-m-d');
}

// kelas filter optional
$kelasFilter = $_GET['kelas'] ?? '';

// ambil daftar kelas (pakai tabel kelas)
$kelas_list = [];
try {
    $stmt = $pdo->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas ASC");
    $kelas_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error loading kelas: " . $e->getMessage());
}

// CSRF token (jika helper ada)
$token_attendance = function_exists('generate_csrf_token') ? generate_csrf_token() : '';

// --- Mulai: ambil data siswa + rekap kehadiran dari table `attendance` ---
$data_siswa = [];

// pastikan format tanggal Y-m-d
$from = date('Y-m-d', strtotime($from));
$to   = date('Y-m-d', strtotime($to));

try {
    // Query: LEFT JOIN agar siswa tanpa record tetap muncul
    // gunakan DATE(a.tanggal) untuk aman jika kolom tanggal bertipe datetime atau date
    $sql = "
        SELECT
            s.id,
            s.nama,
            s.nis,
            k.nama_kelas AS kelas,
            COALESCE(SUM(CASE WHEN UPPER(a.status) = 'H' THEN 1 ELSE 0 END), 0) AS hadir,
            COALESCE(SUM(CASE WHEN UPPER(a.status) = 'I' THEN 1 ELSE 0 END), 0) AS izin,
            COALESCE(SUM(CASE WHEN UPPER(a.status) = 'S' THEN 1 ELSE 0 END), 0) AS sakit,
            COALESCE(SUM(CASE WHEN UPPER(a.status) = 'A' OR UPPER(a.status) IN ('B','P') THEN 1 ELSE 0 END), 0) AS alfa
        FROM siswa s
        JOIN kelas k ON k.id = s.kelas_id
        LEFT JOIN attendance a
            ON a.student_id = s.id
            AND DATE(a.tanggal) BETWEEN :from AND :to
    ";

    $params = [':from' => $from, ':to' => $to];

    // filter kelas jika ada
    if (!empty($kelasFilter)) {
        // jika kelasFilter datang dari pengguna, gunakan prepared param
        $sql .= " WHERE s.kelas_id = :kelas";
        $params[':kelas'] = $kelasFilter;
    }

    $sql .= "
        GROUP BY s.id, s.nama, s.nis, k.nama_kelas
        ORDER BY k.nama_kelas, s.nama ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data_siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Error loading attendance summary (attendance table): " . $e->getMessage());
    $data_siswa = [];
}
// --- Selesai: data siswa & rekap ---

$pageTitle = "Absensi (".htmlspecialchars($from)." — ".htmlspecialchars($to).")";
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $pageTitle ?></title>

  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../css/sb-admin-2.min.css" rel="stylesheet">
  <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
  <style>
    .status-count { min-width:60px; text-align:center; }
    .filter-inline .form-control { min-width:150px; margin-right:8px; }
    /* Fix table borders */
    table.table-bordered { border:1px solid #e3e6f0; }
    table.table-bordered > thead > tr > th, 
    table.table-bordered > tbody > tr > td { border:1px solid #e3e6f0; }
  </style>
</head>
<body id="page-top">
<div id="wrapper">
  <?php include "sidebar.php"; ?>

  <div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
      <?php include "topbar.php"; ?>

      <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h1 class="h3 text-gray-800">Absensi Siswa</h1>
        </div>

        <div id="alert-area">
          <?php
          if (function_exists('flash_get')) {
              $s = flash_get('success'); $e = flash_get('error');
              if ($s) echo '<div class="alert alert-success alert-dismissible fade show" role="alert">'.htmlspecialchars($s,ENT_QUOTES).'<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
              if ($e) echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">'.htmlspecialchars($e,ENT_QUOTES).'<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
          } elseif (!empty($_SESSION['flash'])) {
              if (!empty($_SESSION['flash']['success'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">'.htmlspecialchars($_SESSION['flash']['success'],ENT_QUOTES).'<button type="button" class="close" data-dismiss="alert">&times;</button></div>'; unset($_SESSION['flash']['success']); }
              if (!empty($_SESSION['flash']['error'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">'.htmlspecialchars($_SESSION['flash']['error'],ENT_QUOTES).'<button type="button" class="close" data-dismiss="alert">&times;</button></div>'; unset($_SESSION['flash']['error']); }
          }
          ?>
        </div>

        <!-- FILTER -->
        <div class="card mb-4">
          <div class="card-body">
            <form id="filterForm" class="form-inline filter-inline" method="get" action="">
              <label class="mr-2">Kelas</label>
              <select id="kelasFilter" name="kelas" class="form-control mr-3">
                <option value="">-- Semua Kelas --</option>
                <?php foreach ($kelas_list as $k):
                    $val = $k['nama_kelas'] ?? '';
                ?>
                  <option value="<?= htmlspecialchars($val, ENT_QUOTES) ?>" <?= ($val === $kelasFilter) ? 'selected' : '' ?>><?= htmlspecialchars($val) ?></option>
                <?php endforeach; ?>
              </select>

              <label class="mr-2">Dari</label>
              <input type="date" id="from" name="from" class="form-control mr-3" value="<?= htmlspecialchars($from) ?>">

              <label class="mr-2">Sampai</label>
              <input type="date" id="to" name="to" class="form-control mr-3" value="<?= htmlspecialchars($to) ?>">

              <button id="btnTampilkan" class="btn btn-primary mr-2">Tampilkan</button>
              <a class="btn btn-secondary mr-2" id="btnReset" href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">Reset</a>

              <a href="export_absensi.php?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?><?= $kelasFilter ? '&kelas='.urlencode($kelasFilter) : '' ?>"
                 class="btn btn-success mr-2" id="btnExport" target="_blank">Export Excel</a>
            </form>

          </div>
        </div>

        <!-- TABLE -->
        <div class="card shadow mb-4">
          <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Rekap Kehadiran per Siswa</h6>
            <div class="small text-muted">
                Rentang: <?= date('d-m-Y', strtotime($from)) ?> — <?= date('d-m-Y', strtotime($to)) ?>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered" id="dataTableAbsensi" width="100%" cellspacing="0">
                <thead class="text-center">
                  <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>NIS</th>
                    <th>Kelas</th>
                    <th class="status-count">Hadir</th>
                    <th class="status-count">Izin</th>
                    <th class="status-count">Sakit</th>
                    <th class="status-count">Alfa</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $no = 1;
                  if (empty($data_siswa)): ?>
                    <tr><td colspan="9" class="text-center text-muted">Tidak ada data</td></tr>
                  <?php else:
                    foreach ($data_siswa as $r): ?>
                      <tr data-id="<?= (int)$r['id'] ?>">
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= htmlspecialchars($r['nama'], ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($r['nis'], ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($r['kelas'], ENT_QUOTES) ?></td>
                        <td class="text-center"><?= (int)$r['hadir'] ?></td>
                        <td class="text-center"><?= (int)$r['izin'] ?></td>
                        <td class="text-center"><?= (int)$r['sakit'] ?></td>
                        <td class="text-center"><?= (int)$r['alfa'] ?></td>
                        <td class="text-center">
                          <a href="detail-absensi.php?student_id=<?= (int)$r['id'] ?>&from=<?= htmlspecialchars($from) ?>&to=<?= htmlspecialchars($to) ?>" class="btn btn-sm btn-info">Detail</a>
                        </td>
                      </tr>
                    <?php endforeach;
                  endif;
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div> <!-- container-fluid -->
    </div> <!-- content -->

    <?php include "footer.php"; ?>

  </div> <!-- content-wrapper -->
</div> <!-- wrapper -->

<!-- toast -->
<div style="position:fixed; top:80px; right:1rem; z-index:1060; pointer-events:none;">
  <div id="toastMsg" class="toast" role="alert" aria-live="assertive" aria-atomic="true" style="pointer-events:auto;">
    <div class="toast-header"><strong class="mr-auto">Absensi</strong><small class="text-muted">sekarang</small><button type="button" class="ml-2 mb-1 close" data-dismiss="toast">&times;</button></div>
    <div class="toast-body" id="toastBody"></div>
  </div>
</div>

<!-- SCRIPTS -->
<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/datatables/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>

<script>
$(document).ready(function(){
  var table = $('#dataTableAbsensi').DataTable({
    pageLength: 25,
    responsive: true,
    columnDefs: [
      { targets: [0,8], orderable: false }
    ]
  });

  $('#tableSearch').on('keyup', function(){ table.search(this.value).draw(); });

  $('#btnTampilkan').on('click', function(e){
    e.preventDefault();
    var from = $('#from').val();
    var to = $('#to').val();
    var kelas = $('#kelasFilter').val();
    var qs = '?from=' + encodeURIComponent(from) + '&to=' + encodeURIComponent(to);
    if (kelas) qs += '&kelas=' + encodeURIComponent(kelas);
    window.location = window.location.pathname + qs;
  });

  $('#btnReset').on('click', function(e){
    window.location = window.location.pathname;
  });

  function showToast(html) {
    $('#toastBody').html(html);
    $('#toastMsg').toast({ delay: 2500 }).toast('show');
  }
});
</script>

</body>
</html>
