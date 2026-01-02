<?php
// detail-absensi.php
// Menampilkan detail absensi per siswa — MENAMPILKAN DATA ASLI DARI TABEL `attendance` (RAW).
// Status tampil apa adanya: H, I, S, A, B, P (Bolos tetap B, Partial tetap P).
// Export CSV dan form penyimpanan per-tanggal (append/insert) disediakan.
//
// Referensi struktur tabel (screenshot upload): /mnt/data/61cf2a73-d6fc-4198-a039-7a570535ce1f.png

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/function.php';


// pastikan login
if (function_exists('require_login')) {
    require_login('../login.php');
} else {
    if (empty($_SESSION['user'])) { header('Location: ../login.php'); exit; }
}

$user = function_exists('current_user') ? current_user() : ($_SESSION['user'] ?? null);

// Ambil parameter student_id (prioritas) atau name (opsional)
$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$studentNameParam = isset($_GET['name']) ? trim($_GET['name']) : 'Ali'; // default 'Ali' bila tidak ada
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
$from = date('Y-m-d', strtotime($from));
$to   = date('Y-m-d', strtotime($to));

// CSRF token helper (optional)
$token = function_exists('generate_csrf_token') ? generate_csrf_token() : '';
$verify_csrf = function_exists('verify_csrf_token') ? 'verify_csrf_token' : null;

