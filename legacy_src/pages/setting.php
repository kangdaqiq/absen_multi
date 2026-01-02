<?php
// pages/settings.php
$pageTitle = "Pengaturan Sistem";
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?></title>

  <!-- SB Admin 2 + FontAwesome + DataTables + Select2 -->
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../css/sb-admin-2.min.css" rel="stylesheet">
  <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

  <style>
    .small-muted { font-size:.9rem; color:#6c757d; }
    .table-fixed-height { max-height:420px; overflow:auto; }
    /* compact form spacing */
    .form-inline .form-control { margin-right: .5rem; }
  </style>
</head>
<body id="page-top">
<div id="wrapper">
  <?php include "sidebar.php"; ?>

  <div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
      <?php include "topbar.php"; ?>

      <div class="container-fluid">
        <!-- Page Title -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
          <h1 class="h3 text-gray-800"><?= htmlspecialchars($pageTitle) ?></h1>
        </div>

        <!-- Alert placeholder -->
        <div id="alert-area"></div>

        <div class="row">

          <!-- LEFT: Kelas -->
          <div class="col-lg-5">
            <div class="card shadow mb-4">
              <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Manajemen Kelas</strong>
                <button class="btn btn-sm btn-primary" id="btnAddKelas" data-toggle="modal" data-target="#modalKelas">+ Tambah Kelas</button>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table id="tableKelas" class="table table-bordered" width="100%" cellspacing="0">
                    <thead class="text-center">
                      <tr><th>No</th><th>Nama Kelas</th><th>Deskripsi</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                      <!-- contoh statis; idealnya di-load via AJAX -->
                      <tr>
                        <td class="text-center">1</td>
                        <td>Kelas 1</td>
                        <td>RPL / X IPA</td>
                        <td class="text-center">
                          <button class="btn btn-sm btn-warning btnEditKelas"
                                  data-id="1" data-nama="Kelas 1" data-desc="RPL / X IPA">Edit</button>
                          <button class="btn btn-sm btn-danger btnHapusKelas" data-id="1" data-nama="Kelas 1">Hapus</button>
                        </td>
                      </tr>
                      <tr>
                        <td class="text-center">2</td>
                        <td>Kelas 2</td>
                        <td>TKJ / XI</td>
                        <td class="text-center">
                          <button class="btn btn-sm btn-warning btnEditKelas"
                                  data-id="2" data-nama="Kelas 2" data-desc="TKJ / XI">Edit</button>
                          <button class="btn btn-sm btn-danger btnHapusKelas" data-id="2" data-nama="Kelas 2">Hapus</button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Device summary small -->
            <div class="card shadow mb-4">
              <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Ringkasan Device</strong>
                <a href="pages/device_management.php" class="btn btn-sm btn-secondary">Kelola Device</a>
              </div>
              <div class="card-body">
                <div class="small-muted">Total Device Terdaftar:</div>
                <div class="h5">3</div>
                <div class="small-muted mt-2">Device Online:</div>
                <div class="h5 text-success">2</div>
                <div class="small-muted mt-2">Device Offline:</div>
                <div class="h5 text-danger">1</div>
              </div>
            </div>

          </div> <!-- end col -->

          <!-- MIDDLE / RIGHT: Jam masuk/pulang dan Device management -->
          <div class="col-lg-7">

            <!-- Jam & Policy -->
            <div class="card shadow mb-4">
              <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Pengaturan Jam Kerja / Hari</strong>
                <button class="btn btn-sm btn-success" id="btnSaveSchedule">Simpan Pengaturan</button>
              </div>
              <div class="card-body">
                <div class="small-muted mb-3">Atur jam masuk dan jam pulang per hari. Sistem akan menggunakan jam ini untuk mendeteksi keterlambatan.</div>

                <form id="formSchedule">
                  <table class="table table-sm">
                    <thead>
                      <tr>
                        <th>Hari</th>
                        <th>Aktif</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Toleransi (menit)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $days = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
                      // contoh default values (di production nilai diambil dari DB)
                      $defaults = [
                        ['on'=>1,'in'=>'07:30','out'=>'15:00','tol'=>5],
                        ['on'=>1,'in'=>'07:30','out'=>'15:00','tol'=>5],
                        ['on'=>1,'in'=>'07:30','out'=>'15:00','tol'=>5],
                        ['on'=>1,'in'=>'07:30','out'=>'15:00','tol'=>5],
                        ['on'=>1,'in'=>'07:30','out'=>'13:00','tol'=>10],
                        ['on'=>0,'in'=>'08:00','out'=>'12:00','tol'=>0],
                        ['on'=>0,'in'=>'00:00','out'=>'00:00','tol'=>0],
                      ];
                      foreach($days as $i=>$d):
                        $conf = $defaults[$i];
                      ?>
                      <tr>
                        <td><?= $d ?></td>
                        <td class="text-center">
                          <input type="checkbox" class="form-control-sm" name="day_on[<?= $i ?>]" <?= $conf['on'] ? 'checked' : '' ?>>
                        </td>
                        <td>
                          <input type="time" class="form-control form-control-sm" name="day_in[<?= $i ?>]" value="<?= $conf['in'] ?>">
                        </td>
                        <td>
                          <input type="time" class="form-control form-control-sm" name="day_out[<?= $i ?>]" value="<?= $conf['out'] ?>">
                        </td>
                        <td>
                          <input type="number" class="form-control form-control-sm" name="day_tol[<?= $i ?>]" value="<?= $conf['tol'] ?>" min="0" style="width:100px;">
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </form>
                <div class="small-muted">Tips: set toleransi untuk memperbolehkan keterlambatan beberapa menit (mis. toleransi 5 = terlambat setelah +5 menit).</div>
              </div>
            </div>

            <!-- Device management (simple list, modal add/edit) -->
            <div class="card shadow mb-4">
              <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Manajemen Device (ESP32)</strong>
                <button class="btn btn-sm btn-primary" id="btnAddDevice" data-toggle="modal" data-target="#modalDevice">+ Tambah Device</button>
              </div>
              <div class="card-body">
                <div class="table-responsive table-fixed-height">
                  <table id="tableDevices" class="table table-bordered" width="100%" cellspacing="0">
                    <thead class="text-center">
                      <tr><th>No</th><th>Nama Device</th><th>Token</th><th>Lokasi</th><th>Status</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                      <!-- contoh data -->
                      <tr>
                        <td class="text-center">1</td>
                        <td>ESP32-Finger-01</td>
                        <td><code>abcd-ef12-3456</code></td>
                        <td>Laboratorium</td>
                        <td class="text-center"><span class="badge badge-success">Online</span></td>
                        <td class="text-center">
                          <button class="btn btn-sm btn-warning btnEditDevice" 
                                  data-id="1" data-name="ESP32-Finger-01" data-token="abcd-ef12-3456" data-loc="Laboratorium" data-status="1">Edit</button>
                          <button class="btn btn-sm btn-danger btnDelDevice" data-id="1" data-name="ESP32-Finger-01">Hapus</button>
                        </td>
                      </tr>
                      <tr>
                        <td class="text-center">2</td>
                        <td>ESP32-Finger-02</td>
                        <td><code>zz99-yy88-77aa</code></td>
                        <td>Ruang BK</td>
                        <td class="text-center"><span class="badge badge-secondary">Offline</span></td>
                        <td class="text-center">
                          <button class="btn btn-sm btn-warning btnEditDevice" 
                                  data-id="2" data-name="ESP32-Finger-02" data-token="zz99-yy88-77aa" data-loc="Ruang BK" data-status="0">Edit</button>
                          <button class="btn btn-sm btn-danger btnDelDevice" data-id="2" data-name="ESP32-Finger-02">Hapus</button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="small-muted mt-2">Note: Device token digunakan untuk autentikasi API (ESP32 → server).</div>
              </div>
            </div>

          </div> <!-- end right col -->

        </div> <!-- end row -->

      </div> <!-- container -->
    </div> <!-- content -->

    <?php include "footer.php"; ?>
  </div>
</div>

<!-- Modals -->
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
          <div class="form-group">
            <label>Nama Kelas</label>
            <input type="text" name="kelas_name" id="kelas_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Deskripsi</label>
            <input type="text" name="kelas_desc" id="kelas_desc" class="form-control">
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
          <input type="hidden" name="device_id" id="device_id">
          <div class="form-group">
            <label>Nama Device</label>
            <input type="text" name="device_name" id="device_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Token (autentikasi)</label>
            <div class="input-group">
              <input type="text" name="device_token" id="device_token" class="form-control" required>
              <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button" id="btnGenToken">Generate</button>
              </div>
            </div>
            <small class="form-text text-muted">Token akan dipakai untuk otentikasi ESP32 ke API.</small>
          </div>
          <div class="form-group">
            <label>Lokasi</label>
            <input type="text" name="device_loc" id="device_loc" class="form-control">
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

<!-- Confirm Delete Kelas -->
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
<script src="../js/sb-admin-2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function(){
  // DataTables init
  $('#tableKelas').DataTable({ paging: true, pageLength: 10, searching: true, ordering: false });
  $('#tableDevices').DataTable({ paging: true, pageLength: 10, searching: true });

  // --- KELAS: modal add/edit ---
  $('#btnAddKelas').on('click', function(){
    $('#modalKelasTitle').text('Tambah Kelas');
    $('#kelas_id').val('');
    $('#kelas_name').val('');
    $('#kelas_desc').val('');
    $('#modalKelas').modal('show');
  });

  $(document).on('click', '.btnEditKelas', function(){
    const id = $(this).data('id');
    $('#modalKelasTitle').text('Edit Kelas');
    $('#kelas_id').val(id);
    $('#kelas_name').val($(this).data('nama'));
    $('#kelas_desc').val($(this).data('desc'));
    $('#modalKelas').modal('show');
  });

  // submit Kelas (AJAX placeholder)
  $('#formKelas').on('submit', function(e){
    e.preventDefault();
    const payload = $(this).serialize();
    $('#saveKelasBtn').prop('disabled', true).text('Menyimpan...');
    // contoh AJAX (ganti url)
    $.post('../api/save_class.php', payload)
      .done(function(res){
        // res expected { success:true, message:'' }
        // For demo, just show success and reload page
        showAlert('success','Pengaturan kelas tersimpan.');
        setTimeout(()=> location.reload(), 800);
      })
      .fail(function(){ showAlert('danger','Gagal menyimpan kelas (AJAX).'); })
      .always(function(){ $('#saveKelasBtn').prop('disabled', false).text('Simpan'); });
  });

  // delete kelas
  let pendingDelete = null;
  $(document).on('click', '.btnHapusKelas', function(){
    pendingDelete = { type:'kelas', id: $(this).data('id'), name: $(this).data('nama') };
    $('#confirmDeleteText').html('Yakin ingin menghapus kelas: <strong>'+pendingDelete.name+'</strong> ?');
    $('#modalConfirmDelete').modal('show');
  });

  // --- SCHEDULE: save ---
  $('#btnSaveSchedule').on('click', function(){
    const data = $('#formSchedule').serialize();
    $('#btnSaveSchedule').prop('disabled', true).text('Menyimpan...');
    $.post('../api/save_schedule.php', data)
      .done(function(res){
        showAlert('success','Pengaturan jam berhasil disimpan.');
      })
      .fail(function(){ showAlert('danger','Gagal menyimpan pengaturan jam.'); })
      .always(function(){ $('#btnSaveSchedule').prop('disabled', false).text('Simpan Pengaturan'); });
  });

  // --- DEVICE: modal add/edit ---
  $('#btnAddDevice').on('click', function(){
    $('#modalDeviceTitle').text('Tambah Device');
    $('#device_id').val('');
    $('#device_name').val('');
    $('#device_token').val('');
    $('#device_loc').val('');
    $('#device_status').val('1');
    $('#modalDevice').modal('show');
  });

  $(document).on('click', '.btnEditDevice', function(){
    $('#modalDeviceTitle').text('Edit Device');
    $('#device_id').val($(this).data('id'));
    $('#device_name').val($(this).data('name'));
    $('#device_token').val($(this).data('token'));
    $('#device_loc').val($(this).data('loc'));
    $('#device_status').val($(this).data('status'));
    $('#modalDevice').modal('show');
  });

  // generate token
  $('#btnGenToken').on('click', function(){
    const t = generateToken();
    $('#device_token').val(t);
  });

  // device save (AJAX placeholder)
  $('#formDevice').on('submit', function(e){
    e.preventDefault();
    const payload = $(this).serialize();
    $('#saveDeviceBtn').prop('disabled',true).text('Menyimpan...');
    $.post('../api/save_device.php', payload)
      .done(function(){ showAlert('success','Device tersimpan.'); setTimeout(()=> location.reload(),800); })
      .fail(function(){ showAlert('danger','Gagal menyimpan device.'); })
      .always(function(){ $('#saveDeviceBtn').prop('disabled',false).text('Simpan Device'); });
  });

  // delete device
  $(document).on('click', '.btnDelDevice', function(){
    pendingDelete = { type:'device', id: $(this).data('id'), name: $(this).data('name') };
    $('#confirmDeleteText').html('Yakin ingin menghapus device: <strong>'+pendingDelete.name+'</strong> ?');
    $('#modalConfirmDelete').modal('show');
  });

  // confirm delete action
  $('#confirmDeleteBtn').on('click', function(){
    if(!pendingDelete) return;
    $('#modalConfirmDelete').modal('hide');
    // example AJAX delete (ganti url)
    $.post('../api/delete_item.php', { type: pendingDelete.type, id: pendingDelete.id })
      .done(function(){ showAlert('success', pendingDelete.type+' berhasil dihapus.'); setTimeout(()=> location.reload(),800); })
      .fail(function(){ showAlert('danger','Gagal menghapus.'); });
    pendingDelete = null;
  });

  // helper: token generator
  function generateToken(len=16){
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let out = '';
    for(let i=0;i<len;i++) out += chars.charAt(Math.floor(Math.random()*chars.length));
    return out;
  }

  // helper: alert
  function showAlert(type, message){
    const html = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
      ${message}
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>`;
    $('#alert-area').html(html);
    // auto close after 4s
    setTimeout(()=> $('.alert').alert('close'), 4000);
  }

}); // end ready
</script>
</body>
</html>
