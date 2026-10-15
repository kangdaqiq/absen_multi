<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbsensiPelajaran;
use App\Models\JadwalPelajaran;
use App\Models\JurnalPelajaran;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileJadwalPelajaranController extends Controller
{
    /**
     * Dapatkan jadwal mengajar guru untuk hari ini (atau tanggal tertentu)
     */
    public function getHariIni(Request $request)
    {
        $user = $request->user();
        $schoolId = $user->school_id ?? ($user->guru ? $user->guru->school_id : null);
        $guru = $user->guru;

        if (!$guru) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak terhubung dengan data guru pengajar.'
            ], 403);
        }

        $rawTanggal = $request->query('tanggal', Carbon::today()->format('Y-m-d'));
        try {
            $parsedDate = Carbon::parse($rawTanggal);
            $tanggal = $parsedDate->format('Y-m-d');
        } catch (\Exception $e) {
            $parsedDate = Carbon::today();
            $tanggal = $parsedDate->format('Y-m-d');
        }

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

        $jadwals = JadwalPelajaran::where('school_id', $schoolId)
            ->where('guru_id', $guru->id)
            ->where('hari', $namaHari)
            ->with(['kelas', 'mapel'])
            ->orderBy('jam_mulai')
            ->get();

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

        $formatted = $jadwals->map(function ($j) use ($jurnals, $absensiCounts) {
            $jurnal = $jurnals->get($j->id);
            $counts = $absensiCounts->get($j->id);
            $isAttended = !empty($counts) && $counts->total_absen > 0;

            return [
                'id'           => $j->id,
                'hari'         => $j->hari,
                'jam_ke'       => $j->jam_ke ?? '-',
                'jam_mulai'    => substr($j->jam_mulai, 0, 5),
                'jam_selesai'  => substr($j->jam_selesai, 0, 5),
                'jam_format'   => $j->jam_format,
                'ruangan'      => $j->ruangan ?? '-',
                'kelas_id'     => $j->kelas_id,
                'kelas_nama'   => $j->kelas?->nama_kelas ?? '-',
                'mapel_id'     => $j->mapel_id,
                'mapel_nama'   => $j->mapel?->nama_mapel ?? '-',
                'is_attended'  => $isAttended,
                'jurnal'       => $jurnal ? [
                    'id'      => $jurnal->id,
                    'materi'  => $jurnal->materi,
                    'catatan' => $jurnal->catatan,
                ] : null,
                'stats'        => [
                    'total' => (int)($counts?->total_absen ?? 0),
                    'h'     => (int)($counts?->count_h ?? 0),
                    's'     => (int)($counts?->count_s ?? 0),
                    'i'     => (int)($counts?->count_i ?? 0),
                    'a'     => (int)($counts?->count_a ?? 0),
                    't'     => (int)($counts?->count_t ?? 0),
                    'd'     => (int)($counts?->count_d ?? 0),
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'tanggal'   => $tanggal,
                'hari'      => $namaHari,
                'guru'      => [
                    'id'   => $guru->id,
                    'nama' => $guru->nama,
                    'nip'  => $guru->nip,
                ],
                'jadwals'   => $formatted,
            ]
        ]);
    }

    /**
     * Dapatkan semua jadwal mengajar mingguan guru
     */
    public function getMingguan(Request $request)
    {
        $user = $request->user();
        $schoolId = $user->school_id ?? ($user->guru ? $user->guru->school_id : null);
        $guru = $user->guru;

        if (!$guru) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak terhubung dengan data guru pengajar.'
            ], 403);
        }

        $jadwals = JadwalPelajaran::where('school_id', $schoolId)
            ->where('guru_id', $guru->id)
            ->with(['kelas', 'mapel'])
            ->orderByRaw("FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')")
            ->orderBy('jam_mulai')
            ->get();

        $grouped = $jadwals->groupBy('hari')->map(function ($items, $day) {
            return $items->map(function ($j) {
                return [
                    'id'          => $j->id,
                    'hari'        => $j->hari,
                    'jam_ke'      => $j->jam_ke ?? '-',
                    'jam_mulai'   => substr($j->jam_mulai, 0, 5),
                    'jam_selesai' => substr($j->jam_selesai, 0, 5),
                    'jam_format'  => $j->jam_format,
                    'ruangan'     => $j->ruangan ?? '-',
                    'kelas_id'    => $j->kelas_id,
                    'kelas_nama'  => $j->kelas?->nama_kelas ?? '-',
                    'mapel_id'    => $j->mapel_id,
                    'mapel_nama'  => $j->mapel?->nama_mapel ?? '-',
                ];
            });
        });

        return response()->json([
            'success' => true,
            'data'    => $grouped,
        ]);
    }

    /**
     * Dapatkan detail jadwal, jurnal, dan daftar siswa dengan status kehadiran
     */
    public function getPresensiDetail($jadwalId, Request $request)
    {
        $user = $request->user();
        $schoolId = $user->school_id ?? ($user->guru ? $user->guru->school_id : null);
        $guru = $user->guru;

        $jadwal = JadwalPelajaran::where('school_id', $schoolId)
            ->with(['kelas', 'mapel', 'guru'])
            ->findOrFail($jadwalId);

        if ($guru && $jadwal->guru_id !== $guru->id && !in_array($user->role, ['admin', 'super_admin', 'waka_kurikulum'])) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke jadwal pembelajaran ini.'
            ], 403);
        }

        $rawTanggal = $request->query('tanggal', Carbon::today()->format('Y-m-d'));
        try {
            $tanggal = Carbon::parse($rawTanggal)->format('Y-m-d');
        } catch (\Exception $e) {
            $tanggal = Carbon::today()->format('Y-m-d');
        }

        // Ambil Jurnal
        $jurnal = JurnalPelajaran::where('jadwal_pelajaran_id', $jadwal->id)
            ->where('tanggal', $tanggal)
            ->first();

        // Ambil Siswa
        $students = Siswa::where('school_id', $schoolId)
            ->where('kelas_id', $jadwal->kelas_id)
            ->orderBy('nama')
            ->get();

        // Ambil data presensi tersimpan
        $attendanceMap = AbsensiPelajaran::where('jadwal_pelajaran_id', $jadwal->id)
            ->where('tanggal', $tanggal)
            ->get()
            ->keyBy('student_id');

        $studentList = $students->map(function ($s) use ($attendanceMap) {
            $record = $attendanceMap->get($s->id);
            return [
                'id'         => $s->id,
                'nama'       => $s->nama,
                'nis'        => $s->nis,
                'foto'       => $s->foto_url ?? null,
                'status'     => $record?->status ?? 'H', // Default Hadir
                'status_label' => $record?->status_label ?? 'Hadir',
                'keterangan' => $record?->keterangan ?? '',
                'is_saved'   => !empty($record),
            ];
        });

        $stats = [
            'total' => $studentList->count(),
            'h'     => $studentList->where('status', 'H')->count(),
            's'     => $studentList->where('status', 'S')->count(),
            'i'     => $studentList->where('status', 'I')->count(),
            'a'     => $studentList->where('status', 'A')->count(),
            't'     => $studentList->where('status', 'T')->count(),
            'd'     => $studentList->where('status', 'D')->count(),
        ];

        return response()->json([
            'success' => true,
            'data'    => [
                'jadwal' => [
                    'id'          => $jadwal->id,
                    'hari'        => $jadwal->hari,
                    'jam_ke'      => $jadwal->jam_ke ?? '-',
                    'jam_mulai'   => substr($jadwal->jam_mulai, 0, 5),
                    'jam_selesai' => substr($jadwal->jam_selesai, 0, 5),
                    'jam_format'  => $jadwal->jam_format,
                    'ruangan'     => $jadwal->ruangan ?? '-',
                    'kelas'       => [
                        'id'   => $jadwal->kelas?->id,
                        'nama' => $jadwal->kelas?->nama_kelas,
                    ],
                    'mapel'       => [
                        'id'   => $jadwal->mapel?->id,
                        'nama' => $jadwal->mapel?->nama_mapel,
                    ],
                    'guru'        => [
                        'id'   => $jadwal->guru?->id,
                        'nama' => $jadwal->guru?->nama,
                    ]
                ],
                'tanggal'  => $tanggal,
                'jurnal'   => $jurnal ? [
                    'id'      => $jurnal->id,
                    'materi'  => $jurnal->materi,
                    'catatan' => $jurnal->catatan,
                ] : null,
                'summary'  => $stats,
                'students' => $studentList,
            ]
        ]);
    }

    /**
     * Simpan / Perbarui Presensi Batch dan Jurnal KBM dari Apps
     */
    public function submitPresensi($jadwalId, Request $request)
    {
        $user = $request->user();
        $schoolId = $user->school_id ?? ($user->guru ? $user->guru->school_id : null);
        $guru = $user->guru;

        $jadwal = JadwalPelajaran::where('school_id', $schoolId)->findOrFail($jadwalId);

        if ($guru && $jadwal->guru_id !== $guru->id && !in_array($user->role, ['admin', 'super_admin', 'waka_kurikulum'])) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $request->validate([
            'tanggal'        => 'required|date',
            'materi'         => 'required|string|max:1000',
            'catatan'        => 'nullable|string|max:1000',
            'attendances'    => 'required|array',
            'attendances.*.student_id' => 'required|exists:siswa,id',
            'attendances.*.status'     => 'required|in:H,S,I,A,T,D',
            'attendances.*.keterangan' => 'nullable|string|max:255',
        ]);

        $tanggal = Carbon::parse($request->tanggal)->format('Y-m-d');
        $operatorGuruId = $guru?->id ?? $jadwal->guru_id;

        DB::transaction(function () use ($request, $jadwal, $schoolId, $tanggal, $operatorGuruId) {
            // 1. Jurnal KBM
            $jurnal = JurnalPelajaran::updateOrCreate(
                [
                    'jadwal_pelajaran_id' => $jadwal->id,
                    'tanggal'             => $tanggal,
                ],
                [
                    'school_id'    => $schoolId,
                    'guru_id'      => $operatorGuruId,
                    'jam_ke'       => $jadwal->jam_ke,
                    'materi'       => trim($request->materi),
                    'catatan'      => $request->catatan ? trim($request->catatan) : null,
                    'jam_mulai'    => $jadwal->jam_mulai,
                    'jam_selesai'  => $jadwal->jam_selesai,
                ]
            );

            // 2. Presensi Siswa
            foreach ($request->input('attendances', []) as $item) {
                AbsensiPelajaran::updateOrCreate(
                    [
                        'jadwal_pelajaran_id' => $jadwal->id,
                        'student_id'          => $item['student_id'],
                        'tanggal'             => $tanggal,
                    ],
                    [
                        'school_id'           => $schoolId,
                        'jurnal_pelajaran_id' => $jurnal->id,
                        'guru_id'             => $operatorGuruId,
                        'status'              => $item['status'],
                        'keterangan'          => !empty($item['keterangan']) ? trim($item['keterangan']) : null,
                    ]
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Presensi dan Jurnal KBM berhasil disimpan.'
        ]);
    }

    /**
     * Update presensi 1 siswa secara instan dari apps
     */
    public function updateSinglePresensi($jadwalId, Request $request)
    {
        $user = $request->user();
        $schoolId = $user->school_id ?? ($user->guru ? $user->guru->school_id : null);
        $guru = $user->guru;

        $jadwal = JadwalPelajaran::where('school_id', $schoolId)->findOrFail($jadwalId);

        if ($guru && $jadwal->guru_id !== $guru->id && !in_array($user->role, ['admin', 'super_admin', 'waka_kurikulum'])) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $request->validate([
            'student_id' => 'required|exists:siswa,id',
            'tanggal'    => 'required|date',
            'status'     => 'required|in:H,S,I,A,T,D',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $tanggal = Carbon::parse($request->tanggal)->format('Y-m-d');
        $operatorGuruId = $guru?->id ?? $jadwal->guru_id;

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

        return response()->json([
            'success' => true,
            'message' => 'Status siswa berhasil diperbarui.',
            'data'    => [
                'id'           => $absensi->id,
                'student_id'   => $absensi->student_id,
                'status'       => $absensi->status,
                'status_label' => $absensi->status_label,
            ]
        ]);
    }

    /**
     * Dapatkan daftar mapel yang aktif (dapat difilter per kelas_id)
     */
    public function getMapels(Request $request)
    {
        $user = $request->user();
        $schoolId = $user->school_id ?? ($user->guru ? $user->guru->school_id : null);
        $kelasId = $request->query('kelas_id');

        $query = \App\Models\Mapel::where('school_id', $schoolId);

        if (!empty($kelasId)) {
            $query->where(function ($q) use ($kelasId) {
                $q->whereHas('kelases', function ($k) use ($kelasId) {
                    $k->where('kelas.id', $kelasId);
                })->orDoesntHave('kelases');
            });
        }

        $mapels = $query->orderBy('nama_mapel')->get(['id', 'nama_mapel']);

        return response()->json([
            'success' => true,
            'data'    => $mapels
        ]);
    }

    /**
     * Dapatkan daftar jam pelajaran aktif
     */
    public function getJamPelajaran(Request $request)
    {
        $user = $request->user();
        $schoolId = $user->school_id ?? ($user->guru ? $user->guru->school_id : null);
        $hari = $request->query('hari');

        $query = \App\Models\JamPelajaran::where('school_id', $schoolId)
            ->where('is_active', true);

        if (!empty($hari)) {
            $query->where(function ($q) use ($hari) {
                $q->where('hari', 'like', "%{$hari}%")
                  ->orWhereNull('hari')
                  ->orWhere('hari', '');
            });
        }

        $jamPelajarans = $query->orderBy('urutan', 'asc')
            ->orderBy('jam_mulai', 'asc')
            ->get()
            ->map(function ($jam) {
                return [
                    'id'          => $jam->id,
                    'nama_jam'    => $jam->nama_jam,
                    'jam_ke'      => $jam->jam_ke,
                    'jam_mulai'   => $jam->formatted_jam_mulai,
                    'jam_selesai' => $jam->formatted_jam_selesai,
                    'rentang'     => $jam->rentang_waktu,
                    'durasi'      => $jam->durasi_menit,
                    'label'       => $jam->label,
                    'hari'        => $jam->hari,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $jamPelajarans
        ]);
    }
}
