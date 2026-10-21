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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            if ($user->student || in_array($user->role, ['student', 'siswa']) || $request->filled('student_id')) {
                return $this->getStudentHariIni($request);
            }

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

        // Ambil data jumlah siswa riil terdaftar per kelas
        $allKelasIds = $jadwals->pluck('kelas_id')->filter()->unique()->values();
        $studentCountsByKelas = Siswa::where('school_id', $schoolId)
            ->whereIn('kelas_id', $allKelasIds)
            ->selectRaw('kelas_id, COUNT(*) as count')
            ->groupBy('kelas_id')
            ->pluck('count', 'kelas_id');

        $formatted = collect();
        $processedIds = [];

        foreach ($jadwals as $j) {
            if (in_array($j->id, $processedIds)) {
                continue;
            }

            // Cari jadwal paralel (sesi multi-kelas gabungan)
            $parallels = $jadwals->filter(function ($item) use ($j) {
                if ($item->mapel_id != $j->mapel_id) {
                    return false;
                }
                $time1 = substr(trim($item->jam_mulai ?? ''), 0, 5) . '-' . substr(trim($item->jam_selesai ?? ''), 0, 5);
                $time2 = substr(trim($j->jam_mulai ?? ''), 0, 5) . '-' . substr(trim($j->jam_selesai ?? ''), 0, 5);
                if ($time1 !== '-' && $time1 === $time2) {
                    return true;
                }
                if (!empty($item->jam_pelajaran_id) && $item->jam_pelajaran_id == $j->jam_pelajaran_id) {
                    return true;
                }
                if (!empty($item->jam_ke) && str_replace(' ', '', strtolower($item->jam_ke)) === str_replace(' ', '', strtolower($j->jam_ke))) {
                    return true;
                }
                return false;
            });

            foreach ($parallels as $p) {
                $processedIds[] = $p->id;
            }

            $isMulti = $parallels->count() > 1;
            $parallelIds = $parallels->pluck('id')->all();
            $parallelKelasIds = $parallels->pluck('kelas_id')->filter()->unique()->values()->all();
            $parallelKelasIds = $parallels->pluck('kelas_id')->filter()->unique()->values()->all();
                $parallelKelasNames = $parallels->map(fn($p) => $p->kelas?->nama_kelas)->filter()->unique()->values()->all();
                $totalSiswaKelas = collect($parallelKelasIds)->sum(fn($cid) => $studentCountsMingguan[$cid] ?? 0);
            $totalSiswaKelas = collect($parallelKelasIds)->sum(fn($cid) => $studentCountsByKelas[$cid] ?? 0);

            // Ambil jurnal KBM jika ada yang terisi di antara jadwal paralel
            $jurnal = null;
            foreach ($parallels as $p) {
                if ($jurnals->has($p->id)) {
                    $jurnal = $jurnals->get($p->id);
                    break;
                }
            }

            // Akumulasi statistik kehadiran dari seluruh kelas paralel
            $stats = [
                'total'       => 0,
                'total_absen' => 0,
                'total_siswa' => $totalSiswaKelas,
                'h'           => 0,
                's'     => 0,
                'i'     => 0,
                'a'     => 0,
                't'     => 0,
                'd'     => 0,
            ];
            $isAttended = false;

            foreach ($parallels as $p) {
                $counts = $absensiCounts->get($p->id);
                if ($counts) {
                    $stats['total'] += (int)$counts->total_absen;
                    $stats['h']     += (int)$counts->count_h;
                    $stats['s']     += (int)$counts->count_s;
                    $stats['i']     += (int)$counts->count_i;
                    $stats['a']     += (int)$counts->count_a;
                    $stats['t']     += (int)$counts->count_t;
                    $stats['d']     += (int)$counts->count_d;
                    $stats['total_absen'] += (int)$counts->total_absen;
                    if ($counts->total_absen > 0) {
                        $isAttended = true;
                    }
                }
            }

            // Jika belum ada presensi yang tersimpan hari ini, fallback stats.total ke jumlah siswa riil di kelas
            if ($stats['total'] === 0) {
                $stats['total'] = $totalSiswaKelas;
            }

            $breakInfo = $j->jeda_istirahat_info;

            $formatted->push([
                'id'                     => $j->id,
                'hari'                   => $j->hari,
                'jam_ke'                 => $j->jam_ke ?? '-',
                'jam_mulai'              => substr($j->jam_mulai, 0, 5),
                'jam_selesai'            => substr($j->jam_selesai, 0, 5),
                'jam_format'             => $j->jam_format,
                'has_istirahat'          => $breakInfo['has_istirahat'],
                'istirahat_label'        => $breakInfo['istirahat_label'],
                'istirahat_text'         => $breakInfo['istirahat_text'],
                'durasi_istirahat_menit' => $breakInfo['durasi_istirahat_menit'],
                'jam_detail_format'      => $breakInfo['segments_format'],
                'jeda_istirahat'         => $breakInfo['list'],
                'ruangan'                => $j->ruangan ?? '-',
                'kelas_id'         => $j->kelas_id,
                'kelas_nama'       => $isMulti ? implode(', ', $parallelKelasNames) : ($j->kelas?->nama_kelas ?? '-'),
                'total_siswa'      => $totalSiswaKelas,
                'jumlah_siswa'     => $totalSiswaKelas,
                'is_multi_kelas'   => $isMulti,
                'parallel_count'   => $parallels->count(),
                'parallel_kelases' => $parallelKelasNames,
                'parallel_ids'     => $parallelIds,
                'parallel_classes' => $parallels->map(fn($p) => [
                    'jadwal_id'  => $p->id,
                    'kelas_id'   => $p->kelas_id,
                    'kelas_nama' => $p->kelas?->nama_kelas ?? '-',
                ])->values()->all(),
                'mapel_id'         => $j->mapel_id,
                'mapel_nama'       => $j->mapel?->nama_mapel ?? '-',
                'is_attended'      => $isAttended,
                'jurnal'           => $jurnal ? [
                    'id'               => $jurnal->id,
                    'materi'           => $jurnal->materi,
                    'catatan'          => $jurnal->catatan,
                    'foto_dokumentasi' => $jurnal->dokumentasi_list[0]['view_url'] ?? null,
                    'dokumentasi'      => $jurnal->dokumentasi_list,
                    'has_dokumentasi'  => $jurnal->has_dokumentasi,
                ] : null,
                'stats'            => $stats,
            ]);
        }

        $licenseService = app(\App\Services\LicenseService::class);
        $school = $user->school ?? ($guru ? $guru->school : null);
        $dokKbmEnabled = $licenseService->isDokKbmFeatureEnabled($school);
        $storageCfg = config('services.storage_server', []);

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
                'dok_kbm'   => [
                    'enabled'     => $dokKbmEnabled,
                    'storage_url' => $storageCfg['url'] ?? 'https://storage.jagattech.my.id',
                    'api_key'     => $dokKbmEnabled ? ($storageCfg['key'] ?? '') : '',
                    'folder'      => $storageCfg['folder'] ?? 'kbm_docs',
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
            if ($user->student || in_array($user->role, ['student', 'siswa']) || $request->filled('student_id')) {
                return $this->getStudentMingguan($request);
            }

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

        $allMingguanKelasIds = $jadwals->pluck('kelas_id')->filter()->unique()->values();
        $studentCountsMingguan = Siswa::where('school_id', $schoolId)
            ->whereIn('kelas_id', $allMingguanKelasIds)
            ->selectRaw('kelas_id, COUNT(*) as count')
            ->groupBy('kelas_id')
            ->pluck('count', 'kelas_id');

        $grouped = $jadwals->groupBy('hari')->map(function ($items, $day) use ($studentCountsMingguan) {
            $processedIds = [];
            $dayList = collect();

            foreach ($items as $j) {
                if (in_array($j->id, $processedIds)) {
                    continue;
                }

                $parallels = $items->filter(function ($item) use ($j) {
                    if ($item->mapel_id != $j->mapel_id) {
                        return false;
                    }
                    $time1 = substr(trim($item->jam_mulai ?? ''), 0, 5) . '-' . substr(trim($item->jam_selesai ?? ''), 0, 5);
                    $time2 = substr(trim($j->jam_mulai ?? ''), 0, 5) . '-' . substr(trim($j->jam_selesai ?? ''), 0, 5);
                    if ($time1 !== '-' && $time1 === $time2) {
                        return true;
                    }
                    if (!empty($item->jam_pelajaran_id) && $item->jam_pelajaran_id == $j->jam_pelajaran_id) {
                        return true;
                    }
                    if (!empty($item->jam_ke) && str_replace(' ', '', strtolower($item->jam_ke)) === str_replace(' ', '', strtolower($j->jam_ke))) {
                        return true;
                    }
                    return false;
                });

                foreach ($parallels as $p) {
                    $processedIds[] = $p->id;
                }

                $isMulti = $parallels->count() > 1;
                $parallelKelasIds = $parallels->pluck('kelas_id')->filter()->unique()->values()->all();
                $parallelKelasNames = $parallels->map(fn($p) => $p->kelas?->nama_kelas)->filter()->unique()->values()->all();
                $totalSiswaKelas = collect($parallelKelasIds)->sum(fn($cid) => $studentCountsMingguan[$cid] ?? 0);

                $breakInfo = $j->jeda_istirahat_info;

                $dayList->push([
                    'id'                     => $j->id,
                    'hari'                   => $j->hari,
                    'jam_ke'                 => $j->jam_ke ?? '-',
                    'jam_mulai'              => substr($j->jam_mulai, 0, 5),
                    'jam_selesai'            => substr($j->jam_selesai, 0, 5),
                    'jam_format'             => $j->jam_format,
                    'has_istirahat'          => $breakInfo['has_istirahat'],
                    'istirahat_label'        => $breakInfo['istirahat_label'],
                    'istirahat_text'         => $breakInfo['istirahat_text'],
                    'durasi_istirahat_menit' => $breakInfo['durasi_istirahat_menit'],
                    'jam_detail_format'      => $breakInfo['segments_format'],
                    'jeda_istirahat'         => $breakInfo['list'],
                    'ruangan'                => $j->ruangan ?? '-',
                    'kelas_id'         => $j->kelas_id,
                    'kelas_nama'       => $isMulti ? implode(', ', $parallelKelasNames) : ($j->kelas?->nama_kelas ?? '-'),
                    'total_siswa'      => $totalSiswaKelas,
                    'jumlah_siswa'     => $totalSiswaKelas,
                    'is_multi_kelas'   => $isMulti,
                    'parallel_count'   => $parallels->count(),
                    'parallel_kelases' => $parallelKelasNames,
                    'parallel_ids'     => $parallelIds,
                    'parallel_classes' => $parallels->map(fn($p) => [
                        'jadwal_id'  => $p->id,
                        'kelas_id'   => $p->kelas_id,
                        'kelas_nama' => $p->kelas?->nama_kelas ?? '-',
                    ])->values()->all(),
                    'mapel_id'         => $j->mapel_id,
                    'mapel_nama'       => $j->mapel?->nama_mapel ?? '-',
                ]);
            }

            return $dayList;
        });

        return response()->json([
            'success' => true,
            'data'    => $grouped,
        ]);
    }

    /**
     * Helper dapatkan semua jadwal paralel (multi-kelas)
     */
    protected function getParallelJadwals($jadwal, $schoolId)
    {
        $allDay = JadwalPelajaran::where('school_id', $schoolId)
            ->where('guru_id', $jadwal->guru_id)
            ->where('mapel_id', $jadwal->mapel_id)
            ->where('hari', $jadwal->hari)
            ->with(['kelas'])
            ->get();

        return $allDay->filter(function ($item) use ($jadwal) {
            $time1 = substr(trim($item->jam_mulai ?? ''), 0, 5) . '-' . substr(trim($item->jam_selesai ?? ''), 0, 5);
            $time2 = substr(trim($jadwal->jam_mulai ?? ''), 0, 5) . '-' . substr(trim($jadwal->jam_selesai ?? ''), 0, 5);
            if ($time1 !== '-' && $time1 === $time2) {
                return true;
            }
            if (!empty($item->jam_pelajaran_id) && $item->jam_pelajaran_id == $jadwal->jam_pelajaran_id) {
                return true;
            }
            if (!empty($item->jam_ke) && str_replace(' ', '', strtolower($item->jam_ke)) === str_replace(' ', '', strtolower($jadwal->jam_ke))) {
                return true;
            }
            return false;
        });
    }

    /**
     * Dapatkan detail jadwal, jurnal, dan daftar siswa dengan status kehadiran
     * Mendukung Sesi Multi-Kelas (memuat siswa dari seluruh kelas gabungan)
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

        // Cek apakah jadwal ini memiliki sesi paralel (multi-kelas gabungan)
        $parallels = $this->getParallelJadwals($jadwal, $schoolId);
        $isMultiKelas = $parallels->count() > 1;
        $parallelIds = $parallels->pluck('id')->all();
        $parallelKelasIds = $parallels->pluck('kelas_id')->filter()->unique()->values()->all();
        $parallelKelasIds = $parallels->pluck('kelas_id')->filter()->unique()->values()->all();
                $parallelKelasNames = $parallels->map(fn($p) => $p->kelas?->nama_kelas)->filter()->unique()->values()->all();
                $totalSiswaKelas = collect($parallelKelasIds)->sum(fn($cid) => $studentCountsMingguan[$cid] ?? 0);

        // Ambil Jurnal (cari dari jadwal ini atau sister jadwal jika multi-kelas)
        $jurnal = JurnalPelajaran::whereIn('jadwal_pelajaran_id', $parallelIds)
            ->where('tanggal', $tanggal)
            ->first();

        // Ambil Seluruh Siswa (dari semua kelas gabungan jika multi-kelas)
        $students = Siswa::where('school_id', $schoolId)
            ->whereIn('kelas_id', $parallelKelasIds)
            ->with(['kelas'])
            ->orderBy('kelas_id')
            ->orderBy('nama')
            ->get();

        // Ambil data presensi tersimpan dari seluruh jadwal paralel
        $attendanceMap = AbsensiPelajaran::whereIn('jadwal_pelajaran_id', $parallelIds)
            ->where('tanggal', $tanggal)
            ->get()
            ->keyBy('student_id');

        $studentList = $students->map(function ($s) use ($attendanceMap) {
            $record = $attendanceMap->get($s->id);
            return [
                'id'           => $s->id,
                'nama'         => $s->nama,
                'nis'          => $s->nis,
                'foto'         => $s->foto_url ?? null,
                'kelas_id'     => $s->kelas_id,
                'kelas_nama'   => $s->kelas?->nama_kelas ?? '-',
                'status'       => $record?->status ?? 'A', // Default Alpa
                'status_label' => $record ? ($record->status_label ?? 'Hadir') : 'Alpa',
                'keterangan'   => $record?->keterangan ?? '',
                'is_saved'     => !empty($record),
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
                    'id'                     => $jadwal->id,
                    'hari'                   => $jadwal->hari,
                    'jam_ke'                 => $jadwal->jam_ke ?? '-',
                    'jam_mulai'              => substr($jadwal->jam_mulai, 0, 5),
                    'jam_selesai'            => substr($jadwal->jam_selesai, 0, 5),
                    'jam_format'             => $jadwal->jam_format,
                    'has_istirahat'          => $jadwal->jeda_istirahat_info['has_istirahat'],
                    'istirahat_label'        => $jadwal->jeda_istirahat_info['istirahat_label'],
                    'istirahat_text'         => $jadwal->jeda_istirahat_info['istirahat_text'],
                    'durasi_istirahat_menit' => $jadwal->jeda_istirahat_info['durasi_istirahat_menit'],
                    'jam_detail_format'      => $jadwal->jeda_istirahat_info['segments_format'],
                    'jeda_istirahat'         => $jadwal->jeda_istirahat_info['list'],
                    'ruangan'                => $jadwal->ruangan ?? '-',
                    'kelas'            => [
                        'id'   => $jadwal->kelas?->id,
                        'nama' => $isMultiKelas ? implode(', ', $parallelKelasNames) : ($jadwal->kelas?->nama_kelas ?? '-'),
                    ],
                    'is_multi_kelas'   => $isMultiKelas,
                    'parallel_count'   => $parallels->count(),
                    'parallel_kelases' => $parallelKelasNames,
                    'parallel_classes' => $parallels->map(fn($p) => [
                        'jadwal_id'  => $p->id,
                        'kelas_id'   => $p->kelas_id,
                        'kelas_nama' => $p->kelas?->nama_kelas ?? '-',
                    ])->values()->all(),
                    'mapel'            => [
                        'id'   => $jadwal->mapel?->id,
                        'nama' => $jadwal->mapel?->nama_mapel,
                    ],
                    'guru'             => [
                        'id'   => $jadwal->guru?->id,
                        'nama' => $jadwal->guru?->nama,
                    ]
                ],
                'tanggal'  => $tanggal,
                'jurnal'   => $jurnal ? [
                    'id'               => $jurnal->id,
                    'materi'           => $jurnal->materi,
                    'catatan'          => $jurnal->catatan,
                    'foto_dokumentasi' => $jurnal->dokumentasi_list[0]['view_url'] ?? null,
                    'dokumentasi'      => $jurnal->dokumentasi_list,
                    'has_dokumentasi'  => $jurnal->has_dokumentasi,
                ] : null,
                'dok_kbm'  => [
                    'enabled'     => app(\App\Services\LicenseService::class)->isDokKbmFeatureEnabled($jadwal->school ?? $user->school),
                    'storage_url' => config('services.storage_server.url'),
                    'api_key'     => app(\App\Services\LicenseService::class)->isDokKbmFeatureEnabled($jadwal->school ?? $user->school) ? (($jadwal->school ?? $user->school)?->storage_api_key ?: config('services.storage_server.key')) : '',
                    'folder'      => config('services.storage_server.folder'),
                    'quota_mb'    => ($jadwal->school ?? $user->school)?->storage_quota_mb ?? 1000,
                ],
                'summary'  => $stats,
                'students' => $studentList,
            ]
        ]);
    }

    /**
     * Simpan / Perbarui Presensi Batch dan Jurnal KBM dari Apps
     * Otomatis menyinkronkan Jurnal KBM ke seluruh kelas paralel jika multi-kelas
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
            'tanggal'          => 'required|date',
            'materi'           => 'required|string|max:1000',
            'catatan'          => 'nullable|string|max:1000',
            'foto_dokumentasi' => 'nullable|string|max:1000',
            'dokumentasi'      => 'nullable',
            'attendances'      => 'required|array',
            'attendances.*.student_id' => 'required|exists:siswa,id',
            'attendances.*.status'     => 'required|in:H,S,I,A,T,D',
            'attendances.*.keterangan' => 'nullable|string|max:255',
        ]);

        $tanggal = Carbon::parse($request->tanggal)->format('Y-m-d');
        $operatorGuruId = $guru?->id ?? $jadwal->guru_id;

        // Dapatkan semua jadwal paralel (jika multi-kelas)
        $parallels = $this->getParallelJadwals($jadwal, $schoolId);

        // Periksa apakah fitur Dok KBM aktif untuk sekolah ini
        $isDokKbmAllowed = app(\App\Services\LicenseService::class)->isDokKbmFeatureEnabled($jadwal->school ?? $user->school);
        $dokumentasiPayload = null;
        $hasDokumentasiInput = false;

        if ($isDokKbmAllowed) {
            // 1. Prioritaskan payload array 'dokumentasi' jika dikirim dari Android
            if ($request->has('dokumentasi') && !empty($request->input('dokumentasi'))) {
                $hasDokumentasiInput = true;
                $rawDok = $request->input('dokumentasi');
                if (is_string($rawDok)) {
                    $decoded = json_decode($rawDok, true);
                    $rawDok = is_array($decoded) ? $decoded : [];
                }

                if (is_array($rawDok) && !empty($rawDok)) {
                    $dokumentasiPayload = [];
                    foreach ($rawDok as $item) {
                        if (is_string($item)) {
                            $itemUrl = trim($item);
                            if (empty($itemUrl)) continue;
                            $ext = strtolower(pathinfo(parse_url($itemUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                            $isVid = in_array($ext, ['mp4', 'mov', 'webm', '3gp', 'mkv', 'avi'])
                                || str_contains(strtolower($itemUrl), 'video')
                                || str_contains(strtolower($itemUrl), 'vid');
                            $thumb = preg_replace('#/api/v1/files/([^/]+)/view#', '/api/v1/files/$1/thumbnail', $itemUrl);

                            $dokumentasiPayload[] = [
                                'url'           => $itemUrl,
                                'view_url'      => $itemUrl,
                                'download_url'  => $itemUrl,
                                'thumbnail_url' => ($thumb !== $itemUrl) ? $thumb : null,
                                'type'          => $isVid ? 'video' : 'image',
                                'is_video'      => $isVid,
                                'is_image'      => !$isVid,
                                'name'          => $isVid ? 'Video Pembelajaran' : 'Foto Kegiatan',
                            ];
                        } elseif (is_array($item)) {
                            $itemUrl = $item['url'] ?? ($item['view_url'] ?? ($item['download_url'] ?? ''));
                            if (empty($itemUrl)) continue;
                            $type = strtolower($item['type'] ?? '');
                            $mime = strtolower($item['mime_type'] ?? '');
                            $ext = strtolower(pathinfo(parse_url($itemUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                            $rawName = $item['name'] ?? ($item['original_name'] ?? basename(parse_url($itemUrl, PHP_URL_PATH) ?? ''));

                            $isVid = ($type === 'video')
                                || str_starts_with($mime, 'video/')
                                || in_array($ext, ['mp4', 'mov', 'webm', '3gp', 'mkv', 'avi'])
                                || (!empty($item['is_video']))
                                || str_contains(strtolower($itemUrl), 'video')
                                || str_contains(strtolower($itemUrl), 'vid')
                                || str_contains(strtolower($rawName), 'video')
                                || str_contains(strtolower($rawName), 'vid');

                            $thumb = $item['thumbnail_url'] ?? ($item['thumb_url'] ?? null);
                            if (empty($thumb) && str_contains($itemUrl, '/api/v1/files/')) {
                                $thumb = preg_replace('#/api/v1/files/([^/]+)/view#', '/api/v1/files/$1/thumbnail', $itemUrl);
                            }

                            if (empty($rawName) || in_array(strtolower($rawName), ['view', 'download', 'file', 'thumbnail'])) {
                                $rawName = $isVid ? 'Video Pembelajaran' : 'Foto Kegiatan';
                            }

                            $dokumentasiPayload[] = [
                                'id'            => $item['id'] ?? null,
                                'url'           => $itemUrl,
                                'view_url'      => $itemUrl,
                                'download_url'  => $item['download_url'] ?? $itemUrl,
                                'thumbnail_url' => $thumb,
                                'type'          => $isVid ? 'video' : 'image',
                                'is_video'      => $isVid,
                                'is_image'      => !$isVid,
                                'name'          => $rawName,
                                'size'          => $item['size'] ?? null,
                                'mime_type'     => $item['mime_type'] ?? null,
                            ];
                        }
                    }
                }
            }

            // 2. Fallback untuk input tunggal foto_dokumentasi jika array dokumentasi tidak ada / kosong
            if (empty($dokumentasiPayload) && $request->filled('foto_dokumentasi')) {
                $hasDokumentasiInput = true;
                $rawUrl = trim($request->input('foto_dokumentasi'));
                $ext = strtolower(pathinfo(parse_url($rawUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                $typeInput = strtolower($request->input('foto_dokumentasi_type', $request->input('type', '')));
                $isVid = ($typeInput === 'video')
                    || in_array($ext, ['mp4', 'mov', 'webm', '3gp', 'mkv', 'avi'])
                    || str_contains(strtolower($rawUrl), 'video')
                    || str_contains(strtolower($rawUrl), 'vid');

                $thumbUrl = $request->input('thumbnail_url');
                if (empty($thumbUrl) && str_contains($rawUrl, '/api/v1/files/')) {
                    $thumbUrl = preg_replace('#/api/v1/files/([^/]+)/view#', '/api/v1/files/$1/thumbnail', $rawUrl);
                }

                $rawName = basename(parse_url($rawUrl, PHP_URL_PATH) ?? '');
                if (empty($rawName) || in_array(strtolower($rawName), ['view', 'download', 'file', 'thumbnail'])) {
                    $rawName = $isVid ? 'Video Pembelajaran' : 'Foto Kegiatan';
                }

                $dokumentasiPayload = [
                    [
                        'url'           => $rawUrl,
                        'view_url'      => $rawUrl,
                        'download_url'  => $rawUrl,
                        'thumbnail_url' => $thumbUrl,
                        'type'          => $isVid ? 'video' : 'image',
                        'is_video'      => $isVid,
                        'is_image'      => !$isVid,
                        'name'          => $rawName,
                    ]
                ];
            }

            // Jika ada file multipart langsung di request ini, upload ke storage server
            if ($request->hasFile('dokumentasi_files') || $request->hasFile('files') || $request->hasFile('file')) {
                $hasDokumentasiInput = true;
                $dokumentasiPayload = is_array($dokumentasiPayload) ? $dokumentasiPayload : [];
                $uploadedFiles = [];
                if ($request->hasFile('dokumentasi_files')) {
                    $val = $request->file('dokumentasi_files');
                    $uploadedFiles = is_array($val) ? $val : [$val];
                } elseif ($request->hasFile('files')) {
                    $val = $request->file('files');
                    $uploadedFiles = is_array($val) ? $val : [$val];
                } elseif ($request->hasFile('file')) {
                    $uploadedFiles = [$request->file('file')];
                }

                $schoolForUpload = $jadwal->school ?? $user->school;
                $storageUrl = rtrim(config('services.storage_server.url'), '/');
                $storageKey = $schoolForUpload?->storage_api_key ?: config('services.storage_server.key');
                $storageFolder = config('services.storage_server.folder') ?? 'kbm_docs';

                foreach ($uploadedFiles as $uploadedFile) {
                    try {
                        $res = Http::timeout(60)
                            ->withHeaders(['X-API-Key' => $storageKey])
                            ->attach('file', file_get_contents($uploadedFile->getRealPath()), $uploadedFile->getClientOriginalName())
                            ->post($storageUrl . '/api/v1/files/upload', [
                                'folder' => $storageFolder,
                                'name'   => $uploadedFile->getClientOriginalName(),
                            ]);

                        if ($res->successful()) {
                            $resData = $res->json('data') ?? [];
                            $isVid = str_starts_with($uploadedFile->getMimeType() ?? '', 'video/');
                            $dokumentasiPayload[] = [
                                'id'            => $resData['id'] ?? null,
                                'url'           => $resData['view_url'] ?? '',
                                'view_url'      => $resData['view_url'] ?? '',
                                'download_url'  => $resData['download_url'] ?? '',
                                'thumbnail_url' => $resData['thumbnail_url'] ?? null,
                                'type'          => $isVid ? 'video' : 'image',
                                'name'          => $uploadedFile->getClientOriginalName(),
                                'size'          => $uploadedFile->getSize(),
                                'mime_type'     => $uploadedFile->getMimeType(),
                            ];
                        }
                    } catch (\Exception $e) {
                        Log::error('[DokKbm] Direct file upload failed: ' . $e->getMessage());
                    }
                }
            }
        }

        DB::transaction(function () use ($request, $jadwal, $parallels, $schoolId, $tanggal, $operatorGuruId, $hasDokumentasiInput, $dokumentasiPayload) {
            // 1. Sinkronkan Jurnal KBM ke semua jadwal paralel
            $jurnalMap = [];
            foreach ($parallels as $pJadwal) {
                $jurnalData = [
                    'school_id'    => $schoolId,
                    'guru_id'      => $operatorGuruId,
                    'jam_ke'       => $pJadwal->jam_ke,
                    'materi'       => trim($request->materi),
                    'catatan'      => $request->catatan ? trim($request->catatan) : null,
                    'jam_mulai'    => $pJadwal->jam_mulai,
                    'jam_selesai'  => $pJadwal->jam_selesai,
                ];

                if ($hasDokumentasiInput) {
                    $jurnalData['dokumentasi'] = $dokumentasiPayload;
                }

                $jurnal = JurnalPelajaran::updateOrCreate(
                    [
                        'jadwal_pelajaran_id' => $pJadwal->id,
                        'tanggal'             => $tanggal,
                    ],
                    $jurnalData
                );
                $jurnalMap[$pJadwal->id] = $jurnal->id;
            }

            // 2. Presensi Siswa: petakan masing-masing siswa ke jadwal kelas asalnya
            $kelasToJadwalMap = $parallels->pluck('id', 'kelas_id')->all();
            $studentIds = collect($request->input('attendances', []))->pluck('student_id')->filter()->all();
            $studentKelasMap = Siswa::whereIn('id', $studentIds)->pluck('kelas_id', 'id')->all();

            foreach ($request->input('attendances', []) as $item) {
                $sId = $item['student_id'];
                $sKelasId = $studentKelasMap[$sId] ?? $jadwal->kelas_id;
                $targetJadwalId = $kelasToJadwalMap[$sKelasId] ?? $jadwal->id;
                $targetJurnalId = $jurnalMap[$targetJadwalId] ?? ($jurnalMap[$jadwal->id] ?? null);

                AbsensiPelajaran::updateOrCreate(
                    [
                        'jadwal_pelajaran_id' => $targetJadwalId,
                        'student_id'          => $sId,
                        'tanggal'             => $tanggal,
                    ],
                    [
                        'school_id'           => $schoolId,
                        'jurnal_pelajaran_id' => $targetJurnalId,
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

        // Cari jadwal target yang sesuai dengan kelas siswa jika sesi multi-kelas
        $student = Siswa::find($request->student_id);
        $targetJadwalId = $jadwal->id;

        if ($student && $student->kelas_id !== $jadwal->kelas_id) {
            $parallels = $this->getParallelJadwals($jadwal, $schoolId);
            $matched = $parallels->firstWhere('kelas_id', $student->kelas_id);
            if ($matched) {
                $targetJadwalId = $matched->id;
            }
        }

        $jurnal = JurnalPelajaran::where('jadwal_pelajaran_id', $targetJadwalId)
            ->where('tanggal', $tanggal)
            ->first();

        if (!$jurnal) {
            $jurnal = JurnalPelajaran::where('jadwal_pelajaran_id', $jadwal->id)
                ->where('tanggal', $tanggal)
                ->first();
        }

        $absensi = AbsensiPelajaran::updateOrCreate(
            [
                'jadwal_pelajaran_id' => $targetJadwalId,
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

    /**
     * Dapatkan jadwal pelajaran siswa untuk hari ini (atau tanggal tertentu)
     * beserta status presensi siswa di setiap mata pelajaran.
     */
    public function getStudentHariIni(Request $request)
    {
        $user = $request->user();
        $student = $this->resolveStudent($request, $user);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Data profil siswa tidak ditemukan untuk akun ini.'
            ], 404);
        }

        if (!$student->kelas_id) {
            return response()->json([
                'success' => false,
                'message' => 'Siswa belum terdaftar pada kelas manapun.'
            ], 400);
        }

        $schoolId = $student->school_id ?: ($user->school_id ?? 0);
        $schoolTz = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'timezone')->value('setting_value') ?: config('app.timezone', 'Asia/Jakarta');

        $rawTanggal = $request->query('tanggal', Carbon::now($schoolTz)->format('Y-m-d'));
        try {
            $parsedDate = Carbon::parse($rawTanggal);
            $tanggal = $parsedDate->format('Y-m-d');
        } catch (\Exception $e) {
            $parsedDate = Carbon::now($schoolTz);
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

        // Ambil jadwal pelajaran kelas siswa di hari tersebut
        $jadwals = JadwalPelajaran::where('school_id', $schoolId)
            ->where('kelas_id', $student->kelas_id)
            ->where('hari', $namaHari)
            ->with(['guru', 'mapel'])
            ->orderBy('jam_mulai')
            ->get();

        $jadwalIds = $jadwals->pluck('id');

        // Ambil Jurnal KBM yang sudah diisi oleh guru
        $jurnals = JurnalPelajaran::where('school_id', $schoolId)
            ->whereIn('jadwal_pelajaran_id', $jadwalIds)
            ->where('tanggal', $tanggal)
            ->get()
            ->keyBy('jadwal_pelajaran_id');

        // Ambil catatan presensi siswa tersebut di setiap jadwal
        $absensis = AbsensiPelajaran::where('school_id', $schoolId)
            ->whereIn('jadwal_pelajaran_id', $jadwalIds)
            ->where('student_id', $student->id)
            ->where('tanggal', $tanggal)
            ->with('guru')
            ->get()
            ->keyBy('jadwal_pelajaran_id');

        $statCounts = [
            'total'       => $jadwals->count(),
            'sudah_absen' => 0,
            'h'           => 0,
            's'           => 0,
            'i'           => 0,
            'a'           => 0,
            't'           => 0,
            'd'           => 0,
            'belum_absen' => 0,
        ];

        $list = $jadwals->map(function ($j) use ($jurnals, $absensis, &$statCounts, $student) {
            $jurnal = $jurnals->get($j->id);
            $absen = $absensis->get($j->id);

            if ($absen) {
                $statCounts['sudah_absen']++;
                $st = strtolower($absen->status);
                if (isset($statCounts[$st])) {
                    $statCounts[$st]++;
                }
            } else {
                $statCounts['belum_absen']++;
            }

            $breakInfo = $j->jeda_istirahat_info;

            return [
                'id'                     => $j->id,
                'jadwal_pelajaran_id'    => $j->id,
                'hari'                   => $j->hari,
                'jam_ke'                 => $j->jam_ke ?? '-',
                'jam_mulai'              => substr($j->jam_mulai, 0, 5),
                'jam_selesai'            => substr($j->jam_selesai, 0, 5),
                'jam_format'             => $j->jam_format,
                'has_istirahat'          => $breakInfo['has_istirahat'],
                'istirahat_label'        => $breakInfo['istirahat_label'],
                'istirahat_text'         => $breakInfo['istirahat_text'],
                'durasi_istirahat_menit' => $breakInfo['durasi_istirahat_menit'],
                'jam_detail_format'      => $breakInfo['segments_format'],
                'jeda_istirahat'         => $breakInfo['list'],
                'ruangan'                => $j->ruangan ?? '-',
                'kelas_id'            => $student->kelas_id,
                'kelas_nama'          => $student->kelas ? ($student->kelas->nama_kelas ?? $student->kelas->nama) : ($j->kelas?->nama_kelas ?? '-'),
                'mapel_id'            => $j->mapel_id,
                'mapel_nama'          => $j->mapel?->nama_mapel ?? '-',
                'nama_mapel'          => $j->mapel?->nama_mapel ?? '-',
                'mapel'               => [
                    'id'   => $j->mapel_id,
                    'nama' => $j->mapel?->nama_mapel ?? '-',
                ],
                'guru_id'             => $j->guru_id,
                'guru_nama'           => $j->guru?->nama ?? '-',
                'nama_guru'           => $j->guru?->nama ?? '-',
                'guru'                => [
                    'id'       => $j->guru_id,
                    'nama'     => $j->guru?->nama ?? '-',
                    'nip'      => $j->guru?->nip ?? '-',
                    'foto_url' => $j->guru?->foto_url ?? null,
                ],
                'is_attended'         => !empty($absen),
                'status'              => $absen?->status ?? null,
                'status_label'        => $absen ? $absen->status_label : 'Belum Presensi',
                'status_kehadiran'    => $absen ? $absen->status_label : 'Belum Presensi',
                'keterangan'          => $absen?->keterangan ?? '',
                'presensi'            => [
                    'is_attended'    => !empty($absen),
                    'status'         => $absen?->status ?? null,
                    'status_label'   => $absen ? $absen->status_label : 'Belum Presensi',
                    'keterangan'     => $absen?->keterangan ?? '',
                    'guru_pengabsen' => $absen?->guru?->nama ?? null,
                    'waktu_absen'    => $absen?->created_at ? $absen->created_at->format('Y-m-d H:i:s') : null,
                ],
                'jurnal'              => $jurnal ? [
                    'id'      => $jurnal->id,
                    'materi'  => $jurnal->materi,
                    'catatan' => $jurnal->catatan,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'tanggal'   => $tanggal,
                'hari'      => $namaHari,
                'student'   => [
                    'id'         => $student->id,
                    'nama'       => $student->nama,
                    'nis'        => $student->nis,
                    'foto_url'   => $student->foto_url,
                    'kelas_id'   => $student->kelas_id,
                    'kelas_nama' => $student->kelas ? ($student->kelas->nama_kelas ?? $student->kelas->nama) : null,
                ],
                'summary'   => $statCounts,
                'pelajaran' => $list,
                'jadwals'   => $list,
            ]
        ]);
    }

    /**
     * Dapatkan seluruh jadwal pelajaran mingguan kelas siswa
     */
    public function getStudentMingguan(Request $request)
    {
        $user = $request->user();
        $student = $this->resolveStudent($request, $user);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Data profil siswa tidak ditemukan untuk akun ini.'
            ], 404);
        }

        if (!$student->kelas_id) {
            return response()->json([
                'success' => false,
                'message' => 'Siswa belum terdaftar pada kelas manapun.'
            ], 400);
        }

        $schoolId = $student->school_id ?: ($user->school_id ?? 0);

        $jadwals = JadwalPelajaran::where('school_id', $schoolId)
            ->where('kelas_id', $student->kelas_id)
            ->with(['guru', 'mapel'])
            ->orderByRaw("FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')")
            ->orderBy('jam_mulai')
            ->get();

        $grouped = $jadwals->groupBy('hari')->map(function ($items, $day) use ($student) {
            return $items->map(function ($j) use ($student) {
                $breakInfo = $j->jeda_istirahat_info;

                return [
                    'id'                     => $j->id,
                    'hari'                   => $j->hari,
                    'jam_ke'                 => $j->jam_ke ?? '-',
                    'jam_mulai'              => substr($j->jam_mulai, 0, 5),
                    'jam_selesai'            => substr($j->jam_selesai, 0, 5),
                    'jam_format'             => $j->jam_format,
                    'has_istirahat'          => $breakInfo['has_istirahat'],
                    'istirahat_label'        => $breakInfo['istirahat_label'],
                    'istirahat_text'         => $breakInfo['istirahat_text'],
                    'durasi_istirahat_menit' => $breakInfo['durasi_istirahat_menit'],
                    'jam_detail_format'      => $breakInfo['segments_format'],
                    'jeda_istirahat'         => $breakInfo['list'],
                    'ruangan'                => $j->ruangan ?? '-',
                    'kelas_id'    => $student->kelas_id,
                    'kelas_nama'  => $student->kelas ? ($student->kelas->nama_kelas ?? $student->kelas->nama) : ($j->kelas?->nama_kelas ?? '-'),
                    'mapel_id'    => $j->mapel_id,
                    'mapel_nama'  => $j->mapel?->nama_mapel ?? '-',
                    'nama_mapel'  => $j->mapel?->nama_mapel ?? '-',
                    'mapel'       => [
                        'id'   => $j->mapel_id,
                        'nama' => $j->mapel?->nama_mapel ?? '-',
                    ],
                    'guru_id'     => $j->guru_id,
                    'guru_nama'   => $j->guru?->nama ?? '-',
                    'nama_guru'   => $j->guru?->nama ?? '-',
                    'guru'        => [
                        'id'       => $j->guru_id,
                        'nama'     => $j->guru?->nama ?? '-',
                        'nip'      => $j->guru?->nip ?? '-',
                        'foto_url' => $j->guru?->foto_url ?? null,
                    ],
                ];
            });
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'student' => [
                    'id'         => $student->id,
                    'nama'       => $student->nama,
                    'nis'        => $student->nis,
                    'kelas_id'   => $student->kelas_id,
                    'kelas_nama' => $student->kelas ? ($student->kelas->nama_kelas ?? $student->kelas->nama) : null,
                ],
                'jadwals' => $grouped,
            ]
        ]);
    }

    /**
     * Helper resolve Siswa instance
     */
    protected function resolveStudent(Request $request, $user)
    {
        if ($request->filled('student_id')) {
            return Siswa::with(['kelas', 'school'])->find($request->student_id);
        }

        if ($user && $user->student) {
            return $user->student->loadMissing(['kelas', 'school']);
        }

        if ($user) {
            return Siswa::with(['kelas', 'school'])
                ->where('user_id', $user->id)
                ->orWhere('nis', str_replace('siswa_', '', $user->username))
                ->first();
        }

        return null;
    }

    /**
     * Upload dokumentasi foto/video KBM langsung via backend ke storage server
     * POST /api/mobile/jadwal-pelajaran/upload-dokumentasi
     */
    public function uploadDokumentasi(Request $request)
    {
        $user = $request->user();
        $schoolId = $user->school_id ?? ($user->guru ? $user->guru->school_id : null);
        $school = $user->school ?? \App\Models\School::find($schoolId);

        $licenseService = app(\App\Services\LicenseService::class);
        if (!$licenseService->isDokKbmFeatureEnabled($school)) {
            return response()->json([
                'success' => false,
                'message' => 'Fitur Dokumentasi KBM dinonaktifkan untuk sekolah Anda. Hubungi Super Administrator.'
            ], 403);
        }

        $request->validate([
            'file' => 'required|file|max:102400|mimes:jpg,jpeg,png,webp,mp4,mov,avi,mkv,3gp,webm',
        ]);

        $file = $request->file('file');
        $schoolForUpload = $jadwal->school ?? $user->school;
        $storageUrl = rtrim(config('services.storage_server.url'), '/');
        $storageKey = $schoolForUpload?->storage_api_key ?: config('services.storage_server.key');
        $storageFolder = config('services.storage_server.folder') ?? 'kbm_docs';

        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'X-API-Key' => $storageKey,
                ])
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post($storageUrl . '/api/v1/files/upload', [
                    'folder' => $storageFolder,
                    'name'   => $file->getClientOriginalName(),
                ]);

            if ($response->status() === 413) {
                Log::warning('[DokKbm] Storage quota exceeded for school ' . ($schoolForUpload?->id ?? 'unknown'));
                return response()->json([
                    'success' => false,
                    'message' => $response->json('message') ?? 'Kapasitas penyimpanan sekolah telah penuh. Hubungi administrator untuk menambah kuota.',
                    'error'   => $response->json('error') ?? null,
                ], 413);
            }

            if (!$response->successful()) {
                Log::error('[DokKbm] Storage server response error: ' . $response->body());
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengupload berkas ke server penyimpanan (' . $response->status() . ').'
                ], 502);
            }

            $storageData = $response->json('data') ?? [];
            $mime = $file->getMimeType() ?? '';
            $isVideo = str_starts_with($mime, 'video/');

            $docItem = [
                'id'            => $storageData['id'] ?? null,
                'url'           => $storageData['view_url'] ?? '',
                'view_url'      => $storageData['view_url'] ?? '',
                'download_url'  => $storageData['download_url'] ?? '',
                'thumbnail_url' => $storageData['thumbnail_url'] ?? null,
                'type'          => $isVideo ? 'video' : 'image',
                'name'          => $file->getClientOriginalName(),
                'size'          => $file->getSize(),
                'mime_type'     => $mime,
            ];

            // Jika jadwal_pelajaran_id & tanggal disertakan, langsung lampirkan ke jurnal KBM
            if ($request->filled('jadwal_pelajaran_id') && $request->filled('tanggal')) {
                $jDate = Carbon::parse($request->tanggal)->format('Y-m-d');
                $jadwal = JadwalPelajaran::where('school_id', $schoolId)->find($request->jadwal_pelajaran_id);
                if ($jadwal) {
                    $parallels = $this->getParallelJadwals($jadwal, $schoolId);
                    foreach ($parallels as $pJadwal) {
                        $jurnal = JurnalPelajaran::firstOrCreate(
                            [
                                'jadwal_pelajaran_id' => $pJadwal->id,
                                'tanggal'             => $jDate,
                            ],
                            [
                                'school_id'   => $schoolId,
                                'guru_id'     => $user->guru?->id ?? $jadwal->guru_id,
                                'jam_ke'      => $pJadwal->jam_ke,
                                'materi'      => 'KBM ' . ($pJadwal->mapel?->nama_mapel ?? ''),
                                'jam_mulai'   => $pJadwal->jam_mulai,
                                'jam_selesai' => $pJadwal->jam_selesai,
                            ]
                        );

                        $currDok = $jurnal->dokumentasi ?? [];
                        if (is_string($currDok)) {
                            $currDok = json_decode($currDok, true) ?? [];
                        }
                        $currDok[] = $docItem;
                        $jurnal->dokumentasi = $currDok;
                        $jurnal->save();
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Dokumentasi berhasil diupload.',
                'data'    => $docItem,
            ]);
        } catch (\Exception $e) {
            Log::error('[DokKbm] Upload exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat upload berkas: ' . $e->getMessage()
            ], 500);
        }
    }
}

