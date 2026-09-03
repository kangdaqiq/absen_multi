<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Siswa;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Attendance;
use App\Models\Announcement;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $today = Carbon::today();
        $isSuperAdmin = $user->isSuperAdmin();
        $schoolId = $isSuperAdmin ? null : $user->school_id;
        $isWaliKelas = $user->role === 'wali_kelas';
        $managedKelasIds = [];

        if ($isWaliKelas) {
            $guru = $user->guru;
            if ($guru) {
                $managedKelasIds = \App\Models\Kelas::where(function($q) use ($guru) {
                    $q->where('wali_kelas_id', $guru->id)
                      ->orWhere('wali_kelas_2_id', $guru->id);
                })->pluck('id')->toArray();
            } else {
                $managedKelasIds = [-1]; // No classes
            }
        }

        if ($user->role === 'student') {
            $siswa = $user->student; // relasi hasOne

            if (!$siswa) {
                return view('dashboard-student', [
                    'linked' => false
                ]);
            }

            // Student Stats
            $stats = [
                'H' => Attendance::where('student_id', $siswa->id)->where('status', 'H')->count(),
                'I' => Attendance::where('student_id', $siswa->id)->where('status', 'I')->count(),
                'S' => Attendance::where('student_id', $siswa->id)->where('status', 'S')->count(),
                'A' => Attendance::where('student_id', $siswa->id)->where('status', 'A')->count(),
                'T' => Attendance::where('student_id', $siswa->id)->where('status', 'H')->where('keterangan', 'like', 'Telat%')->count(),
            ];

            // Recent Logs (My Logs)
            $recentLogs = Attendance::where('student_id', $siswa->id)
                ->orderBy('tanggal', 'desc')
                ->take(5)
                ->get();

            return view('dashboard-student', compact('siswa', 'stats', 'recentLogs') + ['linked' => true]);
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
            'kegiatanHariIni'
        ));
    }
}
