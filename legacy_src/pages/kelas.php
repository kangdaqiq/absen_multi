<?php
// pages/classes.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/function.php'; // <-- gunakan helper yang sudah ada

// Pastikan user login
require_login('../login.php');

// ----------------- AJAX handlers (POST) -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    // CSRF token dari includes/functions.php
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid. Silakan muat ulang halaman.']);
        exit;
    }

    $action = $_POST['action'];

    try {
        if ($action === 'save') {
            // Simpan (create/update)
            $kelas_id = isset($_POST['kelas_id']) ? trim($_POST['kelas_id']) : '';
            $kelas_name = isset($_POST['kelas_name']) ? trim($_POST['kelas_name']) : '';

            if ($kelas_name === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Nama kelas wajib diisi']);
                exit;
            }
            if (mb_strlen($kelas_name) > 191) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Nama kelas terlalu panjang']);
                exit;
            }

            // cek duplikat nama (case-insensitive)
            $stmt = $pdo->prepare("SELECT id FROM kelas WHERE LOWER(nama_kelas) = LOWER(:nama)");
            $stmt->execute([':nama' => $kelas_name]);
            $exists = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($kelas_id === '' || $kelas_id == '0') {
                // create
                if ($exists) {
                    http_response_code(409);
                    echo json_encode(['success' => false, 'message' => 'Nama kelas sudah ada']);
                    exit;
                }
                $stmt = $pdo->prepare("INSERT INTO kelas (nama_kelas) VALUES (:nama)");
                $stmt->execute([':nama' => $kelas_name]);
                echo json_encode(['success' => true, 'message' => 'Kelas berhasil ditambahkan']);
                exit;
            } else {
                // update
                $id = (int)$kelas_id;
                $stmt = $pdo->prepare("SELECT id FROM kelas WHERE id = :id");
                $stmt->execute([':id' => $id]);
                if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Kelas tidak ditemukan']);
                    exit;
                }
                if ($exists && ((int)$exists['id'] !== $id)) {
                    http_response_code(409);
                    echo json_encode(['success' => false, 'message' => 'Nama kelas sudah dipakai oleh kelas lain']);
                    exit;
                }
                $stmt = $pdo->prepare("UPDATE kelas SET nama_kelas = :nama WHERE id = :id");
                $stmt->execute([':nama' => $kelas_name, ':id' => $id]);
                echo json_encode(['success' => true, 'message' => 'Kelas berhasil diperbarui']);
                exit;
            }

        } elseif ($action === 'delete') {
            // Hapus
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
                exit;
            }

            // cek ada atau tidak
            $stmt = $pdo->prepare("SELECT id, nama_kelas FROM kelas WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Kelas tidak ditemukan']);
                exit;
            }

            $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE kelas = :nama_kelas");
            $stmt2->execute([':nama_kelas' => $row['nama_kelas']]);
            $cnt = (int)$stmt2->fetchColumn();
            if ($cnt > 0) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus: masih ada siswa pada kelas ini']);
                exit;
            }


            $stmt = $pdo->prepare("DELETE FROM kelas WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Kelas berhasil dihapus']);
            exit;

        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal']);
            exit;
        }
    } catch (Exception $e) {
        error_log("classes.php (AJAX) error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server']);
        exit;
    }
}
// ----------------- End AJAX handlers -----------------

// Jika bukan AJAX POST, ambil daftar kelas untuk ditampilkan
$kelas_list = [];
try {
    $stmt = $pdo->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas ASC");
    $kelas_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error loading kelas: " . $e->getMessage());
    $kelas_list = [];
}

// buat token CSRF via helper
$csrf_token = generate_csrf_token();

$pageTitle = "Manajemen Kelas";
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
  <style>.small-muted{font-size:.9rem;color:#6c757d;}</style>
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

        <div id="alert-area"></div>

        <div class="card shadow mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Manajemen Kelas</strong>
            <button class="btn btn-sm btn-primary" id="btnAddKelas" data-toggle="modal" data-target="#modalKelas">+ Tambah Kelas</button>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table id="tableKelas" class="table table-bordered" width="100%" cellspacing="0">
                <thead class="text-center">
                  <tr><th>No</th><th>Nama Kelas</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                  <?php if (empty($kelas_list)): ?>
                    <tr><td colspan="3" class="text-center text-muted">Belum ada kelas</td></tr>
                  <?php else:
                    $no = 1;
                    foreach ($kelas_list as $k): ?>
                      <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES) ?></td>
                        <td class="text-center">
                          <button class="btn btn-sm btn-warning btnEditKelas"
                                  data-id="<?= (int)$k['id'] ?>" data-nama="<?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES) ?>">Edit</button>
                          <button class="btn btn-sm btn-danger btnHapusKelas"
                                  data-id="<?= (int)$k['id'] ?>" data-nama="<?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES) ?>">Hapus</button>
                        </td>
                      </tr>
                    <?php endforeach;
                  endif; ?>
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

