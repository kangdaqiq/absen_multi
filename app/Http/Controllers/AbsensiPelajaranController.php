<?php

namespace App\Http\Controllers;

use App\Models\AbsensiPelajaran;
use App\Models\JadwalPelajaran;
use App\Models\JurnalPelajaran;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Guru;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AbsensiPelajaranController extends Controller
{
    /**
     * Dashboard / Daftar Jadwal Mengajar Hari Ini
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $isGuru = !empty($user->guru);
        $guruId = $user->guru?->id;
        $role = $user->role;

        $isAdminOrWaka = in_array($role, ['admin', 'super_admin', 'waka_kurikulum']);

        // Default tanggal: hari ini
        $tanggal = $request->query('tanggal', Carbon::today()->format('Y-m-d'));
        try {
            $parsedDate = Carbon::parse($tanggal);
        } catch (\Exception $e) {
            $parsedDate = Carbon::today();
            $tanggal = $parsedDate->format('Y-m-d');
        }

        // Dapatkan nama hari dalam bahasa Indonesia (Senin - Minggu)
        $hariIndonesia = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
        $namaHari = $hariIndonesia[$parsedDate->dayOfWeekIso] ?? 'Senin';

        // Filter hari jika user secara spesifik memilih hari tertentu
        $filterHari = $request->query('hari', $namaHari);

        $query = JadwalPelajaran::where('school_id', $schoolId)
            ->with(['guru', 'kelas', 'mapel'])
            ->where('hari', $filterHari);

        // Jika bukan admin/waka dan merupakan guru, filter hanya jadwal guru tersebut
        if (!$isAdminOrWaka && $isGuru) {
            $query->where('guru_id', $guruId);
        } elseif ($request->filled('guru_id')) {
            $query->where('guru_id', $request->guru_id);
        }

        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        }

        if ($request->filled('mapel_id')) {
            $query->where('mapel_id', $request->mapel_id);
        }

        $jadwals = $query->orderBy('jam_mulai')->get();

        // Cek status apakah jadwal sudah diabsen pada tanggal tersebut
        $jadwalIds = $jadwals->pluck('id');
        $jurnals = JurnalPelajaran::where('school_id', $schoolId)
            ->whereIn('jadwal_pelajaran_id', $jadwalIds)
            ->where('tanggal', $tanggal)
            ->get()
            ->keyBy('jadwal_pelajaran_id');

        $absensiCounts = AbsensiPelajaran::where('school_id', $schoolId)
            ->whereIn('jadwal_pelajaran_id', $jadwalIds)
            ->where('tanggal', $tanggal)
            ->selectRaw('jadwal_pelajaran_id, 
                COUNT(*) as total_absen,
                SUM(CASE WHEN status = "H" THEN 1 ELSE 0 END) as count_h,
                SUM(CASE WHEN status = "S" THEN 1 ELSE 0 END) as count_s,
                SUM(CASE WHEN status = "I" THEN 1 ELSE 0 END) as count_i,
                SUM(CASE WHEN status = "A" THEN 1 ELSE 0 END) as count_a,
                SUM(CASE WHEN status = "T" THEN 1 ELSE 0 END) as count_t,
                SUM(CASE WHEN status = "D" THEN 1 ELSE 0 END) as count_d')
            ->groupBy('jadwal_pelajaran_id')
            ->get()
            ->keyBy('jadwal_pelajaran_id');

        foreach ($jadwals as $jadwal) {
            $jadwal->absensi_summary = $absensiCounts->get($jadwal->id);
            $jadwal->is_attended = !empty($jadwal->absensi_summary) && $jadwal->absensi_summary->total_absen > 0;

            // Multi-kelas parallel check: guru yang sama, mapel sama, hari & jam sama
            $parallel = $jadwals->filter(function ($item) use ($jadwal) {
                return $item->guru_id === $jadwal->guru_id
                    && $item->mapel_id === $jadwal->mapel_id
                    && $item->hari === $jadwal->hari
                    && $item->jam_mulai === $jadwal->jam_mulai
                    && $item->jam_selesai === $jadwal->jam_selesai;
            });
            $jadwal->is_multi_kelas = $parallel->count() > 1;
            $jadwal->parallel_kelases = $parallel->pluck('kelas.nama_kelas')->filter()->values()->all();
            $jadwal->parallel_count = $parallel->count();

            // Jurnal hari ini (ambil dari jadwal ini atau sister jadwal jika multi-kelas)
            $jurnalToday = $jurnals->get($jadwal->id);
            if (!$jurnalToday && $jadwal->is_multi_kelas) {
                foreach ($parallel as $pItem) {
                    if ($jurnals->has($pItem->id)) {
                        $jurnalToday = $jurnals->get($pItem->id);
                        break;
                    }
                }
            }
            $jadwal->jurnal_today = $jurnalToday;
        }

        $kelases = Kelas::where('school_id', $schoolId)->orderBy('nama_kelas')->get();
        $gurus = Guru::where('school_id', $schoolId)->orderBy('nama')->get();
        $mapels = Mapel::where('school_id', $schoolId)->orderBy('nama_mapel')->get();
        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        return view('absensi-pelajaran.index', compact(
            'jadwals',
            'tanggal',
            'namaHari',
            'filterHari',
            'kelases',
            'gurus',
            'mapels',
            'hariList',
            'isAdminOrWaka',
            'isGuru'
        ));
    }

    /**
     * Halaman Formulir Input Presensi Siswa & Jurnal KBM
     * Mendukung Sesi Multi-Kelas (Guru & Mapel sama di jam yang sama pada 2+ kelas)
     */
    public function presensi($jadwalId, Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $isGuru = !empty($user->guru);
        $guruId = $user->guru?->id;
        $isAdminOrWaka = in_array($user->role, ['admin', 'super_admin', 'waka_kurikulum']);

        $jadwal = JadwalPelajaran::where('school_id', $schoolId)
            ->with(['guru', 'kelas', 'mapel'])
            ->findOrFail($jadwalId);

        // Jika bukan admin/waka, hanya guru pemilik jadwal yang boleh mengabsen
        if (!$isAdminOrWaka && $isGuru && $jadwal->guru_id !== $guruId) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengabsen jadwal ini.');
        }

        $tanggal = $request->query('tanggal', Carbon::today()->format('Y-m-d'));
        try {
            $parsedDate = Carbon::parse($tanggal);
        } catch (\Exception $e) {
            $parsedDate = Carbon::today();
            $tanggal = $parsedDate->format('Y-m-d');
        }

        // Cari semua jadwal paralel (multi-kelas gabungan di jam, hari, mapel, & guru yang sama)
        $parallelJadwals = JadwalPelajaran::where('school_id', $schoolId)
            ->where('guru_id', $jadwal->guru_id)
            ->where('mapel_id', $jadwal->mapel_id)
            ->where('hari', $jadwal->hari)
            ->where('jam_mulai', $jadwal->jam_mulai)
            ->where('jam_selesai', $jadwal->jam_selesai)
            ->with(['guru', 'kelas', 'mapel'])
            ->orderBy('id')
            ->get();

        $isMultiKelas = $parallelJadwals->count() > 1;

        // Ambil Jurnal KBM jika sudah ada di salah satu jadwal paralel ini
        $jurnal = JurnalPelajaran::whereIn('jadwal_pelajaran_id', $parallelJadwals->pluck('id'))
            ->where('tanggal', $tanggal)
            ->latest('updated_at')
            ->first();

        $operatorGuruId = $isGuru ? $guruId : $jadwal->guru_id;
        $now = now();

        $classesData = [];
        $allStudents = collect();
        $globalStats = [
            'total' => 0,
            'H'     => 0,
            'I'     => 0,
            'S'     => 0,
            'A'     => 0,
        ];

        foreach ($parallelJadwals as $pj) {
            $classStudents = Siswa::where('school_id', $schoolId)
                ->where('kelas_id', $pj->kelas_id)
                ->orderBy('nama')
                ->get();

            $existingAttendance = AbsensiPelajaran::where('jadwal_pelajaran_id', $pj->id)
                ->where('tanggal', $tanggal)
                ->get()
                ->keyBy('student_id');

            // Auto-seed default Alpha (A) jika siswa belum memiliki record di database
            $toInsert = [];
            foreach ($classStudents as $student) {
                if (!$existingAttendance->has($student->id)) {
                    $toInsert[] = [
                        'school_id'           => $schoolId,
                        'jadwal_pelajaran_id' => $pj->id,
                        'jurnal_pelajaran_id' => $jurnal?->id,
                        'student_id'          => $student->id,
                        'guru_id'             => $operatorGuruId,
                        'tanggal'             => $tanggal,
                        'status'              => 'A', // DEFAULT ALPHA
                        'keterangan'          => null,
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ];
                }
            }

            if (!empty($toInsert)) {
                AbsensiPelajaran::insert($toInsert);
                $existingAttendance = AbsensiPelajaran::where('jadwal_pelajaran_id', $pj->id)
                    ->where('tanggal', $tanggal)
                    ->get()
                    ->keyBy('student_id');
            }

            // Pasangkan status ke masing-masing siswa
            foreach ($classStudents as $student) {
                $record = $existingAttendance->get($student->id);
                $student->presensi_status = $record?->status ?? 'A';
                $student->presensi_keterangan = $record?->keterangan ?? '';
                $student->presensi_id = $record?->id;
                $student->jadwal_id = $pj->id;
                $student->nama_kelas = $pj->kelas?->nama_kelas ?? '-';
            }

            $classStats = [
                'total' => $classStudents->count(),
                'H'     => $classStudents->where('presensi_status', 'H')->count(),
                'I'     => $classStudents->where('presensi_status', 'I')->count(),
                'S'     => $classStudents->where('presensi_status', 'S')->count(),
                'A'     => $classStudents->where('presensi_status', 'A')->count(),
            ];

            $globalStats['total'] += $classStats['total'];
            $globalStats['H']     += $classStats['H'];
            $globalStats['I']     += $classStats['I'];
            $globalStats['S']     += $classStats['S'];
            $globalStats['A']     += $classStats['A'];

            $classesData[] = (object) [
                'jadwal'     => $pj,
                'kelas'      => $pj->kelas,
                'students'   => $classStudents,
                'stats'      => $classStats,
                'is_current' => ($pj->id === $jadwal->id),
            ];

            $allStudents = $allStudents->concat($classStudents);
        }

        // Kompatibilitas variabel
        $students = $isMultiKelas ? $allStudents : ($classesData[0]->students ?? collect());
        $stats = $isMultiKelas ? $globalStats : ($classesData[0]->stats ?? $globalStats);

        return view('absensi-pelajaran.presensi', compact(
            'jadwal',
            'tanggal',
            'jurnal',
            'students',
            'stats',
            'isAdminOrWaka',
            'isMultiKelas',
            'parallelJadwals',
            'classesData',
            'globalStats'
        ));
    }

    /**
     * Simpan Khusus Jurnal Pembelajaran (KBM) Mandiri
     */
    public function storeJurnalOnly($jadwalId, Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $isGuru = !empty($user->guru);
        $guruId = $user->guru?->id;
        $isAdminOrWaka = in_array($user->role, ['admin', 'super_admin', 'waka_kurikulum']);

        $jadwal = JadwalPelajaran::where('school_id', $schoolId)->findOrFail($jadwalId);

        if (!$isAdminOrWaka && $isGuru && $jadwal->guru_id !== $guruId) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            }
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'tanggal' => 'required|date',
            'materi'  => 'required|string|max:1000',
            'catatan' => 'nullable|string|max:1000',
        ], [
            'materi.required' => 'Materi Pembelajaran / Jurnal KBM wajib diisi.',
        ]);

        $tanggal = Carbon::parse($request->tanggal)->format('Y-m-d');
        $operatorGuruId = $isGuru ? $guruId : $jadwal->guru_id;

        // Cari semua jadwal paralel (multi-kelas gabungan di jam, hari, mapel, & guru yang sama)
        $parallelJadwals = JadwalPelajaran::where('school_id', $schoolId)
            ->where('guru_id', $jadwal->guru_id)
            ->where('mapel_id', $jadwal->mapel_id)
            ->where('hari', $jadwal->hari)
            ->where('jam_mulai', $jadwal->jam_mulai)
            ->where('jam_selesai', $jadwal->jam_selesai)
            ->with('kelas')
            ->get();

        $savedJurnal = null;
        foreach ($parallelJadwals as $pj) {
            $j = JurnalPelajaran::updateOrCreate(
                [
                    'jadwal_pelajaran_id' => $pj->id,
                    'tanggal'             => $tanggal,
                ],
                [
                    'school_id'    => $schoolId,
                    'guru_id'      => $operatorGuruId,
                    'jam_ke'       => $pj->jam_ke,
                    'materi'       => trim($request->materi),
                    'catatan'      => $request->catatan ? trim($request->catatan) : null,
                    'jam_mulai'    => $pj->jam_mulai,
                    'jam_selesai'  => $pj->jam_selesai,
                ]
            );

            // Sambungkan jurnal_id ke absensi yang sudah ada pada tanggal ini
            AbsensiPelajaran::where('jadwal_pelajaran_id', $pj->id)
                ->where('tanggal', $tanggal)
                ->whereNull('jurnal_pelajaran_id')
                ->update(['jurnal_pelajaran_id' => $j->id]);

            if ($pj->id === $jadwal->id) {
                $savedJurnal = $j;
            }
        }
        if (!$savedJurnal) {
            $savedJurnal = $j ?? null;
        }

        $isMulti = $parallelJadwals->count() > 1;
        $classNames = $parallelJadwals->pluck('kelas.nama_kelas')->filter()->join(', ');
        $msg = $isMulti
            ? "Jurnal KBM berhasil disimpan dan disinkronkan ke {$parallelJadwals->count()} kelas ({$classNames})!"
            : 'Jurnal KBM berhasil disimpan!';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'jurnal'  => $savedJurnal,
                'is_multi'=> $isMulti,
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Simpan / Perbarui Presensi Kelas dan Jurnal KBM (Legacy form compatibility)
     */
    public function storePresensi($jadwalId, Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $isGuru = !empty($user->guru);
        $guruId = $user->guru?->id;
        $isAdminOrWaka = in_array($user->role, ['admin', 'super_admin', 'waka_kurikulum']);

        $jadwal = JadwalPelajaran::where('school_id', $schoolId)
            ->findOrFail($jadwalId);

        if (!$isAdminOrWaka && $isGuru && $jadwal->guru_id !== $guruId) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'tanggal'        => 'required|date',
            'materi'         => 'nullable|string|max:1000',
            'catatan'        => 'nullable|string|max:1000',
            'attendance'     => 'nullable|array',
            'attendance.*'   => 'nullable|in:H,I,S,A',
            'keterangan'     => 'nullable|array',
            'keterangan.*'   => 'nullable|string|max:255',
            'student_jadwal' => 'nullable|array',
        ]);

        $tanggal = Carbon::parse($request->tanggal)->format('Y-m-d');
        $operatorGuruId = $isGuru ? $guruId : $jadwal->guru_id;

        DB::transaction(function () use ($request, $jadwal, $schoolId, $tanggal, $operatorGuruId) {
            // 1. Simpan / Perbarui Jurnal Pelajaran (disinkronkan ke seluruh jadwal paralel jika multi-kelas)
            $parallelJadwals = JadwalPelajaran::where('school_id', $schoolId)
                ->where('guru_id', $jadwal->guru_id)
                ->where('mapel_id', $jadwal->mapel_id)
                ->where('hari', $jadwal->hari)
                ->where('jam_mulai', $jadwal->jam_mulai)
                ->where('jam_selesai', $jadwal->jam_selesai)
                ->get();

            $jurnalsByJadwal = [];
            if ($request->filled('materi')) {
                foreach ($parallelJadwals as $pj) {
                    $j = JurnalPelajaran::updateOrCreate(
                        [
                            'jadwal_pelajaran_id' => $pj->id,
                            'tanggal'             => $tanggal,
                        ],
                        [
                            'school_id'    => $schoolId,
                            'guru_id'      => $operatorGuruId,
                            'jam_ke'       => $pj->jam_ke,
                            'materi'       => trim($request->materi),
                            'catatan'      => $request->catatan ? trim($request->catatan) : null,
                            'jam_mulai'    => $pj->jam_mulai,
                            'jam_selesai'  => $pj->jam_selesai,
                        ]
                    );
                    $jurnalsByJadwal[$pj->id] = $j;
                }
            }

            // 2. Simpan / Perbarui Absensi Siswa
            $attendances = $request->input('attendance', []);
            $keterangans = $request->input('keterangan', []);
            $studentJadwals = $request->input('student_jadwal', []);

            foreach ($attendances as $studentId => $status) {
                $ket = $keterangans[$studentId] ?? null;
                $targetJadwalId = $studentJadwals[$studentId] ?? $jadwal->id;
                $targetJurnalId = $jurnalsByJadwal[$targetJadwalId]?->id ?? null;

                AbsensiPelajaran::updateOrCreate(
                    [
                        'jadwal_pelajaran_id' => $targetJadwalId,
                        'student_id'          => $studentId,
                        'tanggal'             => $tanggal,
                    ],
                    [
                        'school_id'           => $schoolId,
                        'jurnal_pelajaran_id' => $targetJurnalId,
                        'guru_id'             => $operatorGuruId,
                        'status'              => $status,
                        'keterangan'          => !empty($ket) ? trim($ket) : null,
                    ]
                );
            }
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Presensi dan Jurnal KBM berhasil disimpan.'
            ]);
        }

        return redirect()->route('absensi-pelajaran.index', ['tanggal' => $tanggal])
            ->with('success', 'Presensi dan Jurnal KBM berhasil disimpan!');
    }

    /**
     * AJAX Live Action: Single Student Attendance Update
     */
    public function updateSingle(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $isGuru = !empty($user->guru);
        $guruId = $user->guru?->id;
        $isAdminOrWaka = in_array($user->role, ['admin', 'super_admin', 'waka_kurikulum']);

        $request->validate([
            'jadwal_pelajaran_id' => 'required|exists:jadwal_pelajaran,id',
            'student_id'          => 'required|exists:siswa,id',
            'tanggal'             => 'required|date',
            'status'              => 'required|in:H,I,S,A',
            'keterangan'          => 'nullable|string|max:255',
        ]);

        $jadwal = JadwalPelajaran::where('school_id', $schoolId)->findOrFail($request->jadwal_pelajaran_id);

        if (!$isAdminOrWaka && $isGuru && $jadwal->guru_id !== $guruId) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $tanggal = Carbon::parse($request->tanggal)->format('Y-m-d');
        $operatorGuruId = $isGuru ? $guruId : $jadwal->guru_id;

        $jurnal = JurnalPelajaran::where('jadwal_pelajaran_id', $jadwal->id)
            ->where('tanggal', $tanggal)
            ->first();

        $absensi = AbsensiPelajaran::updateOrCreate(
            [
                'jadwal_pelajaran_id' => $jadwal->id,
                'student_id'          => $request->student_id,
                'tanggal'             => $tanggal,
            ],
            [
                'school_id'           => $schoolId,
                'jurnal_pelajaran_id' => $jurnal?->id,
                'guru_id'             => $operatorGuruId,
                'status'              => $request->status,
                'keterangan'          => $request->keterangan ? trim($request->keterangan) : null,
            ]
        );

        $stats = [
            'total' => Siswa::where('school_id', $schoolId)->where('kelas_id', $jadwal->kelas_id)->count(),
            'H'     => AbsensiPelajaran::where('jadwal_pelajaran_id', $jadwal->id)->where('tanggal', $tanggal)->where('status', 'H')->count(),
            'I'     => AbsensiPelajaran::where('jadwal_pelajaran_id', $jadwal->id)->where('tanggal', $tanggal)->where('status', 'I')->count(),
            'S'     => AbsensiPelajaran::where('jadwal_pelajaran_id', $jadwal->id)->where('tanggal', $tanggal)->where('status', 'S')->count(),
            'A'     => AbsensiPelajaran::where('jadwal_pelajaran_id', $jadwal->id)->where('tanggal', $tanggal)->where('status', 'A')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Presensi siswa berhasil diperbarui.',
            'data'    => [
                'id'                  => $absensi->id,
                'jadwal_pelajaran_id' => $jadwal->id,
                'status'              => $absensi->status,
                'status_label'        => $absensi->status_label,
            ],
            'stats'   => $stats,
        ]);
    }

    /**
     * AJAX Live Action: Setel Semua Hadir (Mendukung kelas spesifik atau seluruh kelas gabungan)
     */
    public function setAllHadir(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $isGuru = !empty($user->guru);
        $guruId = $user->guru?->id;
        $isAdminOrWaka = in_array($user->role, ['admin', 'super_admin', 'waka_kurikulum']);

        $request->validate([
            'jadwal_pelajaran_id' => 'required|exists:jadwal_pelajaran,id',
            'tanggal'             => 'required|date',
            'status'              => 'nullable|in:H,I,S,A',
            'all_parallel'        => 'nullable|boolean',
        ]);

        $status = $request->input('status', 'H');
        $jadwal = JadwalPelajaran::where('school_id', $schoolId)->findOrFail($request->jadwal_pelajaran_id);

        if (!$isAdminOrWaka && $isGuru && $jadwal->guru_id !== $guruId) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $tanggal = Carbon::parse($request->tanggal)->format('Y-m-d');
        $operatorGuruId = $isGuru ? $guruId : $jadwal->guru_id;

        $targetJadwals = collect([$jadwal]);
        if ($request->boolean('all_parallel')) {
            $targetJadwals = JadwalPelajaran::where('school_id', $schoolId)
                ->where('guru_id', $jadwal->guru_id)
                ->where('mapel_id', $jadwal->mapel_id)
                ->where('hari', $jadwal->hari)
                ->where('jam_mulai', $jadwal->jam_mulai)
                ->where('jam_selesai', $jadwal->jam_selesai)
                ->get();
        }

        $targetJadwalIds = $targetJadwals->pluck('id');
        $targetKelasIds = $targetJadwals->pluck('kelas_id');

        foreach ($targetJadwals as $tJadwal) {
            $jurnal = JurnalPelajaran::where('jadwal_pelajaran_id', $tJadwal->id)->where('tanggal', $tanggal)->first();
            $students = Siswa::where('school_id', $schoolId)->where('kelas_id', $tJadwal->kelas_id)->get();

            foreach ($students as $student) {
                AbsensiPelajaran::updateOrCreate(
                    [
                        'jadwal_pelajaran_id' => $tJadwal->id,
                        'student_id'          => $student->id,
                        'tanggal'             => $tanggal,
                    ],
                    [
                        'school_id'           => $schoolId,
                        'jurnal_pelajaran_id' => $jurnal?->id,
                        'guru_id'             => $operatorGuruId,
                        'status'              => $status,
                    ]
                );
            }
        }

        $stats = [
            'total' => Siswa::where('school_id', $schoolId)->whereIn('kelas_id', $targetKelasIds)->count(),
            'H'     => AbsensiPelajaran::whereIn('jadwal_pelajaran_id', $targetJadwalIds)->where('tanggal', $tanggal)->where('status', 'H')->count(),
            'I'     => AbsensiPelajaran::whereIn('jadwal_pelajaran_id', $targetJadwalIds)->where('tanggal', $tanggal)->where('status', 'I')->count(),
            'S'     => AbsensiPelajaran::whereIn('jadwal_pelajaran_id', $targetJadwalIds)->where('tanggal', $tanggal)->where('status', 'S')->count(),
            'A'     => AbsensiPelajaran::whereIn('jadwal_pelajaran_id', $targetJadwalIds)->where('tanggal', $tanggal)->where('status', 'A')->count(),
        ];

        return response()->json([
            'success'      => true,
            'message'      => 'Semua siswa berhasil disetel ' . ($status === 'H' ? 'Hadir' : $status) . '.',
            'stats'        => $stats,
            'all_parallel' => $request->boolean('all_parallel'),
            'jadwal_id'    => $jadwal->id,
        ]);
    }

    /**
     * Rekap Absensi Pembelajaran (Per Kelas / Per Mapel / Per Rentang Tanggal)
     */
    public function rekap(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $isGuru = !empty($user->guru);
        $guruId = $user->guru?->id;
        $isAdminOrWaka = in_array($user->role, ['admin', 'super_admin', 'waka_kurikulum']);

        $startDate = $request->query('start_date', Carbon::today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->query('end_date', Carbon::today()->format('Y-m-d'));
        $kelasId = $request->query('kelas_id');
        $mapelId = $request->query('mapel_id');
        $selectedGuruId = $request->query('guru_id');

        $query = AbsensiPelajaran::where('absensi_pelajaran.school_id', $schoolId)
            ->whereBetween('absensi_pelajaran.tanggal', [$startDate, $endDate])
            ->with(['student.kelas', 'jadwal.mapel', 'jadwal.guru', 'jurnal']);

        if (!$isAdminOrWaka && $isGuru) {
            $query->whereHas('jadwal', function ($q) use ($guruId) {
                $q->where('guru_id', $guruId);
            });
        } elseif (!empty($selectedGuruId)) {
            $query->whereHas('jadwal', function ($q) use ($selectedGuruId) {
                $q->where('guru_id', $selectedGuruId);
            });
        }

        if (!empty($kelasId)) {
            $query->whereHas('student', function ($q) use ($kelasId) {
                $q->where('kelas_id', $kelasId);
            });
        }

        if (!empty($mapelId)) {
            $query->whereHas('jadwal', function ($q) use ($mapelId) {
                $q->where('mapel_id', $mapelId);
            });
        }

        // Summary counts
        $summary = (clone $query)->selectRaw('
            COUNT(*) as total_records,
            SUM(CASE WHEN status = "H" THEN 1 ELSE 0 END) as count_h,
            SUM(CASE WHEN status = "S" THEN 1 ELSE 0 END) as count_s,
            SUM(CASE WHEN status = "I" THEN 1 ELSE 0 END) as count_i,
            SUM(CASE WHEN status = "A" THEN 1 ELSE 0 END) as count_a,
            SUM(CASE WHEN status = "T" THEN 1 ELSE 0 END) as count_t,
            SUM(CASE WHEN status = "D" THEN 1 ELSE 0 END) as count_d
        ')->first();

        // Paginated records
        $records = $query->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(30)
            ->withQueryString();

        $kelases = Kelas::where('school_id', $schoolId)->orderBy('nama_kelas')->get();
        $gurus = Guru::where('school_id', $schoolId)->orderBy('nama')->get();
        $mapels = Mapel::where('school_id', $schoolId)->orderBy('nama_mapel')->get();

        return view('absensi-pelajaran.rekap', compact(
            'records',
            'summary',
            'startDate',
            'endDate',
            'kelasId',
            'mapelId',
            'selectedGuruId',
            'kelases',
            'gurus',
            'mapels',
            'isAdminOrWaka',
            'isGuru'
        ));
    }
}
