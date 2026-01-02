<?php
// halaman: data_siswa.php
// Pastikan file ini berada di folder yang sama seperti sebelumnya.
// Memuat DB & helper
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/function.php'; // untuk CSRF & flash jika tersedia
require_once __DIR__ . '/../vendor/autoload.php';   // untuk Import Excel

use PhpOffice\PhpSpreadsheet\IOFactory;

// Pastikan user login; jika tidak akan redirect ke index.php
require_login('../login.php');

$user = current_user();

/* =================================================================================
   PROCESS LOGIC (POST REQUESTS)
   ================================================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validasi CSRF Token
    $token = $_POST['token_siswa'] ?? '';
    if (!verify_csrf_token($token)) {
        flash_set('error', 'Token CSRF tidak valid. Silakan refresh halaman.');
        header("Location: data-siswa.php");
        exit;
    }

    $action = $_POST['action'] ?? '';

    // --- 1. TAMBAH SISWA ---
    if ($action === 'add_siswa') {
        try {
            $nama     = trim($_POST['nama'] ?? '');
            $nis      = trim($_POST['nis'] ?? '');
            $kelas_id = (int)($_POST['kelas'] ?? 0);
            $no_wa    = normalize_wa($_POST['no_wa'] ?? null);

            if ($nama === '' || $nis === '' || $kelas_id === 0) {
                flash_set('error', 'Nama, NIS, dan Kelas wajib diisi');
                header("Location: data-siswa.php"); exit;
            }

            // Cek Duplikasi NIS
            $cekNis = $pdo->prepare("SELECT id FROM siswa WHERE nis = :nis LIMIT 1");
            $cekNis->execute([':nis' => $nis]);
            if ($cekNis->fetch()) {
                flash_set('error', 'NIS sudah terdaftar');
                header("Location: data-siswa.php"); exit;
            }

            // Cek Duplikasi WA
            if ($no_wa !== null) {
                $cekWa = $pdo->prepare("SELECT id FROM siswa WHERE no_wa = :wa LIMIT 1");
                $cekWa->execute([':wa' => $no_wa]);
                if ($cekWa->fetch()) {
                    flash_set('error', 'Nomor WhatsApp sudah digunakan siswa lain');
                    header("Location: data-siswa.php"); exit;
                }
            }

            // Insert
            $stmt = $pdo->prepare("INSERT INTO siswa (nama, nis, kelas_id, no_wa) VALUES (:nama, :nis, :kelas_id, :no_wa)");
            $stmt->execute([':nama' => $nama, ':nis' => $nis, ':kelas_id' => $kelas_id, ':no_wa' => $no_wa]);

            flash_set('success', 'Data siswa berhasil ditambahkan');

        } catch (Exception $e) {
            flash_set('error', 'Gagal menambahkan data siswa: ' . $e->getMessage());
        }
        header("Location: data-siswa.php");
        exit;
    }

    // --- 2. EDIT SISWA ---
    if ($action === 'edit_siswa') {
        try {
            $id       = (int)($_POST['id'] ?? 0);
            $nama     = trim($_POST['nama'] ?? '');
            $nis      = trim($_POST['nis'] ?? '');
            $kelas_id = (int)($_POST['kelas'] ?? 0);
            $no_wa    = normalize_wa($_POST['no_wa'] ?? null);
            $uid_rfid = trim($_POST['uid_rfid'] ?? '');

            if ($id === 0 || $nama === '' || $nis === '' || $kelas_id === 0) {
                flash_set('error', 'Data tidak lengkap');
                header("Location: data-siswa.php"); exit;
            }

            // Cek Duplikasi NIS (Kecuali diri sendiri)
            $cekNis = $pdo->prepare("SELECT id FROM siswa WHERE nis = :nis AND id != :id LIMIT 1");
            $cekNis->execute([':nis' => $nis, ':id' => $id]);
            if ($cekNis->fetch()) {
                flash_set('error', 'NIS sudah digunakan siswa lain');
                header("Location: data-siswa.php"); exit;
            }

            // Cek Duplikasi WA
            if ($no_wa !== null) {
                $cekWa = $pdo->prepare("SELECT id FROM siswa WHERE no_wa = :wa AND id != :id LIMIT 1");
                $cekWa->execute([':wa' => $no_wa, ':id' => $id]);
                if ($cekWa->fetch()) {
                    flash_set('error', 'Nomor WhatsApp sudah digunakan siswa lain');
                    header("Location: data-siswa.php"); exit;
                }
            }

            // Update
            $stmt = $pdo->prepare("
                UPDATE siswa SET
                    nama     = :nama,
                    nis      = :nis,
                    kelas_id = :kelas_id,
                    no_wa    = :no_wa,
                    uid_rfid = :uid_rfid
                WHERE id = :id
            ");
            $stmt->execute([
                ':nama' => $nama, ':nis' => $nis, ':kelas_id' => $kelas_id, 
                ':no_wa' => $no_wa, ':uid_rfid' => $uid_rfid, ':id' => $id
            ]);

            flash_set('success', 'Data siswa berhasil diperbarui');

        } catch (Exception $e) {
            flash_set('error', 'Gagal memperbarui data siswa: ' . $e->getMessage());
        }
        header("Location: data-siswa.php");
        exit;
    }

    // --- 3. HAPUS SISWA ---
    if ($action === 'delete_siswa') {
        try {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === 0) {
                flash_set('error', 'ID siswa tidak valid');
                header("Location: data-siswa.php"); exit;
            }

            // Cek exist
            $cek = $pdo->prepare("SELECT id FROM siswa WHERE id = :id LIMIT 1");
            $cek->execute([':id' => $id]);
            if (!$cek->fetch()) {
                flash_set('error', 'Data siswa tidak ditemukan');
                header("Location: data-siswa.php"); exit;
            }

            // Hapus
            $stmt = $pdo->prepare("DELETE FROM siswa WHERE id = :id");
            $stmt->execute([':id' => $id]);

            flash_set('success', 'Data siswa berhasil dihapus');

        } catch (Exception $e) {
            flash_set('error', 'Gagal menghapus data siswa: ' . $e->getMessage());
        }
        header("Location: data-siswa.php");
        exit;
    }

    // --- 4. IMPORT SISWA ---
    if ($action === 'import_siswa') {
        if (!isset($_FILES['fileExcel']['error']) || is_array($_FILES['fileExcel']['error'])) {
            flash_set('error', 'Parameter file tidak valid.');
            header('Location: data-siswa.php'); exit;
        }

        if ($_FILES['fileExcel']['error'] !== UPLOAD_ERR_OK) {
            $msg = 'Terjadi kesalahan upload: ' . $_FILES['fileExcel']['error'];
            flash_set('error', $msg);
            header('Location: data-siswa.php'); exit;
        }

        $filepath = $_FILES['fileExcel']['tmp_name'];

        try {
            $spreadsheet = IOFactory::load($filepath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $countSuccess = 0;
            $countSkip = 0;
            $firstRow = true;
            $kelasMap = []; 
            $debugLog = [];

            $sqlInsert = "INSERT INTO siswa (nama, nis, kelas_id, no_wa, created_at) VALUES (:nm, :nis, :kid, :wa, NOW())";
            $stmtInsert = $pdo->prepare($sqlInsert);

            $sqlCheck = "SELECT id FROM siswa WHERE nis = :nis LIMIT 1";
            $stmtCheck = $pdo->prepare($sqlCheck);
            
            $rowNum = 0;

            foreach ($rows as $row) {
                $rowNum++; 
                if ($firstRow) { $firstRow = false; continue; } // Skip header

                $nama = trim($row[0] ?? '');
                $nis = trim($row[1] ?? '');
                $namaKelas = trim($row[2] ?? '');
                $waRaw = trim($row[3] ?? '');
                $wa = normalize_wa($waRaw) ?? '';

                if ($nama === '' || $nis === '') {
                    $countSkip++; continue;
                }

                // Resolve Kelas ID
                $kelasId = null;
                if ($namaKelas !== '') {
                    if (isset($kelasMap[$namaKelas])) {
                        $kelasId = $kelasMap[$namaKelas];
                    } else {
                        $stmtk = $pdo->prepare("SELECT id FROM kelas WHERE nama_kelas = :nk LIMIT 1");
                        $stmtk->execute([':nk' => $namaKelas]);
                        $k = $stmtk->fetchColumn();
                        if ($k) {
                            $kelasId = $k; 
                        } else {
                            // Create new class
                            try {
                                $stmtkc = $pdo->prepare("INSERT INTO kelas (nama_kelas) VALUES (:nk)");
                                $stmtkc->execute([':nk' => $namaKelas]);
                                $kelasId = $pdo->lastInsertId();
                            } catch (Exception $e) { 
                                $kelasId = null; 
                            }
                        }
                        if ($kelasId) $kelasMap[$namaKelas] = $kelasId;
                    }
                }

                // Cek Duplikat NIS
                $stmtCheck->execute([':nis' => $nis]);
                if ($stmtCheck->fetch()) {
                    $countSkip++;
                    $debugLog[] = "Baris $rowNum: NIS '$nis' sudah ada.";
                    continue; 
                }

                // Insert
                try {
                    $stmtInsert->execute([':nm' => $nama, ':nis' => $nis, ':kid' => $kelasId, ':wa' => $wa]);
                    $countSuccess++;
                } catch (Exception $e) {
                    $countSkip++;
                    $debugLog[] = "Baris $rowNum: Gagal insert. " . $e->getMessage();
                }
            }

            $msg = "Import selesai. Berhasil: $countSuccess. Dilewati/Gagal: $countSkip.";
            if (!empty($debugLog)) {
                $msg .= "\n\nDetail:\n" . implode("\n", $debugLog);
            }
            flash_set('info', $msg);

        } catch (Exception $e) {
            flash_set('error', 'Gagal membaca file Excel: ' . $e->getMessage());
        }
        header('Location: data-siswa.php');
        exit;
    }
}

/* =================================================================================
   VIEW LOGIC
   ================================================================================= */
