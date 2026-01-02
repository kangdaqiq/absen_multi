<?php
// pages/devices.php (fixed + improved)
// Requirements: includes/db.php -> $pdo (PDO), includes/function.php -> auth + csrf helpers
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/function.php';

require_login('../login.php');
$user = current_user();


// -------------------------
// AJAX handlers (save / delete)
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'];
    // verify CSRF for state-changing actions
    if (!verify_csrf_token($_POST['csrf'] ?? '')) {
        echo json_encode(['ok'=>0,'error'=>'CSRF token invalid']);
        exit;
    }

    try {
        if ($action === 'save_device') {
            // create or update
            $id = isset($_POST['device_id']) && $_POST['device_id'] !== '' ? (int)$_POST['device_id'] : null;
            $name = trim($_POST['device_name'] ?? '');
            $token = trim($_POST['device_token'] ?? '');
            $active = isset($_POST['device_status']) ? ((int)$_POST['device_status'] ? 1 : 0) : 0;

            if ($name === '' || $token === '') {
                echo json_encode(['ok'=>0,'error'=>'Nama dan token wajib diisi']); exit;
            }

            // transaction
            $pdo->beginTransaction();
            if ($id) {
                // update - ensure token unique for other rows
                $chk = $pdo->prepare("SELECT id FROM api_keys WHERE api_key = :token AND id <> :id LIMIT 1");
                $chk->execute([':token'=>$token, ':id'=>$id]);
                if ($chk->fetch()) {
                    $pdo->rollBack();
                    echo json_encode(['ok'=>0,'error'=>'Token sudah digunakan oleh device lain']); exit;
                }
                $upd = $pdo->prepare("UPDATE api_keys SET name = :name, api_key = :token, active = :act WHERE id = :id");
                $upd->execute([':name'=>$name, ':token'=>$token, ':act'=>$active, ':id'=>$id]);
            } else {
                // insert - ensure token unique
                $chk = $pdo->prepare("SELECT id FROM api_keys WHERE api_key = :token LIMIT 1");
                $chk->execute([':token'=>$token]);
                if ($chk->fetch()) {
                    $pdo->rollBack();
                    echo json_encode(['ok'=>0,'error'=>'Token sudah digunakan']); exit;
                }
                $ins = $pdo->prepare("INSERT INTO api_keys (name, api_key, active, created_at) VALUES (:name,:token,:act,NOW())");
                $ins->execute([':name'=>$name, ':token'=>$token, ':act'=>$active]);
            }
            $pdo->commit();
            echo json_encode(['ok'=>1]);
            exit;
        }

        if ($action === 'delete_device') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) { echo json_encode(['ok'=>0,'error'=>'ID tidak valid']); exit; }
            $del = $pdo->prepare("DELETE FROM api_keys WHERE id = :id");
            $del->execute([':id'=>$id]);
            echo json_encode(['ok'=>1]);
            exit;
        }

        echo json_encode(['ok'=>0,'error'=>'Unknown action']);
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("devices.php AJAX error: " . $e->getMessage());
        echo json_encode(['ok'=>0,'error'=>'Server error']);
        exit;
    }
}

// -------------------------
// Page render
// -------------------------
$pageTitle = "Manajemen Device";
$smsg = flash_get('success');
$emsg = flash_get('error');

// fetch devices
try {
    // Use prepared statement for good measure (even though no user input here)
    $stmt = $pdo->prepare("SELECT id, name, api_key, active FROM api_keys ORDER BY created_at DESC");
    $stmt->execute();
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error loading devices: " . $e->getMessage());
    // For debugging only: print an HTML comment so you can see it in View Source
    echo "<!-- DB ERROR: " . htmlspecialchars($e->getMessage()) . " -->\n";
    $devices = [];
}

