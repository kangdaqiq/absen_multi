<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Siswa;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Attendance;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $today = Carbon::today();

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

        // Admin / Teacher View
        // 1. Counts
        $countSiswa = Siswa::count();
        $countGuru = Guru::count();
        $countKelas = Kelas::count();
        
        // 2. Attendance Today
        $countHadir = Attendance::whereDate('tanggal', $today)->where('status', 'H')->count();
        $countTelat = Attendance::whereDate('tanggal', $today)
                        ->where('status', 'H')
                        ->where('keterangan', 'like', 'Telat%')
                        ->count();
        
        // 3. Recent Activity (Last 5)
        $recentLogs = Attendance::with('student.kelas')
                        ->whereDate('tanggal', $today)
                        ->orderBy('updated_at', 'desc')
                        ->take(5)
                        ->get();

        // 4. Chart Data (Last 7 Days)
        $dates = [];
        $chartData = [
            'H' => [], 'I' => [], 'S' => [], 'A' => []
        ];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->format('Y-m-d');
            $dates[] = Carbon::today()->subDays($i)->format('d M'); // Label: 26 Dec

            $dailyStats = Attendance::whereDate('tanggal', $date)
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

        return view('dashboard', compact(
            'countSiswa', 'countGuru', 'countKelas', 
            'countHadir', 'countTelat', 'recentLogs',
            'dates', 'chartData'
        ));
    }
}
