<?php
// absensi_harian.php (versi lengkap, menggunakan tabel `siswa` dan tabel `attendance`)
// Catatan: file ini mengasumsikan ada file include: includes/db.php (PDO $pdo) dan includes/function.php
// yang menyediakan: generate_csrf_token(), verify_csrf_token(), flash_set(), flash_get(), require_login(), current_user().

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/function.php'; // pastikan fungsi-fungsi ada

require_login('../login.php');
$user = current_user();

// params
$tanggal = $_GET['tanggal'] ?? date('Y-m-d'); // single date filter
$kelasFilter = $_GET['kelas'] ?? '';

// validate tanggal
function valid_date($d) {
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}
if (!valid_date($tanggal)) $tanggal = date('Y-m-d');

// CSRF token
$token = generate_csrf_token();

// --------------------
// Handle POST actions
// --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $posted = $_POST['csrf'] ?? '';
    if (!verify_csrf_token($posted)) {
        flash_set('error', 'Token CSRF tidak valid.');
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    try {
        if ($action === 'edit_attendance') {
            // Edit data absensi (Modal)
            $id = (int)($_POST['id'] ?? 0);
            $sid = (int)($_POST['student_id'] ?? 0);
            $tgl = $_POST['tanggal_absen'] ?? date('Y-m-d');
            
            $time_in = $_POST['jam_masuk'] ?: null; // format H:i
            $time_out = $_POST['jam_pulang'] ?: null; // format H:i
            
            $status = $_POST['status'] ?? 'A';
            $keterangan = trim($_POST['keterangan'] ?? '');

            // Validasi input
            if (!in_array($status, ['H','I','S','A','B','P'], true)) throw new Exception('Status tidak valid.');
            if (!$sid && !$id) throw new Exception('ID Siswa tidak ditemukan.');

            // Format datetime: "Y-m-d H:i:s"
            $jm_full = $time_in ? "$tgl $time_in:00" : null;
            $jp_full = $time_out ? "$tgl $time_out:00" : null;
            
            // Hitung total seconds
            $total_seconds = 0;
            if ($jm_full && $jp_full) {
                $ts = strtotime($jp_full) - strtotime($jm_full);
                $total_seconds = max(0, $ts);
            }

            $pdo->beginTransaction();

            if ($id > 0) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE attendance 
                                       SET jam_masuk = :jm, jam_pulang = :jp, total_seconds = :ts, 
                                           status = :st, keterangan = :ket, updated_at = NOW() 
                                       WHERE id = :id");
                $stmt->execute([
                    ':jm'=>$jm_full, ':jp'=>$jp_full, ':ts'=>$total_seconds,
                    ':st'=>$status, ':ket'=>$keterangan, ':id'=>$id
                ]);
            } else {
                // Insert new record (jika belum ada row tapi di-edit dari list)
                $stmt = $pdo->prepare("INSERT INTO attendance (student_id, tanggal, jam_masuk, jam_pulang, total_seconds, status, keterangan, created_at, updated_at) 
                                       VALUES (:sid, :tgl, :jm, :jp, :ts, :st, :ket, NOW(), NOW())");
                $stmt->execute([
                    ':sid'=>$sid, ':tgl'=>$tgl, ':jm'=>$jm_full, ':jp'=>$jp_full,
                    ':ts'=>$total_seconds, ':st'=>$status, ':ket'=>$keterangan
                ]);
            }

            $pdo->commit();
            flash_set('success', 'Data absensi berhasil diperbarui.');

        } else {
            flash_set('error', 'Aksi tidak dikenal.');
        }
    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
        error_log("absensi POST error: " . $e->getMessage());
        flash_set('error', 'Gagal menyimpan: ' . $e->getMessage());
    }

    // Redirect back
    header('Location: ' . $_SERVER['PHP_SELF'] . '?tanggal=' . urlencode($tanggal) . ($kelasFilter ? '&kelas='.urlencode($kelasFilter) : ''));
    exit;
}

// AJAX: return students list (for manual form) - menggunakan tabel `siswa`
// AJAX: return students list (for manual form)



