<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/function.php'; // untuk CSRF & flash jika tersedia
// Pastikan user login; jika tidak akan redirect ke index.php
require_login('../login.php');

// (opsional) Jika hanya admin yang boleh, gunakan:
// require_role('admin', 'index.php');

$user = current_user();

/* =================================================================================
   PROCESS LOGIC (POST REQUESTS)
   ================================================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validasi CSRF Token
    $token = $_POST['token_guru'] ?? '';
    if (!verify_csrf_token($token)) {
        flash_set('error', 'Token CSRF tidak valid. Silakan refresh halaman.');
        header("Location: data-guru.php");
        exit;
    }

    $action = $_POST['action'] ?? '';

    // --- 1. TAMBAH GURU ---
    if ($action === 'add_guru') {
        try {
            $nama  = trim($_POST['nama'] ?? '');
            $nip   = trim($_POST['nip'] ?? '');
            $no_wa = trim($_POST['no_wa'] ?? '');

            if ($nama === '' || $no_wa === '') {
                flash_set('error', 'Nama dan No WhatsApp wajib diisi');
                header("Location: data-guru.php"); exit;
            }

            // cek duplicate wa
            $stmt = $pdo->prepare('SELECT id FROM guru WHERE no_wa = ? LIMIT 1');
            $stmt->execute([$no_wa]);
            if ($stmt->fetch()) {
                flash_set('error', 'WA sudah terdaftar.');
                header("Location: data-guru.php"); exit;
            }

            $insert = $pdo->prepare('INSERT INTO guru (nama, nip, no_wa) VALUES (?, ?, ?)');
            $insert->execute([$nama, $nip, $no_wa]);

            flash_set('success', 'Guru berhasil ditambahkan.');

        } catch (Exception $e) {
            flash_set('error', 'Gagal menambahkan guru: ' . $e->getMessage());
        }
        header("Location: data-guru.php");
        exit;
    }

    // --- 2. EDIT GURU ---
    if ($action === 'edit_guru') {
        try {
            $id        = (int)($_POST['id'] ?? 0);
            $nama      = trim($_POST['nama'] ?? '');
            $nip       = trim($_POST['nip'] ?? '');
            $no_wa     = trim($_POST['no_wa'] ?? '');
            $id_finger = trim($_POST['id_finger'] ?? '');

            if ($id <= 0 || $nama === '' || $no_wa === '') {
                flash_set('error', 'Data tidak lengkap.');
                header("Location: data-guru.php"); exit;
            }

            // Cek exist
            $stmt = $pdo->prepare('SELECT id FROM guru WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                flash_set('error', 'Guru tidak ditemukan.');
                header("Location: data-guru.php"); exit;
            }

            // cek WA conflict
            $stmt = $pdo->prepare('SELECT id FROM guru WHERE no_wa = ? AND id != ? LIMIT 1');
            $stmt->execute([$no_wa, $id]);
            if ($stmt->fetch()) {
                flash_set('error', 'No WhatsApp sudah dipakai.');
                header("Location: data-guru.php"); exit;
            }

            $update = $pdo->prepare('UPDATE guru SET nama = ?, nip = ?, id_finger = ?, no_wa = ? WHERE id = ?');
            $update->execute([$nama, $nip, $id_finger, $no_wa, $id]);

            flash_set('success', 'Data guru berhasil diperbarui.');

        } catch (Exception $e) {
            flash_set('error', 'Gagal memperbarui data guru: ' . $e->getMessage());
        }
        header("Location: data-guru.php");
        exit;
    }

    // --- 3. HAPUS GURU ---
    if ($action === 'delete_guru') {
        try {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                flash_set('error', 'ID tidak valid.');
                header("Location: data-guru.php"); exit;
            }

            $stmt = $pdo->prepare('DELETE FROM guru WHERE id = ?');
            $stmt->execute([$id]);

            flash_set('success', 'Guru berhasil dihapus.');

        } catch (Exception $e) {
            flash_set('error', 'Gagal menghapus guru: ' . $e->getMessage());
        }
        header("Location: data-guru.php");
        exit;
    }
}

/* =================================================================================
   VIEW LOGIC
   ================================================================================= */
