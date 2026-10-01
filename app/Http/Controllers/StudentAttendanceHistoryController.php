<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Siswa;
use Carbon\Carbon;

class StudentAttendanceHistoryController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Cari relasi siswa
        $siswa = $user->student;
        if (!$siswa) {
            $cleanNis = str_replace('siswa_', '', $user->username);
            $siswa = Siswa::where('user_id', $user->id)
                ->orWhere('nis', $cleanNis)
                ->first();
            if ($siswa && \Illuminate\Support\Facades\Schema::hasColumn('siswa', 'user_id')) {
                $siswa->user_id = $user->id;
                $siswa->save();
            }
        }

        if (!$siswa) {
            return redirect()->route('dashboard')->with('error', 'Akun Anda belum terhubung dengan data master siswa.');
        }

        $siswa->load(['kelas', 'school']);

        // Filter Bulan dan Tahun (default ke bulan & tahun sekarang)
        $bulan = (int) $request->input('bulan', date('m'));
        $tahun = (int) $request->input('tahun', date('Y'));
        $search = trim($request->input('q', ''));

        // Query Presensi Siswa
        $query = Attendance::where('student_id', $siswa->id);

        if ($bulan && $tahun) {
            $query->whereYear('tanggal', $tahun)
                  ->whereMonth('tanggal', $bulan);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('keterangan', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%")
                  ->orWhere('tanggal', 'like', "%{$search}%");
            });
        }

        $attendances = $query->orderBy('tanggal', 'desc')->paginate(31)->withQueryString();

        // Statistik pada periode bulan/tahun yang dipilih
        $statsPeriod = [
            'H' => Attendance::where('student_id', $siswa->id)->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan)->where('status', 'H')->count(),
            'I' => Attendance::where('student_id', $siswa->id)->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan)->where('status', 'I')->count(),
            'S' => Attendance::where('student_id', $siswa->id)->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan)->where('status', 'S')->count(),
            'A' => Attendance::where('student_id', $siswa->id)->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan)->where('status', 'A')->count(),
            'T' => Attendance::where('student_id', $siswa->id)->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan)->where('status', 'H')->where(function($q) {
                $q->where('keterangan', 'like', 'Telat%')->orWhere('keterangan', 'like', 'Terlambat%');
            })->count(),
        ];

        $totalRecorded = $statsPeriod['H'] + $statsPeriod['I'] + $statsPeriod['S'] + $statsPeriod['A'];
        $rateHadir = $totalRecorded > 0 ? round(($statsPeriod['H'] / $totalRecorded) * 100, 1) : 100;

        return view('siswa.riwayat-kehadiran', compact(
            'siswa',
            'attendances',
            'statsPeriod',
            'rateHadir',
            'bulan',
            'tahun',
            'search'
        ));
    }
}
