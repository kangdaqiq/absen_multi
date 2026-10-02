<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Siswa;
use App\Models\Attendance;
use App\Models\ApiLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LiveDashboardController extends Controller
{
    public function index()
    {
        return view('live_dashboard.index');
    }

    public function fullscreen()
    {
        return view('live_dashboard.fullscreen');
    }

    public function data()
    {
        $schoolId = auth()->user()->school_id;
        $today = Carbon::today()->format('Y-m-d');

        // 1. Counters — hanya kelas yang aktif absensi (is_active_attendance = true)
        $totalSiswa = Siswa::where('school_id', $schoolId)
            ->whereHas('kelas', function($q) {
                $q->where('is_active_attendance', true);
            })
            ->count();

        $attendanceToday = Attendance::where('tanggal', $today)
            ->whereHas('student', function($q) use ($schoolId) {
                $q->where('school_id', $schoolId)
                  ->whereHas('kelas', function($sub) {
                      $sub->where('is_active_attendance', true);
                  });
            })
            ->get();

        $hadirCount     = $attendanceToday->where('status', 'H')->count();
        $alphaCount     = $attendanceToday->where('status', 'A')->count();
        $izinCount      = $attendanceToday->where('status', 'I')->count();
        $sakitCount     = $attendanceToday->where('status', 'S')->count();
        $bolosCount     = $attendanceToday->where('status', 'B')->count();
        $terlambatCount = $attendanceToday->where('status', 'T')->count();
        $tidakHadirCount = $alphaCount + $izinCount + $sakitCount;

        $sudahTap = $attendanceToday->count(); // semua yang sudah absen hari ini (kelas aktif)
        $belumTap = max(0, $totalSiswa - $sudahTap);

        // 2. Real-time Attendance Activity Logs (Hanya siswa yang hadir/tap, bukan Alpha, Sakit, Izin, atau Bolos)
        $attendances = Attendance::with(['student.kelas'])
            ->where('tanggal', $today)
            ->whereNotIn('status', ['A', 'S', 'I', 'B'])
            ->where(function($q) {
                $q->whereNotNull('jam_masuk')->orWhereNotNull('jam_pulang');
            })
            ->whereHas('student', function($q) use ($schoolId) {
                $q->where('school_id', $schoolId)
                  ->whereHas('kelas', function($sub) {
                      $sub->where('is_active_attendance', true);
                  });
            })
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get();

        // Fallback jika hari ini belum ada aktivitas absensi, tampilkan riwayat kehadiran terbaru yang hadir
        if ($attendances->isEmpty()) {
            $attendances = Attendance::with(['student.kelas'])
                ->whereNotIn('status', ['A', 'S', 'I', 'B'])
                ->where(function($q) {
                    $q->whereNotNull('jam_masuk')->orWhereNotNull('jam_pulang');
                })
                ->whereHas('student', function($q) use ($schoolId) {
                    $q->where('school_id', $schoolId);
                })
                ->orderByDesc('updated_at')
                ->limit(15)
                ->get();
        }

        $attLogs = $attendances->map(function($att) {
            $student = $att->student;
            $namaKelas = $student?->kelas?->nama_kelas ?? $student?->kelas?->nama ?? '-';

            $action = 'checkin_success';
            $isTelat = $att->status === 'T' || ($att->status === 'H' && (str_contains(strtolower($att->keterangan ?? ''), 'telat') || str_contains(strtolower($att->keterangan ?? ''), 'terlambat')));

            if ($att->jam_pulang && !$att->jam_masuk) {
                $action = 'checkout_success';
            } elseif ($att->jam_pulang && $att->jam_masuk && $att->updated_at && Carbon::parse($att->updated_at)->isSameMinute(Carbon::parse($att->jam_pulang))) {
                $action = 'checkout_success';
            } elseif ($isTelat) {
                $action = 'terlambat';
            } elseif ($att->status === 'I') {
                $action = 'izin';
            } elseif ($att->status === 'S') {
                $action = 'sakit';
            } elseif ($att->status === 'A') {
                $action = 'alpha';
            }

            $time = $att->jam_pulang ?? $att->jam_masuk ?? ($att->updated_at ? Carbon::parse($att->updated_at)->format('H:i:s') : '-');
            $timeShort = strlen($time) >= 5 ? substr($time, 0, 8) : $time;

            $statusText = match($action) {
                'checkout_success' => 'Sudah Pulang',
                'terlambat'        => 'Terlambat',
                'izin'             => 'Izin',
                'sakit'            => 'Sakit',
                'alpha'            => 'Alpha',
                default            => 'Tepat Waktu'
            };

            $desc = $att->keterangan ? " ({$att->keterangan})" : ($statusText ? " - {$statusText}" : '');
            $message = ($student?->nama ?? 'Siswa') . " (Kelas {$namaKelas})" . $desc;

            return [
                'time'      => $timeShort,
                'action'    => $action,
                'message'   => $message,
                'success'   => in_array($att->status, ['H', 'T']) || !empty($att->jam_pulang),
                'uid'       => 'NIS: ' . ($student?->nis ?? '-') . ($student?->rfid_uid ? " • UID: {$student->rfid_uid}" : ''),
                'timestamp' => $att->updated_at ? $att->updated_at->timestamp : strtotime($att->tanggal . ' ' . $timeShort)
            ];
        });

        // Tetap sertakan log perangkat RFID penting dari ApiLog jika ada (seperti kartu asing/gate)
        $hardwareLogs = ApiLog::where('school_id', $schoolId)
            ->whereDate('created_at', $today)
            ->whereIn('action', ['unknown_card', 'auth_failed', 'gate_access'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($log) {
                return [
                    'time'      => Carbon::parse($log->created_at)->format('H:i:s'),
                    'action'    => $log->action,
                    'message'   => $log->message,
                    'success'   => $log->success,
                    'uid'       => $log->uid,
                    'timestamp' => Carbon::parse($log->created_at)->timestamp
                ];
            });

        $logs = $attLogs->concat($hardwareLogs)
            ->sortByDesc('timestamp')
            ->values()
            ->take(25)
            ->map(function($item) {
                unset($item['timestamp']);
                return $item;
            });

        return response()->json([
            'stats' => [
                'total'        => $totalSiswa,
                'absen'        => $sudahTap,
                'belum'        => $belumTap,
                'hadir'        => $hadirCount,
                'alpha'        => $alphaCount,
                'izin'         => $izinCount,
                'sakit'        => $sakitCount,
                'bolos'        => $bolosCount,
                'terlambat'    => $terlambatCount,
                'tidak_hadir'  => $tidakHadirCount,
            ],
            'logs' => $logs
        ]);
    }

    public function boxes()
    {
        $schoolId = auth()->user()->school_id;
        $kelas = \App\Models\Kelas::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('nama_kelas')
            ->get();

        return view('live_dashboard.boxes', compact('kelas'));
    }

    public function boxData(Request $request)
    {
        $schoolId = auth()->user()->school_id;
        $today = Carbon::today()->format('Y-m-d');
        $kelasId = $request->query('kelas_id');

        // 1. Counters — hanya kelas yang aktif absensi
        $totalSiswa = Siswa::where('school_id', $schoolId)
            ->when($kelasId, fn($q) => $q->where('kelas_id', $kelasId))
            ->whereHas('kelas', function($q) {
                $q->where('is_active_attendance', true);
            })
            ->count();

        $attendanceQuery = Attendance::where('tanggal', $today)
            ->whereHas('student', function($q) use ($schoolId, $kelasId) {
                $q->where('school_id', $schoolId);
                if ($kelasId) {
                    $q->where('kelas_id', $kelasId);
                }
                $q->whereHas('kelas', function($sub) {
                    $sub->where('is_active_attendance', true);
                });
            });

        $attendanceToday = (clone $attendanceQuery)->get();

        $hadirCount     = $attendanceToday->where('status', 'H')->count();
        $alphaCount     = $attendanceToday->where('status', 'A')->count();
        $izinCount      = $attendanceToday->where('status', 'I')->count();
        $sakitCount     = $attendanceToday->where('status', 'S')->count();
        $bolosCount     = $attendanceToday->where('status', 'B')->count();
        $terlambatCount = $attendanceToday->where('status', 'T')->count();
        $tidakHadirCount = $alphaCount + $izinCount + $sakitCount;

        $sudahTap = $attendanceToday->count();
        $belumTap = max(0, $totalSiswa - $sudahTap);

        // 2. Real-time Student Attendance Boxes (Today's Taps, latest first)
        // Hanya siswa yang hadir/tap (bukan Alpha, Sakit, Izin, atau Bolos)
        $attendances = Attendance::with(['student.kelas'])
            ->where('tanggal', $today)
            ->whereNotIn('status', ['A', 'S', 'I', 'B'])
            ->where(function($q) {
                $q->whereNotNull('jam_masuk')->orWhereNotNull('jam_pulang');
            })
            ->whereHas('student', function($q) use ($schoolId, $kelasId) {
                $q->where('school_id', $schoolId);
                if ($kelasId) {
                    $q->where('kelas_id', $kelasId);
                }
            })
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        // If today is empty, fallback to most recent records so display has preview data
        $isFallback = false;
        if ($attendances->isEmpty()) {
            $fallbackAttendances = Attendance::with(['student.kelas'])
                ->whereNotIn('status', ['A', 'S', 'I', 'B'])
                ->where(function($q) {
                    $q->whereNotNull('jam_masuk')->orWhereNotNull('jam_pulang');
                })
                ->whereHas('student', function($q) use ($schoolId, $kelasId) {
                    $q->where('school_id', $schoolId);
                    if ($kelasId) {
                        $q->where('kelas_id', $kelasId);
                    }
                })
                ->orderByDesc('updated_at')
                ->limit(20)
                ->get();

            if ($fallbackAttendances->isNotEmpty()) {
                $attendances = $fallbackAttendances;
                $isFallback = true;
            }
        }

        $boxes = $attendances->map(function($att) {
            $student = $att->student;
            $isTelat = $att->status === 'H' && (str_contains(strtolower($att->keterangan ?? ''), 'telat') || str_contains(strtolower($att->keterangan ?? ''), 'terlambat'));

            // Status label & color scheme
            $statusText = 'Hadir Tepat Waktu';
            $statusType = 'hadir';
            $themeColor = 'emerald';

            if ($att->jam_pulang && !$att->jam_masuk) {
                $statusText = 'Absensi Pulang';
                $statusType = 'pulang';
                $themeColor = 'sky';
            } elseif ($att->jam_pulang && $att->jam_masuk && $att->updated_at && Carbon::parse($att->updated_at)->isSameMinute(Carbon::parse($att->jam_pulang))) {
                $statusText = 'Sudah Pulang';
                $statusType = 'pulang';
                $themeColor = 'sky';
            } elseif ($isTelat || $att->status === 'T') {
                $statusText = 'Terlambat';
                $statusType = 'terlambat';
                $themeColor = 'amber';
            } elseif ($att->status === 'I') {
                $statusText = 'Izin';
                $statusType = 'izin';
                $themeColor = 'blue';
            } elseif ($att->status === 'S') {
                $statusText = 'Sakit';
                $statusType = 'sakit';
                $themeColor = 'purple';
            } elseif ($att->status === 'A') {
                $statusText = 'Alpha';
                $statusType = 'alpha';
                $themeColor = 'rose';
            }

            $latestTime = $att->jam_pulang ?? $att->jam_masuk ?? ($att->updated_at ? Carbon::parse($att->updated_at)->format('H:i:s') : '-');
            $timeShort = strlen($latestTime) >= 5 ? substr($latestTime, 0, 5) : $latestTime;

            return [
                'id'            => $att->id,
                'student_id'    => $student?->id,
                'nama'          => $student?->nama ?? 'Siswa',
                'nis'           => $student?->nis ?? '-',
                'kelas'         => $student?->kelas?->nama_kelas ?? $student?->kelas?->nama ?? 'Umum',
                'foto'          => $student?->foto_url ?? ("https://ui-avatars.com/api/?name=" . urlencode($student?->nama ?? 'Siswa') . "&background=4f46e5&color=fff&size=256&bold=true"),
                'jam_masuk'     => $att->jam_masuk ? substr($att->jam_masuk, 0, 5) : '-',
                'jam_pulang'    => $att->jam_pulang ? substr($att->jam_pulang, 0, 5) : '-',
                'time'          => $latestTime,
                'time_short'    => $timeShort,
                'status_raw'    => $att->status,
                'status_type'   => $statusType,
                'status_text'   => $statusText,
                'theme_color'   => $themeColor,
                'keterangan'    => $att->keterangan ?? '',
                'updated_at'    => $att->updated_at ? $att->updated_at->format('H:i:s') : $latestTime,
                'timestamp'     => $att->updated_at ? $att->updated_at->timestamp : strtotime($att->tanggal . ' ' . $latestTime),
            ];
        });

        $latestStudent = $boxes->first();

        return response()->json([
            'stats' => [
                'total'        => $totalSiswa,
                'absen'        => $sudahTap,
                'belum'        => $belumTap,
                'hadir'        => $hadirCount,
                'alpha'        => $alphaCount,
                'izin'         => $izinCount,
                'sakit'        => $sakitCount,
                'bolos'        => $bolosCount,
                'terlambat'    => $terlambatCount,
                'tidak_hadir'  => $tidakHadirCount,
            ],
            'boxes'         => $boxes,
            'latest'        => $latestStudent,
            'is_fallback'   => $isFallback,
            'server_time'   => Carbon::now()->format('H:i:s'),
            'server_date'   => Carbon::now()->locale('id')->isoFormat('dddd, D MMMM Y')
        ]);
    }

    public function simulateTap(Request $request)
    {
        $schoolId = auth()->user()->school_id;
        $student = Siswa::where('school_id', $schoolId)->inRandomOrder()->first();

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Belum ada data siswa untuk disimulasikan.'], 404);
        }

        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now()->format('H:i:s');

        $attendance = Attendance::firstOrNew([
            'student_id' => $student->id,
            'tanggal'    => $today,
        ]);

        if (!$attendance->exists || !$attendance->jam_masuk) {
            $attendance->jam_masuk = $now;
            $attendance->status = (rand(1, 10) > 8) ? 'T' : 'H';
            $attendance->keterangan = ($attendance->status === 'T') ? 'Terlambat ' . rand(5, 25) . ' menit' : 'Tepat Waktu';
        } else {
            $attendance->jam_pulang = $now;
            $attendance->keterangan = 'Pulang Sekolah';
        }

        $attendance->save();

        return response()->json([
            'success' => true,
            'message' => 'Simulasi absensi berhasil untuk ' . $student->nama,
            'student' => $student->nama,
            'time'    => $now
        ]);
    }
}
