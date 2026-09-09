<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\AbsensiGuru;
use App\Models\Attendance;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\ApiLog;
use Carbon\Carbon;

class MobileAttendanceController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = $request->email;
        $user = User::where('email', $loginInput)
            ->orWhere('username', $loginInput)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password_hash ?? $user->password)) {
            ApiLog::create([
                'school_id' => $user?->school_id,
                'api_key' => 'MOBILE_APP',
                'action' => 'mobile_login_failed',
                'uid' => $loginInput,
                'success' => false,
                'message' => 'Login gagal: Email/Username atau password salah',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent() ?? 'Android Mobile App',
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Email/Username atau password salah.'
            ], 401);
        }

        // Generate Sanctum Token
        $token = $user->createToken('android-app')->plainTextToken;

        $isWaliKelas = false;
        $kelasWali = null;
        if ($user->guru) {
            $kelasWali = Kelas::where('wali_kelas_id', $user->guru->id)
                ->orWhere('wali_kelas_2_id', $user->guru->id)
                ->first();
            $isWaliKelas = !empty($kelasWali) || $user->role === 'wali_kelas';
        } elseif ($user->role === 'wali_kelas') {
            $isWaliKelas = true;
        }

        $guruPayload = null;
        if ($user->guru) {
            $guruPayload = array_merge($user->guru->toArray(), [
                'is_wali_kelas' => $isWaliKelas,
                'kelas_id' => $kelasWali?->id,
                'kelas_name' => $kelasWali ? ($kelasWali->nama_kelas ?? $kelasWali->nama) : null,
            ]);
        }

        ApiLog::create([
            'school_id' => $user->school_id,
            'api_key' => 'MOBILE_APP',
            'action' => 'mobile_login',
            'uid' => $user->username ?? $user->email,
            'success' => true,
            'message' => "Login berhasil: {$user->full_name} ({$user->role})",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent() ?? 'Android Mobile App',
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'school_id' => $user->school_id,
                'is_wali_kelas' => $isWaliKelas,
                'wali_kelas' => $isWaliKelas,
                'kelas_name' => $kelasWali ? ($kelasWali->nama_kelas ?? $kelasWali->nama) : null,
                'kelas_id' => $kelasWali?->id,
                'kelas' => $kelasWali ? [
                    'id' => $kelasWali->id,
                    'nama' => $kelasWali->nama_kelas ?? $kelasWali->nama,
                    'nama_kelas' => $kelasWali->nama_kelas ?? $kelasWali->nama,
                ] : null,
                'guru' => $guruPayload,
                'student' => $user->student
            ],
            'is_wali_kelas' => $isWaliKelas,
            'school' => $user->school
        ]);
    }

    public function today(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->format('Y-m-d');

        $isWaliKelas = false;
        $kelasWali = null;
        if ($user && $user->guru) {
            $kelasWali = Kelas::where('wali_kelas_id', $user->guru->id)
                ->orWhere('wali_kelas_2_id', $user->guru->id)
                ->first();
            $isWaliKelas = !empty($kelasWali) || $user->role === 'wali_kelas';
        } elseif ($user && $user->role === 'wali_kelas') {
            $isWaliKelas = true;
        }

        if ($user && (in_array($user->role, ['guru', 'teacher', 'wali_kelas', 'admin']) || $user->guru) && $user->guru) {
            $absen = AbsensiGuru::where('guru_id', $user->guru->id)
                ->where('tanggal', $today)
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'tanggal' => $today,
                    'jam_masuk' => $absen?->jam_masuk,
                    'jam_pulang' => $absen?->jam_pulang,
                    'status_kehadiran' => $absen?->status_kehadiran ?? ($absen?->status ?? 'Belum Absen'),
                    'is_checked_in' => !empty($absen?->jam_masuk),
                    'is_checked_out' => !empty($absen?->jam_pulang),
                    'is_wali_kelas' => $isWaliKelas,
                    'wali_kelas' => $isWaliKelas,
                    'kelas_name' => $kelasWali ? ($kelasWali->nama_kelas ?? $kelasWali->nama) : null,
                    'kelas_id' => $kelasWali?->id,
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'tanggal' => $today,
                'jam_masuk' => null,
                'jam_pulang' => null,
                'status_kehadiran' => 'Belum Absen',
                'is_checked_in' => false,
                'is_checked_out' => false,
                'is_wali_kelas' => $isWaliKelas,
                'wali_kelas' => $isWaliKelas,
                'kelas_name' => null,
                'kelas_id' => null,
            ]
        ]);
    }

    public function checkIn(Request $request)
    {
        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
            'photo' => 'nullable|image|max:5120'
        ]);

        $user = $request->user();
        $today = Carbon::today()->format('Y-m-d');
        $nowTime = Carbon::now()->format('H:i:s');

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('absensi_photos', 'public');
        }

        if ($user && ($user->role === 'guru' || $user->guru) && $user->guru) {
            $absen = AbsensiGuru::updateOrCreate(
                [
                    'guru_id' => $user->guru->id,
                    'tanggal' => $today,
                ],
                [
                    'school_id' => $user->school_id,
                    'waktu_hadir' => now(),
                    'jam_masuk' => $nowTime,
                    'status_kehadiran' => 'Hadir',
                    'status' => 'Hadir',
                    'keterangan' => "Absen via Mobile App (Lat: {$request->latitude}, Lng: {$request->longitude})"
                ]
            );

            ApiLog::create([
                'school_id' => $user->school_id,
                'api_key' => 'MOBILE_APP',
                'action' => 'mobile_checkin',
                'uid' => $user->guru->nip ?? ($user->username ?? $user->email),
                'success' => true,
                'message' => "Absen Masuk: {$user->full_name} (Lat: {$request->latitude}, Lng: {$request->longitude})",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent() ?? 'Android Mobile App',
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Absen masuk berhasil dicatat',
                'data' => [
                    'tanggal' => $today,
                    'jam_masuk' => $absen->jam_masuk,
                    'jam_pulang' => $absen->jam_pulang,
                    'status_kehadiran' => $absen->status_kehadiran,
                    'is_checked_in' => true,
                    'is_checked_out' => !empty($absen->jam_pulang)
                ]
            ]);
        }

        ApiLog::create([
            'school_id' => $user?->school_id,
            'api_key' => 'MOBILE_APP',
            'action' => 'mobile_checkin_failed',
            'uid' => $user?->username ?? $user?->email,
            'success' => false,
            'message' => 'Absen Masuk Gagal: Role tidak didukung untuk absen guru',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent() ?? 'Android Mobile App',
            'created_at' => now(),
        ]);

        return response()->json(['success' => false, 'message' => 'Role tidak didukung untuk absen'], 400);
    }

    public function checkOut(Request $request)
    {
        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
            'photo' => 'nullable|image|max:5120'
        ]);

        $user = $request->user();
        $today = Carbon::today()->format('Y-m-d');
        $nowTime = Carbon::now()->format('H:i:s');

        if ($user && ($user->role === 'guru' || $user->guru) && $user->guru) {
            $absen = AbsensiGuru::where('guru_id', $user->guru->id)
                ->where('tanggal', $today)
                ->first();

            if (!$absen) {
                ApiLog::create([
                    'school_id' => $user->school_id,
                    'api_key' => 'MOBILE_APP',
                    'action' => 'mobile_checkout_failed',
                    'uid' => $user->guru->nip ?? ($user->username ?? $user->email),
                    'success' => false,
                    'message' => "Absen Pulang Gagal: {$user->full_name} belum absen masuk hari ini",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent() ?? 'Android Mobile App',
                    'created_at' => now(),
                ]);

                return response()->json(['success' => false, 'message' => 'Anda belum melakukan absen masuk hari ini'], 400);
            }

            $absen->update([
                'jam_pulang' => $nowTime,
            ]);

            ApiLog::create([
                'school_id' => $user->school_id,
                'api_key' => 'MOBILE_APP',
                'action' => 'mobile_checkout',
                'uid' => $user->guru->nip ?? ($user->username ?? $user->email),
                'success' => true,
                'message' => "Absen Pulang: {$user->full_name} (Lat: {$request->latitude}, Lng: {$request->longitude})",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent() ?? 'Android Mobile App',
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Absen pulang berhasil dicatat',
                'data' => [
                    'tanggal' => $today,
                    'jam_masuk' => $absen->jam_masuk,
                    'jam_pulang' => $absen->jam_pulang,
                    'status_kehadiran' => $absen->status_kehadiran,
                    'is_checked_in' => true,
                    'is_checked_out' => true
                ]
            ]);
        }

        ApiLog::create([
            'school_id' => $user?->school_id,
            'api_key' => 'MOBILE_APP',
            'action' => 'mobile_checkout_failed',
            'uid' => $user?->username ?? $user?->email,
            'success' => false,
            'message' => 'Absen Pulang Gagal: Role tidak didukung',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent() ?? 'Android Mobile App',
            'created_at' => now(),
        ]);

        return response()->json(['success' => false, 'message' => 'Role tidak didukung'], 400);
    }

    public function history(Request $request)
    {
        $user = $request->user();
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if ($user && (in_array($user->role, ['guru', 'teacher', 'wali_kelas', 'admin']) || $user->role === 'guru') && $user->guru) {
            $query = AbsensiGuru::where('guru_id', $user->guru->id)
                ->whereNull('jadwal_pelajaran_id');

            // Jika ada filter range tanggal
            if ($startDate && $endDate) {
                $query->whereBetween('tanggal', [$startDate, $endDate]);
            }

            $history = $query->orderBy('tanggal', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'tanggal' => $item->tanggal,
                        'jam_masuk' => $item->jam_masuk,
                        'jam_pulang' => $item->jam_pulang,
                        'status_kehadiran' => $item->status ?? $item->status_kehadiran,
                        'keterangan' => $item->keterangan
                    ];
                });

            return response()->json(['success' => true, 'data' => $history]);
        }

        return response()->json(['success' => true, 'data' => []]);
    }

    public function recap(Request $request)
    {
        $user = $request->user();
        // Pilihan rentang bulan (1, 3, 6, 12 bulan)
        $months = (int) $request->query('months', 1);

        // Hitung tanggal mundur 30 hari * jumlah bulan (Sama seperti Web)
        $endDate = Carbon::now()->format('Y-m-d');
        $startDate = Carbon::now()->subDays(30 * $months)->format('Y-m-d');

        if ($user && (in_array($user->role, ['guru', 'teacher', 'wali_kelas', 'admin']) || $user->role === 'guru') && $user->guru) {
            // Query absensi harian guru (Sama seperti RekapGuruController.php)
            $query = AbsensiGuru::where('guru_id', $user->guru->id)
                ->whereNull('jadwal_pelajaran_id')
                ->whereBetween('tanggal', [$startDate, $endDate]);

            $allStats = $query->get();

            $totalCatatan = $allStats->count(); // Total di Web
            $totalHadir = $allStats->where('status', 'Hadir')->count(); // Hadir Tepat Waktu
            $totalTerlambat = $allStats->where('status', 'Terlambat')->count(); // Terlambat
            $totalIzin = $allStats->where('status', 'Izin')->count();
            $totalSakit = $allStats->where('status', 'Sakit')->count(); // Izin & Sakit
            $totalAlpha = $allStats->whereIn('status', ['Tidak Hadir', 'Alpha'])->count();

            // Hitung persentase kehadiran (Hadir + Terlambat)
            $totalHadirDanTerlambat = $totalHadir + $totalTerlambat;
            $persentase = $totalCatatan > 0 ? round(($totalHadirDanTerlambat / $totalCatatan) * 100, 1) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_hadir' => $totalHadir,
                    'total_terlambat' => $totalTerlambat,
                    'total_izin' => $totalIzin,
                    'total_sakit' => $totalSakit,
                    'total_alpha' => $totalAlpha,
                    'total_hari_kerja' => $totalCatatan,
                    'persentase_kehadiran' => $persentase,
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_hadir' => 0,
                'total_terlambat' => 0,
                'total_izin' => 0,
                'total_sakit' => 0,
                'total_alpha' => 0,
                'total_hari_kerja' => 0,
                'persentase_kehadiran' => 0.0,
            ]
        ]);
    }

    public function classRecap(Request $request)
    {
        $user = $request->user();

        if (!in_array($user->role, ['guru', 'teacher', 'wali_kelas', 'admin']) || !$user->guru) {
            return response()->json(['success' => false, 'message' => 'Akses khusus dewan guru'], 403);
        }

        $guru = $user->guru;
        // Cari kelas di mana guru ini ditugaskan sebagai wali kelas
        $kelas = Kelas::where('wali_kelas_id', $guru->id)
            ->orWhere('wali_kelas_2_id', $guru->id)
            ->first();

        if (!$kelas) {
            return response()->json(['success' => false, 'message' => 'Anda belum ditugaskan sebagai wali kelas'], 404);
        }

        $startDate = $request->query('start_date', Carbon::today()->format('Y-m-d'));
        $endDate = $request->query('end_date', Carbon::today()->format('Y-m-d'));

        // Ambil daftar siswa di kelas tersebut
        $siswaList = Siswa::where('kelas_id', $kelas->id)->get();

        $studentsRecap = $siswaList->map(function ($siswa) use ($startDate, $endDate) {
            $attendances = Attendance::where('student_id', $siswa->id)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->get();

            return [
                'student_id' => $siswa->id,
                'nama' => $siswa->nama,
                'nis' => $siswa->nis,
                'hadir' => $attendances->whereIn('status', ['H', 'Hadir'])->count(),
                'terlambat' => $attendances->whereIn('status', ['T', 'Terlambat'])->count(),
                'izin' => $attendances->whereIn('status', ['I', 'Izin'])->count(),
                'sakit' => $attendances->whereIn('status', ['S', 'Sakit'])->count(),
                'alpha' => $attendances->whereIn('status', ['A', 'Alpha'])->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'kelas_name' => "Kelas " . $kelas->nama,
                'total_siswa' => $siswaList->count(),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'students' => $studentsRecap
            ]
        ]);
    }

    public function getStudentsForAttendance(Request $request)
    {
        $user = $request->user();

        if ($user && (!in_array($user->role, ['guru', 'teacher', 'wali_kelas', 'admin']) && $user->role !== 'guru') || !$user->guru) {
            return response()->json(['success' => false, 'message' => 'Akses khusus dewan guru'], 403);
        }

        $tanggal = $request->query('tanggal', Carbon::today()->format('Y-m-d'));
        $queryStr = $request->query('q');

        // Jika kata kunci pencarian kurang dari 2 karakter, kembalikan array kosong
        if (empty($queryStr) || strlen(trim($queryStr)) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        // Cari seluruh siswa di sekolah berdasarkan Nama atau NIS/NISN
        $siswaQuery = Siswa::with('kelas');
        if ($user->school_id) {
            $siswaQuery->where('school_id', $user->school_id);
        }

        $siswaList = $siswaQuery->where(function ($q) use ($queryStr) {
                $q->where('nama', 'like', "%{$queryStr}%")
                  ->orWhere('nis', 'like', "%{$queryStr}%");
            })
            ->limit(30)
            ->get();

        $data = $siswaList->map(function ($siswa) use ($tanggal) {
            $absen = Attendance::where('student_id', $siswa->id)
                ->where('tanggal', $tanggal)
                ->first();

            return [
                'student_id' => $siswa->id,
                'nama' => $siswa->nama,
                'nis' => $siswa->nis,
                'kelas_name' => $siswa->kelas ? "Kelas " . ($siswa->kelas->nama_kelas ?? $siswa->kelas->nama) : 'Siswa',
                'status' => $absen ? $absen->status : 'H',
                'keterangan' => $absen ? $absen->keterangan : null
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function submitStudentsAttendance(Request $request)
    {
        $user = $request->user();

        if (!in_array($user->role, ['guru', 'teacher', 'wali_kelas', 'admin']) || !$user->guru) {
            return response()->json(['success' => false, 'message' => 'Akses khusus dewan guru'], 403);
        }

        $request->validate([
            'tanggal' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|integer',
            'attendances.*.status' => 'required|string',
        ]);

        $tanggal = $request->tanggal;

        foreach ($request->attendances as $item) {
            $status = $item['status'];
            $isHadir = in_array($status, ['H', 'Hadir', 'T', 'Terlambat']);

            Attendance::updateOrCreate(
                [
                    'student_id' => $item['student_id'],
                    'tanggal' => $tanggal
                ],
                [
                    'status' => $status,
                    'keterangan' => $item['keterangan'] ?? null,
                    'jam_masuk' => $isHadir ? now()->format('H:i:s') : null,
                    'updated_at' => now()
                ]
            );
        }

        ApiLog::create([
            'school_id' => $user->school_id,
            'api_key' => 'MOBILE_APP',
            'action' => 'mobile_bulk_attendance',
            'uid' => $user->guru->nip ?? $user->username,
            'success' => true,
            'message' => "Input Absensi Siswa Massal oleh {$user->full_name}: " . count($request->attendances) . " siswa (Tgl: {$tanggal})",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent() ?? 'Android Mobile App',
            'created_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Absensi seluruh siswa berhasil disimpan']);
    }

    public function updateSingleStudentAttendance(Request $request)
    {
        $user = $request->user();

        if ($user && (!in_array($user->role, ['guru', 'teacher', 'wali_kelas', 'admin']) && $user->role !== 'guru') || !$user->guru) {
            return response()->json(['success' => false, 'message' => 'Akses khusus dewan guru'], 403);
        }

        $request->validate([
            'tanggal' => 'required|date',
            'student_id' => 'required|integer',
            'status' => 'required|string',
        ]);

        $status = $request->status;
        $isHadir = in_array($status, ['H', 'Hadir', 'T', 'Terlambat']);

        Attendance::updateOrCreate(
            [
                'student_id' => $request->student_id,
                'tanggal' => $request->tanggal
            ],
            [
                'school_id' => $user->school_id,
                'status' => $status,
                'keterangan' => $request->keterangan ?? null,
                'jam_masuk' => $isHadir ? now()->format('H:i:s') : null,
                'updated_at' => now()
            ]
        );

        return response()->json(['success' => true, 'message' => 'Absensi siswa tersimpan secara realtime']);
    }

    public function getKelases(Request $request)
    {
        $user = $request->user();
        $query = Kelas::query();
        if ($user && $user->school_id) {
            $query->where('school_id', $user->school_id);
        }
        $kelases = $query->orderBy('nama_kelas', 'asc')->get();

        $data = $kelases->map(function ($k) {
            return [
                'id' => $k->id,
                'nama' => $k->nama_kelas ?? $k->nama,
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function schoolStudentsRecap(Request $request)
    {
        $user = $request->user();
        $kelasId = $request->query('kelas_id');
        $months = (int) $request->query('months', 1);
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if (!$startDate || !$endDate) {
            $endDate = Carbon::now()->format('Y-m-d');
            $startDate = Carbon::now()->subDays(30 * $months)->format('Y-m-d');
        }

        // Ambil daftar ID siswa (seluruh sekolah atau kelas tertentu)
        $siswaQuery = Siswa::where('school_id', $user->school_id);
        if ($kelasId) {
            $siswaQuery->where('kelas_id', $kelasId);
        }
        $siswaIds = $siswaQuery->pluck('id');

        // Query akumulasi absensi siswa
        $attendances = Attendance::whereIn('student_id', $siswaIds)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->get();

        $totalHadir = $attendances->whereIn('status', ['H', 'Hadir'])->count();
        $totalTerlambat = $attendances->whereIn('status', ['T', 'Terlambat'])->count();
        $totalIzin = $attendances->whereIn('status', ['I', 'Izin'])->count();
        $totalSakit = $attendances->whereIn('status', ['S', 'Sakit'])->count();
        $totalAlpha = $attendances->whereIn('status', ['A', 'Alpha'])->count();

        $totalRecord = $attendances->count();
        $totalAbsenOk = $totalHadir + $totalTerlambat;
        $persentase = $totalRecord > 0 ? round(($totalAbsenOk / $totalRecord) * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_hadir' => $totalHadir,
                'total_terlambat' => $totalTerlambat,
                'total_izin' => $totalIzin,
                'total_sakit' => $totalSakit,
                'total_alpha' => $totalAlpha,
                'total_hari_kerja' => $totalRecord,
                'persentase_kehadiran' => $persentase,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }
        return response()->json(['success' => true, 'message' => 'Logout berhasil']);
    }
}
