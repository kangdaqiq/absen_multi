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
            'frekuensi'      => 'required|string|in:harian,mingguan,bulanan,sekali',
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
            'frekuensi'      => 'required|string|in:harian,mingguan,bulanan,sekali',
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
            'status'      => 'required|in:H,I,S,A', // H = Hadir, I = Izin, S = Sakit, A = Alpha
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
                'message' => 'Status diubah menjadi Alpha.'
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
                    'status'      => $request->status,
                    'keterangan'  => $request->keterangan,
                ]);

                // Notifikasi WA kegiatan ke siswa/ortu dinonaktifkan (hanya masuk rekap).
                // Telegram tetap dikirim jika status Hadir.
                if ($request->status === 'H') {
                    try {
                        $telegramService = app(\App\Services\TelegramService::class);
                        $formattedDate = \Carbon\Carbon::parse($request->tanggal)->translatedFormat('l, d F Y');
                        $telegramService->sendKegiatanCheckIn(
                            namaSiswa: $student->nama,
                            namaKegiatan: $kegiatan->nama_kegiatan,
                            jam: \Carbon\Carbon::parse($jam)->format('H:i'),
                            tanggal: $formattedDate,
                            schoolId: $schoolId,
                            chatIdSiswa: $student->telegram_chat_id ?: null,
                            chatIdOrtu: $student->telegram_ortu_chat_id ?: null
                        );
                    } catch (\Exception $e) {
                        \Log::error("Failed to send Telegram for manual kegiatan attendance: " . $e->getMessage());
                    }
                }
            } else {
                $attendance->update([
                    'status'     => $request->status,
                    'keterangan' => $request->keterangan,
                ]);
            }

            $labelStatus = $request->status === 'H' ? 'Hadir' : ($request->status === 'I' ? 'Izin' : 'Sakit');

            return response()->json([
                'success'   => true,
                'message'   => "Status kehadiran berhasil diubah ({$labelStatus}).",
                'jam_masuk' => \Carbon\Carbon::parse($attendance->jam_masuk)->format('H:i')
            ]);
        }
    }

    /**
     * Absen massal (misal: Tandai Semua Hadir untuk kelas terpilih).
     */
    public function bulkUpdateAttendance(Request $request)
    {
        $request->validate([
            'kegiatan_id' => 'required|exists:kegiatans,id',
            'tanggal'     => 'required|date',
            'kelas_id'    => 'nullable|exists:kelas,id',
            'status'      => 'required|in:H,I,S,A',
        ]);

        $schoolId = auth()->user()->school_id;
        $kegiatan = Kegiatan::where('school_id', $schoolId)->findOrFail($request->kegiatan_id);

        $studentQuery = Siswa::where('school_id', $schoolId);
        if ($request->filled('kelas_id')) {
            $studentQuery->where('kelas_id', $request->kelas_id);
        }

        $studentIds = $studentQuery->pluck('id');
        $jam = now()->format('H:i:s');

        if ($request->status === 'A') {
            KegiatanAttendance::where('school_id', $schoolId)
                ->where('kegiatan_id', $kegiatan->id)
                ->where('tanggal', $request->tanggal)
                ->whereIn('student_id', $studentIds)
                ->delete();
        } else {
            foreach ($studentIds as $sId) {
                KegiatanAttendance::updateOrCreate(
                    [
                        'school_id'   => $schoolId,
                        'kegiatan_id' => $kegiatan->id,
                        'student_id'  => $sId,
                        'tanggal'     => $request->tanggal,
                    ],
                    [
                        'jam_masuk'   => $jam,
                        'status'      => $request->status,
                        'keterangan'  => 'Absen Massal (' . $request->status . ')',
                    ]
                );
            }
        }

        return back()->with('success', 'Berhasil memperbarui absensi massal kegiatan.');
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

        // 3. Query Siswa dengan count kehadiran per status
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

        $studentQuery->withCount([
            'kegiatanAttendances as count_hadir' => function ($q) use ($startDate, $endDate, $kegiatanId) {
                $q->whereBetween('tanggal', [$startDate, $endDate])->where('status', 'H');
                if ($kegiatanId) $q->where('kegiatan_id', $kegiatanId);
            },
            'kegiatanAttendances as count_izin' => function ($q) use ($startDate, $endDate, $kegiatanId) {
                $q->whereBetween('tanggal', [$startDate, $endDate])->where('status', 'I');
                if ($kegiatanId) $q->where('kegiatan_id', $kegiatanId);
            },
            'kegiatanAttendances as count_sakit' => function ($q) use ($startDate, $endDate, $kegiatanId) {
                $q->whereBetween('tanggal', [$startDate, $endDate])->where('status', 'S');
                if ($kegiatanId) $q->where('kegiatan_id', $kegiatanId);
            },
        ]);

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

    /**
     * Ekspor Rekap Kegiatan ke format CSV.
     */
    public function exportRekap(Request $request)
    {
        $schoolId = auth()->user()->school_id;

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));
        $kegiatanId = $request->input('kegiatan_id');
        $kelasId    = $request->input('kelas_id');

        $studentQuery = Siswa::where('siswa.school_id', $schoolId)
            ->with(['kelas'])
            ->orderBy('nama');

        if ($kelasId) {
            $studentQuery->where('kelas_id', $kelasId);
        }

        if ($request->filled('search')) {
            $studentQuery->where('nama', 'like', '%' . $request->search . '%');
        }

        $studentQuery->withCount([
            'kegiatanAttendances as count_hadir' => function ($q) use ($startDate, $endDate, $kegiatanId) {
                $q->whereBetween('tanggal', [$startDate, $endDate])->where('status', 'H');
                if ($kegiatanId) $q->where('kegiatan_id', $kegiatanId);
            },
            'kegiatanAttendances as count_izin' => function ($q) use ($startDate, $endDate, $kegiatanId) {
                $q->whereBetween('tanggal', [$startDate, $endDate])->where('status', 'I');
                if ($kegiatanId) $q->where('kegiatan_id', $kegiatanId);
            },
            'kegiatanAttendances as count_sakit' => function ($q) use ($startDate, $endDate, $kegiatanId) {
                $q->whereBetween('tanggal', [$startDate, $endDate])->where('status', 'S');
                if ($kegiatanId) $q->where('kegiatan_id', $kegiatanId);
            },
        ]);

        $students = $studentQuery->get();

        $filename = "rekap_kegiatan_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($students) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, ['No', 'NIS/NISN', 'Nama Siswa', 'Kelas', 'Hadir (H)', 'Izin (I)', 'Sakit (S)']);

            foreach ($students as $index => $s) {
                fputcsv($file, [
                    $index + 1,
                    $s->nisn ?? $s->nis ?? '-',
                    $s->nama,
                    $s->kelas->nama_kelas ?? '-',
                    $s->count_hadir ?? 0,
                    $s->count_izin ?? 0,
                    $s->count_sakit ?? 0,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