try {
    $stmt = $pdo->query("SELECT id, nama, nip, no_wa, id_finger FROM guru ORDER BY nama ASC");
    $data_guru = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error Query Guru: " . $e->getMessage());
    $data_guru = [];
}
$token_guru = function_exists('generate_csrf_token') ? generate_csrf_token() : '';
?>
<?php $pageTitle = "Data Guru"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?></title>

    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>
      /* Highlight simple untuk status */
      .status-requested { background:#fff3cd; }
      .status-done { background:#d4edda; }
      .status-error { background:#f8d7da; }
      /* Fix table borders */
      table.table-bordered { border:1px solid #e3e6f0; }
      table.table-bordered > thead > tr > th, 
      table.table-bordered > tbody > tr > td { border:1px solid #e3e6f0; }
    </style>
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

                <!-- Page Heading -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 text-gray-800">Data Guru</h1>
                    <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#modalTambahGuru">
                        <i class="fas fa-plus fa-sm"></i> Tambah Guru
                    </button>
                </div>

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

                <!-- Tabel Data Guru -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Tabel Data Guru</h6>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTableGuru" width="100%" cellspacing="0">
                                <thead class="text-center">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama</th>
                                        <th>NIP</th>
                                        <th>No WhatsApp</th>
                                        <th>ID Fingerprint</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>

                               <tbody>
                                <?php 
                                $no = 1;
                                if (empty($data_guru)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Tidak ada data guru</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($data_guru as $row): 
                                        $status = $row['enroll_status'] ?? '-';
                                        $status_class = '';
                                        if (strtolower($status) === 'requested') $status_class = 'status-requested';
                                        if (strtolower($status) === 'done') $status_class = 'status-done';
                                        if (strtolower($status) === 'error') $status_class = 'status-error';
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($row['nama'], ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars($row['nip'], ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars($row['no_wa'], ENT_QUOTES) ?></td>
                                            <td class="text-center"><?= htmlspecialchars($row['id_finger'], ENT_QUOTES) ?></td>

                                            <td hidden class="cell-enroll-status text-center <?= $status_class ?>"><?= htmlspecialchars($status, ENT_QUOTES) ?></td>

                                            <td class="text-center">
                                                <a href="#" class="btn btn-sm btn-warning btnEditGuru"
                                                    data-id="<?= $row['id'] ?>"
                                                    data-nama="<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>"
                                                    data-nip="<?= htmlspecialchars($row['nip'], ENT_QUOTES) ?>"
                                                    data-wa="<?= htmlspecialchars($row['no_wa'], ENT_QUOTES) ?>"
                                                    data-finger="<?= htmlspecialchars($row['id_finger'], ENT_QUOTES) ?>"
                                                    data-toggle="modal" data-target="#modalEditGuru">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <a href="#" class="btn btn-sm btn-danger btnHapusGuru"
                                                    data-id="<?= $row['id'] ?>"
                                                    data-nama="<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>"
                                                    data-toggle="modal" data-target="#modalHapusGuru">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <button
                                                   class="btn btn-sm btn-primary btnEnrollGuru"
                                                   data-id="<?= htmlspecialchars($row['id'], ENT_QUOTES) ?>"
                                                   data-nama="<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>"
                                                   data-current-finger="<?= htmlspecialchars($row['id_finger'] ?? '', ENT_QUOTES) ?>"
                                                   data-enroll-status="<?= htmlspecialchars($status, ENT_QUOTES) ?>"
                                                   data-toggle="modal"
                                                   data-target="#modalEnrollGuru"
                                                   title="Enroll" >
                                                    <i class="fas fa-fingerprint"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>

                            </table>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- Footer -->
        <?php include "footer.php"; ?>

    </div>

</div>
<!-- ============================= -->
<!-- MODAL ENROLL GURU (Sama seperti sebelumnya) -->
<!-- ============================= -->
<div class="modal fade" id="modalEnrollGuru" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formEnrollGuru">
                <input type="hidden" name="token_guru" value="<?= htmlspecialchars($token_guru, ENT_QUOTES) ?>">
                <input type="hidden" name="guru_id" id="enroll_guru_id">

                <div class="modal-header">
                    <h5 class="modal-title">Enroll Sidik Jari</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <div class="modal-body">
                    <p><strong id="enroll_guru_nama"></strong></p>

                    <div class="form-group">
                        <label>Mode Enroll</label>
                        <select name="mode" id="enroll_mode" class="form-control">
                            <option value="auto">Auto — pilih ID kosong pertama</option>
                            <option value="manual">Manual — gunakan ID berikut</option>
                        </select>
                    </div>

                    <div class="form-group" id="enroll_manual_group" style="display:none">
                        <label>Masukkan ID Fingerprint</label>
                        <input type="number" name="id_finger" id="enroll_id_finger" class="form-control" min="1">
                        <small class="form-text text-muted">Masukkan ID yang ingin dipakai (1..127). Kosongkan untuk memilih otomatis.</small>
                    </div>

                    <div class="form-group">
                        <p class="text-muted">Proses: setelah tekan <strong>Mulai Enroll</strong>, sistem akan menunggu proses enroll pada perangkat fingerprint. Pastikan perangkat terhubung.</p>
                        <div id="enroll_status" class="alert" style="display:none"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="button" id="btnDeleteId" class="btn btn-danger" title="Hapus ID Finger">
                        <i class="fas fa-trash-alt"></i> Hapus ID
                    </button>
                    <button type="button" id="btnStartEnroll" class="btn btn-primary">Mulai Enroll</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- ============================= -->
<!-- MODAL TAMBAH GURU -->
<!-- ============================= -->
<div class="modal fade" id="modalTambahGuru" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Tambah Guru</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <form action="" method="POST">
                <input type="hidden" name="action" value="add_guru">
                <input type="hidden" name="token_guru" value="<?= htmlspecialchars($token_guru, ENT_QUOTES) ?>">
                <div class="modal-body">

                    <div class="form-group">
                        <label>Nama Guru</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>NIP</label>
                        <input type="text" name="nip" class="form-control" >
                    </div>

                    <div class="form-group">
                        <label>No WhatsApp</label>
                        <input type="text" name="no_wa" class="form-control">
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>

            </form>

        </div>
    </div>
</div>

<!-- ============================= -->
<!-- MODAL EDIT GURU -->
<!-- ============================= -->
<div class="modal fade" id="modalEditGuru" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Edit Guru</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <form action="" method="POST">
                <input type="hidden" name="action" value="edit_guru">
                <input type="hidden" name="token_guru" value="<?= htmlspecialchars($token_guru, ENT_QUOTES) ?>">
                <div class="modal-body">

                    <input type="hidden" name="id" id="edit_guru_id">
                    <input type="hidden" name="id_finger" id="edit_finger_hidden">
                    
                    <div class="form-group">
                        <label>Nama Guru</label>
                        <input type="text" name="nama" id="edit_guru_nama" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>NIP</label>
                        <input type="text" name="nip" id="edit_guru_nip" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>No WhatsApp</label>
                        <input type="text" name="no_wa" id="edit_no_wa" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>ID Fingerprint</label>
                        <input type="text" id="edit_finger" class="form-control" disabled>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>

            </form>

        </div>
    </div>
</div>

<!-- ============================= -->
<!-- MODAL HAPUS GURU -->
<!-- ============================= -->
<div class="modal fade" id="modalHapusGuru" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>

            <form action="" method="POST">
                <input type="hidden" name="action" value="delete_guru">
                <input type="hidden" name="token_guru" value="<?= htmlspecialchars($token_guru, ENT_QUOTES) ?>">
                <input type="hidden" name="id" id="hapus_guru_id">

                <div class="modal-body">
                    <p>Yakin ingin menghapus guru berikut?</p>
                    <h5 class="text-danger" id="hapus_guru_nama"></h5>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </div>

            </form>

        </div>
    </div>
</div>

<!-- SCRIPTS -->
<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/datatables/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>
<!-- Paho MQTT for browser websocket -->
<script src="../js/paho-mqtt.js"></script>

<script>
/* ================================================
   JS untuk Data Guru: DataTable, UI handlers, MQTT
   ================================================ */

(function(window, $, undefined){
  'use strict';

  // CONFIG (bisa dioverride dari PHP via window.APP_CONFIG)
  const APP = window.APP_CONFIG || {};
  const DEBUG = APP.debug === true || false;

  const MQTT_CONFIG = {
    host: APP.mqtt_host || '127.0.0.1',
    port: Number(APP.mqtt_port || 9001),
    useSSL: !!APP.mqtt_use_ssl,
    user: APP.mqtt_user || '',
    pass: APP.mqtt_pass || ''
  };

  function log(){ if (!DEBUG) return; if (console && console.log) console.log.apply(console, arguments); }
  function warn(){ if (DEBUG && console && console.warn) console.warn.apply(console, arguments); }
  function error(){ if (DEBUG && console && console.error) console.error.apply(console, arguments); }

  function showAlert(msg, type='info', timeout=5000) {
    const id = 'app-alert-' + Date.now();
    const $a = $('<div/>', {
      id, class: 'alert alert-' + type + ' alert-dismissible fade show',
      role: 'alert', html: $('<span/>').text(msg)
    });
    $a.append('<button type="button" class="close" data-dismiss="alert">&times;</button>');
    $('#alert-area').prepend($a);
    if (timeout) setTimeout(()=> $a.alert('close'), timeout);
  }

  // Bersihkan backdrop modal (overlay hitam)
  function cleanupModalBackdrop() {
    if ($('.modal.show').length === 0) {
      $('.modal-backdrop').remove();
      $('body').removeClass('modal-open').css('padding-right', '');
    }
  }

  // Saat modal enroll guru benar-benar tertutup
  $('#modalEnrollGuru').on('hidden.bs.modal', function() {
    cleanupModalBackdrop();
    $('#enroll_status').hide().removeClass('alert-danger alert-success alert-info').text('');
    $('#enroll_mode').val('auto');
    $('#enroll_manual_group').hide();
    $('#enroll_id_finger').val('');
  });

  /* ================= DataTable Guru ================= */
  $(document).ready(function() {
    $('#dataTableGuru').DataTable({
      columnDefs: [
        { orderable: false, targets: -1 } // kolom Aksi
      ],
      autoWidth: false
    });
    log('DataTableGuru initialized');
  });

  /* ============= UI: Edit / Hapus Guru ============= */

  // Edit guru
  $('#dataTableGuru').on('click', '.btnEditGuru', function(e){
    e.preventDefault();
    const $btn = $(this);
    const id = String($btn.data('id') || '');
    const nama = $btn.data('nama') || '';
    const nip = $btn.data('nip') || '';
    const wa = $btn.data('wa') || '';
    const finger = $btn.data('finger') || '';

    $('#edit_guru_id').val(id);
    $('#edit_guru_nama').val(nama);
    $('#edit_guru_nip').val(nip);
    $('#edit_no_wa').val(wa);
    $('#edit_finger_hidden').val(finger);
    $('#edit_finger').val(finger);

    $('#modalEditGuru').modal('show');
  });

  // Hapus guru
  $('#dataTableGuru').on('click', '.btnHapusGuru', function(e){
    e.preventDefault();
    const $btn = $(this);
    const id = $btn.data('id') || '';
    const nama = $btn.data('nama') || '';

    $('#hapus_guru_id').val(id);
    $('#hapus_guru_nama').text(nama);
    $('#modalHapusGuru').modal('show');
  });

  /* ================= Enroll: open modal ================= */

  // NB: tombol masih class .btnEnrollGuru di HTML guru, jadi kita pakai itu
  $('#dataTableGuru').on('click', '.btnEnrollGuru', function(e){
    e.preventDefault();
    const $btn = $(this);
    const id = String($btn.data('id') || '');
    const nama = $btn.data('nama') || '';
    const current = $btn.data('current-finger') || '-';
    const enroll_status = $btn.data('enroll-status') || '-';

    $('#enroll_guru_id').val(id);
    $('#enroll_guru_nama').text(nama + ' (current: ' + current + ')');
    $('#enroll_status').hide().removeClass('alert-danger alert-success alert-info').text('');
    $('#enroll_mode').val('auto');
    $('#enroll_manual_group').hide();
    $('#enroll_id_finger').val('');

    if (enroll_status && enroll_status !== '-') {
      $('#enroll_status').show().addClass('alert-info').text('Status saat ini: ' + enroll_status);
    }

    $('#modalEnrollGuru').modal('show');
  });

  // toggle manual group
  $(document).on('change', '#enroll_mode', function(){
    if ($(this).val() === 'manual') $('#enroll_manual_group').show();
    else $('#enroll_manual_group').hide();
  });

  /* ================= Enroll: Hapus ID ================= */

  $(document).on('click', '#btnDeleteId', function(e){
    e.preventDefault();
    const $btn = $(this);
    const guru_id = $('#enroll_guru_id').val();
    const token = $('input[name="token_guru"]').val(); // ambil token_guru dari form

    if (!guru_id) { showAlert('ID guru tidak ditemukan.', 'warning'); return; }

    const rowBtn = $("button.btnEnrollGuru[data-id='" + guru_id + "']");
    let id_finger = null;
    if (rowBtn.length) {
      const df = rowBtn.data('current-finger');
      if (df !== undefined && df !== null && df !== '-' && df !== '') {
        id_finger = Number(df);
      }
    }
    const manualVal = $("#enroll_id_finger").val();
    if ((!id_finger || id_finger <= 0) && manualVal) {
      const m = Number(manualVal);
      if (!isNaN(m) && m > 0) id_finger = m;
    }

    if (!id_finger || id_finger <= 0) {
      showAlert('Tidak ada ID fingerprint tersimpan untuk guru ini. Tidak dapat menghapus.', 'warning');
      return;
    }

    if (!confirm('Yakin ingin menghapus ID fingerprint ' + id_finger + ' untuk guru ini?')) return;

    $btn.prop('disabled', true).text('Menghapus...');

    const statusCell = rowBtn.closest('tr').find('.cell-enroll-status');
    statusCell.text('delete-requested').removeClass('status-done status-error').addClass('status-requested');
    $('#enroll_status').show().removeClass('alert-danger alert-success').addClass('alert-info').text('Mengirim permintaan hapus...');

    $.post('enroll-publish.php', {
      // kirim token sebagai token_guru untuk kompatibel dengan backend yang sama
      token_guru: token,
      absen_id: guru_id,   // reuse param generik absen_id
      type: 'guru',
      action: 'delete',
      id_finger: id_finger
    }, function(resp){
      let data = resp;
      try { if (typeof resp === 'string') data = JSON.parse(resp); } catch(e) {}
      if (data && data.ok) {
        $('#enroll_status').removeClass('alert-danger alert-success').addClass('alert-info')
                           .text('Permintaan hapus terkirim. Menunggu perangkat...');
        showAlert('Permintaan hapus ID guru terkirim', 'info', 4000);
      } else {
        const err = (data && data.error) ? data.error : 'Gagal mengirim permintaan';
        $('#enroll_status').removeClass('alert-info alert-success').addClass('alert-danger').text(err);
        statusCell.text('-').removeClass('status-requested');
        showAlert(err, 'danger', 6000);
      }
    }).fail(function(xhr, status){
      $('#enroll_status').removeClass('alert-info alert-success').addClass('alert-danger')
                         .text('Gagal menghubungi server: ' + status);
      statusCell.text('-').removeClass('status-requested');
      showAlert('Gagal menghubungi server: ' + status, 'danger', 6000);
    }).always(function(){
      $btn.prop('disabled', false).text('Hapus ID');
    });
  });

  /* ================= Enroll: Mulai Enroll ================= */

  $(document).on('click', '#btnStartEnroll', function(e){
    e.preventDefault();
    const $btn = $(this);
    const guru_id = $('#enroll_guru_id').val();
    const mode = $('#enroll_mode').val();
    const id_finger = $('#enroll_id_finger').val();
    const token = $('input[name="token_guru"]').val();

    if (!guru_id) { showAlert('ID guru tidak ditemukan.', 'warning'); return; }

    if (mode === 'manual' && id_finger) {
      const v = parseInt(id_finger, 10);
      if (isNaN(v) || v < 1 || v > 127) {
        showAlert('ID fingerprint manual harus antara 1 dan 127.', 'warning'); return;
      }
    }

    $btn.prop('disabled', true).text('Mengirim...');
    $('#enroll_status').removeClass('alert-danger alert-success').addClass('alert-info')
                       .show().text('Mengirim perintah ke server...');

    $.post('enroll-publish.php', {
      token_guru: token,
      absen_id: guru_id,
      type: 'guru',       // bedakan dari guru
      mode: mode,
      id_finger: id_finger
    }, function(resp){
      let data = resp;
      try { if (typeof resp === 'string') data = JSON.parse(resp); } catch(e) {}
      if (data && data.ok) {
        const rowBtn = $("button.btnEnrollGuru[data-id='" + guru_id + "']");
        const statusCell = rowBtn.closest('tr').find('.cell-enroll-status');
        statusCell.text('requested').removeClass('status-done status-error').addClass('status-requested');
        $('#enroll_status').removeClass('alert-danger alert-success').addClass('alert-info')
                           .text('Perintah enroll terkirim. Menunggu perangkat...');
        showAlert('Perintah enroll guru terkirim', 'info', 3000);
      } else {
        const msg = data && data.error ? data.error : 'Gagal mengirim perintah';
        $('#enroll_status').removeClass('alert-info alert-success').addClass('alert-danger').text(msg);
        showAlert(msg, 'danger', 6000);
      }
    }).fail(function(xhr, status){
      $('#enroll_status').removeClass('alert-info alert-success').addClass('alert-danger')
                         .text('Gagal menghubungi server: ' + status);
      showAlert('Gagal menghubungi server: ' + status, 'danger', 6000);
    }).always(function(){
      $btn.prop('disabled', false).text('Mulai Enroll');
    });
  });

  /* ================= Helper untuk row ================= */

  function findRowByGuruId(id) {
    if (!id) return $();
    const btn = $("button.btnEnrollGuru[data-id='" + id + "']");
    if (btn.length) return btn.closest('tr');
    return $();
  }

  // ambil sel fingerprint di tabel guru (kolom ke-5 = index 4)
  function getFingerCell(row) {
    const tds = row.find('td');
    if (tds.length >= 5) return tds.eq(4);
    return $();
  }

  /* ================= MQTT MODULE (sharing dengan guru) ================= */

  const MQTT = (function(){
    let client = null;
    let connectOpts = null;
    let connected = false;
    let reconnectAttempts = 0;

    function nextBackoff(n){ return Math.min(30000, Math.pow(2, n) * 1000); }

    function initClient(){
      try {
        const clientId = 'webui-guru-' + Math.floor(Math.random()*1e6);
        if (typeof Paho === 'undefined' || !Paho.MQTT) {
          warn('Paho missing — shim akan digunakan bila ada');
        }
        client = new Paho.MQTT.Client(MQTT_CONFIG.host, MQTT_CONFIG.port, clientId);

        client.onConnectionLost = function(resp){
          connected = false;
          reconnectAttempts = 0;
          log('MQTT guru connection lost', resp);
          attemptReconnect();
        };

        client.onMessageArrived = handleMessageArrived;

        connectOpts = {
          useSSL: MQTT_CONFIG.useSSL,
          userName: MQTT_CONFIG.user,
          password: MQTT_CONFIG.pass,
          onSuccess: function(){
            connected = true;
            reconnectAttempts = 0;
            safePostConnect();
            log('MQTT guru connected');
          },
          onFailure: function(err){
            connected = false;
            warn('MQTT guru connect failed', err);
            attemptReconnect();
          }
        };
      } catch(e){
        error('initClient error', e);
      }
    }

    function attemptReconnect(){
      reconnectAttempts++;
      const delay = nextBackoff(reconnectAttempts);
      log('Reconnecting MQTT guru in', delay, 'ms');
      setTimeout(function(){
        try {
          if (client && connectOpts) client.connect(connectOpts);
        } catch(e){
          warn('Reconnect exception', e);
          attemptReconnect();
        }
      }, delay);
    }

    function safePostConnect(){
      try {
        client.subscribe('school/enroll/status/#');
        client.subscribe('school/enroll/result');
      } catch(e){
        warn('Subscribe error guru', e);
      }
    }

    function start(){
      if (!client) initClient();
      try { client.connect(connectOpts); }
      catch(e){ warn('MQTT guru connect exception', e); attemptReconnect(); }
    }

    function handleMessageArrived(message){
      try {
        const topic = message.destinationName || message.topic || '';
        const raw = (message.payloadString || message.payload || '').toString();

        let txt = raw.trim().replace(/\r?\n/g, '').replace(/,\s*}/g, '}').replace(/,\s*\]/g, ']');
        let payload = null;
        try { payload = JSON.parse(txt); }
        catch(e){
          const m = txt.match(/\{[\s\S]*\}/);
          if (m && m[0]) {
            try { payload = JSON.parse(m[0]); }
            catch(e2){ warn('fallback JSON guru gagal', e2); }
          }
        }
        if (!payload) payload = { raw: txt };

        const pickIdFromPayload = (p) => p.absen_id || p.id || p.guru_id || null;

        // Status: school/enroll/status/<id>
        if (topic.startsWith('school/enroll/status/')) {
          const parts = topic.split('/');
          const id = parts[parts.length - 1];
          const row = findRowByGuruId(id);
          const statusText = payload.message || payload.status || payload.raw || JSON.stringify(payload);

          if (row.length) {
            const statusCell = row.find('.cell-enroll-status');
            statusCell.text(statusText).removeClass('status-requested status-done status-error status-progress');
            const stLower = (payload.status || '').toString().toLowerCase();
            if (stLower === 'requested') statusCell.addClass('status-requested');
            else if (stLower === 'done' || stLower === 'ok') statusCell.addClass('status-done');
            else if (stLower === 'error') statusCell.addClass('status-error');
            else statusCell.addClass('status-progress');

            const btn = row.find('button.btnEnrollGuru').first();
            if (btn.length && payload.id_finger !== undefined) {
              const attrVal = (payload.id_finger === 0 || payload.id_finger === null) ? '' : payload.id_finger;
              btn.data('current-finger', attrVal).attr('data-current-finger', attrVal);
            }
          }

          if (String($('#enroll_guru_id').val()) === String(id)) {
            $('#enroll_status').show().removeClass('alert-danger alert-success').addClass('alert-info')
                               .text((payload.status || '') + ': ' + statusText);
          }

          return;
        }

        // Result: school/enroll/result
        if (topic === 'school/enroll/result') {
          const id = pickIdFromPayload(payload) || '';
          if (!id) return;

          const row = findRowByGuruId(id);
          const rawStatus = (payload.status || '').toString().toLowerCase();
          const successStatuses = ['ok','done','deleted','removed'];
          const isSuccess = successStatuses.includes(rawStatus);

          if (row.length) {
            if (payload.id_finger !== undefined) {
              const fingerCell = getFingerCell(row);
              const idVal = (payload.id_finger === 0 || payload.id_finger === null) ? '-' : String(payload.id_finger);
              fingerCell.text(idVal);
            }
            const statusCell = row.find('.cell-enroll-status');
            statusCell.text(rawStatus || (payload.message || 'result'))
                      .removeClass('status-requested status-done status-error status-progress');
            if (isSuccess) statusCell.addClass('status-done');
            else if (rawStatus === 'error') statusCell.addClass('status-error');
            else statusCell.addClass('status-progress');

            const btn = row.find('button.btnEnrollGuru').first();
            if (btn.length) {
              if (payload.id_finger !== undefined) {
                const idAttr = (payload.id_finger === 0 || payload.id_finger === null) ? '' : payload.id_finger;
                btn.data('current-finger', idAttr).attr('data-current-finger', idAttr);
              }
              if (payload.status !== undefined) {
                btn.data('enroll-status', payload.status).attr('data-enroll-status', payload.status);
              }
            }
          }

          if (String($('#enroll_guru_id').val()) === String(id)) {
            if (isSuccess) {
              const friendly =
                (rawStatus === 'deleted') ? 'Hapus ID berhasil' :
                (rawStatus === 'ok' || rawStatus === 'done') ?
                  ('Sukses' + (payload.id_finger ? (' (ID ' + payload.id_finger + ')') : '')) :
                  'Berhasil';
              $('#enroll_status').show().removeClass('alert-info alert-danger')
                                 .addClass('alert-success').text('Hasil: ' + friendly);
              $('#modalEnrollGuru').modal('hide');
              setTimeout(cleanupModalBackdrop, 200);
            } else {
              const msg = payload.message || payload.status || JSON.stringify(payload);
              $('#enroll_status').show().removeClass('alert-info alert-success')
                                 .addClass('alert-danger').text('Gagal: ' + msg);
            }
          }
        }

      } catch(e){
        error('MQTT guru message handler exception', e);
      }
    }

    return { start };
  })();

  /* ================= Inisialisasi MQTT ================= */
  $(document).ready(function(){
    MQTT.start();
  });

})(window, jQuery);
</script>

</body>
</html>
