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
            ->with([
                'guru',
                'kelas' => function ($q) {
                    $q->withCount('siswas');
                },
                'mapel'
            ])
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
            $jadwal->jurnal_today = $jurnals->get($jadwal->id);
        }

        // Jika tidak memfilter kelas tertentu, satukan sesi multi-kelas paralel menjadi 1 kartu
        if (!$request->filled('kelas_id')) {
            $groupedJadwals = collect();
            $processedIds = [];

            foreach ($jadwals as $jadwal) {
                if (in_array($jadwal->id, $processedIds)) {
                    continue;
                }

                $parallels = $jadwals->filter(function ($item) use ($jadwal) {
                    return $item->guru_id === $jadwal->guru_id
                        && $item->mapel_id === $jadwal->mapel_id
                        && $item->hari === $jadwal->hari
                        && substr($item->jam_mulai, 0, 5) === substr($jadwal->jam_mulai, 0, 5)
                        && substr($item->jam_selesai, 0, 5) === substr($jadwal->jam_selesai, 0, 5);
                });

                foreach ($parallels as $p) {
                    $processedIds[] = $p->id;
                }

                if ($parallels->count() > 1) {
                    $primary = clone $jadwal;
                    $primary->is_multi_kelas = true;
                    $primary->parallel_count = $parallels->count();
                    $primary->parallel_ids = $parallels->pluck('id')->all();

                    $allKelases = $parallels->map(fn($p) => $p->kelas?->nama_kelas)->filter()->values();
                    $uniqueKelases = $allKelases->unique()->values()->all();
                    $primary->parallel_kelases = $uniqueKelases;

                    // Akumulasi total siswa gabungan
                    $totalSiswa = 0;
                    foreach ($parallels as $p) {
                        $totalSiswa += ($p->kelas?->siswas ? $p->kelas->siswas->count() : ($p->kelas?->siswas_count ?? 0));
                    }
                    $primary->total_siswa_gabungan = $totalSiswa;

                    // Akumulasi absensi gabungan
                    $mergedAbs = (object)[
                        'total_absen' => 0,
                        'count_h' => 0,
                        'count_s' => 0,
                        'count_i' => 0,
                        'count_a' => 0,
                        'count_t' => 0,
                        'count_d' => 0,
                    ];
                    $hasAttended = false;
                    foreach ($parallels as $p) {
                        if ($p->absensi_summary) {
                            $mergedAbs->total_absen += $p->absensi_summary->total_absen ?? 0;
                            $mergedAbs->count_h += $p->absensi_summary->count_h ?? 0;
                            $mergedAbs->count_s += $p->absensi_summary->count_s ?? 0;
                            $mergedAbs->count_i += $p->absensi_summary->count_i ?? 0;
                            $mergedAbs->count_a += $p->absensi_summary->count_a ?? 0;
                            $mergedAbs->count_t += $p->absensi_summary->count_t ?? 0;
                            $mergedAbs->count_d += $p->absensi_summary->count_d ?? 0;
                        }
                        if ($p->is_attended) {
                            $hasAttended = true;
                        }
                    }
                    $primary->absensi_summary = $mergedAbs;
                    $primary->is_attended = $hasAttended || ($mergedAbs->total_absen > 0);

                    // Jurnal KBM
                    $filledJurnal = null;
                    foreach ($parallels as $p) {
                        if (!empty($p->jurnal_today) && !empty($p->jurnal_today->materi)) {
                            $filledJurnal = $p->jurnal_today;
                            break;
                        }
                    }
                    $primary->jurnal_today = $filledJurnal ?: $primary->jurnal_today;

                    $groupedJadwals->push($primary);
                } else {
                    $jadwal->is_multi_kelas = false;
                    $jadwal->parallel_count = 1;
                    $jadwal->parallel_kelases = [$jadwal->kelas?->nama_kelas];
                    $jadwal->total_siswa_gabungan = $jadwal->kelas?->siswas ? $jadwal->kelas->siswas->count() : ($jadwal->kelas?->siswas_count ?? 0);
                    $groupedJadwals->push($jadwal);
                }
            }

            $jadwals = $groupedJadwals;
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

    /**
     * Halaman Galeri Dokumentasi Pembelajaran (Foto & Video)
     * Dikelompokkan per Guru dengan filter tanggal, kelas, mapel, tipe media.
     */
    public function dokumentasi(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $isGuru = !empty($user->guru);
        $guruId = $user->guru?->id;
        $role = $user->role;
        $isAdminOrWaka = in_array($role, ['admin', 'super_admin', 'waka_kurikulum']);

        // Default rentang tanggal: 1 bulan terakhir
        $defaultStart = Carbon::now()->startOfMonth()->format('Y-m-d');
        $defaultEnd   = Carbon::now()->format('Y-m-d');

        $startDate = $request->query('start_date', $defaultStart);
        $endDate   = $request->query('end_date', $defaultEnd);

        $selectedGuruId = $request->query('guru_id');
        $kelasId        = $request->query('kelas_id');
        $mapelId        = $request->query('mapel_id');
        $mediaType      = $request->query('media_type', 'all'); // 'all', 'image', 'video'
        $search         = trim($request->query('q', ''));

        $query = JurnalPelajaran::where('jurnal_pelajaran.school_id', $schoolId)
            ->whereNotNull('jurnal_pelajaran.dokumentasi')
            ->where('jurnal_pelajaran.dokumentasi', '!=', '[]')
            ->where('jurnal_pelajaran.dokumentasi', '!=', '""')
            ->where('jurnal_pelajaran.dokumentasi', '!=', '')
            ->with([
                'guru',
                'jadwal.kelas',
                'jadwal.mapel',
            ]);

        if (!empty($startDate) && !empty($endDate)) {
            $query->whereBetween('jurnal_pelajaran.tanggal', [$startDate, $endDate]);
        }

        if (!$isAdminOrWaka && $isGuru) {
            $query->where('jurnal_pelajaran.guru_id', $guruId);
        } elseif (!empty($selectedGuruId)) {
            $query->where('jurnal_pelajaran.guru_id', $selectedGuruId);
        }

        if (!empty($kelasId)) {
            $query->whereHas('jadwal', fn($q) => $q->where('kelas_id', $kelasId));
        }

        if (!empty($mapelId)) {
            $query->whereHas('jadwal', fn($q) => $q->where('mapel_id', $mapelId));
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('materi', 'like', "%{$search}%")
                  ->orWhere('catatan', 'like', "%{$search}%")
                  ->orWhereHas('guru', fn($g) => $g->where('nama', 'like', "%{$search}%"))
                  ->orWhereHas('jadwal.mapel', fn($m) => $m->where('nama_mapel', 'like', "%{$search}%"))
                  ->orWhereHas('jadwal.kelas', fn($k) => $k->where('nama_kelas', 'like', "%{$search}%"));
            });
        }

        $allJurnals = $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->get();

        // Filter media_type pada masing-masing item dokumentasi
        $filteredJurnals = $allJurnals->filter(function ($jurnal) use ($mediaType) {
            $list = $jurnal->dokumentasi_list;
            if (empty($list)) return false;

            if ($mediaType === 'image') {
                $filtered = array_values(array_filter($list, fn($item) => !empty($item['is_image'])));
                $jurnal->filtered_docs = $filtered;
                return !empty($filtered);
            } elseif ($mediaType === 'video') {
                $filtered = array_values(array_filter($list, fn($item) => !empty($item['is_video'])));
                $jurnal->filtered_docs = $filtered;
                return !empty($filtered);
            }

            $jurnal->filtered_docs = $list;
            return true;
        });

        // Grouping per Guru
        $groupedByGuru = $filteredJurnals->groupBy(function ($j) {
            return $j->guru_id ?? 0;
        })->map(function ($items, $gId) {
            $guru = $items->first()->guru;
            $allDocs = $items->flatMap(fn($j) => $j->filtered_docs ?? $j->dokumentasi_list);

            return (object) [
                'guru'         => $guru,
                'guru_id'      => $gId,
                'guru_nama'    => $guru ? $guru->nama : 'Guru Tidak Diketahui',
                'guru_nip'     => $guru?->nip ?? '-',
                'guru_foto'    => $guru?->foto_url ?? null,
                'total_kbm'    => $items->count(),
                'total_files'  => $allDocs->count(),
                'total_images' => $allDocs->where('is_image', true)->count(),
                'total_videos' => $allDocs->where('is_video', true)->count(),
                'jurnals'      => $items,
            ];
        })->sortByDesc('total_files')->values();

        // Statistik Keseluruhan
        $allDocsFlattened = $filteredJurnals->flatMap(fn($j) => $j->filtered_docs ?? $j->dokumentasi_list);
        $stats = [
            'total_gurus'    => $groupedByGuru->count(),
            'total_sessions' => $filteredJurnals->count(),
            'total_files'    => $allDocsFlattened->count(),
            'total_images'   => $allDocsFlattened->where('is_image', true)->count(),
            'total_videos'   => $allDocsFlattened->where('is_video', true)->count(),
        ];

        // Master dropdown filters
        $kelases = Kelas::where('school_id', $schoolId)->orderBy('nama_kelas')->get();
        $gurus   = Guru::where('school_id', $schoolId)->orderBy('nama')->get();
        $mapels  = Mapel::where('school_id', $schoolId)->orderBy('nama_mapel')->get();

        return view('absensi-pelajaran.dokumentasi', compact(
            'groupedByGuru',
            'stats',
            'startDate',
            'endDate',
            'selectedGuruId',
            'kelasId',
            'mapelId',
            'mediaType',
            'search',
            'kelases',
            'gurus',
            'mapels',
            'isAdminOrWaka',
            'isGuru'
        ));
    }
}
