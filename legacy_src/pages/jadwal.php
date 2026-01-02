<?php
// schedule.php — satu file tampilkan + proses save

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/function.php';
require_login('../login.php');
$token_jadwal = generate_csrf_token();

$pageTitle = "Pengaturan Jam Sekolah";

$days = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];

// Default jika database kosong
$defaults = [
    1 => ['on'=>1,'in'=>'07:30','out'=>'15:00','tol'=>5],
    2 => ['on'=>1,'in'=>'07:30','out'=>'15:00','tol'=>5],
    3 => ['on'=>1,'in'=>'07:30','out'=>'15:00','tol'=>5],
    4 => ['on'=>1,'in'=>'07:30','out'=>'15:00','tol'=>5],
    5 => ['on'=>1,'in'=>'07:30','out'=>'13:00','tol'=>10],
    6 => ['on'=>0,'in'=>'08:00','out'=>'12:00','tol'=>0],
    7 => ['on'=>0,'in'=>'00:00','out'=>'00:00','tol'=>0],
];

// ===============================
// PROSES SAVE
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = ['type'=>'danger','text'=>'CSRF token tidak valid!'];
    } else {

    $day_on  = $_POST['day_on']  ?? [];
    $day_in  = $_POST['day_in']  ?? [];
    $day_out = $_POST['day_out'] ?? [];
    $day_tol = $_POST['day_tol'] ?? [];

    try {
        $pdo->beginTransaction();

        $sql = "INSERT INTO jadwal
            (hari, index_hari, is_active, jam_masuk, jam_pulang, toleransi, created_at, updated_at)
            VALUES (:hari, :index_hari, :is_active, :jam_masuk, :jam_pulang, :toleransi, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                hari = VALUES(hari),
                is_active = VALUES(is_active),
                jam_masuk = VALUES(jam_masuk),
                jam_pulang = VALUES(jam_pulang),
                toleransi = VALUES(toleransi),
                updated_at = NOW()";

        $stmt = $pdo->prepare($sql);

        for ($i = 1; $i <= 7; $i++) {
            $is_active  = isset($day_on[$i]) ? 1 : 0;
            $jam_in     = $day_in[$i]  ?? '00:00';
            $jam_out    = $day_out[$i] ?? '00:00';
            $toleransi  = max(0, (int)($day_tol[$i] ?? 0));

            $stmt->execute([
                ':hari'       => $days[$i-1],
                ':index_hari' => $i,
                ':is_active'  => $is_active,
                ':jam_masuk'  => $jam_in,
                ':jam_pulang' => $jam_out,
                ':toleransi'  => $toleransi
            ]);
        }

        $pdo->commit();

// PRG redirect
header("Location: jadwal.php?status=success");
exit;


    } catch (Exception $e) {
        $pdo->rollBack();

$err = urlencode("Gagal menyimpan data: ".$e->getMessage());
header("Location: jadwal.php?status=error&msg=$err");
exit;

    }
}}
$message = null;

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $message = ['type'=>'success','text'=>'Pengaturan jam berhasil disimpan.'];
    } elseif ($_GET['status'] === 'error') {
        $msg = $_GET['msg'] ?? 'Terjadi kesalahan.';
        $message = ['type'=>'danger','text'=>htmlspecialchars($msg)];
    }
}
// ===============================
// LOAD DATA UNTUK FORM
// ===============================
$stmt = $pdo->query("SELECT * FROM jadwal ORDER BY index_hari ASC");
$dbRows = $stmt->fetchAll();

$dbMap = [];
foreach ($dbRows as $r) {
    $dbMap[(int)$r['index_hari']] = $r;
}

?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $pageTitle ?></title>

  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">

<div id="wrapper">
<?php include "sidebar.php"; ?>

<div id="content-wrapper" class="d-flex flex-column">
<div id="content">

<?php include "topbar.php"; ?>

<div class="container-fluid">

<h1 class="h3 text-gray-800 mb-4"><?= htmlspecialchars($pageTitle) ?></h1>

<?php if (!empty($message)): ?>
<div class="alert alert-<?= htmlspecialchars($message['type']) ?> alert-dismissible fade show">
  <?= htmlspecialchars($message['text']) ?>
  <button class="close" data-dismiss="alert">&times;</button>
</div>

<script>
setTimeout(function() {
    $('.alert').alert('close');
}, 3500);
</script>
<?php endif; ?>


<div class="card shadow mb-4">
<div class="card-header d-flex justify-content-between align-items-center">
  <strong>Pengaturan Jam Sekolah / Hari</strong>
</div>

<div class="card-body">
<form method="POST">

<table class="table table-sm">
<thead>
<tr>
  <th>Hari</th>
  <th>Aktif</th>
  <th>Jam Masuk</th>
  <th>Jam Pulang</th>
  <th>Toleransi</th>
</tr>
</thead>

<tbody>
<?php
for ($i = 1; $i <= 7; $i++):
    if (isset($dbMap[$i])) {
        $row = $dbMap[$i];
        $conf = [
            'on' => $row['is_active'],
            'in' => substr($row['jam_masuk'],0,5),
            'out'=> substr($row['jam_pulang'],0,5),
            'tol'=> $row['toleransi']
        ];
    } else {
        $conf = $defaults[$i];
    }
?>
<tr>
  <td><?= $days[$i-1] ?></td>
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token_jadwal) ?>">
  <td><input type="checkbox" name="day_on[<?= $i ?>]" <?= $conf['on'] ? 'checked' : '' ?>></td>
  <td><input type="time" class="form-control form-control-sm" name="day_in[<?= $i ?>]"  value="<?= $conf['in'] ?>"></td>
  <td><input type="time" class="form-control form-control-sm" name="day_out[<?= $i ?>]" value="<?= $conf['out'] ?>"></td>
  <td><input type="number" class="form-control form-control-sm" name="day_tol[<?= $i ?>]" value="<?= $conf['tol'] ?>" min="0" style="width:80px"></td>
</tr>
<?php endfor; ?>
</tbody>

</table>

<button class="btn btn-success">Simpan Pengaturan</button>

</form>
</div>
</div>

</div>
</div>

<?php include "footer.php"; ?>

</div>
</div>

<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