// Ambil data siswa berdasarkan id atau nama
$student = null;
try {
    if ($studentId > 0) {
        $stmt = $pdo->prepare("SELECT s.id, s.nama, s.nis, k.nama_kelas AS kelas, s.no_wa FROM siswa s JOIN kelas k ON k.id = s.kelas_id WHERE s.id = :id LIMIT 1");
        $stmt->execute([':id' => $studentId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // cari berdasarkan nama (exact match) jika student_id tidak diberikan
        $stmt = $pdo->prepare("SELECT s.id, s.nama, s.nis, k.nama_kelas AS kelas, s.no_wa FROM siswa s JOIN kelas k ON k.id = s.kelas_id WHERE s.nama = :nama LIMIT 1");
        $stmt->execute([':nama' => $studentNameParam]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$student) {
        header('Content-Type: text/plain; charset=utf-8', true, 404);
        echo "Siswa tidak ditemukan.";
        exit;
    }
    $studentId = (int)$student['id'];
} catch (Exception $e) {
    error_log("Error loading student: " . $e->getMessage());
    header('Content-Type: text/plain; charset=utf-8', true, 500);
    echo "Gagal mengambil data siswa.";
    exit;
}




// Ambil flash jika ada (set oleh PRG)
$flash_success = null;
$flash_error = null;
if (!empty($_SESSION['flash']['success'])) { $flash_success = $_SESSION['flash']['success']; unset($_SESSION['flash']['success']); }
if (!empty($_SESSION['flash']['error']))   { $flash_error = $_SESSION['flash']['error'];   unset($_SESSION['flash']['error']); }

// ---------- ambil history RAW dari tabel attendance (APA ADANYA) ----------
$historyRows = [];
try {
    $sql = "
        SELECT
            id,
            DATE(tanggal) AS tanggal,
            status,
            jam_masuk,
            jam_pulang,
            total_seconds,
            keterangan,
            created_at
        FROM attendance
        WHERE student_id = :student_id
          AND DATE(tanggal) BETWEEN :from AND :to
        ORDER BY DATE(tanggal) ASC, id ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':student_id' => $studentId,
        ':from' => $from,
        ':to' => $to
    ]);
    $historyRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error load raw attendance: " . $e->getMessage());
    $historyRows = [];
}

// ---------- Export Excel (jika diminta) ----------
if (isset($_GET['export']) && $_GET['export'] === 'xlsx') {
    $filename = sprintf("detail_absensi_%s_%s_%s", $studentId, str_replace('-', '', $from), str_replace('-', '', $to));
    
    // Cek keberadaan PhpSpreadsheet
    // Cari autoload composer di lokasi-lokasi umum (SAMA DENGAN export_absensi.php)
    $autoloadCandidates = [
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/../../vendor/autoload.php',
        __DIR__ . '/vendor/autoload.php',
    ];

    foreach ($autoloadCandidates as $p) {
        if (file_exists($p)) {
            require_once $p;
            break;
        }
    }

    if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Metadata
            $sheet->setCellValue('A1', 'Nama Siswa');
            $sheet->setCellValue('B1', ': ' . ($student['nama'] ?? '-'));
            $sheet->setCellValue('A2', 'Kelas');
            $sheet->setCellValue('B2', ': ' . ($student['kelas'] ?? '-'));
            $sheet->setCellValue('A3', 'Periode');
            $sheet->setCellValue('B3', ': ' . date('d/m/Y', strtotime($from)) . ' s/d ' . date('d/m/Y', strtotime($to)));

            $sheet->getStyle('A1:A3')->getFont()->setBold(true);

            // Header Tabel (mulai baris 5)
            $headerRow = 5;
            $headers = ['No', 'Tanggal', 'Status', 'Jam Masuk', 'Jam Pulang', 'Keterangan'];
            $col = 1;
            foreach ($headers as $h) {
                $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++);
                $sheet->setCellValue($letter . $headerRow, $h);
                $sheet->getStyle($letter . $headerRow)->getFont()->setBold(true);
            }

            // Data (mulai baris 6)
            $rowNum = $headerRow + 1;
            $no = 1;
            foreach ($historyRows as $r) {
                $col = 1;
                $s = $r['status'] ?? ''; 
                $label = '';
                if ($s==='H') $label='Hadir';
                elseif ($s==='B') $label='Bolos';
                elseif ($s==='P') $label='Partial';
                elseif ($s==='I') $label='Izin';
                elseif ($s==='S') $label='Sakit';
                elseif ($s==='A') $label='Alfa';

                // No
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $rowNum, $no++);
                // Tanggal (dd/mm/yyyy)
                $tglFormatted = $r['tanggal'] ? date('d/m/Y', strtotime($r['tanggal'])) : '';
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $rowNum, $tglFormatted);
                // Status
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $rowNum, $label);
                // Masuk (HH:mm)
                $masukFormatted = $r['jam_masuk'] ? date('H:i', strtotime($r['jam_masuk'])) : '';
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $rowNum, $masukFormatted);
                // Pulang (HH:mm)
                $pulangFormatted = $r['jam_pulang'] ? date('H:i', strtotime($r['jam_pulang'])) : '';
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $rowNum, $pulangFormatted);
                // Keterangan
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $rowNum, $r['keterangan']);
                
                $rowNum++;
            }

            // Auto-size columns
            foreach (range(1, count($headers)) as $i) {
                $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                $sheet->getColumnDimension($letter)->setAutoSize(true);
            }

            // Output
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="'.$filename.'.xlsx"');
            header('Cache-Control: max-age=0');
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (Exception $e) {
            error_log("Export Excel Error: " . $e->getMessage());
            // Fallback content type text? or just simple error?
            echo "Gagal export XLSX: " . htmlspecialchars($e->getMessage());
            exit;
        }
    } else {
        echo "Library PhpSpreadsheet tidak ditemukan.";
        exit;
    }
}