// AMBIL DATA SISWA + KELAS
$sql = "
SELECT
    s.id,
    s.nama,
    s.nis,
    s.no_wa,
    s.uid_rfid AS uid_rfid,
    s.kelas_id,
    k.nama_kelas AS kelas
FROM siswa s
LEFT JOIN kelas k ON k.id = s.kelas_id
ORDER BY s.nama ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$data_siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);

// AMBIL DATA KELAS (DROPDOWN)
$sqlKelas = "SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas ASC";
$kelas_list = $pdo->query($sqlKelas)->fetchAll(PDO::FETCH_ASSOC);

// CSRF token untuk form
$token_siswa = function_exists('generate_csrf_token') ? generate_csrf_token() : '';
?>
<?php $pageTitle = "Data Siswa"; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?></title>

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

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 text-gray-800">Data Siswa</h1>
                    <div>
                        <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#modalTambahSiswa">
                            <i class="fas fa-plus fa-sm"></i> Tambah Siswa
                        </button>
                        <button class="btn btn-success shadow-sm ml-2" data-toggle="modal" data-target="#modalImportSiswa">
                            <i class="fas fa-file-excel fa-sm"></i> Import Excel
                        </button>
                    </div>
                </div>

                <!-- Flash messages (jika menggunakan flash_set) -->
                <div id="alert-area">
                    <?php
                    if (function_exists('flash_get')) {
                        $s = flash_get('success');
                        $e = flash_get('error');
                        $i = flash_get('info');
                        if ($s) echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . nl2br(htmlspecialchars($s, ENT_QUOTES)) . '<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
                        if ($e) echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . nl2br(htmlspecialchars($e, ENT_QUOTES)) . '<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
                        if ($i) echo '<div class="alert alert-info alert-dismissible fade show" role="alert">' . nl2br(htmlspecialchars($i, ENT_QUOTES)) . '<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
                    }
                    ?>
                </div>

                <!-- Tabel Data Siswa -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Tabel Data Siswa</h6>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTableSiswa" width="100%" cellspacing="0">
                                <thead class="text-center">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama</th>
                                        <th>NIS</th>
                                        <th>Kelas</th>
                                        <th>No WhatsApp</th>
                                        <th>UID RFID</th>
                                        <th hidden>Enroll Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                <?php 
                                $no = 1;
                                if (empty($data_siswa)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Tidak ada data siswa</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($data_siswa as $row): 
                                        $status = $row['enroll_status'] ?? '-';
                                        $status_class = '';
                                        if (strtolower($status) === 'requested') $status_class = 'status-requested';
                                        if (strtolower($status) === 'done') $status_class = 'status-done';
                                        if (strtolower($status) === 'error') $status_class = 'status-error';
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($row['nama'], ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars($row['nis'], ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars($row['kelas'], ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars($row['no_wa'] ?? '-', ENT_QUOTES) ?></td>

                                            <!-- beri class agar JS bisa update cell ini -->
                                            <td class="cell-uid-rfid text-center"><?= htmlspecialchars($row['uid_rfid'] ?? '-', ENT_QUOTES) ?></td>

                                            <td hidden class="cell-enroll-status text-center <?= $status_class ?>"><?= htmlspecialchars($status, ENT_QUOTES) ?></td>

                                            <td class="text-center">
                                                <a href="#"
                                                   class="btn btn-sm btn-warning btnEditSiswa"
                                                   data-id="<?= htmlspecialchars($row['id'], ENT_QUOTES) ?>"
                                                   data-nama="<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>"
                                                   data-nis="<?= htmlspecialchars($row['nis'], ENT_QUOTES) ?>"
                                                   data-kelas="<?= htmlspecialchars($row['kelas_id'] ?? '', ENT_QUOTES) ?>"
                                                   data-wa="<?= htmlspecialchars($row['no_wa'] ?? '', ENT_QUOTES) ?>"
                                                   data-uid="<?= htmlspecialchars($row['uid_rfid'] ?? '', ENT_QUOTES) ?>"
                                                   data-toggle="modal" data-target="#modalEditSiswa"
                                                   title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <a href="#"
                                                   class="btn btn-sm btn-danger btnHapusSiswa"
                                                   data-id="<?= htmlspecialchars($row['id'], ENT_QUOTES) ?>"
                                                   data-nama="<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>"
                                                   data-toggle="modal" data-target="#modalHapusSiswa"
                                                   title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </a>

                                                <a href="#"
                                                   class="btn btn-sm btn-info btnEnrollRFID"
                                                   data-id="<?= htmlspecialchars($row['id'], ENT_QUOTES) ?>"
                                                   data-nama="<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>"
                                                   data-toggle="modal"
                                                   data-target="#modalEnrollRFID"
                                                   title="Enroll Kartu RFID">
                                                    <i class="fas fa-id-card"></i>
                                                </a>
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
<!-- MODAL ENROLL RFID -->
<!-- ============================= -->
<div class="modal fade" id="modalEnrollRFID" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-id-card"></i> Enroll Kartu RFID
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body text-center">

    <input type="hidden" id="enroll_siswa_id">

    <!-- Nama -->
    <p class="mb-1 text-muted">Nama Siswa:</p>
    <h5 id="enroll_nama" class="text-primary mb-1"></h5>

    <!-- UID (di bawah nama) -->
    <div id="uid_wrapper" class="mb-2 d-none">
        <small class="text-muted">UID RFID</small><br>
        <strong id="enroll_uid" class="text-dark"></strong>
    </div>

    <hr>

    <!-- Instruksi -->
    <div id="enroll_info" class="mb-2">
        <i class="fas fa-hand-point-up fa-3x text-info mb-2"></i>
        <p class="mb-0">
            Silakan <b>tempelkan kartu RFID</b><br>
            ke reader untuk mendaftarkan kartu.
        </p>
    </div>

    <!-- Status -->
    <div id="enroll_status" class="mt-2"></div>

</div>

<div class="modal-footer justify-content-between">

    <button type="button"
            class="btn btn-danger"
            id="btnHapusUID"
            disabled>
        <i class="fas fa-trash"></i> Hapus UID RFID
    </button>

    <div>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
            Tutup
        </button>
        <button type="button" class="btn btn-info" id="btnMulaiEnroll">
            Mulai Enroll
        </button>
    </div>

</div>


        </div>
    </div>
</div>

<!-- ============================= -->
<!-- MODAL TAMBAH SISWA -->
<!-- ============================= -->
<div class="modal fade" id="modalTambahSiswa" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Tambah Siswa</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <form action="" method="POST">
                <input type="hidden" name="action" value="add_siswa">
                <input type="hidden" name="token_siswa" value="<?= htmlspecialchars($token_siswa, ENT_QUOTES) ?>">
                <div class="modal-body">

                    <div class="form-group">
                        <label>Nama Siswa</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>NIS</label>
                        <input type="text" name="nis" class="form-control" required>
                    </div>

                    <div class="form-group">
                      <label>Kelas</label>
                      <select name="kelas" id="tambah_kelas" class="form-control" required>
                        <option value="">Pilih kelas</option>
                        <?php foreach ($kelas_list as $k): ?>
                          <option value="<?= htmlspecialchars($k['id'], ENT_QUOTES) ?>">
                            <?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
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
<!-- MODAL EDIT SISWA -->
<!-- ============================= -->
<div class="modal fade" id="modalEditSiswa" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Edit Siswa</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <form action="" method="POST">
                <input type="hidden" name="action" value="edit_siswa">
                <input type="hidden" name="token_siswa" value="<?= htmlspecialchars($token_siswa, ENT_QUOTES) ?>">
                <input type="hidden" name="id" id="edit_id">
                <!-- tambahan: hidden supaya uid_rfid tetap terkirim walau input disabled -->
                <input type="hidden" name="uid_rfid" id="edit_uid_hidden">

                <div class="modal-body">

                    <div class="form-group">
                        <label>Nama Siswa</label>
                        <input type="text" name="nama" id="edit_nama" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>NIS</label>
                        <input type="text" name="nis" id="edit_nis" class="form-control" required>
                    </div>

                    <div class="form-group">
                      <label>Kelas</label>
                      <select name="kelas" id="edit_kelas" class="form-control" required>
                        <option value="">Pilih kelas</option>
                        <?php foreach ($kelas_list as $k): ?>
                          <option value="<?= htmlspecialchars($k['id'], ENT_QUOTES) ?>">
                            <?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="form-group">
                        <label>No WhatsApp</label>
                        <input type="text" name="no_wa" id="edit_wa" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>UID RFID</label>
                        <!-- tampilkan sebagai disabled (jangan diubah), tapi kirim nilai lewat hidden input -->
                        <input type="text" id="edit_uid" class="form-control" disabled>
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
<!-- MODAL HAPUS SISWA -->
<!-- ============================= -->
<div class="modal fade" id="modalHapusSiswa" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>

            <form action="" method="POST">
                <input type="hidden" name="action" value="delete_siswa">
                <input type="hidden" name="token_siswa" value="<?= htmlspecialchars($token_siswa, ENT_QUOTES) ?>">
                <input type="hidden" name="id" id="hapus_id">

                <div class="modal-body">
                    <p>Yakin ingin menghapus siswa berikut?</p>
                    <h5 class="text-danger" id="hapus_nama"></h5>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </div>

            </form>

        </div>
    </div>
</div>

<!-- ============================= -->
<!-- MODAL IMPORT SISWA -->
<!-- ============================= -->
<div class="modal fade" id="modalImportSiswa" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-file-excel"></i> Import Data Siswa</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_siswa">
                <input type="hidden" name="token_siswa" value="<?= htmlspecialchars($token_siswa, ENT_QUOTES) ?>">
                <div class="modal-body">
                    <p>Format Excel: <strong>Nama, NIS, Kelas, No WA</strong></p>
                    <div class="mb-3">
                        <a href="download-template-siswa.php" class="btn btn-sm btn-info">
                            <i class="fas fa-download"></i> Download Template
                        </a>
                    </div>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="fileExcel" name="fileExcel" accept=".xls,.xlsx,.csv" required>
                        <label class="custom-file-label" for="fileExcel">Pilih file...</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-success">Import</button>
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
<script>
$(document).ready(function() {
  $('#dataTableSiswa').DataTable({
    pageLength: 25,
    responsive: true,
    columnDefs: [
      { targets: [7], orderable: false } // Aksi column disabled for sorting
    ]
  });
});

/* ======================================================
   EDIT & HAPUS SISWA (AMAN)
====================================================== */
$(document).on('click', '.btnEditSiswa', function () {
    const btn = $(this);
    $('#edit_id').val(btn.data('id'));
    $('#edit_nama').val(btn.data('nama'));
    $('#edit_nis').val(btn.data('nis'));
    $('#edit_kelas').val(btn.data('kelas'));
    $('#edit_wa').val(btn.data('wa'));
    $('#edit_uid').val(btn.data('uid') || '');
    $('#edit_uid_hidden').val(btn.data('uid') || '');
});

$(document).on('click', '.btnHapusSiswa', function () {
    $('#hapus_id').val($(this).data('id'));
    $('#hapus_nama').text($(this).data('nama'));
});

/* ======================================================
   ENROLL RFID
====================================================== */
let enrollSiswaId = null;
let enrollInterval = null;

/* === buka modal enroll === */
$(document).on('click', '.btnEnrollRFID', function () {
    enrollSiswaId = $(this).data('id');

    $('#enroll_siswa_id').val(enrollSiswaId);
    $('#enroll_nama').text($(this).data('nama'));

    // reset UI
    $('#enroll_status').html('');
    $('#uid_wrapper').addClass('d-none');
    $('#enroll_uid').text('');
    $('#btnHapusUID').prop('disabled', true);

    // 🔥 cek UID saat modal dibuka
    $.getJSON('check-enroll.php', { siswa_id: enrollSiswaId }, function (res) {
        if (!res || !res.ok) return;

        if (res.uid) {
            $('#enroll_uid').text(res.uid);
            $('#uid_wrapper').removeClass('d-none');
            $('#btnHapusUID').prop('disabled', false);
        }
    });
});

/* === mulai enroll === */
$('#btnMulaiEnroll').on('click', function () {
    if (!enrollSiswaId) return;

    $('#enroll_status').html(
        '<span class="text-info">Silakan tempelkan kartu RFID...</span>'
    );

    $.post('request-enroll.php', { siswa_id: enrollSiswaId }, function (res) {
        if (!res || !res.ok) {
            $('#enroll_status').html(
                '<span class="text-danger">'+ (res?.message || 'Gagal') +'</span>'
            );
            return;
        }

        // mulai polling
        startEnrollPolling(enrollSiswaId);
    }, 'json');
});

/* === polling enroll === */
function startEnrollPolling(siswaId) {
    if (enrollInterval) clearInterval(enrollInterval);
    let counter = 0;

    enrollInterval = setInterval(function () {
        counter++;
        if (counter > 20) { // ±30 detik
            clearInterval(enrollInterval);
            $('#enroll_status').html(
                '<span class="text-warning">Enroll timeout</span>'
            );
            return;
        }

        $.getJSON('check-enroll.php', { siswa_id: siswaId }, function (res) {
            if (!res || !res.ok) return;

            if (res.uid) {
                clearInterval(enrollInterval);

                // update modal
                $('#enroll_uid').text(res.uid);
                $('#uid_wrapper').removeClass('d-none');
                $('#btnHapusUID').prop('disabled', false);

                $('#enroll_status').html(
                    '<span class="text-success font-weight-bold">Enroll berhasil</span>'
                );

                // update tabel
                const row = $('a.btnEnrollRFID[data-id="'+siswaId+'"]').closest('tr');
                row.find('.cell-uid-rfid').text(res.uid);
            }
        });
    }, 1500);
}

/* === hapus UID RFID === */
$('#btnHapusUID').on('click', function () {
    if (!enrollSiswaId) return;
    if (!confirm('Yakin ingin menghapus UID RFID siswa ini?')) return;

    $.post('hapus-uid.php', { siswa_id: enrollSiswaId }, function (res) {
        if (!res || !res.ok) {
            alert(res?.message || 'Gagal menghapus UID');
            return;
        }

        // reset modal
        $('#uid_wrapper').addClass('d-none');
        $('#enroll_uid').text('');
        $('#btnHapusUID').prop('disabled', true);

        $('#enroll_status').html(
            '<span class="text-warning">UID RFID dihapus</span>'
        );

        // update tabel
        const row = $('a.btnEnrollRFID[data-id="'+enrollSiswaId+'"]').closest('tr');
        row.find('.cell-uid-rfid').text('-');
    }, 'json');
});

/* === reset saat modal ditutup === */
$('#modalEnrollRFID').on('hidden.bs.modal', function () {
    if (enrollInterval) clearInterval(enrollInterval);
    enrollSiswaId = null;
});

/* === Update custom file input label === */
$('.custom-file-input').on('change', function() {
    var fileName = $(this).val().split('\\').pop();
    $(this).next('.custom-file-label').html(fileName);
});
</script>


</body>
</html>
