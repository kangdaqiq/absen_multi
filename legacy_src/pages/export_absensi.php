<?php
// export_absensi.php
// Generate rekap absensi sebagai .xlsx (jika PhpSpreadsheet terpasang) atau .csv sebagai fallback.
// Sesuaikan path require_once jika struktur folder Anda berbeda.

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/function.php';

// Pastikan user login (sesuaikan jika helper berbeda)
if (function_exists('require_login')) {
    require_login('../login.php');
} else {
    if (empty($_SESSION['user'])) { header('Location: ../login.php'); exit; }
}

// Ambil parameter
$from = $_GET['from'] ?? date('Y-m-d');
$to   = $_GET['to']   ?? date('Y-m-d');
$kelasFilter = $_GET['kelas'] ?? '';

// Normalisasi tanggal
try {
    $d1 = new DateTime($from);
    $d2 = new DateTime($to);
    if ($d1 > $d2) { $tmp = $from; $from = $to; $to = $tmp; }
} catch (Exception $e) {
    $from = $to = date('Y-m-d');
}

// Ambil data rekap (sama logika seperti di absensi.php)
$data_siswa = [];
try {
    // Subquery: ambil record terakhir per student per tanggal dari tabel attendance
    // Gunakan DATE(tanggal) supaya aman bila kolom bertipe datetime
    $latestSub = "
      SELECT a.student_id, DATE(a.tanggal) AS tanggal, a.status
      FROM attendance a
      JOIN (
        SELECT student_id, DATE(tanggal) AS tanggal, MAX(id) AS max_id
        FROM attendance
        WHERE DATE(tanggal) BETWEEN :from AND :to
        GROUP BY student_id, DATE(tanggal)
      ) latest
      ON a.student_id = latest.student_id
         AND DATE(a.tanggal) = latest.tanggal
         AND a.id = latest.max_id
    ";

    // Query utama: rekap per siswa
    // NOTE: treat 'B' and 'P' as 'A' (alfa)
    $sql = "
      SELECT s.id, s.nama, s.nis, k.nama_kelas AS kelas,
        COALESCE(SUM(CASE WHEN UPPER(sub.status) = 'H' THEN 1 ELSE 0 END),0) AS hadir,
        COALESCE(SUM(CASE WHEN UPPER(sub.status) = 'I' THEN 1 ELSE 0 END),0) AS izin,
        COALESCE(SUM(CASE WHEN UPPER(sub.status) = 'S' THEN 1 ELSE 0 END),0) AS sakit,
        COALESCE(SUM(CASE WHEN UPPER(sub.status) IN ('A','B','P') THEN 1 ELSE 0 END),0) AS alfa
      FROM siswa s
      JOIN kelas k ON k.id = s.kelas_id
      LEFT JOIN ( $latestSub ) AS sub ON sub.student_id = s.id
      WHERE 1=1
    ";

    $params = [':from' => $from, ':to' => $to];

    if (!empty($kelasFilter)) {
        $sql .= " AND s.kelas_id = :kelas ";
        $params[':kelas'] = $kelasFilter;
    }

    $sql .= " GROUP BY s.id, s.nama, s.nis, k.nama_kelas ORDER BY k.nama_kelas, s.nama";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data_siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Error Query rekap dari attendance (export): " . $e->getMessage());
    header('Content-Type: text/plain; charset=utf-8', true, 500);
    echo "Gagal mengambil data: " . $e->getMessage();
    exit;
}

// Nama file
$labelKelas = $kelasFilter ? preg_replace('/\s+/', '_', $kelasFilter) : 'semua_kelas';
$filenameBase = "rekap_absensi_{$labelKelas}_{$from}_{$to}";

// Cari autoload composer di lokasi-lokasi umum
$autoloadCandidates = [
    __DIR__ . '/../vendor/autoload.php',     // biasanya jika file di pages/ dan vendor di project root
    __DIR__ . '/../../vendor/autoload.php',  // variasi path
    __DIR__ . '/vendor/autoload.php',        // jika file ada di root project
];

$autoloadFound = false;
foreach ($autoloadCandidates as $p) {
    if (file_exists($p)) {
        require_once $p;
        $autoloadFound = true;
        break;
    }
}

// Jika PhpSpreadsheet tersedia -> buat XLSX
if ($autoloadFound && class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    try {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $headers = ['No','Nama','NIS','Kelas','Hadir','Izin','Sakit','Alfa'];
        foreach ($headers as $idx => $h) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue($colLetter . '1', $h);
        }

        // Isi data
        $row = 2;
        $no = 1;
        foreach ($data_siswa as $r) {
            $col = 1;
            // No
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $row, $no++);
            // Nama
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $row, $r['nama']);
            // NIS sebagai string (agar leading zero tidak hilang)
            $sheet->setCellValueExplicit(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $row, (string)$r['nis'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            // Kelas
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $row, $r['kelas']);
            // Hadir, Izin, Sakit, Alfa
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $row, (int)$r['hadir']);
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $row, (int)$r['izin']);
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $row, (int)$r['sakit']);
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $row, (int)$r['alfa']);

            $row++;
        }

        // Auto-size kolom
        foreach (range(1, count($headers)) as $i) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($letter)->setAutoSize(true);
        }

        // Output XLSX
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. $filenameBase .'.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;

    } catch (Throwable $e) {
        error_log("Error generate xlsx: " . $e->getMessage());
        // fallback ke CSV jika pembuatan xlsx gagal
    }
}

// Jika tidak ada PhpSpreadsheet atau terjadi error, fallback ke CSV dengan delimiter ';'
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="'. $filenameBase .'.csv"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
// Tulis BOM supaya Excel membaca UTF-8 dengan benar
fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Header CSV (delimiter ';' agar Excel lokal mem-parsingnya)
fputcsv($out, ['No','Nama','NIS','Kelas','Hadir','Izin','Sakit','Alfa'], ';');

$no = 1;
foreach ($data_siswa as $r) {
    fputcsv($out, [
        $no++,
        $r['nama'],
        $r['nis'],
        $r['kelas'],
        (int)$r['hadir'],
        (int)$r['izin'],
        (int)$r['sakit'],
        (int)$r['alfa'],
    ], ';');
}

fclose($out);
exit;