// --------------------
// Load kelas list & students with daily info (for selected $tanggal)
$kelas_list = [];
try {
    $stmt = $pdo->query("
        SELECT id, nama_kelas
        FROM kelas
        ORDER BY nama_kelas ASC
    ");
    $kelas_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error loading kelas: " . $e->getMessage());
}

// fetch students + attendance info for date
$students = [];
try {
    $sql = "
        SELECT
            a.id AS attendance_id,
            s.id,
            s.nama,
            s.nis,
            k.nama_kelas,
            a.jam_masuk   AS first_in,
            a.jam_pulang  AS last_out,
            a.total_seconds,
            a.status,
            a.keterangan
        FROM siswa s
        JOIN kelas k ON k.id = s.kelas_id
        LEFT JOIN attendance a
            ON a.student_id = s.id
           AND a.tanggal = :tgl
        WHERE 1=1
    ";

    $params = [':tgl' => $tanggal];

    if ($kelasFilter !== '') {
        $sql .= " AND s.kelas_id = :kelas_id ";
        $params[':kelas_id'] = $kelasFilter;
    }

    $sql .= " ORDER BY k.nama_kelas, s.nama";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Error loading students: " . $e->getMessage());
}

// flash messages
$smsg = flash_get('success');
$emsg = flash_get('error');

$pageTitle = "Absensi Harian - " . date('d-m-Y', strtotime($tanggal));
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?></title>

  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../css/sb-admin-2.min.css" rel="stylesheet">
  <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
  <style>
    .status-badge { padding:4px 8px; border-radius:4px; color:#fff; display:inline-block; }
    .st-H{background:#28a745;} .st-I{background:#ffc107;color:#212529;} .st-S{background:#17a2b8;} .st-A{background:#6c757d;} .st-B{background:#dc3545;} .st-P{background:#007bff;}
    .status-select { min-width:130px; }
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
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
          <h1 class="h3 text-gray-800"><?= htmlspecialchars($pageTitle) ?></h1>
        </div>

        <div id="alert-area">
          <?php if ($smsg): ?><div class="alert alert-success"><?= htmlspecialchars($smsg) ?></div><?php endif; ?>
          <?php if ($emsg): ?><div class="alert alert-danger"><?= htmlspecialchars($emsg) ?></div><?php endif; ?>
        </div>



        <!-- Filter -->
        <div class="card mb-4">
          <div class="card-body">
            <form id="filterForm" class="form-inline" method="get">
              <label class="mr-2">Kelas</label>
              <select id="kelasFilter" name="kelas" class="form-control mr-3">
                <option value="">-- Semua Kelas --</option>
                <?php foreach ($kelas_list as $k): $val=$k['nama_kelas'] ?? ''; ?>
                  <option value="<?= htmlspecialchars($val,ENT_QUOTES) ?>" <?= ($val===$kelasFilter ? 'selected':'') ?>><?= htmlspecialchars($val) ?></option>
                <?php endforeach; ?>
              </select>

              <label class="mr-2">Tanggal</label>
              <input type="date" id="tanggal" name="tanggal" class="form-control mr-3" value="<?= htmlspecialchars($tanggal) ?>">

              <button id="btnTampilkan" class="btn btn-primary mr-2">Tampilkan</button>
              <a class="btn btn-secondary" href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">Reset</a>
            </form>
          </div>
        </div>

        <!-- TABLE -->
        <div class="card shadow mb-4">
          <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <strong>Daftar Absensi</strong>
            <div class="small text-muted">Tanggal: <?= date('d-m-Y', strtotime($tanggal)) ?></div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table id="absensiTable" class="table table-bordered" width="100%" cellspacing="0">
                <thead class="text-center">
                  <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>NIS</th>
					<th>Kelas</th>
                    <th>Jam Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                    <th>Ubah</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($students)): ?>
                    <tr><td colspan="9" class="text-center text-muted">Tidak ada data</td></tr>
                  <?php else:
                    $no=1;
                    foreach($students as $s):
                      $fi = $s['first_in'] ? date('H:i:s', strtotime($s['first_in'])) : '-';
                      $lo = $s['last_out'] ? date('H:i:s', strtotime($s['last_out'])) : '-';
                      $k = $s['status'] ?? '';
                      $badgeClass = 'st-'.($k?:'A');
                      $labelMap = ['H'=>'Hadir','I'=>'Izin','S'=>'Sakit','A'=>'Alfa','B'=>'Bolos','P'=>'Partial'];
                      $label = $labelMap[$k] ?? ($k ? $k : 'Alfa');
                  ?>
                    <tr data-id="<?= (int)$s['id'] ?>" data-nama="<?= htmlspecialchars($s['nama'],ENT_QUOTES) ?>">
                      <td class="text-center"><?= $no++ ?></td>
                      <td><?= htmlspecialchars($s['nama']) ?></td>
                      <td><?= htmlspecialchars($s['nis']) ?></td>
					  <td class="text-center"><?= htmlspecialchars($s['nama_kelas']) ?></td>
                      <td class="text-center"><?= $fi ?></td>
                      <td class="text-center"><?= $lo ?></td>
                      <td class="text-center"><span class="status-badge <?= $badgeClass ?>"><?= htmlspecialchars($label) ?></span></td>
                      <td class="text-center"><?= htmlspecialchars($s['keterangan'] ?? '') ?></td>
                      <td class="text-center">
                        <button class="btn btn-sm btn-info btnEditAbsen"
                                data-id="<?= (int)($s['id']) ?>"
                                data-att-id="<?= isset($s['first_in']) || isset($s['last_out']) || isset($s['keterangan']) ? 'wait_how_to_get_att_id' : 0 // we need attendance id in join query ?>"
                                data-nama="<?= htmlspecialchars($s['nama']) ?>"
                                data-masuk="<?= $s['first_in'] ? date('H:i', strtotime($s['first_in'])) : '' ?>"
                                data-pulang="<?= $s['last_out'] ? date('H:i', strtotime($s['last_out'])) : '' ?>"
                                data-status="<?= $s['status'] ?? 'A' ?>"
                                data-keterangan="<?= htmlspecialchars($s['keterangan'] ?? '') ?>">
                            <i class="fas fa-edit"></i> Ubah
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div> <!-- container -->
    </div> <!-- content -->
    <?php include "footer.php"; ?>
  </div>
</div>

<!-- Modal Edit Absensi -->
<div class="modal fade" id="modalEditAbsen" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Data Absensi</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="action" value="edit_attendance">
                    <input type="hidden" name="id" id="edit_id"> <!-- attendance id -->
                    <input type="hidden" name="student_id" id="edit_student_id"> <!-- siswa id -->
                    <input type="hidden" name="tanggal_absen" value="<?= htmlspecialchars($tanggal) ?>">

                    <div class="form-group">
                        <label>Nama Siswa</label>
                        <input type="text" id="edit_nama" class="form-control" readonly>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Jam Masuk</label>
                            <input type="time" name="jam_masuk" id="edit_masuk" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Jam Pulang</label>
                            <input type="time" name="jam_pulang" id="edit_pulang" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="edit_status" class="form-control">
                            <option value="H">Hadir</option>
                            <option value="I">Izin</option>
                            <option value="S">Sakit</option>
                            <option value="A">Alfa</option>
                            <option value="B">Bolos</option>
                            <option value="P">Partial</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="keterangan" id="edit_note" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>


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
  var table = $('#absensiTable').DataTable({ pageLength:25, responsive:true, columnDefs:[{targets:[0,7,8],orderable:false}] });

  // Handler Tombol Ubah
  $('#absensiTable').on('click', '.btnEditAbsen', function(){
      var btn = $(this);
      
      // Ambil data
      var atId = btn.data('att-id') || 0;
      var sId = btn.data('id');
      var nama = btn.data('nama');
      var masuk = btn.data('masuk');
      var pulang = btn.data('pulang');
      var status = btn.data('status');
      var keterangan = btn.data('keterangan');

      // Populate Modal
      $('#edit_id').val(atId);
      $('#edit_student_id').val(sId);
      $('#edit_nama').val(nama);
      $('#edit_masuk').val(masuk);
      $('#edit_pulang').val(pulang);
      $('#edit_status').val(status);
      $('#edit_note').val(keterangan);

      // Show
      $('#modalEditAbsen').modal('show');
  });

  // filter form submit


  // filter form submit
  $('#filterForm').on('submit', function(e){
    e.preventDefault();
    var k = $('#kelasFilter').val();
    var t = $('#tanggal').val();
    var qs = '?tanggal=' + encodeURIComponent(t);
    if (k) qs += '&kelas=' + encodeURIComponent(k);
    window.location = window.location.pathname + qs;
  });

  $('#btnTampilkan').on('click', function(e){ e.preventDefault(); $('#filterForm').submit(); });

});
</script>

</body>
</html>
