<?php

namespace App\Http\Controllers;

use App\Models\AbsensiGuru;
use App\Models\Guru;
use App\Models\JadwalPelajaran;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TeacherAttendanceReportController extends Controller
{
    public function index(Request $request)
    {
        // 1. Determine Date (default Today)
        $dateStr = $request->input('tanggal', Carbon::now()->format('Y-m-d'));
        $date = Carbon::parse($dateStr);
        
        // 2. Determine Day Name (Indonesian)
        $dayNames = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu'
        ];
        $dayName = $dayNames[$date->format('l')];

        // 3. Filter Options
        $guruId = $request->input('guru_id');

        // 4. Query Schedule (JadwalPelajaran) for that Day
        $query = JadwalPelajaran::with(['guru', 'kelas', 'mapel', 'absensis' => function($q) use ($dateStr) {
            $q->where('tanggal', $dateStr);
        }])
        ->where('hari', $dayName);

        if ($guruId) {
            $query->where('guru_id', $guruId);
        }

        $schedules = $query->orderBy('jam_mulai')->get();

        // 5. Get List of Teachers for Filter
        $gurus = Guru::orderBy('nama')->get();

        return view('teacher-attendance.index', compact('schedules', 'gurus', 'dateStr', 'dayName'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'guru_id' => 'required|exists:guru,id',
            'jadwal_pelajaran_id' => 'required|exists:jadwal_pelajaran,id',
            'tanggal' => 'required|date',
            'status' => 'required|in:Hadir,Tidak Hadir',
        ]);

        $data = [
            'guru_id' => $request->guru_id,
            'jadwal_pelajaran_id' => $request->jadwal_pelajaran_id,
            'tanggal' => $request->tanggal,
        ];

        // Jika Hadir, set waktu_hadir sekarang (jika belum ada) atau tetap
        // Tapi biasanya admin yang input, jadi waktu_hadir bisa null atau now()
        // Kita set now() jika status Hadir dan belum punya waktu_hadir
        
        $updateData = [
            'status' => $request->status,
        ];

        if ($request->status == 'Hadir') {
             // Opsional: set waktu hadir jika record baru
             $updateData['waktu_hadir'] = Carbon::now(); 
        } else {
             $updateData['waktu_hadir'] = null; // Reset jika tidak hadir? Atau biarkan? 
             // Better to nullify if not Present
        }

        AbsensiGuru::updateOrCreate($data, $updateData);

        return redirect()->back()->with('success', 'Status absensi berhasil diperbarui.');
    }
}
