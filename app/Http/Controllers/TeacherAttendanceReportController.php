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

        // 2. Get School ID
        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin()) ? auth()->user()->school_id : null;

        // 3. Get Teachers
        $guruQuery = Guru::orderBy('nama');
        if ($schoolId) {
            $guruQuery->where('school_id', $schoolId);
        }
        $gurus = $guruQuery->get();

        // 4. Get Attendance for Date
        $attendanceQuery = AbsensiGuru::where('tanggal', $dateStr)
            ->whereNull('jadwal_pelajaran_id'); // Only daily records

        if ($schoolId) {
            $attendanceQuery->where('school_id', $schoolId);
        }
        $attendances = $attendanceQuery->get()->keyBy('guru_id');

        // 5. Merge Data
        $report = $gurus->map(function ($guru) use ($attendances) {
            $att = $attendances->get($guru->id);
            return [
                'guru' => $guru,
                'status' => $att ? $att->status : 'Belum Absen',
                'jam_masuk' => $att ? $att->jam_masuk : '-',
                'jam_pulang' => $att ? $att->jam_pulang : '-',
                'keterangan' => $att ? $att->keterangan : '',
                'attendance_id' => $att ? $att->id : null
            ];
        });

        return view('teacher-attendance.index', compact('report', 'dateStr', 'dayName'));
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
