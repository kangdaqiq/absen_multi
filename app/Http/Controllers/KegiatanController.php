<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kegiatan;
use App\Models\KegiatanAttendance;
use App\Models\Siswa;
use Illuminate\Support\Facades\DB;

class KegiatanController extends Controller
{
    /**
     * Daftar kegiatan milik sekolah.
     */
    public function index(Request $request)
    {
        $query = Kegiatan::where('school_id', auth()->user()->school_id)
            ->withCount(['attendances as total_hadir' => function ($q) {
                $q->where('status', 'H');
            }])
            ->orderByDesc('tanggal_mulai');

        if ($request->filled('search')) {
            $query->where('nama_kegiatan', 'like', '%' . $request->search . '%');
        }

        $kegiatans = $query->paginate(20)->withQueryString();

        return view('kegiatan.index', compact('kegiatans'));
    }

    /**
     * Simpan kegiatan baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_kegiatan'  => 'required|string|max:100',
            'deskripsi'      => 'nullable|string|max:500',
            'tanggal_mulai'  => 'required|date',
            'frekuensi'      => 'required|string|in:harian,mingguan,bulanan',
            'jam_mulai'      => 'nullable|date_format:H:i',
            'jam_selesai'    => 'nullable|date_format:H:i|after:jam_mulai',
            'uid_kartu'      => 'nullable|string|max:50',
        ]);

        Kegiatan::create([
            'school_id'      => auth()->user()->school_id,
            'nama_kegiatan'  => $request->nama_kegiatan,
            'deskripsi'      => $request->deskripsi,
            'tanggal_mulai'  => $request->tanggal_mulai,
            'frekuensi'      => $request->frekuensi,
            'jam_mulai'      => $request->jam_mulai ?: null,
            'jam_selesai'    => $request->jam_selesai ?: null,
            'uid_kartu'      => $request->uid_kartu ? strtoupper(trim($request->uid_kartu)) : null,
            'is_active'      => true,
            'created_by'     => auth()->id(),
        ]);

        return back()->with('success', 'Kegiatan berhasil ditambahkan.');
    }

    /**
     * Update kegiatan.
     */
    public function update(Request $request, $id)
    {
        $kegiatan = Kegiatan::where('school_id', auth()->user()->school_id)
            ->findOrFail($id);

        $request->validate([
            'nama_kegiatan'  => 'required|string|max:100',
            'deskripsi'      => 'nullable|string|max:500',
            'tanggal_mulai'  => 'required|date',
            'frekuensi'      => 'required|string|in:harian,mingguan,bulanan',
            'jam_mulai'      => 'nullable|date_format:H:i',
            'jam_selesai'    => 'nullable|date_format:H:i|after:jam_mulai',
            'uid_kartu'      => 'nullable|string|max:50',
            'is_active'      => 'boolean',
        ]);

        $kegiatan->update([
            'nama_kegiatan'  => $request->nama_kegiatan,
            'deskripsi'      => $request->deskripsi,
            'tanggal_mulai'  => $request->tanggal_mulai,
            'frekuensi'      => $request->frekuensi,
            'jam_mulai'      => $request->jam_mulai ?: null,
            'jam_selesai'    => $request->jam_selesai ?: null,
            'uid_kartu'      => $request->uid_kartu ? strtoupper(trim($request->uid_kartu)) : null,
            'is_active'      => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Kegiatan berhasil diperbarui.');
    }

    /**
     * Hapus kegiatan.
     */
    public function destroy($id)
    {
        $kegiatan = Kegiatan::where('school_id', auth()->user()->school_id)
            ->findOrFail($id);

        $kegiatan->delete();

        return back()->with('success', 'Kegiatan berhasil dihapus.');
    }

    /**
     * Rekap absensi per kegiatan.
     */
    public function attendance(Request $request, $id)
    {
        $kegiatan = Kegiatan::where('school_id', auth()->user()->school_id)
            ->findOrFail($id);

        $tanggal = $request->input('tanggal', now()->format('Y-m-d'));

        $records = KegiatanAttendance::with(['student.kelas'])
            ->where('kegiatan_id', $kegiatan->id)
            ->where('tanggal', $tanggal)
            ->orderBy('jam_masuk')
            ->get();

        // Semua tanggal yang ada data
        $availableDates = KegiatanAttendance::where('kegiatan_id', $kegiatan->id)
            ->selectRaw('DATE(tanggal) as tanggal')
            ->distinct()
            ->orderByDesc('tanggal')
            ->pluck('tanggal');

        return view('kegiatan.attendance', compact('kegiatan', 'records', 'tanggal', 'availableDates'));
    }

    /**
     * Halaman Absen Kegiatan (Form & tabel absensi manual santri).
     */
    public function absen(Request $request)
    {
        $schoolId = auth()->user()->school_id;

        // 1. Ambil semua kegiatan aktif sekolah saat ini untuk pilihan dropdown
        $kegiatans = Kegiatan::where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('nama_kegiatan')
            ->get();

        // 2. Tentukan kegiatan terpilih
        $selectedKegiatanId = $request->input('kegiatan_id');
        if (!$selectedKegiatanId && $kegiatans->isNotEmpty()) {
            $selectedKegiatanId = $kegiatans->first()->id;
        }

        $kegiatan = null;
        if ($selectedKegiatanId) {
            $kegiatan = Kegiatan::where('school_id', $schoolId)->find($selectedKegiatanId);
        }

        // 3. Tentukan tanggal absensi (default hari ini)
        $tanggal = $request->input('tanggal', now()->format('Y-m-d'));

        // 4. Filter Kelas
        $kelasId = $request->input('kelas_id');
        $allKelas = \App\Models\Kelas::where('school_id', $schoolId)
            ->orderBy('nama_kelas')
            ->get();

        // 5. Dapatkan daftar siswa dan status kehadiran mereka
        $students = collect();
        if ($kegiatan) {
            $studentQuery = Siswa::where('siswa.school_id', $schoolId)
                ->with(['kelas'])
                ->orderBy('nama');

            if ($kelasId) {
                $studentQuery->where('kelas_id', $kelasId);
            }

            if ($request->filled('search')) {
                $search = $request->input('search');
                $studentQuery->where('nama', 'like', "%{$search}%");
            }

            $students = $studentQuery->paginate(20)->withQueryString();

            // Ambil record absensi kegiatan terpilih untuk tanggal ini
            $attendanceMap = KegiatanAttendance::where('kegiatan_id', $kegiatan->id)
                ->where('tanggal', $tanggal)
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->keyBy('student_id');

            foreach ($students as $student) {
                $student->attendance = $attendanceMap->get($student->id);
            }
        }

        return view('kegiatan.absen', compact(
            'kegiatans',
            'selectedKegiatanId',
            'kegiatan',
            'tanggal',
            'kelasId',
            'allKelas',
            'students'
        ));
    }

    /**
     * Simpan/update data kehadiran santri (AJAX).
     */
    public function updateAttendance(Request $request)
    {
        $request->validate([
            'kegiatan_id' => 'required|exists:kegiatans,id',
            'student_id'  => 'required|exists:siswa,id',
            'tanggal'     => 'required|date',
            'status'      => 'required|in:H,A', // H = Hadir, A = Alpha (hapus record)
            'keterangan'  => 'nullable|string|max:255',
        ]);

        $schoolId = auth()->user()->school_id;

        // Verifikasi kepemilikan data
        $kegiatan = Kegiatan::where('school_id', $schoolId)->findOrFail($request->kegiatan_id);
        $student  = Siswa::where('school_id', $schoolId)->findOrFail($request->student_id);

        if ($request->status === 'A') {
            // Hapus record untuk menandai Alpha
            KegiatanAttendance::where([
                'school_id'   => $schoolId,
                'kegiatan_id' => $request->kegiatan_id,
                'student_id'  => $request->student_id,
                'tanggal'     => $request->tanggal,
            ])->delete();

            return response()->json([
                'success' => true,
                'message' => 'Kehadiran dibatalkan (Alpha).'
            ]);
        } else {
            // Cari atau buat record kehadiran
            $attendance = KegiatanAttendance::where([
                'school_id'   => $schoolId,
                'kegiatan_id' => $request->kegiatan_id,
                'student_id'  => $request->student_id,
                'tanggal'     => $request->tanggal,
            ])->first();

            $isNew = !$attendance;
            $jam = now()->format('H:i:s');

            if ($isNew) {
                $attendance = KegiatanAttendance::create([
                    'school_id'   => $schoolId,
                    'kegiatan_id' => $request->kegiatan_id,
                    'student_id'  => $request->student_id,
                    'tanggal'     => $request->tanggal,
                    'jam_masuk'   => $jam,
                    'status'      => 'H',
                    'keterangan'  => $request->keterangan,
                ]);

                // Kirim notifikasi WhatsApp jika WA aktif
                $school = auth()->user()->school;
                if ($school && $school->wa_enabled) {
                    try {
                        $waService = app(\App\Services\WhatsAppService::class);
                        $formattedDate = \Carbon\Carbon::parse($request->tanggal)->translatedFormat('l, d F Y');
                        $waService->sendKegiatanCheckIn(
                            namaSiswa: $student->nama,
                            namaKegiatan: $kegiatan->nama_kegiatan,
                            jam: \Carbon\Carbon::parse($jam)->format('H:i'),
                            tanggal: $formattedDate,
                            schoolId: $schoolId,
                            phoneSiswa: $student->no_wa,
                            phoneOrtu: $student->wa_ortu
                        );
                    } catch (\Exception $e) {
                        \Log::error("Failed to send WA for manual kegiatan attendance: " . $e->getMessage());
                    }
                }
            } else {
                $attendance->update([
                    'status'     => 'H',
                    'keterangan' => $request->keterangan,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Kehadiran berhasil dicatat (Hadir).',
                'jam_masuk' => \Carbon\Carbon::parse($attendance->jam_masuk)->format('H:i')
            ]);
        }
    }

    /**
     * Halaman Rekap Kegiatan (Rekapitulasi kumulatif kehadiran).
     */
    public function rekap(Request $request)
    {
        $schoolId = auth()->user()->school_id;

        // 1. Tentukan tanggal mulai dan selesai (default awal bulan s.d hari ini)
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        // 2. Filter Kegiatan & Kelas
        $kegiatanId = $request->input('kegiatan_id');
        $kelasId    = $request->input('kelas_id');

        $kegiatans = Kegiatan::where('school_id', $schoolId)
            ->orderBy('nama_kegiatan')
            ->get();

        $allKelas = \App\Models\Kelas::where('school_id', $schoolId)
            ->orderBy('nama_kelas')
            ->get();

        // 3. Query Siswa dengan withCount kegiatanAttendances
        $studentQuery = Siswa::where('siswa.school_id', $schoolId)
            ->with(['kelas'])
            ->orderBy('nama');

        if ($kelasId) {
            $studentQuery->where('kelas_id', $kelasId);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $studentQuery->where('nama', 'like', "%{$search}%");
        }

        $studentQuery->withCount(['kegiatanAttendances as total_kehadiran' => function ($q) use ($startDate, $endDate, $kegiatanId) {
            $q->whereBetween('tanggal', [$startDate, $endDate])
              ->where('status', 'H');
            if ($kegiatanId) {
                $q->where('kegiatan_id', $kegiatanId);
            }
        }]);

        $students = $studentQuery->paginate(20)->withQueryString();

        // Hitung total pertemuan kegiatan dalam periode tanggal ini
        $meetingQuery = KegiatanAttendance::where('school_id', $schoolId)
            ->whereBetween('tanggal', [$startDate, $endDate]);
        
        if ($kegiatanId) {
            $meetingQuery->where('kegiatan_id', $kegiatanId);
        }

        $totalMeetings = $meetingQuery->selectRaw('COUNT(DISTINCT tanggal, kegiatan_id) as count')
            ->first()
            ->count ?? 0;

        return view('kegiatan.rekap', compact(
            'startDate',
            'endDate',
            'kegiatanId',
            'kelasId',
            'kegiatans',
            'allKelas',
            'students',
            'totalMeetings'
        ));
    }
}
