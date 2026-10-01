<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AbsensiGuru;
use App\Models\Guru;
use App\Models\Shift;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class RekapGuruController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $guruId = $request->input('guru_id');
        $shiftId = $request->input('shift_id');

        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin()) ? auth()->user()->school_id : null;

        // Base Query untuk Guru
        $gurusQuery = Guru::with(['defaultShift', 'school']);

        if ($schoolId) {
            $gurusQuery->where('school_id', $schoolId);
        }

        if ($guruId) {
            $gurusQuery->where('id', $guruId);
        }

        if ($shiftId) {
            $gurusQuery->where(function ($q) use ($shiftId) {
                $q->where('default_shift_id', $shiftId)
                  ->orWhereHas('shifts', function ($sq) use ($shiftId) {
                      $sq->where('shifts.id', $shiftId);
                  });
            });
        }

        // Search guru by name or NIP
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $gurusQuery->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        // Apply Sorting
        $sortBy = $request->input('sort_by', 'nama');
        $sortDir = strtolower($request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $this->applySorting($gurusQuery, $sortBy, $sortDir, $startDate, $endDate);

        $allGurus = $gurusQuery->paginate(50)->withQueryString();

        // Fetch attendances for these paginated gurus in range
        $attendances = AbsensiGuru::whereBetween('tanggal', [$startDate, $endDate])
            ->whereIn('guru_id', $allGurus->pluck('id'))
            ->whereNull('jadwal_pelajaran_id')
            ->get();

        $summary = [];
        foreach ($allGurus as $g) {
            $summary[$g->id] = [
                'hadir' => 0,
                'terlambat' => 0,
                'tidak_hadir' => 0,
                'izin' => 0,
                'sakit' => 0,
                'alpha' => 0,
                'total' => 0,
                'persen' => 0,
            ];
        }

        foreach ($attendances as $att) {
            if (isset($summary[$att->guru_id])) {
                $st = $att->status;
                if ($st === 'Hadir' || $st === 'H') {
                    $summary[$att->guru_id]['hadir']++;
                } elseif ($st === 'Terlambat' || $st === 'Telat' || $st === 'T') {
                    $summary[$att->guru_id]['terlambat']++;
                } elseif ($st === 'Izin' || $st === 'I') {
                    $summary[$att->guru_id]['izin']++;
                    $summary[$att->guru_id]['tidak_hadir']++;
                } elseif ($st === 'Sakit' || $st === 'S') {
                    $summary[$att->guru_id]['sakit']++;
                    $summary[$att->guru_id]['tidak_hadir']++;
                } else {
                    $summary[$att->guru_id]['alpha']++;
                    $summary[$att->guru_id]['tidak_hadir']++;
                }
            }
        }

        foreach ($allGurus as $g) {
            $s = &$summary[$g->id];
            $tot = $s['hadir'] + $s['terlambat'] + $s['tidak_hadir'];
            $s['total'] = $tot;
            $s['persen'] = $tot > 0 ? round((($s['hadir'] + $s['terlambat']) / $tot) * 100, 1) : 0;
        }

        // Global stats in range
        $allSchoolAttendances = AbsensiGuru::whereBetween('tanggal', [$startDate, $endDate])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->whereNull('jadwal_pelajaran_id')
            ->get(['status']);

        $stats = [
            'total_guru' => $allGurus->total(),
            'total' => $allSchoolAttendances->count(),
            'hadir' => $allSchoolAttendances->whereIn('status', ['Hadir', 'H'])->count(),
            'terlambat' => $allSchoolAttendances->whereIn('status', ['Terlambat', 'Telat', 'T'])->count(),
            'izin' => $allSchoolAttendances->whereIn('status', ['Izin', 'I'])->count(),
            'sakit' => $allSchoolAttendances->whereIn('status', ['Sakit', 'S'])->count(),
            'tidak_hadir' => $allSchoolAttendances->whereIn('status', ['Tidak Hadir', 'Alpha', 'Izin', 'Sakit', 'I', 'S', 'A'])->count(),
            'alpha' => $allSchoolAttendances->whereIn('status', ['Tidak Hadir', 'Alpha', 'A'])->count(),
        ];

        // List for dropdown filters
        $gurusListQuery = Guru::orderBy('nama');
        if ($schoolId) {
            $gurusListQuery->where('school_id', $schoolId);
        }
        $gurusList = $gurusListQuery->get(['id', 'nama']);

        $shiftsQuery = Shift::where('is_active', true);
        if ($schoolId) {
            $shiftsQuery->where('school_id', $schoolId);
        }
        $shifts = $shiftsQuery->orderBy('jam_masuk')->get();

        return view('rekap-guru.index', compact('allGurus', 'summary', 'gurusList', 'shifts', 'startDate', 'endDate', 'guruId', 'shiftId', 'stats'));
    }

    public function export(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $guruId = $request->input('guru_id');
        $shiftId = $request->input('shift_id');
        $search = $request->input('search');
        $sortBy = $request->input('sort_by');
        $sortDir = $request->input('sort_dir', 'asc');

        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin()) ? auth()->user()->school_id : null;

        $gurusQuery = Guru::with(['defaultShift']);
        if ($schoolId) {
            $gurusQuery->where('school_id', $schoolId);
        }
        if ($guruId) {
            $gurusQuery->where('id', $guruId);
        }
        if ($shiftId) {
            $gurusQuery->where(function ($q) use ($shiftId) {
                $q->where('default_shift_id', $shiftId)
                  ->orWhereHas('shifts', function ($sq) use ($shiftId) {
                      $sq->where('shifts.id', $shiftId);
                  });
            });
        }
        if ($search) {
            $gurusQuery->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        if ($sortBy) {
            $this->applySorting($gurusQuery, $sortBy, $sortDir, $startDate, $endDate);
        } else {
            $gurusQuery->orderBy('nama', 'asc');
        }

        $allGurus = $gurusQuery->get();

        $attendances = AbsensiGuru::whereBetween('tanggal', [$startDate, $endDate])
            ->whereIn('guru_id', $allGurus->pluck('id'))
            ->whereNull('jadwal_pelajaran_id')
            ->get();

        $summary = [];
        foreach ($allGurus as $g) {
            $summary[$g->id] = [
                'hadir' => 0, 'terlambat' => 0, 'tidak_hadir' => 0,
                'izin' => 0, 'sakit' => 0, 'alpha' => 0, 'total' => 0, 'persen' => 0
            ];
        }

        foreach ($attendances as $att) {
            if (isset($summary[$att->guru_id])) {
                $st = $att->status;
                if ($st === 'Hadir' || $st === 'H') {
                    $summary[$att->guru_id]['hadir']++;
                } elseif ($st === 'Terlambat' || $st === 'Telat' || $st === 'T') {
                    $summary[$att->guru_id]['terlambat']++;
                } elseif ($st === 'Izin' || $st === 'I') {
                    $summary[$att->guru_id]['izin']++;
                    $summary[$att->guru_id]['tidak_hadir']++;
                } elseif ($st === 'Sakit' || $st === 'S') {
                    $summary[$att->guru_id]['sakit']++;
                    $summary[$att->guru_id]['tidak_hadir']++;
                } else {
                    $summary[$att->guru_id]['alpha']++;
                    $summary[$att->guru_id]['tidak_hadir']++;
                }
            }
        }

        foreach ($allGurus as $g) {
            $s = &$summary[$g->id];
            $tot = $s['hadir'] + $s['terlambat'] + $s['tidak_hadir'];
            $s['total'] = $tot;
            $s['persen'] = $tot > 0 ? round((($s['hadir'] + $s['terlambat']) / $tot) * 100, 1) : 0;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'REKAP ABSENSI GURU & STAFF');
        $sheet->setCellValue('A2', "Periode: $startDate s/d $endDate");

        $headers = ['No', 'Nama Guru / Staff', 'NIP', 'Shift Utama', 'Hadir', 'Terlambat', 'Tidak Hadir', 'Izin', 'Sakit', 'Alpha', '% Hadir'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $col++;
        }

        $row = 5;
        $no = 1;
        $totHadir = 0; $totTerlambat = 0; $totTidakHadir = 0; $totIzin = 0; $totSakit = 0; $totAlpha = 0;

        foreach ($allGurus as $g) {
            $s = $summary[$g->id];

            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $g->nama);
            $sheet->setCellValue('C' . $row, $g->nip ?? '-');
            $sheet->setCellValue('D' . $row, $g->defaultShift ? $g->defaultShift->nama_shift : '-');
            $sheet->setCellValue('E' . $row, $s['hadir']);
            $sheet->setCellValue('F' . $row, $s['terlambat']);
            $sheet->setCellValue('G' . $row, $s['tidak_hadir']);
            $sheet->setCellValue('H' . $row, $s['izin']);
            $sheet->setCellValue('I' . $row, $s['sakit']);
            $sheet->setCellValue('J' . $row, $s['alpha']);
            $sheet->setCellValue('K' . $row, $s['persen'] . '%');

            $totHadir += $s['hadir'];
            $totTerlambat += $s['terlambat'];
            $totTidakHadir += $s['tidak_hadir'];
            $totIzin += $s['izin'];
            $totSakit += $s['sakit'];
            $totAlpha += $s['alpha'];

            $row++;
        }

        if ($allGurus->count() > 0) {
            $sheet->setCellValue('A' . $row, 'TOTAL');
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $sheet->setCellValue('E' . $row, $totHadir);
            $sheet->setCellValue('F' . $row, $totTerlambat);
            $sheet->setCellValue('G' . $row, $totTidakHadir);
            $sheet->setCellValue('H' . $row, $totIzin);
            $sheet->setCellValue('I' . $row, $totSakit);
            $sheet->setCellValue('J' . $row, $totAlpha);
            $totGrand = $totHadir + $totTerlambat + $totTidakHadir;
            $totPersen = $totGrand > 0 ? round((($totHadir + $totTerlambat) / $totGrand) * 100, 1) : 0;
            $sheet->setCellValue('K' . $row, $totPersen . '%');
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = "rekap-absensi-guru-{$startDate}-to-{$endDate}.xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . urlencode($fileName) . '"');
        $writer->save('php://output');
        exit;
    }

    public function printPdf(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $guruId = $request->input('guru_id');
        $shiftId = $request->input('shift_id');

        $schoolId = auth()->user()->isSuperAdmin() ? ($guruId ? Guru::find($guruId)->school_id : null) : auth()->user()->school_id;

        $gurusQuery = Guru::with(['defaultShift']);

        if ($schoolId) {
            $gurusQuery->where('school_id', $schoolId);
        }
        if ($guruId) {
            $gurusQuery->where('id', $guruId);
        }
        if ($shiftId) {
            $gurusQuery->where(function ($q) use ($shiftId) {
                $q->where('default_shift_id', $shiftId)
                  ->orWhereHas('shifts', function ($sq) use ($shiftId) {
                      $sq->where('shifts.id', $shiftId);
                  });
            });
        }
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $gurusQuery->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by');
        $sortDir = $request->input('sort_dir', 'asc');
        if ($sortBy) {
            $this->applySorting($gurusQuery, $sortBy, $sortDir, $startDate, $endDate);
        } else {
            $gurusQuery->orderBy('nama');
        }

        $allGurus = $gurusQuery->get();

        $attendances = AbsensiGuru::whereBetween('tanggal', [$startDate, $endDate])
            ->whereIn('guru_id', $allGurus->pluck('id'))
            ->whereNull('jadwal_pelajaran_id')
            ->get();

        $summary = [];
        foreach ($allGurus as $g) {
            $summary[$g->id] = [
                'hadir' => 0,
                'terlambat' => 0,
                'tidak_hadir' => 0,
                'izin' => 0,
                'sakit' => 0,
                'alpha' => 0,
                'total' => 0,
                'persen' => 0,
            ];
        }

        foreach ($attendances as $att) {
            if (isset($summary[$att->guru_id])) {
                $st = $att->status;
                if ($st === 'Hadir' || $st === 'H') {
                    $summary[$att->guru_id]['hadir']++;
                } elseif ($st === 'Terlambat' || $st === 'Telat' || $st === 'T') {
                    $summary[$att->guru_id]['terlambat']++;
                } elseif ($st === 'Izin' || $st === 'I') {
                    $summary[$att->guru_id]['izin']++;
                    $summary[$att->guru_id]['tidak_hadir']++;
                } elseif ($st === 'Sakit' || $st === 'S') {
                    $summary[$att->guru_id]['sakit']++;
                    $summary[$att->guru_id]['tidak_hadir']++;
                } else {
                    $summary[$att->guru_id]['alpha']++;
                    $summary[$att->guru_id]['tidak_hadir']++;
                }
            }
        }

        foreach ($allGurus as $g) {
            $s = &$summary[$g->id];
            $tot = $s['hadir'] + $s['terlambat'] + $s['tidak_hadir'];
            $s['total'] = $tot;
            $s['persen'] = $tot > 0 ? round((($s['hadir'] + $s['terlambat']) / $tot) * 100, 1) : 0;
        }

        $stats = [
            'total_guru' => $allGurus->count(),
            'hadir' => collect($summary)->sum('hadir'),
            'terlambat' => collect($summary)->sum('terlambat'),
            'tidak_hadir' => collect($summary)->sum('tidak_hadir'),
            'izin' => collect($summary)->sum('izin'),
            'sakit' => collect($summary)->sum('sakit'),
            'alpha' => collect($summary)->sum('alpha'),
        ];

        if (!$schoolId && $allGurus->count() > 0) {
            $schoolId = $allGurus->first()->school_id;
        }

        $schoolName = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'nama_sekolah')->value('setting_value');
        $schoolAddress = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'alamat_sekolah')->value('setting_value');
        $kopSurat = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'kop_surat')->value('setting_value');

        $pdf = Pdf::loadView('rekap-guru.pdf', compact('allGurus', 'summary', 'stats', 'startDate', 'endDate', 'schoolName', 'schoolAddress', 'kopSurat'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('rekap-absensi-guru-' . $startDate . '-to-' . $endDate . '.pdf');
    }

    /**
     * Apply sorting to Guru query.
     */
    private function applySorting($query, $sortBy, $sortDir, $startDate, $endDate)
    {
        $direction = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';

        switch ($sortBy) {
            case 'nama':
                $query->orderBy('nama', $direction);
                break;
            case 'nip':
                $query->orderBy('nip', $direction)->orderBy('nama', 'asc');
                break;
            case 'hadir':
                $query->withCount(['absensi as count_hadir' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('tanggal', [$startDate, $endDate])
                      ->whereNull('jadwal_pelajaran_id')
                      ->whereIn('status', ['Hadir', 'H']);
                }])->orderBy('count_hadir', $direction)->orderBy('nama', 'asc');
                break;
            case 'terlambat':
            case 'telat':
                $query->withCount(['absensi as count_terlambat' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('tanggal', [$startDate, $endDate])
                      ->whereNull('jadwal_pelajaran_id')
                      ->whereIn('status', ['Terlambat', 'Telat', 'T']);
                }])->orderBy('count_terlambat', $direction)->orderBy('nama', 'asc');
                break;
            case 'tidak_hadir':
                $query->withCount(['absensi as count_tidak_hadir' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('tanggal', [$startDate, $endDate])
                      ->whereNull('jadwal_pelajaran_id')
                      ->whereIn('status', ['Izin', 'Sakit', 'Alpha', 'Tidak Hadir', 'I', 'S', 'A']);
                }])->orderBy('count_tidak_hadir', $direction)->orderBy('nama', 'asc');
                break;
            case 'izin':
                $query->withCount(['absensi as count_izin' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('tanggal', [$startDate, $endDate])
                      ->whereNull('jadwal_pelajaran_id')
                      ->whereIn('status', ['Izin', 'I']);
                }])->orderBy('count_izin', $direction)->orderBy('nama', 'asc');
                break;
            case 'sakit':
                $query->withCount(['absensi as count_sakit' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('tanggal', [$startDate, $endDate])
                      ->whereNull('jadwal_pelajaran_id')
                      ->whereIn('status', ['Sakit', 'S']);
                }])->orderBy('count_sakit', $direction)->orderBy('nama', 'asc');
                break;
            case 'alpha':
                $query->withCount(['absensi as count_alpha' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('tanggal', [$startDate, $endDate])
                      ->whereNull('jadwal_pelajaran_id')
                      ->whereIn('status', ['Alpha', 'Tidak Hadir', 'A']);
                }])->orderBy('count_alpha', $direction)->orderBy('nama', 'asc');
                break;
            case 'persentase':
                $subHadir = "(SELECT COUNT(*) FROM absensi_guru WHERE absensi_guru.guru_id = guru.id AND absensi_guru.jadwal_pelajaran_id IS NULL AND absensi_guru.tanggal BETWEEN '{$startDate}' AND '{$endDate}' AND absensi_guru.status IN ('Hadir', 'H', 'Terlambat', 'Telat', 'T'))";
                $subTotal = "(SELECT COUNT(*) FROM absensi_guru WHERE absensi_guru.guru_id = guru.id AND absensi_guru.jadwal_pelajaran_id IS NULL AND absensi_guru.tanggal BETWEEN '{$startDate}' AND '{$endDate}')";
                $query->orderByRaw("COALESCE({$subHadir} * 100.0 / NULLIF({$subTotal}, 0), 0) {$direction}")->orderBy('nama', 'asc');
                break;
            default:
                $query->orderBy('nama', 'asc');
                break;
        }

        return $query;
    }
}

