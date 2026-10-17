<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Siswa;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Attendance;
use App\Models\Announcement;
use App\Models\JadwalPelajaran;
use App\Models\JurnalPelajaran;
use App\Models\AbsensiPelajaran;
use App\Models\AbsensiGuru;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Jika user adalah Guru / Teacher / Wali Kelas atau meminta view=guru, arahkan ke Dashboard Guru
        if (in_array($user->role, ['teacher', 'guru', 'wali_kelas']) || $user->isWaliKelas() || ($user->guru && !$user->isAdmin()) || $request->query('view') === 'guru') {
            return $this->guruDashboard($request);
        }

        $today = Carbon::today();
        $isSuperAdmin = $user->isSuperAdmin();
        $schoolId = $isSuperAdmin ? null : $user->school_id;
        $isWaliKelas = $user->isWaliKelas();
        $managedKelasIds = [];

        if ($isWaliKelas) {
            $managedKelasIds = $user->getManagedKelasIds();
            if (empty($managedKelasIds)) {
                $managedKelasIds = [-1]; // No classes
            }
        }

        if ($user->role === 'student') {
            $siswa = $user->student;

            if (!$siswa) {
                $cleanNis = str_replace('siswa_', '', $user->username);
                $siswa = Siswa::where('user_id', $user->id)
                    ->orWhere('nis', $cleanNis)
                    ->first();
                if ($siswa && \Illuminate\Support\Facades\Schema::hasColumn('siswa', 'user_id')) {
                    $siswa->user_id = $user->id;
                    $siswa->save();
                }
            }

            if (!$siswa) {
                return view('dashboard-student', [
                    'linked' => false,
                    'user' => $user
                ]);
            }

            $siswa->load(['kelas', 'school', 'fingerprints']);
            $today = Carbon::today();

            // Status Kehadiran Hari Ini
            $todayAttendance = Attendance::where('student_id', $siswa->id)
                ->whereDate('tanggal', $today)
                ->first();

            // Jadwal Hari Ini
            $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $todayName = $dayNames[$today->dayOfWeek];
            $jadwalHariIni = \App\Models\Jadwal::where('school_id', $siswa->school_id)
                ->where(function ($q) use ($todayName, $today) {
                    $q->where('hari', $todayName)
                      ->orWhere('index_hari', $today->dayOfWeek);
                })
                ->first();

            // Student Stats All Time
            $statsAll = [
                'H' => Attendance::where('student_id', $siswa->id)->where('status', 'H')->count(),
                'I' => Attendance::where('student_id', $siswa->id)->where('status', 'I')->count(),
                'S' => Attendance::where('student_id', $siswa->id)->where('status', 'S')->count(),
                'A' => Attendance::where('student_id', $siswa->id)->where('status', 'A')->count(),
                'T' => Attendance::where('student_id', $siswa->id)->where('status', 'H')->where(function($q) {
                    $q->where('keterangan', 'like', 'Telat%')->orWhere('keterangan', 'like', 'Terlambat%');
                })->count(),
            ];

            // Student Stats Bulan Ini
            $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
            $endOfMonth = Carbon::now()->endOfMonth()->toDateString();
            $statsMonth = [
                'H' => Attendance::where('student_id', $siswa->id)->whereBetween('tanggal', [$startOfMonth, $endOfMonth])->where('status', 'H')->count(),
                'I' => Attendance::where('student_id', $siswa->id)->whereBetween('tanggal', [$startOfMonth, $endOfMonth])->where('status', 'I')->count(),
                'S' => Attendance::where('student_id', $siswa->id)->whereBetween('tanggal', [$startOfMonth, $endOfMonth])->where('status', 'S')->count(),
                'A' => Attendance::where('student_id', $siswa->id)->whereBetween('tanggal', [$startOfMonth, $endOfMonth])->where('status', 'A')->count(),
                'T' => Attendance::where('student_id', $siswa->id)->whereBetween('tanggal', [$startOfMonth, $endOfMonth])->where('status', 'H')->where(function($q) {
                    $q->where('keterangan', 'like', 'Telat%')->orWhere('keterangan', 'like', 'Terlambat%');
                })->count(),
            ];

            // Kehadiran Rate (%)
            $totalRecorded = $statsAll['H'] + $statsAll['I'] + $statsAll['S'] + $statsAll['A'];
            $rateHadir = $totalRecorded > 0 ? round(($statsAll['H'] / $totalRecorded) * 100, 1) : 100;

            // Recent Logs (30 records terbaru)
            $recentLogs = Attendance::where('student_id', $siswa->id)
                ->orderBy('tanggal', 'desc')
                ->take(30)
                ->get();

            // Riwayat Pengajuan Izin
            $leaves = \App\Models\StudentLeave::where('student_id', $siswa->id)
                ->latest()
                ->take(10)
                ->get();

            // Pengumuman
            $announcements = Announcement::where('is_active', true)->latest()->take(5)->get();

            return view('dashboard-student', compact(
                'siswa',
                'todayAttendance',
                'jadwalHariIni',
                'statsAll',
                'statsMonth',
                'rateHadir',
                'recentLogs',
                'leaves',
                'announcements'
            ) + ['linked' => true]);
        }

        // Admin / Teacher / Wali Kelas / Waka Kurikulum View
        // 1. Counts SCOPED — hanya kelas yang aktif absensi
        $countSiswa = Siswa::when(!$isSuperAdmin, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereIn('kelas_id', $managedKelasIds))
            ->whereHas('kelas', fn($q) => $q->where('is_active_attendance', true))
            ->count();
        $countGuru = Guru::when(!$isSuperAdmin, fn($q) => $q->where('school_id', $schoolId))->count();
        $countKelas = Kelas::when(!$isSuperAdmin, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereIn('id', $managedKelasIds))
            ->count();

        // 2. Attendance Today SCOPED [Via Student] — hanya kelas yang aktif absensi
        $countHadir = Attendance::whereDate('tanggal', $today)
            ->where('status', 'H')
            ->when(!$isSuperAdmin, function ($q) use ($schoolId, $isWaliKelas, $managedKelasIds) {
                $q->whereHas('student', function ($sub) use ($schoolId, $isWaliKelas, $managedKelasIds) {
                    $sub->where('school_id', $schoolId)
                        ->whereHas('kelas', fn($k) => $k->where('is_active_attendance', true));
                    if ($isWaliKelas) {
                        $sub->whereIn('kelas_id', $managedKelasIds);
                    }
                });
            })
            ->count();

        $countTidakHadir = max(0, $countSiswa - $countHadir);

        // 3. Recent Activity (Last 5) SCOPED
        $recentLogs = Attendance::with('student.kelas')
            ->whereDate('tanggal', $today)
            ->when(!$isSuperAdmin, function ($q) use ($schoolId, $isWaliKelas, $managedKelasIds) {
                $q->whereHas('student', function ($sub) use ($schoolId, $isWaliKelas, $managedKelasIds) {
                    $sub->where('school_id', $schoolId);
                    if ($isWaliKelas) {
                        $sub->whereIn('kelas_id', $managedKelasIds);
                    }
                });
            })
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        // 4. Chart Data (Last 7 Days) SCOPED
        $dates = [];
        $chartData = [
            'H' => [],
            'I' => [],
            'S' => [],
            'A' => []
        ];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->format('Y-m-d');
            $dates[] = Carbon::today()->subDays($i)->format('d M'); // Label: 26 Dec

            $dailyStats = Attendance::whereDate('tanggal', $date)
                ->when(!$isSuperAdmin, function ($q) use ($schoolId, $isWaliKelas, $managedKelasIds) {
                    $q->whereHas('student', function ($sub) use ($schoolId, $isWaliKelas, $managedKelasIds) {
                        $sub->where('school_id', $schoolId);
                        if ($isWaliKelas) {
                            $sub->whereIn('kelas_id', $managedKelasIds);
                        }
                    });
                })
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            // Map legacy T to H for chart consistency if needed, or just normal counting
            // Note: Our previous refactor counts T as H in reports, but DB might still store T or H.
            // Let's sum them up carefully.

            $hCount = ($dailyStats['H'] ?? 0) + ($dailyStats['Hadir'] ?? 0) + ($dailyStats['T'] ?? 0) + ($dailyStats['Terlambat'] ?? 0);
            $iCount = ($dailyStats['I'] ?? 0) + ($dailyStats['Izin'] ?? 0);
            $sCount = ($dailyStats['S'] ?? 0) + ($dailyStats['Sakit'] ?? 0);
            $aCount = ($dailyStats['A'] ?? 0) + ($dailyStats['Alpha'] ?? 0);

            $chartData['H'][] = $hCount;
            $chartData['I'][] = $iCount;
            $chartData['S'][] = $sCount;
            $chartData['A'][] = $aCount;
        }

        // Fetch active announcements and popup announcements
        $announcements = Announcement::where('is_active', true)->latest()->get();
        $popupAnnouncements = Announcement::where('is_active', true)
            ->where('is_popup', true)
            ->latest()
            ->take(5)
            ->get();

        // 5. Kegiatan Hari Ini
        $kegiatanHariIni = collect();
        if ($schoolId) {
            $kegiatanHariIni = \App\Models\Kegiatan::where('school_id', $schoolId)
                ->where('is_active', true)
                ->withCount(['attendances as total_hadir' => function ($q) use ($today) {
                    $q->where('tanggal', $today->format('Y-m-d'))->where('status', 'H');
                }])
                ->get()
                ->filter(function ($k) {
                    return $k->isScheduledNow() || $k->tanggal_mulai->format('Y-m-d') === now()->format('Y-m-d');
                });
        }

        // 6. Android App Download Info
        $androidApkUrl = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'android_apk_url')->value('setting_value')
            ?: \App\Models\Setting::where('school_id', 0)->where('setting_key', 'android_apk_url')->value('setting_value')
            ?: config('app.android_apk_url')
            ?: route('public.download.android');

        $hasLocalApk = file_exists(public_path('downloads/jagat-absen.apk'))
            || file_exists(storage_path('app/public/downloads/jagat-absen.apk'));

        $isFaceEnabled = app(\App\Services\LicenseService::class)->isFaceFeatureEnabled($user?->school);

        return view('dashboard', compact(
            'countSiswa',
            'countGuru',
            'countKelas',
            'countHadir',
            'countTidakHadir',
            'recentLogs',
            'dates',
            'chartData',
            'announcements',
            'popupAnnouncements',
            'kegiatanHariIni',
            'androidApkUrl',
            'hasLocalApk',
            'isFaceEnabled'
        ));
    }

    /**
     * Download Android App Jagat Absen (APK)
     */
    public function downloadAndroidApp(Request $request)
    {
        $user = auth()->user();
        $school = $user?->school;

        // Validasi: Kunci download jika fitur Face ID dinonaktifkan
        $isFaceEnabled = app(\App\Services\LicenseService::class)->isFaceFeatureEnabled($school);
        if (!$isFaceEnabled) {
            return redirect()->route('dashboard')->with('error', 'Download aplikasi Jagat Absen dikunci karena fitur Face ID sekolah sedang dinonaktifkan.');
        }

        $schoolId = $user?->school_id ?? 0;

        $customUrl = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'android_apk_url')->value('setting_value')
            ?: \App\Models\Setting::where('school_id', 0)->where('setting_key', 'android_apk_url')->value('setting_value')
            ?: config('app.android_apk_url');

        if ($customUrl && filter_var($customUrl, FILTER_VALIDATE_URL)) {
            return redirect()->away($customUrl);
        }

        $candidates = [
            public_path('downloads/jagat-absen.apk'),
            storage_path('app/public/downloads/jagat-absen.apk'),
            public_path('downloads/jagat-absen-latest.apk'),
            public_path('apk/jagat-absen.apk'),
        ];

        foreach ($candidates as $filePath) {
            if (file_exists($filePath)) {
                return response()->download($filePath, 'Jagat-Absen.apk', [
                    'Content-Type' => 'application/vnd.android.package-archive',
                    'Cache-Control' => 'no-cache, must-revalidate',
                ]);
            }
        }

        return redirect()->route('dashboard')->with('info', 'File APK Jagat Absen sedang disiapkan oleh administrator. Silakan hubungi admin sekolah atau atur URL download APK di menu Pengaturan.');
    }

    /**
     * Dashboard Khusus Guru / KBM (Input Absen & Jurnal)
     */
    public function guruDashboard(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;

        // Dapatkan data guru terkait
        $guru = $user->guru;

        // Jika admin / waka / super_admin mengakses dashboard guru untuk simulasi
        $canSwitchGuru = in_array($user->role, ['admin', 'super_admin', 'waka_kurikulum']);
        $allGurus = collect();

        if ($canSwitchGuru) {
            $allGurus = Guru::where('school_id', $schoolId)->orderBy('nama')->get();
            if ($request->filled('guru_id')) {
                $guru = Guru::where('school_id', $schoolId)->find($request->guru_id) ?? $guru;
            } elseif (!$guru && $allGurus->isNotEmpty()) {
                $guru = $allGurus->first();
            }
        }

        // Tanggal & Hari Aktif
        $tanggal = $request->query('tanggal', Carbon::today()->format('Y-m-d'));
        try {
            $parsedDate = Carbon::parse($tanggal);
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
        $filterHari = $request->query('hari', $namaHari);

        // Jadwal Mengajar Guru Hari Tersebut
        $jadwals = collect();
        if ($guru) {
            $jadwals = JadwalPelajaran::where('school_id', $schoolId)
                ->where('guru_id', $guru->id)
                ->where('hari', $filterHari)
                ->with(['kelas.siswas', 'mapel', 'jamPelajaran'])
                ->orderBy('jam_mulai', 'asc')
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

            $now = Carbon::now();
            $nowHm = $now->format('H:i');
            $isToday = ($tanggal === Carbon::today()->format('Y-m-d'));

            foreach ($jadwals as $jadwal) {
                $jadwal->jurnal_today = $jurnals->get($jadwal->id);
                $jadwal->absensi_summary = $absensiCounts->get($jadwal->id);
                $jadwal->is_attended = !empty($jadwal->absensi_summary) && $jadwal->absensi_summary->total_absen > 0;
                $jadwal->is_jurnal_filled = !empty($jadwal->jurnal_today) && !empty($jadwal->jurnal_today->materi);

                $jMulai = substr($jadwal->jam_mulai, 0, 5);
                $jSelesai = substr($jadwal->jam_selesai, 0, 5);

                $jadwal->is_live = ($isToday && $nowHm >= $jMulai && $nowHm <= $jSelesai);
                $jadwal->is_passed = ($isToday && $nowHm > $jSelesai);
                $jadwal->is_upcoming = ($isToday && $nowHm < $jMulai);
            }

            // Satukan jadwal kelas gabungan (jam yang sama, mapel sama, hari sama) menjadi 1 kartu
            $groupedJadwals = collect();
            $processedIds = [];

            foreach ($jadwals as $jadwal) {
                if (in_array($jadwal->id, $processedIds)) {
                    continue;
                }

                $parallels = $jadwals->filter(function ($item) use ($jadwal) {
                    return $item->mapel_id === $jadwal->mapel_id
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

                    // Total siswa dari seluruh kelas gabungan
                    $totalSiswa = 0;
                    foreach ($parallels as $p) {
                        $totalSiswa += ($p->kelas?->siswas ? $p->kelas->siswas->count() : 0);
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
                    $primary->is_jurnal_filled = !empty($primary->jurnal_today) && !empty($primary->jurnal_today->materi);

                    $groupedJadwals->push($primary);
                } else {
                    $jadwal->is_multi_kelas = false;
                    $jadwal->parallel_count = 1;
                    $jadwal->parallel_kelases = [$jadwal->kelas?->nama_kelas];
                    $jadwal->total_siswa_gabungan = $jadwal->kelas?->siswas ? $jadwal->kelas->siswas->count() : 0;
                    $groupedJadwals->push($jadwal);
                }
            }

            $jadwals = $groupedJadwals;
        }

        // Metrik KBM
        $totalSesi = $jadwals->count();
        $sudahDiabsen = $jadwals->where('is_attended', true)->count();
        $belumDiabsen = $totalSesi - $sudahDiabsen;
        $jurnalTerisi = $jadwals->where('is_jurnal_filled', true)->count();

        // Absensi Mandiri Guru (Check-in gerbang/sekolah)
        $todayGuruAttendance = $guru
            ? AbsensiGuru::where('guru_id', $guru->id)->whereDate('tanggal', Carbon::today())->first()
            : null;
        $shiftGuru = $guru ? $guru->getShiftForDate() : null;

        // Riwayat Jurnal Terakhir
        $recentJurnals = $guru
            ? JurnalPelajaran::where('school_id', $schoolId)
                ->where('guru_id', $guru->id)
                ->with(['jadwal.kelas', 'jadwal.mapel'])
                ->orderBy('tanggal', 'desc')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
            : collect();

        // Jadwal Mingguan
        $jadwalMingguan = $guru
            ? JadwalPelajaran::where('school_id', $schoolId)
                ->where('guru_id', $guru->id)
                ->with(['kelas', 'mapel', 'jamPelajaran'])
                ->orderBy('jam_mulai')
                ->get()
                ->groupBy('hari')
            : collect();

        // Grouping kelas gabungan pada jadwal mingguan
        if ($guru && $jadwalMingguan->isNotEmpty()) {
            $groupedMingguan = collect();
            foreach ($jadwalMingguan as $hariName => $dayJadwals) {
                $groupedDay = collect();
                $pIds = [];
                foreach ($dayJadwals as $dj) {
                    if (in_array($dj->id, $pIds)) {
                        continue;
                    }
                    $parallels = $dayJadwals->filter(function ($item) use ($dj) {
                        return $item->mapel_id === $dj->mapel_id
                            && substr($item->jam_mulai, 0, 5) === substr($dj->jam_mulai, 0, 5)
                            && substr($item->jam_selesai, 0, 5) === substr($dj->jam_selesai, 0, 5);
                    });
                    foreach ($parallels as $p) {
                        $pIds[] = $p->id;
                    }
                    if ($parallels->count() > 1) {
                        $primary = clone $dj;
                        $primary->is_multi_kelas = true;
                        $primary->parallel_count = $parallels->count();
                        $primary->parallel_kelases = $parallels->map(fn($p) => $p->kelas?->nama_kelas)->filter()->unique()->values()->all();
                        $groupedDay->push($primary);
                    } else {
                        $groupedDay->push($dj);
                    }
                }
                $groupedMingguan[$hariName] = $groupedDay;
            }
            $jadwalMingguan = $groupedMingguan;
        }

        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        $announcements = Announcement::where('is_active', true)->latest()->take(3)->get();

        // Hitung jumlah sesi per hari dalam seminggu
        $sesiPerHari = [];
        foreach ($hariList as $h) {
            $sesiPerHari[$h] = isset($jadwalMingguan[$h]) ? $jadwalMingguan[$h]->count() : 0;
        }
        $totalSesiMingguan = $jadwalMingguan->flatten()->count();
        $nextDayWithJadwal = collect($hariList)->first(fn($h) => ($sesiPerHari[$h] ?? 0) > 0);

        return view('dashboard-guru', compact(
            'guru',
            'jadwals',
            'tanggal',
            'namaHari',
            'filterHari',
            'totalSesi',
            'sudahDiabsen',
            'belumDiabsen',
            'jurnalTerisi',
            'todayGuruAttendance',
            'shiftGuru',
            'recentJurnals',
            'jadwalMingguan',
            'hariList',
            'sesiPerHari',
            'totalSesiMingguan',
            'nextDayWithJadwal',
            'announcements',
            'canSwitchGuru',
            'allGurus'
        ));
    }

    /**
     * Quick Input / Update Jurnal KBM dari Dashboard Guru
     */
    public function storeQuickJurnal(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;

        $request->validate([
            'jadwal_pelajaran_id' => 'required|exists:jadwal_pelajaran,id',
            'tanggal'             => 'required|date',
            'materi'              => 'required|string|max:1000',
            'catatan'             => 'nullable|string|max:1000',
        ], [
            'materi.required' => 'Materi Pembelajaran / Jurnal KBM wajib diisi.'
        ]);

        $jadwal = JadwalPelajaran::where('school_id', $schoolId)->findOrFail($request->jadwal_pelajaran_id);

        $guruId = $user->guru?->id ?? $jadwal->guru_id;

        $jurnal = JurnalPelajaran::updateOrCreate(
            [
                'jadwal_pelajaran_id' => $jadwal->id,
                'tanggal'             => Carbon::parse($request->tanggal)->format('Y-m-d'),
            ],
            [
                'school_id'    => $schoolId,
                'guru_id'      => $guruId,
                'jam_ke'       => $jadwal->jam_ke,
                'materi'       => trim($request->materi),
                'catatan'      => $request->catatan ? trim($request->catatan) : null,
                'jam_mulai'    => $jadwal->jam_mulai,
                'jam_selesai'  => $jadwal->jam_selesai,
            ]
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Jurnal KBM berhasil disimpan.',
                'jurnal'  => $jurnal
            ]);
        }

        return back()->with('success', 'Jurnal KBM untuk ' . ($jadwal->mapel?->nama_mapel ?? 'Mata Pelajaran') . ' berhasil disimpan.');
    }
}
