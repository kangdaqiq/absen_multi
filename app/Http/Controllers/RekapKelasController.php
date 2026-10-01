<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Kelas;
use App\Models\Jurusan;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Barryvdh\DomPDF\Facade\Pdf;

class RekapKelasController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $kelasQuery = Kelas::query();

        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $kelasQuery->where('school_id', auth()->user()->school_id);
        }

        if (auth()->user() && auth()->user()->role === 'wali_kelas') {
            $guru = auth()->user()->guru;
            if ($guru) {
                $managedKelasIds = Kelas::where(function($q) use ($guru) {
                    $q->where('wali_kelas_id', $guru->id)
                      ->orWhere('wali_kelas_2_id', $guru->id);
                })->pluck('id');
                $kelasQuery->whereIn('id', $managedKelasIds);
            } else {
                $kelasQuery->where('id', -1);
            }
        }

        if ($request->has('kelas_id') && !empty($request->kelas_id)) {
            $kelasQuery->where('id', $request->kelas_id);
        }

        if ($request->has('jurusan_id') && !empty($request->jurusan_id)) {
            $kelasQuery->where('jurusan_id', $request->jurusan_id);
        }

        // Apply Sorting
        $sortBy = $request->input('sort_by', 'nama_kelas');
        $sortDir = strtolower($request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $this->applySorting($kelasQuery, $sortBy, $sortDir, $startDate, $endDate);

        $allKelas = $kelasQuery->paginate(50)->withQueryString();

        // Untuk dropdown filter
        $kelasListQuery = Kelas::orderBy('nama_kelas');
        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $kelasListQuery->where('school_id', auth()->user()->school_id);
        }
        if (auth()->user() && auth()->user()->role === 'wali_kelas') {
            $guru = auth()->user()->guru;
            if ($guru) {
                $managedKelasIds = Kelas::where(function($q) use ($guru) {
                    $q->where('wali_kelas_id', $guru->id)
                      ->orWhere('wali_kelas_2_id', $guru->id);
                })->pluck('id');
                $kelasListQuery->whereIn('id', $managedKelasIds);
            } else {
                $kelasListQuery->where('id', -1);
            }
        }
        $kelasList = $kelasListQuery->get(['id', 'nama_kelas']);

        // Dropdown jurusan
        $jurusanList = Jurusan::orderBy('nama_jurusan')
            ->when(auth()->user() && !auth()->user()->isSuperAdmin(), function($q) {
                $q->where('school_id', auth()->user()->school_id);
            })
            ->get(['id', 'nama_jurusan']);

        $summary = [];
        foreach ($allKelas as $k) {
            $summary[$k->id] = [
                'H' => 0,
                'I' => 0,
                'S' => 0,
                'A' => 0,
                'B' => 0,
                'T' => 0
            ];
        }

        if ($allKelas->count() > 0) {
            $attendances = Attendance::whereBetween('tanggal', [$startDate, $endDate])
                ->whereHas('student', function ($query) use ($allKelas) {
                    $query->whereIn('kelas_id', $allKelas->pluck('id'));
                })
                ->with('student:id,kelas_id')
                ->get();

            foreach ($attendances as $att) {
                if ($att->student && isset($summary[$att->student->kelas_id])) {
                    $status = $att->status;
                    if (isset($summary[$att->student->kelas_id][$status])) {
                        $summary[$att->student->kelas_id][$status]++;
                    }
                }
            }
        }

        return view('rekap-kelas.index', compact('allKelas', 'summary', 'startDate', 'endDate', 'kelasList', 'jurusanList'));
    }

    public function export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $kelasQuery = Kelas::query();

        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $kelasQuery->where('school_id', auth()->user()->school_id);
        }

        if (auth()->user() && auth()->user()->role === 'wali_kelas') {
            $guru = auth()->user()->guru;
            if ($guru) {
                $managedKelasIds = Kelas::where(function($q) use ($guru) {
                    $q->where('wali_kelas_id', $guru->id)
                      ->orWhere('wali_kelas_2_id', $guru->id);
                })->pluck('id');
                $kelasQuery->whereIn('id', $managedKelasIds);
            } else {
                $kelasQuery->where('id', -1);
            }
        }

        if ($request->has('kelas_id') && !empty($request->kelas_id)) {
            $kelasQuery->where('id', $request->kelas_id);
        }

        if ($request->has('jurusan_id') && !empty($request->jurusan_id)) {
            $kelasQuery->where('jurusan_id', $request->jurusan_id);
        }

        $sortBy = $request->input('sort_by');
        $sortDir = $request->input('sort_dir', 'asc');
        if ($sortBy) {
            $this->applySorting($kelasQuery, $sortBy, $sortDir, $startDate, $endDate);
        } else {
            $kelasQuery->orderBy('nama_kelas');
        }

        $allKelas = $kelasQuery->get();

        $summary = [];
        foreach ($allKelas as $k) {
            $summary[$k->id] = [
                'H' => 0, 'I' => 0, 'S' => 0, 'A' => 0, 'B' => 0, 'T' => 0
            ];
        }

        if ($allKelas->count() > 0) {
            $attendances = Attendance::whereBetween('tanggal', [$startDate, $endDate])
                ->whereHas('student', function ($query) use ($allKelas) {
                    $query->whereIn('kelas_id', $allKelas->pluck('id'));
                })
                ->with('student:id,kelas_id')
                ->get();

            foreach ($attendances as $att) {
                if ($att->student && isset($summary[$att->student->kelas_id])) {
                    $status = $att->status;
                    if (isset($summary[$att->student->kelas_id][$status])) {
                        $summary[$att->student->kelas_id][$status]++;
                    }
                }
            }
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'Rekap Kehadiran Kelas');
        $sheet->setCellValue('A2', "Periode: $startDate s/d $endDate");

        $headers = ['No', 'Nama Kelas', 'Hadir', 'Tidak Hadir', 'Telat', 'Izin', 'Sakit', 'Bolos', 'Alpha', '% Hadir'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $col++;
        }

        $row = 5;
        $totH = 0; $totT = 0; $totI = 0; $totS = 0; $totB = 0; $totA = 0;
        foreach ($allKelas as $index => $kelas) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $kelas->nama_kelas);
            
            $stat = $summary[$kelas->id];
            $tidakHadir = $stat['I'] + $stat['S'] + $stat['B'] + $stat['A'];
            $total = $stat['H'] + $stat['T'] + $tidakHadir;
            $persentase = $total > 0 ? round((($stat['H'] + $stat['T']) / $total) * 100, 1) : 0;
            
            $sheet->setCellValue('C' . $row, $stat['H']);
            $sheet->setCellValue('D' . $row, $tidakHadir);
            $sheet->setCellValue('E' . $row, $stat['T']);
            $sheet->setCellValue('F' . $row, $stat['I']);
            $sheet->setCellValue('G' . $row, $stat['S']);
            $sheet->setCellValue('H' . $row, $stat['B']);
            $sheet->setCellValue('I' . $row, $stat['A']);
            $sheet->setCellValue('J' . $row, $persentase . '%');
            
            $totH += $stat['H']; $totT += $stat['T']; $totI += $stat['I'];
            $totS += $stat['S']; $totB += $stat['B']; $totA += $stat['A'];
            
            $row++;
        }

        if ($allKelas->count() > 0) {
            $totTidakHadir = $totI + $totS + $totB + $totA;
            $totGlobal = $totH + $totT + $totTidakHadir;
            $totPersen = $totGlobal > 0 ? round((($totH + $totT) / $totGlobal) * 100, 1) : 0;

            $sheet->setCellValue('A' . $row, 'TOTAL KEHADIRAN GLOBAL');
            $sheet->mergeCells('A' . $row . ':B' . $row);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('A' . $row . ':J' . $row)->getFont()->setBold(true);

            $sheet->setCellValue('C' . $row, $totH);
            $sheet->setCellValue('D' . $row, $totTidakHadir);
            $sheet->setCellValue('E' . $row, $totT);
            $sheet->setCellValue('F' . $row, $totI);
            $sheet->setCellValue('G' . $row, $totS);
            $sheet->setCellValue('H' . $row, $totB);
            $sheet->setCellValue('I' . $row, $totA);
            $sheet->setCellValue('J' . $row, $totPersen . '%');
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Rekap_Kelas_' . date('Ymd_His') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. urlencode($fileName).'"');
        $writer->save('php://output');
        exit;
    }

    public function printPdf(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $kelasQuery = Kelas::query();

        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $kelasQuery->where('school_id', auth()->user()->school_id);
        }

        if (auth()->user() && auth()->user()->role === 'wali_kelas') {
            $guru = auth()->user()->guru;
            if ($guru) {
                $managedKelasIds = Kelas::where(function($q) use ($guru) {
                    $q->where('wali_kelas_id', $guru->id)
                      ->orWhere('wali_kelas_2_id', $guru->id);
                })->pluck('id');
                $kelasQuery->whereIn('id', $managedKelasIds);
            } else {
                $kelasQuery->where('id', -1);
            }
        }

        if ($request->has('kelas_id') && !empty($request->kelas_id)) {
            $kelasQuery->where('id', $request->kelas_id);
        }

        if ($request->has('jurusan_id') && !empty($request->jurusan_id)) {
            $kelasQuery->where('jurusan_id', $request->jurusan_id);
        }

        $sortBy = $request->input('sort_by');
        $sortDir = $request->input('sort_dir', 'asc');
        if ($sortBy) {
            $this->applySorting($kelasQuery, $sortBy, $sortDir, $startDate, $endDate);
        } else {
            $kelasQuery->orderBy('nama_kelas');
        }

        $allKelas = $kelasQuery->get();

        $summary = [];
        foreach ($allKelas as $k) {
            $summary[$k->id] = [
                'H' => 0, 'I' => 0, 'S' => 0, 'A' => 0, 'B' => 0, 'T' => 0
            ];
        }

        if ($allKelas->count() > 0) {
            $attendances = Attendance::whereBetween('tanggal', [$startDate, $endDate])
                ->whereHas('student', function ($query) use ($allKelas) {
                    $query->whereIn('kelas_id', $allKelas->pluck('id'));
                })
                ->with('student:id,kelas_id')
                ->get();

            foreach ($attendances as $att) {
                if ($att->student && isset($summary[$att->student->kelas_id])) {
                    $status = $att->status;
                    if (isset($summary[$att->student->kelas_id][$status])) {
                        $summary[$att->student->kelas_id][$status]++;
                    }
                }
            }
        }

        // Fetch signature metadata from settings
        $schoolId = auth()->user()->isSuperAdmin()
            ? ($allKelas->first()->school_id ?? null)
            : auth()->user()->school_id;

        $signatureLocation = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'alamat_ttd')->value('setting_value') ?? '';
        $namaKepsek       = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'nama_kepala_sekolah')->value('setting_value') ?? '';
        $nipKepsek        = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'nip_kepala_sekolah')->value('setting_value') ?? '';
        $namaWaka         = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'nama_waka_kesiswaan')->value('setting_value') ?? '';
        $nipWaka          = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'nip_waka_kesiswaan')->value('setting_value') ?? '';
        $kopSurat         = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'kop_surat')->value('setting_value') ?? '';
        $schoolName       = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'nama_sekolah')->value('setting_value') ?? '';
        $schoolAddress    = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'alamat_sekolah')->value('setting_value') ?? '';

        $pdf = Pdf::loadView('rekap-kelas.pdf', compact(
            'allKelas', 'summary', 'startDate', 'endDate',
            'signatureLocation', 'namaKepsek', 'nipKepsek',
            'namaWaka', 'nipWaka', 'kopSurat', 'schoolName', 'schoolAddress'
        ));
        return $pdf->stream('Rekap_Kelas.pdf');
    }

    /**
     * Apply sorting to Kelas query.
     */
    private function applySorting($query, $sortBy, $sortDir, $startDate, $endDate)
    {
        $direction = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';

        switch ($sortBy) {
            case 'nama_kelas':
            case 'kelas':
                $query->orderBy('nama_kelas', $direction);
                break;
            case 'hadir':
                $sub = "(SELECT COUNT(*) FROM attendance JOIN siswa ON attendance.student_id = siswa.id WHERE siswa.kelas_id = kelas.id AND attendance.tanggal BETWEEN '{$startDate}' AND '{$endDate}' AND attendance.status IN ('H', 'Hadir'))";
                $query->orderByRaw("{$sub} {$direction}")->orderBy('nama_kelas', 'asc');
                break;
            case 'tidak_hadir':
                $sub = "(SELECT COUNT(*) FROM attendance JOIN siswa ON attendance.student_id = siswa.id WHERE siswa.kelas_id = kelas.id AND attendance.tanggal BETWEEN '{$startDate}' AND '{$endDate}' AND attendance.status IN ('I', 'S', 'B', 'A', 'Izin', 'Sakit', 'Bolos', 'Alpha', 'Tidak Hadir'))";
                $query->orderByRaw("{$sub} {$direction}")->orderBy('nama_kelas', 'asc');
                break;
            case 'telat':
                $sub = "(SELECT COUNT(*) FROM attendance JOIN siswa ON attendance.student_id = siswa.id WHERE siswa.kelas_id = kelas.id AND attendance.tanggal BETWEEN '{$startDate}' AND '{$endDate}' AND attendance.status IN ('T', 'Telat'))";
                $query->orderByRaw("{$sub} {$direction}")->orderBy('nama_kelas', 'asc');
                break;
            case 'izin':
                $sub = "(SELECT COUNT(*) FROM attendance JOIN siswa ON attendance.student_id = siswa.id WHERE siswa.kelas_id = kelas.id AND attendance.tanggal BETWEEN '{$startDate}' AND '{$endDate}' AND attendance.status IN ('I', 'Izin'))";
                $query->orderByRaw("{$sub} {$direction}")->orderBy('nama_kelas', 'asc');
                break;
            case 'sakit':
                $sub = "(SELECT COUNT(*) FROM attendance JOIN siswa ON attendance.student_id = siswa.id WHERE siswa.kelas_id = kelas.id AND attendance.tanggal BETWEEN '{$startDate}' AND '{$endDate}' AND attendance.status IN ('S', 'Sakit'))";
                $query->orderByRaw("{$sub} {$direction}")->orderBy('nama_kelas', 'asc');
                break;
            case 'bolos':
                $sub = "(SELECT COUNT(*) FROM attendance JOIN siswa ON attendance.student_id = siswa.id WHERE siswa.kelas_id = kelas.id AND attendance.tanggal BETWEEN '{$startDate}' AND '{$endDate}' AND attendance.status IN ('B', 'Bolos'))";
                $query->orderByRaw("{$sub} {$direction}")->orderBy('nama_kelas', 'asc');
                break;
            case 'alpha':
                $sub = "(SELECT COUNT(*) FROM attendance JOIN siswa ON attendance.student_id = siswa.id WHERE siswa.kelas_id = kelas.id AND attendance.tanggal BETWEEN '{$startDate}' AND '{$endDate}' AND attendance.status IN ('A', 'Alpha'))";
                $query->orderByRaw("{$sub} {$direction}")->orderBy('nama_kelas', 'asc');
                break;
            case 'persentase':
                $subHadir = "(SELECT COUNT(*) FROM attendance JOIN siswa ON attendance.student_id = siswa.id WHERE siswa.kelas_id = kelas.id AND attendance.tanggal BETWEEN '{$startDate}' AND '{$endDate}' AND attendance.status IN ('H', 'Hadir', 'T', 'Telat'))";
                $subTotal = "(SELECT COUNT(*) FROM attendance JOIN siswa ON attendance.student_id = siswa.id WHERE siswa.kelas_id = kelas.id AND attendance.tanggal BETWEEN '{$startDate}' AND '{$endDate}' AND attendance.status IN ('H', 'Hadir', 'T', 'Telat', 'I', 'Izin', 'S', 'Sakit', 'B', 'Bolos', 'A', 'Alpha', 'Tidak Hadir'))";
                $query->orderByRaw("COALESCE({$subHadir} * 100.0 / NULLIF({$subTotal}, 0), 0) {$direction}")->orderBy('nama_kelas', 'asc');
                break;
            default:
                $query->orderBy('nama_kelas', 'asc');
                break;
        }

        return $query;
    }
}
