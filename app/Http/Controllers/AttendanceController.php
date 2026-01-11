<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Siswa;
use App\Models\Kelas;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        // Filter by Date (default today)
        $tanggal = $request->input('tanggal', date('Y-m-d'));

        // Filter by Class (optional)
        $kelasId = $request->input('kelas_id');

        // Fetch all students (filtered by class if needed) to ensure we list everyone
        $siswaQuery = Siswa::with('kelas')->orderBy('nama');

        // Filter by school_id for non-super admin users
        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $siswaQuery->where('school_id', auth()->user()->school_id);
        }

        if ($kelasId) {
            $siswaQuery->where('kelas_id', $kelasId);
        }
        $allSiswa = $siswaQuery->get();

        // Fetch attendance for the date
        $attendance = Attendance::where('tanggal', $tanggal)->get()->keyBy('student_id');

        // Prepare data for view
        $data = [];
        foreach ($allSiswa as $s) {
            $att = $attendance[$s->id] ?? null;
            $data[] = (object) [
                'id' => $s->id, // Siswa ID
                'nama' => $s->nama,
                'kelas' => $s->kelas->nama_kelas ?? '-',
                'absen_id' => $att ? $att->id : null,
                'jam_masuk' => ($att && $att->jam_masuk) ? $att->jam_masuk : '-',
                'jam_pulang' => ($att && $att->jam_pulang) ? $att->jam_pulang : '-',
                'status' => $att ? $att->status : 'A', // Default Alpha if no record
                'keterangan' => $att ? $att->keterangan : '-',
            ];
        }

        $kelasQuery = Kelas::orderBy('nama_kelas');

        // Filter by school_id for non-super admin users
        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $kelasQuery->where('school_id', auth()->user()->school_id);
        }

        $allKelas = $kelasQuery->get();

        return view('absensi.index', compact('data', 'tanggal', 'allKelas', 'kelasId'));
    }

    // Manual Update (e.g., Izin, Sakit)
    public function update(Request $request)
    {
        $request->validate([
            'student_id' => 'required',
            'tanggal' => 'required|date',
            'status' => 'required|in:H,I,S,A,B,T',
            'keterangan' => 'nullable',
            'jam_masuk' => 'nullable', // Allow time format
            'jam_pulang' => 'nullable',
        ]);

        $status = $request->status;
        $studentId = $request->student_id;
        $date = $request->tanggal; // Should match the filter date

        // Check if record exists
        $att = Attendance::where('student_id', $studentId)->where('tanggal', $date)->first();

        // format time or null (Time only)
        $jamMasuk = ($request->jam_masuk && $request->jam_masuk != '-') ? \Carbon\Carbon::parse($request->jam_masuk)->format('H:i:s') : null;
        $jamPulang = ($request->jam_pulang && $request->jam_pulang != '-') ? \Carbon\Carbon::parse($request->jam_pulang)->format('H:i:s') : null;

        if ($att) {
            $att->update([
                'status' => $status,
                'keterangan' => $request->keterangan,
                'jam_masuk' => $jamMasuk,
                'jam_pulang' => $jamPulang
            ]);
        } else {
            // Create new record
            Attendance::create([
                'student_id' => $studentId,
                'tanggal' => $date,
                'status' => $status,
                'keterangan' => $request->keterangan,
                'jam_masuk' => $jamMasuk,
                'jam_pulang' => $jamPulang
            ]);
        }

        return back()->with('success', 'Status absensi berhasil diperbarui.');
    }

    // Delete Attendance Record
    public function destroy(Request $request)
    {
        $request->validate([
            'student_id' => 'required',
            'tanggal' => 'required|date',
        ]);

        $studentId = $request->student_id;
        $date = $request->tanggal;

        // Find and delete the attendance record
        $att = Attendance::where('student_id', $studentId)->where('tanggal', $date)->first();

        if ($att) {
            $att->delete();
            return back()->with('success', 'Data absensi berhasil dihapus.');
        }

        return back()->with('error', 'Data absensi tidak ditemukan.');
    }
}
