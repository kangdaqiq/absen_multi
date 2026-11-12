<?php

namespace App\Http\Controllers;

use App\Models\AbsensiSholat;
use App\Models\Kelas;
use App\Models\Setting;
use App\Models\Siswa;
use App\Models\Guru;
use App\Services\PrayerTimeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AbsensiSholatController extends Controller
{
    protected PrayerTimeService $prayerService;

    public function __construct(PrayerTimeService $prayerService)
    {
        $this->prayerService = $prayerService;
    }

    /**
     * Halaman Monitoring Real-time Absensi Sholat Hari Ini
     */
    public function index(Request $request)
    {
        $schoolId = auth()->user()->school_id ?? 0;
        $tanggal = $request->input('tanggal', now()->format('Y-m-d'));
        $targetDate = Carbon::parse($tanggal);

        // Ambil jadwal sholat hisab hari tersebut
        $prayerSchedule = $this->prayerService->getTodaySchedule($schoolId, $targetDate);

        // Query Absensi Sholat
        $query = AbsensiSholat::with(['student.kelas', 'guru', 'device'])
            ->where('school_id', $schoolId)
            ->whereDate('tanggal', $tanggal);

        if ($request->filled('sholat') && $request->sholat !== 'all') {
            $query->where('sholat', $request->sholat);
        }

        if ($request->filled('person_type') && $request->person_type !== 'all') {
            $query->where('person_type', $request->person_type);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', function ($sq) use ($search) {
                    $sq->where('nama', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%");
                })->orWhereHas('guru', function ($gq) use ($search) {
                    $gq->where('nama', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%");
                });
            });
        }

        // Statistik
        $statsQuery = AbsensiSholat::where('school_id', $schoolId)->whereDate('tanggal', $tanggal);
        $totalHadir = (clone $statsQuery)->count();
        $totalOntime = (clone $statsQuery)->where('status', 'hadir')->count();
        $totalLate = (clone $statsQuery)->where('status', 'terlambat')->count();

        // Breakdown per sholat
        $breakdown = (clone $statsQuery)
            ->selectRaw('sholat, count(*) as total')
            ->groupBy('sholat')
            ->pluck('total', 'sholat')
            ->toArray();

        $attendances = $query->orderBy('jam_absen', 'desc')->paginate(25)->withQueryString();

        return view('absensi-sholat.index', compact(
            'attendances',
            'prayerSchedule',
            'tanggal',
            'totalHadir',
            'totalOntime',
            'totalLate',
            'breakdown'
        ));
    }

    /**
     * Halaman Rekapitulasi Absensi Sholat
     */
    public function rekap(Request $request)
    {
        $schoolId = auth()->user()->school_id ?? 0;
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $query = AbsensiSholat::with(['student.kelas', 'guru', 'device'])
            ->where('school_id', $schoolId)
            ->whereBetween('tanggal', [$startDate, $endDate]);

        if ($request->filled('sholat') && $request->sholat !== 'all') {
            $query->where('sholat', $request->sholat);
        }

        if ($request->filled('kelas_id') && $request->kelas_id !== 'all') {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('kelas_id', $request->kelas_id);
            });
        }

        if ($request->filled('person_type') && $request->person_type !== 'all') {
            $query->where('person_type', $request->person_type);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', function ($sq) use ($search) {
                    $sq->where('nama', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%");
                })->orWhereHas('guru', function ($gq) use ($search) {
                    $gq->where('nama', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%");
                });
            });
        }

        $attendances = $query->orderBy('tanggal', 'desc')->orderBy('jam_absen', 'desc')->paginate(30)->withQueryString();

        // Data filter
        $kelases = Kelas::where('school_id', $schoolId)->orderBy('nama_kelas')->get();

        return view('absensi-sholat.rekap', compact(
            'attendances',
            'kelases',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Export Rekap Absensi Sholat ke PDF
     */
    public function exportPdf(Request $request)
    {
        $schoolId = auth()->user()->school_id ?? 0;
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $query = AbsensiSholat::with(['student.kelas', 'guru'])
            ->where('school_id', $schoolId)
            ->whereBetween('tanggal', [$startDate, $endDate]);

        if ($request->filled('sholat') && $request->sholat !== 'all') {
            $query->where('sholat', $request->sholat);
        }

        if ($request->filled('kelas_id') && $request->kelas_id !== 'all') {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('kelas_id', $request->kelas_id);
            });
        }

        if ($request->filled('person_type') && $request->person_type !== 'all') {
            $query->where('person_type', $request->person_type);
        }

        $attendances = $query->orderBy('tanggal', 'asc')->orderBy('jam_absen', 'asc')->get();

        $school = auth()->user()->school ?? \App\Models\School::find($schoolId);
        $schoolName = $school?->name ?? 'Sekolah';
        $kopSurat = Setting::where('school_id', $schoolId)->where('setting_key', 'kop_surat')->value('setting_value');

        $pdf = Pdf::loadView('absensi-sholat.export-pdf', compact(
            'attendances',
            'schoolName',
            'kopSurat',
            'startDate',
            'endDate'
        ))->setPaper('a4', 'portrait');

        return $pdf->download("rekap-absensi-sholat-{$startDate}-sd-{$endDate}.pdf");
    }

    /**
     * Export Rekap Absensi Sholat ke Excel
     */
    public function exportExcel(Request $request)
    {
        $schoolId = auth()->user()->school_id ?? 0;
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $query = AbsensiSholat::with(['student.kelas', 'guru'])
            ->where('school_id', $schoolId)
            ->whereBetween('tanggal', [$startDate, $endDate]);

        if ($request->filled('sholat') && $request->sholat !== 'all') {
            $query->where('sholat', $request->sholat);
        }

        if ($request->filled('kelas_id') && $request->kelas_id !== 'all') {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('kelas_id', $request->kelas_id);
            });
        }

        $attendances = $query->orderBy('tanggal', 'asc')->orderBy('jam_absen', 'asc')->get();
        $school = auth()->user()->school ?? \App\Models\School::find($schoolId);
        $schoolName = $school?->name ?? 'Sekolah';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Sholat');

        // Judul Laporan
        $sheet->setCellValue('A1', 'LAPORAN REKAPITULASI KEHADIRAN SHOLAT');
        $sheet->setCellValue('A2', strtoupper($schoolName));
        $sheet->setCellValue('A3', "Periode: {$startDate} s.d. {$endDate}");

        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');

        $sheet->getStyle('A1:A3')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setSize(14);
        $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header Tabel
        $headers = ['No', 'Tanggal', 'Nama', 'Peran', 'Kelas / NIP', 'Sholat', 'Jam Tap', 'Status'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $col++;
        }

        $sheet->getStyle('A5:H5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Isi Data
        $row = 6;
        $no = 1;
        foreach ($attendances as $item) {
            $peran = $item->person_type === 'guru' ? 'Guru/Pegawai' : 'Siswa';
            $identitas = $item->person_type === 'guru' ? ($item->guru?->nip ?? '-') : ($item->student?->kelas?->nama_kelas ?? '-');
            $statusLabel = match ($item->status) {
                'hadir'     => 'Hadir (Tepat Waktu)',
                'terlambat' => 'Terlambat / Masbuk',
                'jamaah'    => 'Berjamaah',
                default     => ucfirst($item->status),
            };

            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $item->tanggal->format('d/m/Y'));
            $sheet->setCellValue('C' . $row, $item->person_name);
            $sheet->setCellValue('D' . $row, $peran);
            $sheet->setCellValue('E' . $row, $identitas);
            $sheet->setCellValue('F' . $row, $item->sholat_label);
            $sheet->setCellValue('G' . $row, substr($item->jam_absen, 0, 5));
            $sheet->setCellValue('H' . $row, $statusLabel);

            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}:H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        // Border & Auto width
        $lastRow = max(6, $row - 1);
        $sheet->getStyle("A5:H{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (range('A', 'H') as $colId) {
            $sheet->getColumnDimension($colId)->setAutoSize(true);
        }

        $fileName = "Rekap-Sholat-{$startDate}-sd-{$endDate}.xlsx";
        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Hapus record absensi sholat (jika ada kesalahan)
     */
    public function destroy($id)
    {
        $schoolId = auth()->user()->school_id ?? 0;
        $attendance = AbsensiSholat::where('school_id', $schoolId)->findOrFail($id);
        $attendance->delete();

        return back()->with('success', 'Data absensi sholat berhasil dihapus.');
    }
}