// ---------- page render ----------
$pageTitle = "Detail Absensi: " . ($student['nama'] ?? '—') . " ($from — $to)";
$token_field = htmlspecialchars($token);
$succ = $flash_success;
$err  = $flash_error;
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
    .badge-status-H { background:#28a745; color:#fff; padding:4px 8px; border-radius:4px; display:inline-block; }
    .badge-status-I { background:#ffc107; color:#212529; padding:4px 8px; border-radius:4px; display:inline-block; }
    .badge-status-S { background:#17a2b8; color:#fff; padding:4px 8px; border-radius:4px; display:inline-block; }
    .badge-status-A { background:#dc3545; color:#fff; padding:4px 8px; border-radius:4px; display:inline-block; }
	.badge-status-B { background:#dc3545; color:#fff; padding:4px 8px; border-radius:4px; display:inline-block; }
    .badge-status-P { background:#dc3545; color:#fff; padding:4px 8px; border-radius:4px; display:inline-block; }
    .status-select { min-width:120px; display:inline-block; }
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
          <h1 class="h5 text-gray-800 mb-0"><?= htmlspecialchars($student['nama']) ?> — Detail Absensi</h1>
          <div>
            <a class="btn btn-secondary" href="rekap.php?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>"><i class="fas fa-arrow-left"></i> Kembali</a>
            <a class="btn btn-success" href="?student_id=<?= (int)$studentId ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&export=xlsx"><i class="fas fa-file-excel"></i> Export Excel</a>
          </div>
        </div>

        <?php if ($succ): ?><div class="alert alert-success"><?= htmlspecialchars($succ) ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

        <div class="card mb-3">
          <div class="card-body">
		  <div><strong>Nama</strong>: <?= htmlspecialchars($student['nama']) ?></div>
            <div><strong>NIS</strong>: <?= htmlspecialchars($student['nis']) ?></div>
            <div><strong>Kelas</strong>: <?= htmlspecialchars($student['kelas']) ?></div>
            <div><strong>No WA</strong>: <?= htmlspecialchars($student['no_wa']) ?></div>
            <div class="mt-2 text-muted small">Rentang: <?= htmlspecialchars($from) ?> — <?= htmlspecialchars($to) ?></div>
          </div>
        </div>

        <div class="card shadow mb-4">
          <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Riwayat Absensi (status terakhir per tanggal)</h6>
            <div class="small text-muted"><?= count($historyRows) ?> baris</div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered" id="tableDetail" width="100%" cellspacing="0">
                <thead class="text-center">
                  <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Jam Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Keterangan</th>
                    
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($historyRows)): ?>
                    <tr><td colspan="8" class="text-center text-muted">Tidak ada data</td></tr>
                  <?php else: $i=1; foreach ($historyRows as $row): ?>
                    <?php $s = $row['status'] ?? ''; 
                          $label = '(kosong)'; $cls = 'badge-secondary';
                          if ($s==='H') { $label='Hadir'; $cls='badge-status-H'; }
						  if ($s==='B') { $label='Bolos'; $cls='badge-status-B'; }
						  if ($s==='P') { $label='Partial'; $cls='badge-status-P'; }
                          if ($s==='I') { $label='Izin';  $cls='badge-status-I'; }
                          if ($s==='S') { $label='Sakit'; $cls='badge-status-S'; }
                          if ($s==='A') { $label='Alfa';  $cls='badge-status-A'; }
                    ?>
                    <tr data-tanggal="<?= htmlspecialchars($row['tanggal']) ?>">
                      <td class="text-center"><?= $i++ ?></td>
                      <td class="text-center"><?= htmlspecialchars($row['tanggal']) ?></td>
                      <td class="text-center">
                        <span class="<?= $cls ?>"><?= htmlspecialchars($label) ?></span>
                      </td>
                      <td class="text-center"><?= htmlspecialchars($row['jam_masuk'] ?? '') ?></td>
                      <td class="text-center"><?= htmlspecialchars($row['jam_pulang'] ?? '') ?></td>
                      <td class="text-center"><?= htmlspecialchars($row['keterangan'] ?? '') ?></td>

                    </tr>
                  <?php endforeach; endif; ?>
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

<!-- toast area -->
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
  $('#tableDetail').DataTable({
    pageLength: 25,
    responsive: true,
    columnDefs: [{ targets: [0,2,3,4,5], orderable: false }]
  });
});
</script>

</body>
</html>