$token = function_exists('generate_csrf_token') ? generate_csrf_token() : '';
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
    .table-fixed-height { max-height:60vh; overflow:auto; }
    code.token { background:#f8f9fa; padding:4px 6px; display:inline-block; border-radius:4px; }
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

        <div class="card shadow mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Manajemen Device (ESP32)</strong>
            <button class="btn btn-sm btn-primary" id="btnAddDevice" data-toggle="modal" data-target="#modalDevice">+ Tambah Device</button>
          </div>
          <div class="card-body">
            <div class="table-responsive table-fixed-height">
              <table id="tableDevices" class="table table-bordered" width="100%" cellspacing="0">
                <thead class="text-center">
                  <tr><th>No</th><th>Nama Device</th><th>Token (api_key)</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                  <?php if (empty($devices)): ?>
                    <tr><td colspan="6" class="text-center text-muted">Belum ada device</td></tr>
                  <?php else: $no=1; foreach($devices as $d): ?>
                    <tr>
                      <td class="text-center"><?= $no++ ?></td>
                      <td><?= htmlspecialchars($d['name']) ?></td>
                      <td><code class="token"><?= htmlspecialchars($d['api_key']) ?></code></td>
                      <td class="text-center">
                        <?php if ($d['active']): ?>
                          <span class="badge badge-success">Active</span>
                        <?php else: ?>
                          <span class="badge badge-secondary">Inactive</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-center">
                        <button class="btn btn-sm btn-warning btnEditDevice"
                                data-id="<?= (int)$d['id'] ?>"
                                data-name="<?= htmlspecialchars($d['name'],ENT_QUOTES) ?>"
                                data-token="<?= htmlspecialchars($d['api_key'],ENT_QUOTES) ?>"
                                data-status="<?= (int)$d['active'] ?>">Edit</button>
                        <button class="btn btn-sm btn-danger btnDelDevice"
                                data-id="<?= (int)$d['id'] ?>"
                                data-name="<?= htmlspecialchars($d['name'],ENT_QUOTES) ?>">Hapus</button>
                      </td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
            <div class="small text-muted mt-2">Note: Token digunakan oleh ESP32 (header <code>X-API-Key</code>).</div>
          </div>
        </div>

      </div> <!-- container -->
    </div> <!-- content -->

    <?php include "footer.php"; ?>
  </div>
</div>

<!-- Modal Device (Tambah/Edit) -->
<div class="modal fade" id="modalDevice" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="formDevice">
        <div class="modal-header">
          <h5 class="modal-title" id="modalDeviceTitle">Tambah Device</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="save_device">
          <input type="hidden" name="device_id" id="device_id">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($token) ?>">
          <div class="form-group">
            <label>Nama Device</label>
            <input type="text" name="device_name" id="device_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Token (api_key)</label>
            <div class="input-group">
              <input type="text" name="device_token" id="device_token" class="form-control" required>
              <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button" id="btnGenToken">Generate</button>
              </div>
            </div>
            <small class="form-text text-muted">Token akan dipakai di header <code>X-API-Key</code> oleh device.</small>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="device_status" id="device_status" class="form-control">
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-dismiss="modal">Tutup</button>
          <button type="submit" class="btn btn-primary" id="saveDeviceBtn">Simpan Device</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Confirm Delete -->
<div class="modal fade" id="modalConfirmDelete" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body">
        <h5 id="confirmDeleteText">Yakin ingin menghapus?</h5>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button class="btn btn-danger" id="confirmDeleteBtn">Hapus</button>
      </div>
    </div>
  </div>
</div>

<!-- SCRIPTS -->
<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/datatables/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>
<script>
$(document).ready(function(){
  // Initialize DataTable safely: if multiple inits happen, destroy first
  var table = null;
  try {
    if ($.fn.dataTable) {
      table = $('#tableDevices').DataTable({ paging: true, pageLength: 10, searching: true });
    } else {
      console.warn('DataTables not loaded (check paths).');
    }
  } catch(e) {
    console.error('DataTables init error:', e);
  }

  $('#btnAddDevice').on('click', function(){
    $('#modalDeviceTitle').text('Tambah Device');
    $('#device_id').val('');
    $('#device_name').val('');
    $('#device_token').val('');
    $('#device_status').val('1');
    $('#modalDevice').modal('show');
  });

  $(document).on('click', '.btnEditDevice', function(){
    $('#modalDeviceTitle').text('Edit Device');
    $('#device_id').val($(this).data('id'));
    $('#device_name').val($(this).data('name'));
    $('#device_token').val($(this).data('token'));
    $('#device_status').val($(this).data('status'));
    $('#modalDevice').modal('show');
  });

  $('#btnGenToken').on('click', function(){ $('#device_token').val(generateToken(24)); });

  $('#formDevice').on('submit', function(e){
    e.preventDefault();
    var $btn = $('#saveDeviceBtn').prop('disabled', true).text('Menyimpan...');
    var payload = $(this).serialize();
    $.post(window.location.pathname, payload, function(res){
      if (res && res.ok) {
        showAlert('success','Device tersimpan.');
        setTimeout(()=> location.reload(),700);
      } else {
        showAlert('danger', res ? (res.error||'Gagal menyimpan') : 'Gagal menyimpan (no response)');
      }
    }, 'json').fail(function(){ showAlert('danger','Gagal menyimpan (server)'); })
    .always(function(){ $btn.prop('disabled',false).text('Simpan Device'); });
  });

  var pendingDelete = null;
  $(document).on('click', '.btnDelDevice', function(){
    pendingDelete = { id: $(this).data('id'), name: $(this).data('name') };
    $('#confirmDeleteText').html('Yakin ingin menghapus device: <strong>'+pendingDelete.name+'</strong> ?');
    $('#modalConfirmDelete').modal('show');
  });

  $('#confirmDeleteBtn').on('click', function(){
    if (!pendingDelete) return;
    var $btn = $(this);
    $btn.prop('disabled', true).text('Menghapus...');
    $.post(window.location.pathname, { action:'delete_device', id: pendingDelete.id, csrf: <?= json_encode($token) ?> }, function(res){
      if (res && res.ok) {
        showAlert('success','Device dihapus.');
        setTimeout(()=> location.reload(),700);
      } else {
        showAlert('danger', res ? (res.error||'Gagal hapus') : 'Gagal hapus (no response)');
      }
    }, 'json').fail(function(){ showAlert('danger','Gagal hapus (server)'); })
    .always(function(){ $btn.prop('disabled', false).text('Hapus'); $('#modalConfirmDelete').modal('hide'); pendingDelete = null; });
  });

  function generateToken(len=16){
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let out = '';
    for(let i=0;i<len;i++) out += chars.charAt(Math.floor(Math.random()*chars.length));
    return out;
  }

  function showAlert(type, message){
    const html = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
      ${message}
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>`;
    $('#alert-area').html(html);
    setTimeout(()=> $('.alert').alert('close'), 4000);
  }
});
</script>
</body>
</html>
