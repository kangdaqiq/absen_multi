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

        // 6. Filter by Status (server-side)
        $filterStatus = $request->input('status', '');
        if ($filterStatus !== '') {
            $report = $report->filter(fn($item) => $item['status'] === $filterStatus)->values();
        }

        return view('teacher-attendance.index', compact('report', 'dateStr', 'dayName', 'filterStatus'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'guru_id' => 'required|exists:guru,id',
            'jadwal_pelajaran_id' => 'nullable|exists:jadwal_pelajaran,id',
            'tanggal' => 'required|date',
            'status' => 'required',
            'jam_masuk' => 'nullable|date_format:H:i',
            'jam_pulang' => 'nullable|date_format:H:i',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $data = [
            'guru_id' => $request->guru_id,
            'jadwal_pelajaran_id' => $request->jadwal_pelajaran_id,
            'tanggal' => $request->tanggal,
            'school_id' => auth()->user()->school_id ?? null,
        ];

        // Jika Hadir, set waktu_hadir sekarang (jika belum ada) atau tetap
        // Tapi biasanya admin yang input, jadi waktu_hadir bisa null atau now()
        // Kita set now() jika status Hadir dan belum punya waktu_hadir

        $updateData = [
            'status' => $request->status,
            'jam_masuk' => $request->jam_masuk,
            'jam_pulang' => $request->jam_pulang,
            'keterangan' => $request->keterangan,
        ];

        AbsensiGuru::updateOrCreate($data, $updateData);

        return redirect()->back()->with('success', 'Status absensi berhasil diperbarui.');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'guru_id' => 'required',
            'tanggal' => 'required|date',
        ]);

        $guruId = $request->guru_id;
        $date = $request->tanggal;

        $att = AbsensiGuru::where('guru_id', $guruId)
            ->where('tanggal', $date)
            ->whereNull('jadwal_pelajaran_id')
            ->first();

        if ($att) {
            $att->delete();
            return back()->with('success', 'Data absensi berhasil dihapus.');
        }

        return back()->with('error', 'Data absensi tidak ditemukan.');
    }
}
