<?php
require_once __DIR__ . '/../includes/function.php';
require __DIR__ . '/../vendor/autoload.php';

require_login('../login.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set Header
$sheet->setCellValue('A1', 'Nama');
$sheet->setCellValue('B1', 'NIS');
$sheet->setCellValue('C1', 'Kelas');
$sheet->setCellValue('D1', 'No WA');

// Set Contoh Data
$sheet->setCellValue('A2', 'Contoh Siswa');
$sheet->setCellValue('B2', '123456');
$sheet->setCellValue('C2', 'X RPL 1');
$sheet->setCellValue('D2', '081234567890');

// Style Header
$sheet->getStyle('A1:D1')->getFont()->setBold(true);
foreach(range('A','D') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="template_siswa.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