<!-- Modal Kelas (Tambah/Edit) -->
<div class="modal fade" id="modalKelas" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="formKelas">
        <div class="modal-header">
          <h5 class="modal-title" id="modalKelasTitle">Tambah Kelas</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="kelas_id" id="kelas_id">
          <input type="hidden" name="csrf_token" id="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES) ?>">
          <div class="form-group">
            <label>Nama Kelas</label>
            <input type="text" name="kelas_name" id="kelas_name" class="form-control" required maxlength="191">
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-dismiss="modal">Tutup</button>
          <button type="submit" class="btn btn-primary" id="saveKelasBtn">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Confirm Delete (shared) -->
<div class="modal fade" id="modalConfirmDelete" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body">
        <h5 id="confirmDeleteText">Yakin ingin menghapus?</h5>
        <input type="hidden" id="delete_csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES) ?>">
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
  $('#tableKelas').DataTable({ paging: true, pageLength: 10, searching: true, ordering: false });

  $('#btnAddKelas').on('click', function(){
    $('#modalKelasTitle').text('Tambah Kelas');
    $('#kelas_id').val('');
    $('#kelas_name').val('');
    // pastikan token ada di form (token dibuat saat page render)
    $('#csrf_token').val($('#delete_csrf_token').val());
    $('#modalKelas').modal('show');
  });

  $(document).on('click', '.btnEditKelas', function(){
    const id = $(this).data('id');
    $('#modalKelasTitle').text('Edit Kelas');
    $('#kelas_id').val(id);
    $('#kelas_name').val($(this).data('nama'));
    $('#csrf_token').val($('#delete_csrf_token').val());
    $('#modalKelas').modal('show');
  });

  // submit Kelas (AJAX to same page)
  $('#formKelas').on('submit', function(e){
    e.preventDefault();
    const payload = {
      action: 'save',
      kelas_id: $('#kelas_id').val(),
      kelas_name: $('#kelas_name').val(),
      csrf_token: $('#csrf_token').val()
    };
    $('#saveKelasBtn').prop('disabled', true).text('Menyimpan...');
    $.post(window.location.pathname, payload, function(res){
      if (res && res.success) {
        showAlert('success', res.message || 'Pengaturan kelas tersimpan.');
        setTimeout(()=> location.reload(), 600);
      } else {
        showAlert('danger', (res && res.message) ? res.message : 'Gagal menyimpan kelas.');
      }
    }, 'json').fail(function(xhr){
      let msg = 'Gagal menyimpan kelas (server error).';
      try { const r = JSON.parse(xhr.responseText); if (r && r.message) msg = r.message; } catch(e){}
      showAlert('danger', msg);
    }).always(function(){
      $('#saveKelasBtn').prop('disabled', false).text('Simpan');
    });
  });

  let pendingDelete = null;
  $(document).on('click', '.btnHapusKelas', function(){
    pendingDelete = { id: $(this).data('id'), name: $(this).data('nama') };
    $('#confirmDeleteText').html('Yakin ingin menghapus kelas: <strong>'+pendingDelete.name+'</strong> ?');
    // set delete token
    $('#delete_csrf_token').val($('#csrf_token').val());
    $('#modalConfirmDelete').modal('show');
  });

  $('#confirmDeleteBtn').on('click', function(){
    if(!pendingDelete) return;
    $('#modalConfirmDelete').modal('hide');
    const payload = {
      action: 'delete',
      id: pendingDelete.id,
      csrf_token: $('#delete_csrf_token').val()
    };
    $.post(window.location.pathname, payload, function(res){
      if (res && res.success) {
        showAlert('success', res.message || 'Kelas berhasil dihapus.');
        setTimeout(()=> location.reload(), 600);
      } else {
        showAlert('danger', (res && res.message) ? res.message : 'Gagal menghapus.');
      }
    }, 'json').fail(function(xhr){
      let msg = 'Gagal menghapus (server error).';
      try { const r = JSON.parse(xhr.responseText); if (r && r.message) msg = r.message; } catch(e){}
      showAlert('danger', msg);
    });
    pendingDelete = null;
  });

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
