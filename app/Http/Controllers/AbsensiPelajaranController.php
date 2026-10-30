<?php

namespace App\Http\Controllers;

use App\Models\AbsensiPelajaran;
use App\Models\JadwalPelajaran;
use App\Models\JurnalPelajaran;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Guru;
use App\Models\Siswa;
use App\Services\StorageServerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Barryvdh\DomPDF\Facade\Pdf;

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
     * Rekap Absensi Pembelajaran Berbasis Siswa (Jumlah Hadir, Sakit, Izin, Alpha per Siswa)
     * Lengkap dengan filter range tanggal, kelas, mapel, pencarian nama, dan sorting.
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
        $search = trim($request->query('search', $request->query('q', '')));

        $sortBy = $request->query('sort_by', 'nama');
        $sortDir = strtolower($request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        // 1. Filter Siswa
        $siswaQuery = Siswa::where('school_id', $schoolId)->with('kelas');

        // Jika guru biasa, batasi kelas ke kelas yang diajar
        if (!$isAdminOrWaka && $isGuru) {
            $guruKelasIds = JadwalPelajaran::where('school_id', $schoolId)
                ->where('guru_id', $guruId)
                ->pluck('kelas_id')
                ->filter()
                ->unique()
                ->all();

            if (empty($kelasId)) {
                $siswaQuery->whereIn('kelas_id', $guruKelasIds);
            }
        }

        if (!empty($kelasId)) {
            $siswaQuery->where('kelas_id', $kelasId);
        }

        if (!empty($search)) {
            $siswaQuery->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
                if (\Illuminate\Support\Facades\Schema::hasColumn('siswa', 'nisn')) {
                    $q->orWhere('nisn', 'like', "%{$search}%");
                }
            });
        }

        // Sorting
        if ($sortBy === 'kelas') {
            $siswaQuery->leftJoin('kelas', 'siswa.kelas_id', '=', 'kelas.id')
                ->select('siswa.*')
                ->orderBy('kelas.nama_kelas', $sortDir)
                ->orderBy('siswa.nama', 'asc');
        } elseif ($sortBy === 'hadir' || $sortBy === 'sakit' || $sortBy === 'izin' || $sortBy === 'alpha') {
            $targetStatus = match ($sortBy) {
                'hadir' => 'H',
                'sakit' => 'S',
                'izin'  => 'I',
                'alpha' => 'A',
                default => 'H',
            };
            $siswaQuery->withCount(['absensiPelajaran as status_count' => function ($q) use ($startDate, $endDate, $mapelId, $guruId, $selectedGuruId, $isAdminOrWaka, $isGuru, $targetStatus) {
                $q->whereBetween('tanggal', [$startDate, $endDate])
                  ->where('status', $targetStatus);
                if (!empty($mapelId)) {
                    $q->whereHas('jadwal', fn($sq) => $sq->where('mapel_id', $mapelId));
                }
                if (!$isAdminOrWaka && $isGuru) {
                    $q->whereHas('jadwal', fn($sq) => $sq->where('guru_id', $guruId));
                } elseif (!empty($selectedGuruId)) {
                    $q->whereHas('jadwal', fn($sq) => $sq->where('guru_id', $selectedGuruId));
                }
            }])->orderBy('status_count', $sortDir)->orderBy('nama', 'asc');
        } else {
            $siswaQuery->orderBy('nama', $sortDir);
        }

        $allSiswa = $siswaQuery->paginate(50)->withQueryString();
        $studentIds = $allSiswa->pluck('id')->all();

        // 2. Agregasi Absensi Mapel untuk Siswa di Halaman Ini
        $summary = [];
        foreach ($allSiswa as $s) {
            $summary[$s->id] = [
                'H'          => 0,
                'S'          => 0,
                'I'          => 0,
                'A'          => 0,
                'D'          => 0,
                'T'          => 0,
                'total'      => 0,
                'persentase' => 0,
            ];
        }

        if (!empty($studentIds)) {
            $absensiQuery = AbsensiPelajaran::where('absensi_pelajaran.school_id', $schoolId)
                ->whereBetween('absensi_pelajaran.tanggal', [$startDate, $endDate])
                ->whereIn('absensi_pelajaran.student_id', $studentIds);

            if (!empty($mapelId)) {
                $absensiQuery->whereHas('jadwal', fn($q) => $q->where('mapel_id', $mapelId));
            }
            if (!$isAdminOrWaka && $isGuru) {
                $absensiQuery->whereHas('jadwal', fn($q) => $q->where('guru_id', $guruId));
            } elseif (!empty($selectedGuruId)) {
                $absensiQuery->whereHas('jadwal', fn($q) => $q->where('guru_id', $selectedGuruId));
            }

            $rawSummary = $absensiQuery->selectRaw('
                student_id,
                SUM(CASE WHEN status = "H" THEN 1 ELSE 0 END) as count_h,
                SUM(CASE WHEN status = "S" THEN 1 ELSE 0 END) as count_s,
                SUM(CASE WHEN status = "I" THEN 1 ELSE 0 END) as count_i,
                SUM(CASE WHEN status = "A" THEN 1 ELSE 0 END) as count_a,
                SUM(CASE WHEN status = "D" THEN 1 ELSE 0 END) as count_d,
                SUM(CASE WHEN status = "T" THEN 1 ELSE 0 END) as count_t,
                COUNT(*) as total_pertemuan
            ')
            ->groupBy('student_id')
            ->get();

            foreach ($rawSummary as $row) {
                $h = (int) $row->count_h;
                $s = (int) $row->count_s;
                $i = (int) $row->count_i;
                $a = (int) $row->count_a;
                $d = (int) $row->count_d;
                $t = (int) $row->count_t;
                $tot = (int) $row->total_pertemuan;
                $pct = $tot > 0 ? round((($h + $t) / $tot) * 100, 1) : 0;

                $summary[$row->student_id] = [
                    'H'          => $h,
                    'S'          => $s,
                    'I'          => $i,
                    'A'          => $a,
                    'D'          => $d,
                    'T'          => $t,
                    'total'      => $tot,
                    'persentase' => $pct,
                ];
            }
        }

        // 3. Global Summary Ribbon (Ringkasan Total di Atas)
        $globalQuery = AbsensiPelajaran::where('absensi_pelajaran.school_id', $schoolId)
            ->whereBetween('absensi_pelajaran.tanggal', [$startDate, $endDate]);

        if (!empty($kelasId)) {
            $globalQuery->whereHas('student', fn($q) => $q->where('kelas_id', $kelasId));
        }
        if (!empty($mapelId)) {
            $globalQuery->whereHas('jadwal', fn($q) => $q->where('mapel_id', $mapelId));
        }
        if (!$isAdminOrWaka && $isGuru) {
            $globalQuery->whereHas('jadwal', fn($q) => $q->where('guru_id', $guruId));
        } elseif (!empty($selectedGuruId)) {
            $globalQuery->whereHas('jadwal', fn($q) => $q->where('guru_id', $selectedGuruId));
        }
        if (!empty($search)) {
            $globalQuery->whereHas('student', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        $globalSummary = $globalQuery->selectRaw('
            COUNT(*) as total_records,
            COUNT(DISTINCT student_id) as total_students,
            SUM(CASE WHEN status = "H" THEN 1 ELSE 0 END) as count_h,
            SUM(CASE WHEN status = "S" THEN 1 ELSE 0 END) as count_s,
            SUM(CASE WHEN status = "I" THEN 1 ELSE 0 END) as count_i,
            SUM(CASE WHEN status = "A" THEN 1 ELSE 0 END) as count_a,
            SUM(CASE WHEN status = "D" THEN 1 ELSE 0 END) as count_d,
            SUM(CASE WHEN status = "T" THEN 1 ELSE 0 END) as count_t
        ')->first();

        // 4. Dropdowns Filter
        $kelasesQuery = Kelas::where('school_id', $schoolId)->orderBy('nama_kelas');
        $mapelsQuery  = Mapel::where('school_id', $schoolId)->orderBy('nama_mapel');
        $gurusQuery   = Guru::where('school_id', $schoolId)->orderBy('nama');

        if (!$isAdminOrWaka && $isGuru) {
            $guruJadwals = JadwalPelajaran::where('school_id', $schoolId)
                ->where('guru_id', $guruId)
                ->get();

            $kelasesQuery->whereIn('id', $guruJadwals->pluck('kelas_id')->filter()->unique());
            $mapelsQuery->whereIn('id', $guruJadwals->pluck('mapel_id')->filter()->unique());
        }

        $kelases = $kelasesQuery->get();
        $mapels  = $mapelsQuery->get();
        $gurus   = $gurusQuery->get();

        return view('absensi-pelajaran.rekap', compact(
            'allSiswa',
            'summary',
            'globalSummary',
            'startDate',
            'endDate',
            'kelasId',
            'mapelId',
            'selectedGuruId',
            'search',
            'sortBy',
            'sortDir',
            'kelases',
            'gurus',
            'mapels',
            'isAdminOrWaka',
            'isGuru'
        ));
    }

    /**
     * Helper: Dapatkan data siswa dan agregasi presensi mapel lengkap (tanpa limit pagination) untuk Export & PDF
     */
    private function getRekapPelajaranData(Request $request): array
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $isGuru = !empty($user->guru);
        $guruId = $user->guru?->id;
        $isAdminOrWaka = in_array($user->role, ['admin', 'super_admin', 'waka_kurikulum']);

        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::today()->format('Y-m-d'));
        $kelasId = $request->input('kelas_id');
        $mapelId = $request->input('mapel_id');
        $selectedGuruId = $request->input('guru_id');
        $search = trim($request->input('search', $request->input('q', '')));

        $sortBy = $request->input('sort_by', 'nama');
        $sortDir = strtolower($request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $siswaQuery = Siswa::where('school_id', $schoolId)->with('kelas');

        if (!$isAdminOrWaka && $isGuru) {
            $guruKelasIds = JadwalPelajaran::where('school_id', $schoolId)
                ->where('guru_id', $guruId)
                ->pluck('kelas_id')
                ->filter()
                ->unique()
                ->all();

            if (empty($kelasId)) {
                $siswaQuery->whereIn('kelas_id', $guruKelasIds);
            }
        }

        if (!empty($kelasId)) {
            $siswaQuery->where('kelas_id', $kelasId);
        }

        if (!empty($search)) {
            $siswaQuery->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
                if (\Illuminate\Support\Facades\Schema::hasColumn('siswa', 'nisn')) {
                    $q->orWhere('nisn', 'like', "%{$search}%");
                }
            });
        }

        if ($sortBy === 'kelas') {
            $siswaQuery->leftJoin('kelas', 'siswa.kelas_id', '=', 'kelas.id')
                ->select('siswa.*')
                ->orderBy('kelas.nama_kelas', $sortDir)
                ->orderBy('siswa.nama', 'asc');
        } else {
            $siswaQuery->orderBy('nama', $sortDir);
        }

        $allSiswa = $siswaQuery->get();
        $studentIds = $allSiswa->pluck('id')->all();

        $summary = [];
        foreach ($allSiswa as $s) {
            $summary[$s->id] = [
                'H'          => 0,
                'S'          => 0,
                'I'          => 0,
                'A'          => 0,
                'D'          => 0,
                'T'          => 0,
                'total'      => 0,
                'persentase' => 0,
            ];
        }

        if (!empty($studentIds)) {
            $absensiQuery = AbsensiPelajaran::where('absensi_pelajaran.school_id', $schoolId)
                ->whereBetween('absensi_pelajaran.tanggal', [$startDate, $endDate])
                ->whereIn('absensi_pelajaran.student_id', $studentIds);

            if (!empty($mapelId)) {
                $absensiQuery->whereHas('jadwal', fn($q) => $q->where('mapel_id', $mapelId));
            }
            if (!$isAdminOrWaka && $isGuru) {
                $absensiQuery->whereHas('jadwal', fn($q) => $q->where('guru_id', $guruId));
            } elseif (!empty($selectedGuruId)) {
                $absensiQuery->whereHas('jadwal', fn($q) => $q->where('guru_id', $selectedGuruId));
            }

            $rawSummary = $absensiQuery->selectRaw('
                student_id,
                SUM(CASE WHEN status = "H" THEN 1 ELSE 0 END) as count_h,
                SUM(CASE WHEN status = "S" THEN 1 ELSE 0 END) as count_s,
                SUM(CASE WHEN status = "I" THEN 1 ELSE 0 END) as count_i,
                SUM(CASE WHEN status = "A" THEN 1 ELSE 0 END) as count_a,
                SUM(CASE WHEN status = "D" THEN 1 ELSE 0 END) as count_d,
                SUM(CASE WHEN status = "T" THEN 1 ELSE 0 END) as count_t,
                COUNT(*) as total_pertemuan
            ')
            ->groupBy('student_id')
            ->get();

            foreach ($rawSummary as $row) {
                $h = (int) $row->count_h;
                $s = (int) $row->count_s;
                $i = (int) $row->count_i;
                $a = (int) $row->count_a;
                $d = (int) $row->count_d;
                $t = (int) $row->count_t;
                $tot = (int) $row->total_pertemuan;
                $pct = $tot > 0 ? round((($h + $t) / $tot) * 100, 1) : 0;

                $summary[$row->student_id] = [
                    'H'          => $h,
                    'S'          => $s,
                    'I'          => $i,
                    'A'          => $a,
                    'D'          => $d,
                    'T'          => $t,
                    'total'      => $tot,
                    'persentase' => $pct,
                ];
            }
        }

        $selectedKelas = $kelasId ? Kelas::find($kelasId) : null;
        $selectedMapel = $mapelId ? Mapel::find($mapelId) : null;
        $selectedGuru  = $selectedGuruId ? Guru::find($selectedGuruId) : ($isGuru && !$isAdminOrWaka ? $user->guru : null);

        return [
            'allSiswa'      => $allSiswa,
            'summary'       => $summary,
            'startDate'     => $startDate,
            'endDate'       => $endDate,
            'kelasId'       => $kelasId,
            'mapelId'       => $mapelId,
            'selectedKelas' => $selectedKelas,
            'selectedMapel' => $selectedMapel,
            'selectedGuru'  => $selectedGuru,
            'school'        => $user->school,
        ];
    }

    /**
     * Export Rekap Absensi Pembelajaran ke Excel (.xlsx)
     */
    public function rekapExport(Request $request)
    {
        $data = $this->getRekapPelajaranData($request);
        $allSiswa = $data['allSiswa'];
        $summary = $data['summary'];
        $startDate = $data['startDate'];
        $endDate = $data['endDate'];
        $selectedKelas = $data['selectedKelas'];
        $selectedMapel = $data['selectedMapel'];
        $school = $data['school'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Absensi Mapel');

        // Header Dokumen
        $sheet->setCellValue('A1', 'REKAPITULASI PRESENSI MAPEL SISWA');
        $sheet->mergeCells('A1:L1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $schoolName = $school?->name ?? 'SEKOLAH';
        $sheet->setCellValue('A2', strtoupper($schoolName));
        $sheet->mergeCells('A2:L2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $periodeStr = "Periode: " . Carbon::parse($startDate)->format('d/m/Y') . " s/d " . Carbon::parse($endDate)->format('d/m/Y');
        if ($selectedKelas) $periodeStr .= " | Kelas: " . $selectedKelas->nama_kelas;
        if ($selectedMapel) $periodeStr .= " | Mapel: " . $selectedMapel->nama_mapel;
        $sheet->setCellValue('A3', $periodeStr);
        $sheet->mergeCells('A3:L3');
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header Kolom Tabel
        $headers = [
            'A5' => 'No',
            'B5' => 'NIS',
            'C5' => 'Nama Siswa',
            'D5' => 'Kelas',
            'E5' => 'Hadir (H)',
            'F5' => 'Telat (T)',
            'G5' => 'Sakit (S)',
            'H5' => 'Izin (I)',
            'I5' => 'Dispen (D)',
            'J5' => 'Alpha (A)',
            'K5' => 'Total Pertemuan',
            'L5' => '% Kehadiran',
        ];

        foreach ($headers as $cell => $title) {
            $sheet->setCellValue($cell, $title);
        }

        $sheet->getStyle('A5:L5')->getFont()->setBold(true);
        $sheet->getStyle('A5:L5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E7FF');
        $sheet->getStyle('A5:L5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 6;
        $no = 1;
        foreach ($allSiswa as $s) {
            $sum = $summary[$s->id] ?? ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0, 'D' => 0, 'T' => 0, 'total' => 0, 'persentase' => 0];

            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValueExplicit('B' . $row, (string)($s->nis ?: '-'), DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $row, $s->nama);
            $sheet->setCellValue('D' . $row, $s->kelas->nama_kelas ?? '-');
            $sheet->setCellValue('E' . $row, $sum['H']);
            $sheet->setCellValue('F' . $row, $sum['T']);
            $sheet->setCellValue('G' . $row, $sum['S']);
            $sheet->setCellValue('H' . $row, $sum['I']);
            $sheet->setCellValue('I' . $row, $sum['D']);
            $sheet->setCellValue('J' . $row, $sum['A']);
            $sheet->setCellValue('K' . $row, $sum['total']);
            $sheet->setCellValue('L' . $row, $sum['persentase'] . '%');

            // Alignment
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $row . ':L' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        // Apply borders
        $lastRow = $row - 1;
        if ($lastRow >= 5) {
            $sheet->getStyle('A5:L' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        // Auto width
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = "rekap_absensi_mapel_{$startDate}_{$endDate}.xlsx";

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Cetak / Stream Rekap Absensi Pembelajaran ke PDF
     */
    public function rekapPdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $data = $this->getRekapPelajaranData($request);
        $allSiswa = $data['allSiswa'];
        $summary = $data['summary'];
        $startDate = $data['startDate'];
        $endDate = $data['endDate'];
        $selectedKelas = $data['selectedKelas'];
        $selectedMapel = $data['selectedMapel'];
        $selectedGuru = $data['selectedGuru'];
        $school = $data['school'];
        $schoolId = $school?->id;

        $schoolName = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'nama_sekolah')->value('setting_value') ?: ($school?->name ?? 'SEKOLAH');
        $schoolAddress = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'alamat_sekolah')->value('setting_value') ?: ($school?->address ?? '');
        $signatureLocation = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'alamat_ttd')->value('setting_value') ?: 'Tempat';
        $namaKepsek = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'nama_kepala_sekolah')->value('setting_value') ?: '';
        $nipKepsek = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'nip_kepala_sekolah')->value('setting_value') ?: '';
        $namaWaka = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'nama_waka_kurikulum')->value('setting_value') ?: (\App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'nama_waka_kesiswaan')->value('setting_value') ?: '');
        $nipWaka = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'nip_waka_kurikulum')->value('setting_value') ?: (\App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'nip_waka_kesiswaan')->value('setting_value') ?: '');
        $kopSurat = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'kop_surat')->value('setting_value');

        $pdf = Pdf::loadView('absensi-pelajaran.rekap-pdf', compact(
            'allSiswa',
            'summary',
            'startDate',
            'endDate',
            'selectedKelas',
            'selectedMapel',
            'selectedGuru',
            'schoolName',
            'schoolAddress',
            'signatureLocation',
            'namaKepsek',
            'nipKepsek',
            'namaWaka',
            'nipWaka',
            'kopSurat'
        ));

        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream("rekap_absensi_mapel_{$startDate}_{$endDate}.pdf");
    }

    /**
     * Halaman Detail Rekap Presensi Mapel Siswa (serupa dengan rekap.show pada Rekap Siswa)
     */
    public function rekapShow($id, Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $isGuru = !empty($user->guru);
        $guruId = $user->guru?->id;
        $isAdminOrWaka = in_array($user->role, ['admin', 'super_admin', 'waka_kurikulum']);

        $siswa = Siswa::where('school_id', $schoolId)->with('kelas')->findOrFail($id);

        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', Carbon::today()->format('Y-m-d'));
        $mapelId   = $request->input('mapel_id');
        $selectedGuruId = $request->input('guru_id');

        $query = AbsensiPelajaran::where('absensi_pelajaran.school_id', $schoolId)
            ->where('student_id', $id)
            ->whereBetween('absensi_pelajaran.tanggal', [$startDate, $endDate])
            ->with(['jadwal.mapel', 'jadwal.guru', 'jurnal'])
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc');

        if (!empty($mapelId)) {
            $query->whereHas('jadwal', fn($q) => $q->where('mapel_id', $mapelId));
        }

        if (!$isAdminOrWaka && $isGuru) {
            $query->whereHas('jadwal', fn($q) => $q->where('guru_id', $guruId));
        } elseif (!empty($selectedGuruId)) {
            $query->whereHas('jadwal', fn($q) => $q->where('guru_id', $selectedGuruId));
        }

        $attendance = $query->get();

        $counts = [
            'H' => 0,
            'T' => 0,
            'S' => 0,
            'I' => 0,
            'D' => 0,
            'A' => 0,
            'total' => count($attendance),
            'persentase' => 0,
        ];

        foreach ($attendance as $att) {
            $st = $att->status;
            if (isset($counts[$st])) {
                $counts[$st]++;
            }
        }

        $counts['persentase'] = $counts['total'] > 0
            ? round((($counts['H'] + $counts['T']) / $counts['total']) * 100, 1)
            : 0;

        $selectedMapel = $mapelId ? Mapel::find($mapelId) : null;

        return view('absensi-pelajaran.show', compact(
            'siswa',
            'attendance',
            'startDate',
            'endDate',
            'mapelId',
            'selectedMapel',
            'counts'
        ));
    }

    /**
     * Export Detail Rekap Presensi Mapel Siswa ke Excel (.xlsx)
     */
    public function rekapExportDetail($id, Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $isGuru = !empty($user->guru);
        $guruId = $user->guru?->id;
        $isAdminOrWaka = in_array($user->role, ['admin', 'super_admin', 'waka_kurikulum']);

        $siswa = Siswa::where('school_id', $schoolId)->with('kelas')->findOrFail($id);

        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', Carbon::today()->format('Y-m-d'));
        $mapelId   = $request->input('mapel_id');
        $selectedGuruId = $request->input('guru_id');

        $query = AbsensiPelajaran::where('absensi_pelajaran.school_id', $schoolId)
            ->where('student_id', $id)
            ->whereBetween('absensi_pelajaran.tanggal', [$startDate, $endDate])
            ->with(['jadwal.mapel', 'jadwal.guru', 'jurnal'])
            ->orderBy('tanggal', 'asc');

        if (!empty($mapelId)) {
            $query->whereHas('jadwal', fn($q) => $q->where('mapel_id', $mapelId));
        }

        if (!$isAdminOrWaka && $isGuru) {
            $query->whereHas('jadwal', fn($q) => $q->where('guru_id', $guruId));
        } elseif (!empty($selectedGuruId)) {
            $query->whereHas('jadwal', fn($q) => $q->where('guru_id', $selectedGuruId));
        }

        $attendance = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header Dokumen
        $sheet->setCellValue('A1', 'DETAIL REKAP PRESENSI MATA PELAJARAN');
        $sheet->setCellValue('A2', 'Nama: ' . $siswa->nama . ' | NIS: ' . ($siswa->nis ?: '-') . ' | Kelas: ' . ($siswa->kelas?->nama_kelas ?: '-'));
        $sheet->setCellValue('A3', 'Periode: ' . Carbon::parse($startDate)->format('d/m/Y') . ' s/d ' . Carbon::parse($endDate)->format('d/m/Y'));

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:A3')->getFont()->setSize(10)->getColor()->setRGB('555555');

        // Table Header
        $headers = ['No', 'Hari, Tanggal', 'Jam Ke', 'Mata Pelajaran', 'Guru Pengampu', 'Status', 'Catatan / Keterangan', 'Materi KBM'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        foreach ($headers as $idx => $hdr) {
            $sheet->setCellValue($cols[$idx] . '5', $hdr);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '12B76A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D0D5DD']]],
        ];
        $sheet->getStyle('A5:H5')->applyFromArray($headerStyle);
        $sheet->getRowDimension(5)->setRowHeight(26);

        $rowIdx = 6;
        $no = 1;
        foreach ($attendance as $att) {
            $tanggal = $att->tanggal ? Carbon::parse($att->tanggal)->locale('id')->isoFormat('dddd, D MMMM Y') : '-';
            $jamKe = $att->jadwal?->jam_ke ?: ($att->jurnal?->jam_ke ?: '-');
            $mapel = $att->jadwal?->mapel?->nama_mapel ?? 'Mata Pelajaran';
            $guru = $att->jadwal?->guru?->nama ?? '-';
            $status = $att->status_label ?: $att->status;
            $ket = $att->keterangan ?: '-';
            $materi = $att->jurnal?->materi ?: '-';

            $sheet->setCellValue('A' . $rowIdx, $no++);
            $sheet->setCellValue('B' . $rowIdx, $tanggal);
            $sheet->setCellValue('C' . $rowIdx, $jamKe);
            $sheet->setCellValue('D' . $rowIdx, $mapel);
            $sheet->setCellValue('E' . $rowIdx, $guru);
            $sheet->setCellValue('F' . $rowIdx, $status);
            $sheet->setCellValue('G' . $rowIdx, $ket);
            $sheet->setCellValue('H' . $rowIdx, $materi);

            $rowIdx++;
        }

        $lastRow = max(6, $rowIdx - 1);
        $sheet->getStyle("A6:H{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E4E7EC');
        $sheet->getStyle("A6:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C6:C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F6:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach ($cols as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $cleanName = preg_replace('/[^A-Za-z0-9]/', '_', $siswa->nama);
        $fileName = "detail_absensi_mapel_{$cleanName}_{$startDate}_{$endDate}.xlsx";

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * AJAX: Detail Rincian Presensi Mapel per Siswa untuk Modal
     */
    public function rekapDetailSiswa(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $studentId = $request->query('student_id');
        $startDate = $request->query('start_date', Carbon::today()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->query('end_date', Carbon::today()->format('Y-m-d'));
        $mapelId   = $request->query('mapel_id');

        $siswa = Siswa::where('school_id', $schoolId)->with('kelas')->findOrFail($studentId);

        $query = AbsensiPelajaran::where('absensi_pelajaran.school_id', $schoolId)
            ->where('student_id', $studentId)
            ->whereBetween('absensi_pelajaran.tanggal', [$startDate, $endDate])
            ->with(['jadwal.mapel', 'jadwal.guru', 'jurnal'])
            ->orderBy('tanggal', 'desc');

        if (!empty($mapelId)) {
            $query->whereHas('jadwal', fn($q) => $q->where('mapel_id', $mapelId));
        }

        $records = $query->get()->map(function ($r) {
            return [
                'tanggal'      => $r->tanggal ? $r->tanggal->format('d/m/Y') : '-',
                'jam_ke'       => $r->jadwal?->jam_ke ?: ($r->jurnal?->jam_ke ?: '-'),
                'nama_mapel'   => $r->jadwal?->mapel?->nama_mapel ?? 'Mata Pelajaran',
                'nama_guru'    => $r->jadwal?->guru?->nama ?? '-',
                'status'       => $r->status,
                'status_label' => $r->status_label,
                'materi'       => $r->jurnal?->materi ?? '-',
                'keterangan'   => $r->keterangan ?? '-',
            ];
        });

        return response()->json([
            'success' => true,
            'siswa'   => [
                'id'         => $siswa->id,
                'nama'       => $siswa->nama,
                'nis'        => $siswa->nis,
                'nama_kelas' => $siswa->kelas?->nama_kelas ?? '-',
            ],
            'records' => $records,
        ]);
    }

    /**
     * Halaman Jurnal KBM (Teaching Journal Logbook)
     * Menampilkan daftar guru dengan detail jurnal pembelajaran (mapel, kelas, materi, catatan, presensi siswa, dokumentasi).
     * Dilengkapi filter tanggal tunggal (bukan range), filter kelas, filter mapel, dan kolom pencarian nama guru / materi.
     */
    public function jurnalKbm(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $isGuru = !empty($user->guru);
        $guruId = $user->guru?->id;
        $role = $user->role;
        $isAdminOrWaka = in_array($role, ['admin', 'super_admin', 'waka_kurikulum']);
        $school = $user?->school;

        $isDokKbmEnabled = ($user && $user->role === 'super_admin')
            ? true
            : app(\App\Services\LicenseService::class)->isDokKbmFeatureEnabled($school);

        // 1. Filter Tanggal Tunggal (bukan range, default hari ini)
        $tanggal = $request->query('tanggal', Carbon::today()->format('Y-m-d'));
        try {
            $parsedDate = Carbon::parse($tanggal);
        } catch (\Exception $e) {
            $parsedDate = Carbon::today();
            $tanggal = $parsedDate->format('Y-m-d');
        }

        $kelasId = $request->query('kelas_id');
        $mapelId = $request->query('mapel_id');
        $search  = trim($request->query('search', $request->query('q', '')));

        // 2. Query JurnalPelajaran pada tanggal tersebut
        $query = JurnalPelajaran::where('school_id', $schoolId)
            ->whereDate('tanggal', $tanggal)
            ->with([
                'guru.user',
                'jadwal.kelas',
                'jadwal.mapel',
                'jadwal.guru'
            ]);

        // Filter Kelas jika dipilih
        if (!empty($kelasId)) {
            $query->whereHas('jadwal', fn($q) => $q->where('kelas_id', $kelasId));
        }

        // Filter Mapel jika dipilih
        if (!empty($mapelId)) {
            $query->whereHas('jadwal', fn($q) => $q->where('mapel_id', $mapelId));
        }

        // Search Form Nama Guru atau Materi/Catatan KBM
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('guru', fn($g) => $g->where('nama', 'like', "%{$search}%"))
                  ->orWhereHas('jadwal.guru', fn($g) => $g->where('nama', 'like', "%{$search}%"))
                  ->orWhere('materi', 'like', "%{$search}%")
                  ->orWhere('catatan', 'like', "%{$search}%");
            });
        }

        $allJurnals = $query->orderBy('jam_mulai')->orderBy('jam_ke')->get();

        // 3. Lampirkan data siswa yang tidak hadir (Sakit, Izin, Alpa, Dispen)
        $this->attachAbsentStudentsToJurnals($allJurnals, $schoolId);

        // 4. Konsolidasikan kelas paralel (gabungan) agar sesi terpadu rapi & berkas media tidak dobel
        $consolidatedJurnals = $this->consolidateParallelJurnals($allJurnals);

        // 5. Grouping per Guru dengan detail sesi KBM
        $groupedByGuru = $consolidatedJurnals->groupBy(function ($jurnal) {
            return $jurnal->guru_id ?? ($jurnal->jadwal?->guru_id ?? 0);
        })->map(function ($jurnals, $gId) {
            $firstJurnal = $jurnals->first();
            $guru = $firstJurnal->guru ?? $firstJurnal->jadwal?->guru;
            $publicSlug = $guru?->username ?: ($guru?->user?->username ?: $gId);

            $allDocs = $jurnals->flatMap(fn($j) => $j->consolidated_docs ?? $j->dokumentasi_list);

            return (object) [
                'guru'         => $guru,
                'guru_id'      => $gId,
                'guru_nama'    => $guru ? $guru->nama : 'Guru Tidak Diketahui',
                'guru_nip'     => $guru?->nip ?? '-',
                'guru_foto'    => $guru?->foto_url ?? null,
                'public_slug'  => $publicSlug,
                'jurnals'      => $jurnals->sortBy(function ($j) {
                    return $j->jam_mulai ?: ($j->jam_ke ?: 0);
                })->values(),
                'total_sesi'   => $jurnals->count(),
                'total_docs'   => $allDocs->count(),
                'total_images' => $allDocs->where('is_image', true)->count(),
                'total_videos' => $allDocs->where('is_video', true)->count(),
                'total_absent' => $jurnals->sum('absent_count'),
            ];
        })->sortBy('guru_nama')->values();

        // 6. Ringkasan Keseluruhan (Top Cards)
        $allDocsFlattened = $consolidatedJurnals->flatMap(fn($j) => $j->consolidated_docs ?? $j->dokumentasi_list);
        $stats = [
            'total_guru'   => $groupedByGuru->count(),
            'total_sesi'   => $consolidatedJurnals->count(),
            'total_docs'   => $allDocsFlattened->count(),
            'total_images' => $allDocsFlattened->where('is_image', true)->count(),
            'total_videos' => $allDocsFlattened->where('is_video', true)->count(),
            'total_absent' => $consolidatedJurnals->sum('absent_count'),
        ];

        // 7. Opsi Filter (Kelas & Mapel)
        $kelases = Kelas::where('school_id', $schoolId)->orderBy('nama_kelas')->get();
        $mapels  = Mapel::where('school_id', $schoolId)->orderBy('nama_mapel')->get();

        // Nama hari Indonesia
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

        return view('absensi-pelajaran.jurnal-kbm', compact(
            'groupedByGuru',
            'stats',
            'tanggal',
            'parsedDate',
            'namaHari',
            'kelasId',
            'consolidatedJurnals',
            'mapelId',
            'search',
            'kelases',
            'mapels',
            'isAdminOrWaka',
            'isGuru',
            'isDokKbmEnabled',
            'school',
            'user'
        ));
    }

    /**
     * Halaman Detail Jurnal Pembelajaran (KBM) ke Laman Baru
     * Mendukung Sesi Tunggal maupun Sesi Kelas Gabungan (Multi-Kelas)
     */
    public function jurnalKbmShow($id, Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $isGuru = !empty($user->guru);
        $guruId = $user->guru?->id;
        $isAdminOrWaka = in_array($user->role, ['admin', 'super_admin', 'waka_kurikulum']);

        $jurnal = JurnalPelajaran::where('school_id', $schoolId)
            ->with([
                'jadwal.kelas',
                'jadwal.mapel',
                'jadwal.guru',
                'guru'
            ])
            ->findOrFail($id);

        $jadwal = $jurnal->jadwal;
        $tanggalStr = $jurnal->tanggal ? $jurnal->tanggal->format('Y-m-d') : date('Y-m-d');
        $operatorGuruId = $jurnal->guru_id ?: ($jadwal?->guru_id ?? 0);
        $mapelId = $jadwal?->mapel_id ?? 0;

        // 1. Temukan semua Jadwal Paralel (Kelas Gabungan pada hari, guru, mapel, & jam yang sama)
        $parallelJadwals = collect();
        if ($jadwal) {
            $parallelJadwals = JadwalPelajaran::where('school_id', $schoolId)
                ->where('guru_id', $jadwal->guru_id)
                ->where('mapel_id', $jadwal->mapel_id)
                ->where('hari', $jadwal->hari)
                ->where('jam_mulai', $jadwal->jam_mulai)
                ->where('jam_selesai', $jadwal->jam_selesai)
                ->with(['kelas', 'mapel', 'guru'])
                ->orderBy('id')
                ->get();
        }

        $parallelJadwalIds = $parallelJadwals->pluck('id')->filter()->unique()->all();

        // 2. Temukan semua JurnalPelajaran paralel pada tanggal KBM yang sama
        $parallelJurnals = JurnalPelajaran::where('school_id', $schoolId)
            ->whereDate('tanggal', $tanggalStr)
            ->where(function ($q) use ($parallelJadwalIds, $jurnal, $operatorGuruId, $mapelId) {
                $hasCond = false;
                if (!empty($parallelJadwalIds)) {
                    $q->whereIn('jadwal_pelajaran_id', $parallelJadwalIds);
                    $hasCond = true;
                }
                if ($operatorGuruId && $mapelId) {
                    $closure = function ($sub) use ($operatorGuruId, $mapelId, $jurnal) {
                        $sub->where('guru_id', $operatorGuruId)
                            ->whereHas('jadwal', fn($j) => $j->where('mapel_id', $mapelId));
                        if (!empty($jurnal->jam_ke)) {
                            $sub->where('jam_ke', $jurnal->jam_ke);
                        }
                    };
                    if ($hasCond) {
                        $q->orWhere($closure);
                    } else {
                        $q->where($closure);
                        $hasCond = true;
                    }
                }
                if ($hasCond) {
                    $q->orWhere('id', $jurnal->id);
                } else {
                    $q->where('id', $jurnal->id);
                }
            })
            ->with(['jadwal.kelas', 'jadwal.mapel', 'jadwal.guru'])
            ->get();

        $allJurnalIds = $parallelJurnals->pluck('id')->push($jurnal->id)->filter()->unique()->all();
        $allJadwalIds = $parallelJadwals->pluck('id')
            ->concat($parallelJurnals->pluck('jadwal_pelajaran_id'))
            ->push($jurnal->jadwal_pelajaran_id)
            ->filter()
            ->unique()
            ->all();

        // 3. Kumpulkan seluruh kelas yang terlibat dalam sesi ini
        $kelasCollection = collect();
        foreach ($parallelJadwals as $pj) {
            if ($pj->kelas) {
                $kelasCollection->push($pj->kelas);
            }
        }
        foreach ($parallelJurnals as $pjur) {
            if ($pjur->jadwal?->kelas) {
                $kelasCollection->push($pjur->jadwal->kelas);
            }
        }
        if ($jurnal->jadwal?->kelas) {
            $kelasCollection->push($jurnal->jadwal->kelas);
        }
        $involvedClasses = $kelasCollection->unique('id')->values();
        $isMultiKelas = $involvedClasses->count() > 1;
        $kelasIds = $involvedClasses->pluck('id')->filter()->unique()->all();
        $namaKelasGabungan = $involvedClasses->pluck('nama_kelas')->join(' & ');

        // 4. Ambil seluruh siswa dari semua kelas yang terlibat
        $allStudents = Siswa::where('school_id', $schoolId)
            ->whereIn('kelas_id', $kelasIds)
            ->with('kelas')
            ->orderBy('kelas_id')
            ->orderBy('nama')
            ->get();

        // 5. Ambil data presensi yang sudah dicatat pada sesi KBM ini
        $absensiRecords = AbsensiPelajaran::where('school_id', $schoolId)
            ->where(function ($q) use ($allJurnalIds, $allJadwalIds, $tanggalStr) {
                $hasCond = false;
                if (!empty($allJurnalIds)) {
                    $q->whereIn('jurnal_pelajaran_id', $allJurnalIds);
                    $hasCond = true;
                }
                if (!empty($allJadwalIds)) {
                    $closure = function ($sub) use ($allJadwalIds, $tanggalStr) {
                        $sub->whereIn('jadwal_pelajaran_id', $allJadwalIds)
                            ->whereDate('tanggal', $tanggalStr);
                    };
                    if ($hasCond) {
                        $q->orWhere($closure);
                    } else {
                        $q->where($closure);
                        $hasCond = true;
                    }
                }
            })
            ->with('student.kelas')
            ->get();

        $absensiMap = $absensiRecords->keyBy('student_id');

        // 6. Buat struktur presensi gabungan & breakdown per-kelas
        $presensiList = [];
        $counts = [
            'H' => 0,
            'T' => 0,
            'S' => 0,
            'I' => 0,
            'D' => 0,
            'A' => 0,
            'total' => 0,
        ];

        $classesBreakdown = [];
        foreach ($involvedClasses as $cls) {
            $classesBreakdown[$cls->id] = [
                'kelas_id'   => $cls->id,
                'nama_kelas' => $cls->nama_kelas,
                'counts'     => ['H' => 0, 'T' => 0, 'S' => 0, 'I' => 0, 'D' => 0, 'A' => 0, 'total' => 0],
                'students'   => [],
            ];
        }

        if ($allStudents->isNotEmpty()) {
            foreach ($allStudents as $st) {
                $rec = $absensiMap->get($st->id);
                $status = $rec ? $rec->status : ($absensiRecords->isNotEmpty() ? 'A' : '-');
                $statusLabel = match($status) {
                    'H' => 'Hadir',
                    'T' => 'Telat',
                    'S' => 'Sakit',
                    'I' => 'Izin',
                    'D' => 'Dispen',
                    'A' => 'Alpha',
                    default => 'Belum Absen',
                };
                $keterangan = $rec ? $rec->keterangan : '-';

                if (isset($counts[$status])) {
                    $counts[$status]++;
                }

                $stData = [
                    'student_id'   => $st->id,
                    'nama'         => $st->nama,
                    'nis'          => $st->nis ?: '-',
                    'kelas_id'     => $st->kelas_id,
                    'kelas'        => $st->kelas?->nama_kelas ?? '-',
                    'status'       => $status,
                    'status_label' => $statusLabel,
                    'keterangan'   => $keterangan,
                ];

                $presensiList[] = $stData;

                if (isset($classesBreakdown[$st->kelas_id])) {
                    $classesBreakdown[$st->kelas_id]['students'][] = $stData;
                    if (isset($classesBreakdown[$st->kelas_id]['counts'][$status])) {
                        $classesBreakdown[$st->kelas_id]['counts'][$status]++;
                    }
                    $classesBreakdown[$st->kelas_id]['counts']['total']++;
                }
            }
            $counts['total'] = count($presensiList);
        } else {
            foreach ($absensiRecords as $rec) {
                $st = $rec->student;
                $status = $rec->status;
                if (isset($counts[$status])) {
                    $counts[$status]++;
                }
                $presensiList[] = [
                    'student_id'   => $rec->student_id,
                    'nama'         => $st ? $st->nama : 'Siswa',
                    'nis'          => $st?->nis ?: '-',
                    'kelas_id'     => $st?->kelas_id,
                    'kelas'        => $st?->kelas?->nama_kelas ?? ($jurnal->jadwal?->kelas?->nama_kelas ?? '-'),
                    'status'       => $status,
                    'status_label' => $rec->status_label ?: $status,
                    'keterangan'   => $rec->keterangan ?: '-',
                ];
            }
            $counts['total'] = count($presensiList);
        }

        // 7. Konsolidasikan dokumentasi media dari seluruh jurnal paralel (de-duplicate URL)
        $allDocs = collect();
        foreach ($parallelJurnals as $pjur) {
            foreach ($pjur->dokumentasi_list as $doc) {
                $allDocs->push($doc);
            }
        }
        foreach ($jurnal->dokumentasi_list as $doc) {
            $allDocs->push($doc);
        }
        $docs = $allDocs->unique(fn($d) => $d['url'] ?? ($d['view_url'] ?? ''))->values()->all();

        return view('absensi-pelajaran.jurnal-kbm-detail', compact(
            'jurnal',
            'isMultiKelas',
            'involvedClasses',
            'namaKelasGabungan',
            'classesBreakdown',
            'presensiList',
            'counts',
            'docs',
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
        $school = $user?->school;

        $isDokKbmEnabled = ($user && $user->role === 'super_admin')
            ? true
            : app(\App\Services\LicenseService::class)->isDokKbmFeatureEnabled($school);

        // Ambil data kuota storage untuk sekolah ini dari R2StorageService
        $storageInfo = null;
        if ($school) {
            $storageInfo = app(\App\Services\R2StorageService::class)->getSchoolStorageStats($school);
        }
        // Fallback: tampilkan info dari DB jika storage server tidak responsif
        if (!$storageInfo && $school) {
            $quotaMbDb  = (int) ($school->storage_quota_mb ?? 1000);
            $storageInfo = [
                'used_bytes'  => null,
                'quota_bytes' => $quotaMbDb * 1024 * 1024,
                'used_human'  => null,
                'quota_human' => self::formatBytes($quotaMbDb * 1024 * 1024),
                'free_human'  => null,
                'percent'     => null,
                'is_critical' => false,
                'is_warning'  => false,
            ];
        }

        if (!$isDokKbmEnabled) {
            $groupedByGuru = collect();
            $stats = [
                'total_gurus'    => 0,
                'total_sessions' => 0,
                'total_files'    => 0,
                'total_images'   => 0,
                'total_videos'   => 0,
            ];
            $search = '';

            return view('absensi-pelajaran.dokumentasi', compact(
                'groupedByGuru',
                'stats',
                'search',
                'isAdminOrWaka',
                'isGuru',
                'isDokKbmEnabled',
                'school',
                'storageInfo'
            ));
        }

        $search = trim($request->query('q', ''));

        // Query seluruh sesi KBM yang memiliki berkas dokumentasi
        $query = JurnalPelajaran::where('school_id', $schoolId)
            ->whereNotNull('dokumentasi')
            ->where('dokumentasi', '!=', '[]')
            ->where('dokumentasi', '!=', '""')
            ->where('dokumentasi', '!=', '')
            ->with(['guru.user', 'jadwal.kelas', 'jadwal.mapel']);

        if (!$isAdminOrWaka && $isGuru) {
            $query->where('guru_id', $guruId);
        }

        $allJurnals = $query->orderBy('tanggal', 'desc')->get();

        // Konsolidasikan kelas paralel agar sesi KBM gabungan & foto/videonya tidak terhitung ganda
        $consolidatedJurnals = $this->consolidateParallelJurnals($allJurnals);

        // Grouping per Guru dengan ringkasan statistik yang sudah terkonsolidasi & deduplikasi
        $groupedByGuru = $consolidatedJurnals->groupBy(function ($j) {
            return $j->guru_id ?? 0;
        })->map(function ($items, $gId) {
            $guru = $items->first()->guru;
            $allDocs = $items->flatMap(fn($j) => $j->consolidated_docs ?? $j->dokumentasi_list);
            $publicSlug = $guru?->username ?: ($guru?->user?->username ?: $gId);

            return (object) [
                'guru'             => $guru,
                'guru_id'          => $gId,
                'guru_username'    => $publicSlug,
                'guru_public_slug' => $publicSlug,
                'guru_nama'        => $guru ? $guru->nama : 'Guru Tidak Diketahui',
                'guru_nip'         => $guru?->nip ?? '-',
                'guru_foto'        => $guru?->foto_url ?? null,
                'total_kbm'        => $items->count(),
                'total_files'      => $allDocs->count(),
                'total_images'     => $allDocs->where('is_image', true)->count(),
                'total_videos'     => $allDocs->where('is_video', true)->count(),
                'latest_kbm'       => $items->max('tanggal'),
            ];
        });

        // Filter pencarian nama / NIP guru jika ada
        if (!empty($search)) {
            $groupedByGuru = $groupedByGuru->filter(function ($item) use ($search) {
                return str_contains(strtolower($item->guru_nama), strtolower($search))
                    || str_contains(strtolower($item->guru_nip), strtolower($search));
            });
        }

        $groupedByGuru = $groupedByGuru->sortByDesc('total_files')->values();

        // Statistik Keseluruhan (Kartu Atas) yang telah dikonsolidasikan
        $allDocsFlattened = $consolidatedJurnals->flatMap(fn($j) => $j->consolidated_docs ?? $j->dokumentasi_list);
        $stats = [
            'total_gurus'    => $consolidatedJurnals->pluck('guru_id')->filter()->unique()->count(),
            'total_sessions' => $consolidatedJurnals->count(),
            'total_files'    => $allDocsFlattened->count(),
            'total_images'   => $allDocsFlattened->where('is_image', true)->count(),
            'total_videos'   => $allDocsFlattened->where('is_video', true)->count(),
        ];

        return view('absensi-pelajaran.dokumentasi', compact(
            'groupedByGuru',
            'stats',
            'search',
            'isAdminOrWaka',
            'isGuru',
            'isDokKbmEnabled',
            'school',
            'storageInfo'
        ));
    }

    /**
     * Format bytes ke satuan manusia (KB, MB, GB)
     */
    protected static function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    /**
     * Halaman Galeri Dokumentasi Pembelajaran Spesifik per Guru (Tab Baru)
     */
    public function dokumentasiGuru(Guru $guru, Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;
        $isGuru = !empty($user->guru);
        $guruId = $user->guru?->id;
        $role = $user->role;
        $isAdminOrWaka = in_array($role, ['admin', 'super_admin', 'waka_kurikulum']);
        $school = $user?->school;

        // Pastikan guru satu sekolah
        if ($guru->school_id !== $schoolId) {
            abort(404);
        }

        // Jika bukan admin/waka dan akun guru yang berbeda, tolak akses
        if (!$isAdminOrWaka && $isGuru && $guru->id !== $guruId) {
            abort(403, 'Akses ditolak.');
        }

        $isDokKbmEnabled = ($user && $user->role === 'super_admin')
            ? true
            : app(\App\Services\LicenseService::class)->isDokKbmFeatureEnabled($school);

        if (!$isDokKbmEnabled) {
            $filteredJurnals = collect();
            $allDocs = collect();
            $teacherStats = [
                'total_sessions' => 0,
                'total_files'    => 0,
                'total_images'   => 0,
                'total_videos'   => 0,
            ];
            $startDate = null;
            $endDate = null;
            $kelasId = null;
            $mapelId = null;
            $mediaType = 'all';
            $search = '';
            $kelases = collect();
            $mapels = collect();

            return view('absensi-pelajaran.dokumentasi-guru', compact(
                'guru',
                'filteredJurnals',
                'allDocs',
                'teacherStats',
                'startDate',
                'endDate',
                'kelasId',
                'mapelId',
                'mediaType',
                'search',
                'kelases',
                'mapels',
                'isDokKbmEnabled',
                'school'
            ));
        }

        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');
        $kelasId   = $request->query('kelas_id');
        $mapelId   = $request->query('mapel_id');
        $mediaType = $request->query('media_type', 'all'); // 'all', 'image', 'video'
        $search    = trim($request->query('q', ''));

        $query = JurnalPelajaran::where('school_id', $schoolId)
            ->where('guru_id', $guru->id)
            ->whereNotNull('dokumentasi')
            ->where('dokumentasi', '!=', '[]')
            ->where('dokumentasi', '!=', '""')
            ->where('dokumentasi', '!=', '')
            ->with([
                'jadwal.kelas',
                'jadwal.mapel',
            ]);

        if (!empty($startDate) && !empty($endDate)) {
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        } elseif (!empty($startDate)) {
            $query->where('tanggal', '>=', $startDate);
        } elseif (!empty($endDate)) {
            $query->where('tanggal', '<=', $endDate);
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
                  ->orWhereHas('jadwal.mapel', fn($m) => $m->where('nama_mapel', 'like', "%{$search}%"))
                  ->orWhereHas('jadwal.kelas', fn($k) => $k->where('nama_kelas', 'like', "%{$search}%"));
            });
        }

        $allJurnals = $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->get();

        // Lampirkan data siswa tidak hadir pada tiap jurnal
        $this->attachAbsentStudentsToJurnals($allJurnals, $schoolId);

        // Gabungkan kelas gabungan (paralel) agar sesi & foto/video tidak dobel
        $consolidatedJurnals = $this->consolidateParallelJurnals($allJurnals);

        // Filter media_type pada masing-masing item dokumentasi
        $filteredJurnals = $consolidatedJurnals->filter(function ($jurnal) use ($mediaType) {
            $list = $jurnal->consolidated_docs ?? $jurnal->dokumentasi_list;
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

        // Flatten semua berkas media untuk galeri interaktif
        $allDocs = $filteredJurnals->flatMap(function ($jurnal) {
            $items = $jurnal->filtered_docs ?? ($jurnal->consolidated_docs ?? $jurnal->dokumentasi_list);
            return collect($items)->map(function ($doc) use ($jurnal) {
                $doc['jurnal_id']        = $jurnal->id;
                $doc['tanggal']          = $jurnal->tanggal;
                $doc['jam_ke']           = $jurnal->jam_ke ?: ($jurnal->jadwal?->jam_ke ?: '-');
                $doc['jam_mulai']        = $jurnal->jam_mulai;
                $doc['jam_selesai']      = $jurnal->jam_selesai;
                $doc['nama_kelas']       = $jurnal->nama_kelas ?? ($jurnal->jadwal?->kelas?->nama_kelas ?: 'Semua Kelas');
                $doc['is_multi_kelas']   = $jurnal->is_multi_kelas ?? false;
                $doc['parallel_kelases'] = $jurnal->parallel_kelas_names ?? [];
                $doc['nama_mapel']       = $jurnal->jadwal?->mapel?->nama_mapel ?: 'Mata Pelajaran';
                $doc['materi']           = $jurnal->materi;
                $doc['catatan']          = $jurnal->catatan;
                $doc['jadwal_id']        = $jurnal->jadwal_pelajaran_id;
                $doc['absent_students']  = $jurnal->absent_students ?? [];
                $doc['absent_count']     = $jurnal->absent_count ?? 0;
                return $doc;
            });
        });

        // Statistik Guru Ini
        $teacherStats = [
            'total_sessions' => $filteredJurnals->count(),
            'total_files'    => $allDocs->count(),
            'total_images'   => $allDocs->where('is_image', true)->count(),
            'total_videos'   => $allDocs->where('is_video', true)->count(),
        ];

        // Filter Options (Kelas & Mapel)
        $kelases = Kelas::where('school_id', $schoolId)->orderBy('nama_kelas')->get();
        $mapels = Mapel::where('school_id', $schoolId)->orderBy('nama_mapel')->get();

        $guru->load('user');
        $publicSlug = $guru->username ?: (string)$guru->id;
        $publicUrl = route('publik.dokumentasi-guru', $publicSlug);

        return view('absensi-pelajaran.dokumentasi-guru', compact(
            'guru',
            'filteredJurnals',
            'allDocs',
            'teacherStats',
            'startDate',
            'endDate',
            'kelasId',
            'mapelId',
            'mediaType',
            'search',
            'kelases',
            'mapels',
            'isDokKbmEnabled',
            'school',
            'publicUrl'
        ));
    }

    /**
     * Halaman Publik Galeri Dokumentasi KBM & Jurnal Guru (Tanpa Login / Akses Publik Pakai Username Guru)
     */
    public function publicDokumentasiGuru($identifier, Request $request)
    {
        // 1. Cari Guru berdasarkan username dari User yang terkait
        $guru = Guru::whereHas('user', function ($q) use ($identifier) {
            $q->where('username', $identifier);
        })->first();

        // 2. Jika belum ditemukan, cari User dengan username tersebut lalu ambil relasi gurunya
        if (!$guru) {
            $user = User::where('username', $identifier)->first();
            if ($user) {
                $guru = $user->guru ?: Guru::where('user_id', $user->id)->first();
            }
        }

        // 3. Fallback: jika identifier berupa ID Guru numerik (kompatibilitas link lama)
        if (!$guru && is_numeric($identifier)) {
            $guru = Guru::find($identifier);
        }

        // 4. Fallback: jika identifier berupa NIP guru
        if (!$guru) {
            $guru = Guru::where('nip', $identifier)->first();
        }

        if (!$guru) {
            abort(404, 'Data guru atau dokumentasi KBM tidak ditemukan.');
        }

        $guru->load('school', 'user');
        $school = $guru->school;

        if (!$school) {
            abort(404, 'Data sekolah tidak ditemukan.');
        }

        $isDokKbmEnabled = app(\App\Services\LicenseService::class)->isDokKbmFeatureEnabled($school);

        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');
        $kelasId   = $request->query('kelas_id');
        $mapelId   = $request->query('mapel_id');
        $mediaType = $request->query('media_type', 'all'); // 'all', 'image', 'video'
        $search    = trim($request->query('q', ''));
        $viewMode  = $request->query('view', 'gallery'); // 'gallery' atau 'timeline'

        $publicSlug = $guru->username ?: (string)$guru->id;
        $publicUrl = route('publik.dokumentasi-guru', $publicSlug);

        if (!$isDokKbmEnabled) {
            $filteredJurnals = collect();
            $allDocs = collect();
            $teacherStats = [
                'total_sessions' => 0,
                'total_files'    => 0,
                'total_images'   => 0,
                'total_videos'   => 0,
            ];
            $kelases = collect();
            $mapels = collect();

            return view('absensi-pelajaran.dokumentasi-guru-public', compact(
                'guru',
                'school',
                'filteredJurnals',
                'allDocs',
                'teacherStats',
                'startDate',
                'endDate',
                'kelasId',
                'mapelId',
                'mediaType',
                'search',
                'viewMode',
                'kelases',
                'mapels',
                'isDokKbmEnabled',
                'publicUrl'
            ));
        }

        $query = JurnalPelajaran::where('school_id', $school->id)
            ->where('guru_id', $guru->id)
            ->whereNotNull('dokumentasi')
            ->where('dokumentasi', '!=', '[]')
            ->where('dokumentasi', '!=', '""')
            ->where('dokumentasi', '!=', '')
            ->with([
                'jadwal.kelas',
                'jadwal.mapel',
            ]);

        if (!empty($startDate) && !empty($endDate)) {
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        } elseif (!empty($startDate)) {
            $query->where('tanggal', '>=', $startDate);
        } elseif (!empty($endDate)) {
            $query->where('tanggal', '<=', $endDate);
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
                  ->orWhereHas('jadwal.mapel', fn($m) => $m->where('nama_mapel', 'like', "%{$search}%"))
                  ->orWhereHas('jadwal.kelas', fn($k) => $k->where('nama_kelas', 'like', "%{$search}%"));
            });
        }

        $allJurnals = $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->get();

        // Lampirkan data siswa tidak hadir pada tiap jurnal
        $this->attachAbsentStudentsToJurnals($allJurnals, $school->id);

        // Gabungkan kelas gabungan (paralel) agar sesi & foto/video tidak dobel
        $consolidatedJurnals = $this->consolidateParallelJurnals($allJurnals);

        // Filter media_type pada masing-masing item dokumentasi
        $filteredJurnals = $consolidatedJurnals->filter(function ($jurnal) use ($mediaType) {
            $list = $jurnal->consolidated_docs ?? $jurnal->dokumentasi_list;
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

        // Flatten semua berkas media untuk galeri interaktif
        $allDocs = $filteredJurnals->flatMap(function ($jurnal) {
            $items = $jurnal->filtered_docs ?? ($jurnal->consolidated_docs ?? $jurnal->dokumentasi_list);
            return collect($items)->map(function ($doc) use ($jurnal) {
                $doc['jurnal_id']        = $jurnal->id;
                $doc['tanggal']          = $jurnal->tanggal;
                $doc['jam_ke']           = $jurnal->jam_ke ?: ($jurnal->jadwal?->jam_ke ?: '-');
                $doc['jam_mulai']        = $jurnal->jam_mulai;
                $doc['jam_selesai']      = $jurnal->jam_selesai;
                $doc['nama_kelas']       = $jurnal->nama_kelas ?? ($jurnal->jadwal?->kelas?->nama_kelas ?: 'Semua Kelas');
                $doc['is_multi_kelas']   = $jurnal->is_multi_kelas ?? false;
                $doc['parallel_kelases'] = $jurnal->parallel_kelas_names ?? [];
                $doc['nama_mapel']       = $jurnal->jadwal?->mapel?->nama_mapel ?: 'Mata Pelajaran';
                $doc['materi']           = $jurnal->materi;
                $doc['catatan']          = $jurnal->catatan;
                $doc['jadwal_id']        = $jurnal->jadwal_pelajaran_id;
                $doc['absent_students']  = $jurnal->absent_students ?? [];
                $doc['absent_count']     = $jurnal->absent_count ?? 0;
                return $doc;
            });
        });

        // Statistik Guru Ini
        $teacherStats = [
            'total_sessions' => $filteredJurnals->count(),
            'total_files'    => $allDocs->count(),
            'total_images'   => $allDocs->where('is_image', true)->count(),
            'total_videos'   => $allDocs->where('is_video', true)->count(),
        ];

        // Filter Options (Kelas & Mapel)
        $kelases = Kelas::where('school_id', $school->id)->orderBy('nama_kelas')->get();
        $mapels = Mapel::where('school_id', $school->id)->orderBy('nama_mapel')->get();

        return view('absensi-pelajaran.dokumentasi-guru-public', compact(
            'guru',
            'school',
            'filteredJurnals',
            'allDocs',
            'teacherStats',
            'startDate',
            'endDate',
            'kelasId',
            'mapelId',
            'mediaType',
            'search',
            'viewMode',
            'kelases',
            'mapels',
            'isDokKbmEnabled',
            'publicUrl'
        ));
    }

    /**
     * Lampirkan data siswa yang tidak hadir (Sakit, Izin, Alpa, Dispen) ke setiap jurnal
     */
    private function attachAbsentStudentsToJurnals($allJurnals, $schoolId): void
    {
        if ($allJurnals->isEmpty()) {
            return;
        }

        $jurnalIds = $allJurnals->pluck('id')->filter()->toArray();

        $absentsQuery = AbsensiPelajaran::where('school_id', $schoolId)
            ->whereIn('status', ['S', 'I', 'A', 'D'])
            ->with('student:id,nama,nis,kelas_id');

        $absentsQuery->where(function ($q) use ($jurnalIds, $allJurnals) {
            $hasCondition = false;
            if (!empty($jurnalIds)) {
                $q->whereIn('jurnal_pelajaran_id', $jurnalIds);
                $hasCondition = true;
            }
            foreach ($allJurnals as $j) {
                $tanggalStr = $j->tanggal instanceof \Carbon\Carbon ? $j->tanggal->format('Y-m-d') : substr((string)$j->tanggal, 0, 10);
                if (!empty($j->jadwal_pelajaran_id) && !empty($tanggalStr)) {
                    if ($hasCondition) {
                        $q->orWhere(function ($sub) use ($j, $tanggalStr) {
                            $sub->where('jadwal_pelajaran_id', $j->jadwal_pelajaran_id)
                                ->whereDate('tanggal', $tanggalStr);
                        });
                    } else {
                        $q->where(function ($sub) use ($j, $tanggalStr) {
                            $sub->where('jadwal_pelajaran_id', $j->jadwal_pelajaran_id)
                                ->whereDate('tanggal', $tanggalStr);
                        });
                        $hasCondition = true;
                    }
                }
            }
        });

        $allAbsents = $absentsQuery->get();

        $absentsByJurnal = [];
        foreach ($allAbsents as $abs) {
            $statusLabel = match ($abs->status) {
                'S' => 'Sakit',
                'I' => 'Izin',
                'A' => 'Alpa',
                'D' => 'Dispen',
                default => $abs->status,
            };
            $studentName = $abs->student?->nama ?: 'Siswa';
            $itemData = [
                'nama'         => $studentName,
                'status'       => $abs->status,
                'status_label' => $statusLabel,
                'keterangan'   => $abs->keterangan,
                'display'      => $studentName . ' (' . $statusLabel . ')',
            ];

            if (!empty($abs->jurnal_pelajaran_id)) {
                $absentsByJurnal['j_' . $abs->jurnal_pelajaran_id][] = $itemData;
            }
            $tglKey = $abs->tanggal instanceof \Carbon\Carbon ? $abs->tanggal->format('Y-m-d') : substr((string)$abs->tanggal, 0, 10);
            if (!empty($abs->jadwal_pelajaran_id)) {
                $absentsByJurnal['jt_' . $abs->jadwal_pelajaran_id . '_' . $tglKey][] = $itemData;
            }
        }

        foreach ($allJurnals as $jurnal) {
            $tglStr = $jurnal->tanggal instanceof \Carbon\Carbon ? $jurnal->tanggal->format('Y-m-d') : substr((string)$jurnal->tanggal, 0, 10);
            $listByJurnalId = !empty($jurnal->id) && isset($absentsByJurnal['j_' . $jurnal->id]) ? $absentsByJurnal['j_' . $jurnal->id] : [];
            $listByJadwalTgl = (!empty($jurnal->jadwal_pelajaran_id) && !empty($tglStr) && isset($absentsByJurnal['jt_' . $jurnal->jadwal_pelajaran_id . '_' . $tglStr]))
                ? $absentsByJurnal['jt_' . $jurnal->jadwal_pelajaran_id . '_' . $tglStr]
                : [];

            $combined = array_merge($listByJurnalId, $listByJadwalTgl);
            $uniqueList = collect($combined)->unique('nama')->sortBy('nama')->values()->all();
            $jurnal->absent_students = $uniqueList;
            $jurnal->absent_count = count($uniqueList);
        }
    }

    /**
     * Menggabungkan jurnal sesi paralel (kelas gabungan) menjadi 1 sesi terpadu,
     * sehingga dokumentasi foto & video tidak muncul ganda (duplicate).
     */
    protected function consolidateParallelJurnals($jurnals)
    {
        if ($jurnals->isEmpty()) {
            return collect();
        }

        $grouped = [];

        foreach ($jurnals as $jurnal) {
            $tgl = $jurnal->tanggal instanceof \Carbon\Carbon
                ? $jurnal->tanggal->format('Y-m-d')
                : substr((string)$jurnal->tanggal, 0, 10);
            $guruId = $jurnal->guru_id ?? 0;
            $mapelId = $jurnal->jadwal?->mapel_id ?? 0;
            $jamKe = str_replace(' ', '', strtolower((string)($jurnal->jam_ke ?: ($jurnal->jadwal?->jam_ke ?: ''))));
            $jamMulai = substr(trim((string)($jurnal->jam_mulai ?: ($jurnal->jadwal?->jam_mulai ?: ''))), 0, 5);
            $jamSelesai = substr(trim((string)($jurnal->jam_selesai ?: ($jurnal->jadwal?->jam_selesai ?: ''))), 0, 5);
            $timeSlot = ($jamMulai !== '' || $jamSelesai !== '') ? "{$jamMulai}-{$jamSelesai}" : '';

            $slotKey = ($timeSlot !== '') ? $timeSlot : ($jamKe ?: 'slot');
            $groupKey = "{$tgl}_{$guruId}_{$mapelId}_{$slotKey}";

            // Cek juga jika ada kesamaan URL media dengan grup yang sudah ada pada tanggal dan guru yang sama
            $docUrls = collect($jurnal->dokumentasi_list)->map(fn($d) => $d['url'] ?? ($d['view_url'] ?? ''))->filter()->all();
            if (!empty($docUrls)) {
                foreach ($grouped as $existKey => $gData) {
                    if (str_starts_with($existKey, "{$tgl}_{$guruId}_")) {
                        $existUrls = array_keys($gData['docs']);
                        if (!empty(array_intersect($docUrls, $existUrls))) {
                            $groupKey = $existKey;
                            break;
                        }
                    }
                }
            }

            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'primary'  => $jurnal,
                    'kelases'  => [],
                    'docs'     => [],
                    'absents'  => [],
                ];
            }

            // Kumpulkan nama kelas
            $kelasNama = $jurnal->jadwal?->kelas?->nama_kelas;
            if ($kelasNama && !in_array($kelasNama, $grouped[$groupKey]['kelases'])) {
                $grouped[$groupKey]['kelases'][] = $kelasNama;
            }

            // Kumpulkan dokumen/media dan de-duplicate berdasarkan URL
            $docs = $jurnal->dokumentasi_list;
            foreach ($docs as $d) {
                $dUrl = $d['url'] ?? ($d['view_url'] ?? '');
                if (!empty($dUrl) && !isset($grouped[$groupKey]['docs'][$dUrl])) {
                    $grouped[$groupKey]['docs'][$dUrl] = $d;
                }
            }

            // Kumpulkan siswa tidak hadir dan de-duplicate
            if (!empty($jurnal->absent_students) && is_array($jurnal->absent_students)) {
                foreach ($jurnal->absent_students as $st) {
                    $stKey = $st['nama'] ?? ($st['id'] ?? uniqid());
                    if (!isset($grouped[$groupKey]['absents'][$stKey])) {
                        if ($kelasNama) {
                            $st['kelas'] = $kelasNama;
                        }
                        $grouped[$groupKey]['absents'][$stKey] = $st;
                    }
                }
            }
        }

        $result = collect();
        foreach ($grouped as $group) {
            $j = clone $group['primary'];
            $kelasNames = $group['kelases'];
            $j->is_multi_kelas = count($kelasNames) > 1;
            $j->parallel_kelas_names = $kelasNames;
            $j->nama_kelas = !empty($kelasNames) ? implode(', ', $kelasNames) : ($j->jadwal?->kelas?->nama_kelas ?: 'Semua Kelas');
            
            // Set dokumentasi yang sudah di-deduplicate
            $uniqueDocs = array_values($group['docs']);
            $j->consolidated_docs = $uniqueDocs;
            $j->dokumentasi_list = $uniqueDocs;

            // Set siswa tidak hadir yang sudah digabung
            $mergedAbsents = array_values($group['absents']);
            $j->absent_students = $mergedAbsents;
            $j->absent_count = count($mergedAbsents);

            $result->push($j);
        }

        return $result;
    }
}

