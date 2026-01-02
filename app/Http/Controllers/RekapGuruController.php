<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AbsensiGuru;
use App\Models\Guru;
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

        $query = AbsensiGuru::with(['guru', 'jadwal.mapel', 'jadwal.kelas'])
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal', 'desc')
            ->orderBy('waktu_hadir', 'desc');

        if ($guruId) {
            $query->where('guru_id', $guruId);
        }

        $absensi = $query->get();
        $gurus = Guru::orderBy('nama')->get();

        // Statistics
        $stats = [
            'total' => $absensi->count(),
            'hadir' => $absensi->where('status', 'Hadir')->count(),
            'tidak_hadir' => $absensi->where('status', 'Tidak Hadir')->count(),
        ];

        return view('rekap-guru.index', compact('absensi', 'gurus', 'startDate', 'endDate', 'guruId', 'stats'));
    }

    public function export(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $guruId = $request->input('guru_id');

        $fileName = 'rekap-absensi-guru-' . $startDate . '-to-' . $endDate . '.xlsx';

        return Excel::download(new RekapGuruExport($startDate, $endDate, $guruId), $fileName);
    }

    public function printPdf(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $guruId = $request->input('guru_id');

        $query = AbsensiGuru::with(['guru', 'jadwal.mapel', 'jadwal.kelas'])
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal', 'asc')
            ->orderBy('waktu_hadir', 'asc');

        if ($guruId) {
            $query->where('guru_id', $guruId);
        }

        $absensi = $query->get();

        $stats = [
            'total' => $absensi->count(),
            'hadir' => $absensi->where('status', 'Hadir')->count(),
            'tidak_hadir' => $absensi->where('status', 'Tidak Hadir')->count(),
        ];

        $pdf = Pdf::loadView('rekap-guru.pdf', compact('absensi', 'startDate', 'endDate', 'stats'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('rekap-absensi-guru-' . $startDate . '-to-' . $endDate . '.pdf');
    }
}
