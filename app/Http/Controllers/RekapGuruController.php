<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AbsensiGuru;
use App\Models\Guru;
use App\Models\Shift;
use App\Exports\RekapGuruExport;
use Maatwebsite\Excel\Facades\Excel;
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

        // Query Absensi Harian (jadwal_pelajaran_id IS NULL)
        $query = AbsensiGuru::with(['guru', 'shift'])
            ->whereNull('jadwal_pelajaran_id')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal', 'desc')
            ->orderBy('jam_masuk', 'desc');

        if ($guruId) {
            $query->where('guru_id', $guruId);
        }

        if ($shiftId) {
            $query->where('shift_id', $shiftId);
        }

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        // Search functionality for guru name
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->whereHas('guru', function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            });
        }

        // Statistics based on the full query
        $statsQuery = clone $query;
        $allStats = $statsQuery->get();

        $stats = [
            'total' => $allStats->count(),
            'hadir' => $allStats->where('status', 'Hadir')->count(),
            'terlambat' => $allStats->where('status', 'Terlambat')->count(),
            'izin' => $allStats->where('status', 'Izin')->count(),
            'sakit' => $allStats->where('status', 'Sakit')->count(),
            'tidak_hadir' => $allStats->whereIn('status', ['Tidak Hadir', 'Alpha'])->count(),
        ];

        $absensi = $query->paginate(50)->withQueryString();

        $gurusQuery = Guru::orderBy('nama');
        if ($schoolId) {
            $gurusQuery->where('school_id', $schoolId);
        }
        $gurus = $gurusQuery->get();

        $shiftsQuery = Shift::where('is_active', true);
        if ($schoolId) {
            $shiftsQuery->where('school_id', $schoolId);
        }
        $shifts = $shiftsQuery->orderBy('jam_masuk')->get();

        return view('rekap-guru.index', compact('absensi', 'gurus', 'shifts', 'startDate', 'endDate', 'guruId', 'shiftId', 'stats'));
    }

    public function export(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $guruId = $request->input('guru_id');
        $shiftId = $request->input('shift_id');

        $fileName = 'rekap-absensi-guru-' . $startDate . '-to-' . $endDate . '.xlsx';

        return Excel::download(new RekapGuruExport($startDate, $endDate, $guruId, $shiftId), $fileName);
    }

    public function printPdf(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $guruId = $request->input('guru_id');
        $shiftId = $request->input('shift_id');

        $schoolId = auth()->user()->isSuperAdmin() ? ($guruId ? Guru::find($guruId)->school_id : null) : auth()->user()->school_id;

        $query = AbsensiGuru::with(['guru', 'shift', 'jadwal.mapel', 'jadwal.kelas'])
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal', 'asc')
            ->orderBy('jam_masuk', 'asc');

        if ($guruId) {
            $query->where('guru_id', $guruId);
        }

        if ($shiftId) {
            $query->where('shift_id', $shiftId);
        }

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $absensi = $query->get();

        $stats = [
            'total' => $absensi->count(),
            'hadir' => $absensi->where('status', 'Hadir')->count(),
            'terlambat' => $absensi->where('status', 'Terlambat')->count(),
            'tidak_hadir' => $absensi->whereIn('status', ['Tidak Hadir', 'Alpha'])->count(),
        ];

        if (!$schoolId && $absensi->count() > 0) {
            $schoolId = $absensi->first()->school_id;
        }

        $schoolName = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'nama_sekolah')->value('setting_value');
        $schoolAddress = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'alamat_sekolah')->value('setting_value');
        $kopSurat = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'kop_surat')->value('setting_value');

        $pdf = Pdf::loadView('rekap-guru.pdf', compact('absensi', 'startDate', 'endDate', 'stats', 'schoolName', 'schoolAddress', 'kopSurat'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('rekap-absensi-guru-' . $startDate . '-to-' . $endDate . '.pdf');
    }
}
