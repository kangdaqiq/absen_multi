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
use App\Models\Guru;
use App\Models\ApiLog;
use App\Models\Setting;
use App\Models\Jadwal;
use App\Models\UserFaceData;
use App\Services\FcmNotificationService;
use App\Services\FaceRecognitionService;
use Carbon\Carbon;

class MobileAttendanceController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = trim($request->email);
        $passwordInput = trim($request->password);

        // 1. Cek User tabel terlebih dahulu (Guru, Wali Kelas, Admin)
        $user = User::where('email', $loginInput)
            ->orWhere('username', $loginInput)
            ->first();

        // Jika user ditemukan dan password hash cocok (Guru/Admin)
        if ($user && Hash::check($passwordInput, $user->password_hash ?? $user->password)) {
            // Generate Sanctum Token
            $token = $user->createToken('android-app')->plainTextToken;

            $isWaliKelas = false;
            $kelasWali = null;
            $waliKelases = [];
            if ($user->guru) {
                $allKelasWali = Kelas::where('wali_kelas_id', $user->guru->id)
                    ->orWhere('wali_kelas_2_id', $user->guru->id)
                    ->get();
                $kelasWali = $allKelasWali->first();
                $isWaliKelas = $allKelasWali->isNotEmpty() || $user->role === 'wali_kelas';
                $waliKelases = $allKelasWali->map(function ($kw) use ($user) {
                    $peran = ($kw->wali_kelas_id == $user->guru->id) ? 'Wali Kelas 1' : 'Wali Kelas 2';
                    return [
                        'id' => $kw->id,
                        'nama' => $kw->nama_kelas ?? $kw->nama,
                        'nama_kelas' => $kw->nama_kelas ?? $kw->nama,
                        'peran' => $peran,
                        'is_utama' => ($kw->wali_kelas_id == $user->guru->id),
                    ];
                })->values()->all();
            } elseif ($user->role === 'wali_kelas') {
                $isWaliKelas = true;
            }

            $guruPayload = null;
            if ($user->guru) {
                $guruPayload = [
                    'id' => $user->guru->id,
                    'nama' => $user->guru->nama,
                    'nip' => $user->guru->nip,
                    'tgl_lahir' => $user->guru->tgl_lahir,
                    'no_wa' => $user->guru->no_wa,
                    'foto_url' => $user->guru->foto_url,
                    'is_wali_kelas' => $isWaliKelas,
                    'kelas_id' => $kelasWali?->id,
                    'kelas_name' => $kelasWali ? ($kelasWali->nama_kelas ?? $kelasWali->nama) : null,
                    'wali_kelases' => $waliKelases,
                ];
            }

            // Save FCM token if provided & detach from previous accounts
            if ($request->filled('fcm_token')) {
                $this->assignFcmToken($request->fcm_token, $user);
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
                    'nip' => $user->guru?->nip,
                    'tgl_lahir' => $user->guru?->tgl_lahir ? Carbon::parse($user->guru->tgl_lahir)->format('Y-m-d') : null,
                    'no_wa' => $user->guru?->no_wa,
                    'no_wa_ortu' => null,
                    'wa_ortu' => null,
                    'school_id' => $user->school_id,
                    'is_wali_kelas' => $isWaliKelas,
                    'kelas_id' => $kelasWali?->id,
                    'kelas_name' => $kelasWali ? ($kelasWali->nama_kelas ?? $kelasWali->nama) : null,
                    'wali_kelases' => $waliKelases,
                    'guru' => $guruPayload,
                ],
                'school' => $this->formatSchoolResponse($user->school, $user->school_id)
            ]);
        }

        // 2. Cek apakah login sebagai Siswa (NIS/NISN sebagai username, Tanggal Lahir sebagai password)
        try {
            $siswa = Siswa::with(['kelas', 'school'])
                ->where(function ($q) use ($loginInput) {
                    $q->where('nis', $loginInput);
                    if (\Illuminate\Support\Facades\Schema::hasColumn('siswa', 'nisn')) {
                        $q->orWhere('nisn', $loginInput);
                    }
                })
                ->first();

            if ($siswa && !empty($siswa->tgl_lahir)) {
                $isBirthDateValid = false;
                try {
                    $birthDate = Carbon::parse($siswa->tgl_lahir);
                    $cleanInputPass = preg_replace('/[^0-9]/', '', $passwordInput);

                    $validFormats = [
                        $birthDate->format('Y-m-d'),   // 2008-05-15
                        $birthDate->format('d-m-Y'),   // 15-05-2008
                        $birthDate->format('Y/m/d'),   // 2008/05/15
                        $birthDate->format('d/m/Y'),   // 15/05/2008
                        $birthDate->format('Ymd'),     // 20080515
                        $birthDate->format('dmY'),     // 15052008
                    ];

                    $isBirthDateValid = in_array($passwordInput, $validFormats) ||
                                        in_array($cleanInputPass, [$birthDate->format('Ymd'), $birthDate->format('dmY')]);
                } catch (\Exception $e) {
                    $isBirthDateValid = false;
                }

                if ($isBirthDateValid) {
                    // Temukan atau buatkan shadow User untuk siswa agar memiliki token Sanctum
                    $user = null;
                    if (\Illuminate\Support\Facades\Schema::hasColumn('siswa', 'user_id') && $siswa->user_id) {
                        $user = User::find($siswa->user_id);
                    }

                    if (!$user) {
                        $user = User::where('username', 'siswa_' . $siswa->nis)
                            ->orWhere('username', $siswa->nis)
                            ->first();
                    }

                    if (!$user) {
                        $user = User::create([
                            'full_name' => $siswa->nama,
                            'username' => 'siswa_' . $siswa->nis,
                            'email' => $siswa->nis . '@siswa.local',
                            'password_hash' => Hash::make($passwordInput),
                            'role' => 'student',
                            'school_id' => $siswa->school_id,
                        ]);
                    } else {
                        $user->role = 'student';
                        $user->school_id = $siswa->school_id;
                        $user->password_hash = Hash::make($passwordInput);
                        $user->save();
                    }

                    if (\Illuminate\Support\Facades\Schema::hasColumn('siswa', 'user_id')) {
                        if ($siswa->user_id !== $user->id) {
                            $siswa->user_id = $user->id;
                            $siswa->save();
                        }
                    }

                    // Save FCM token if provided & detach from previous accounts
                    if ($request->filled('fcm_token')) {
                        $this->assignFcmToken($request->fcm_token, $user, $siswa);
                    }

                    $token = $user->createToken('android-app-siswa')->plainTextToken;

                    ApiLog::create([
                        'school_id' => $siswa->school_id,
                        'api_key' => 'MOBILE_APP',
                        'action' => 'mobile_login_student',
                        'uid' => $siswa->nis,
                        'success' => true,
                        'message' => "Login siswa berhasil: {$siswa->nama} (NIS: {$siswa->nis})",
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent() ?? 'Android Mobile App',
                        'created_at' => now(),
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Login siswa berhasil',
                        'token' => $token,
                        'user' => [
                            'id' => $user->id,
                            'full_name' => $siswa->nama,
                            'username' => $siswa->nis,
                            'email' => $user->email,
                            'role' => 'student',
                            'nis' => $siswa->nis,
                            'tgl_lahir' => $siswa->tgl_lahir ? Carbon::parse($siswa->tgl_lahir)->format('Y-m-d') : null,
                            'no_wa' => $siswa->no_wa,
                            'no_wa_ortu' => $siswa->wa_ortu,
                            'wa_ortu' => $siswa->wa_ortu,
                            'school_id' => $siswa->school_id,
                            'student' => [
                                'id' => $siswa->id,
                                'nama' => $siswa->nama,
                                'nis' => $siswa->nis,
                                'tgl_lahir' => $siswa->tgl_lahir ? Carbon::parse($siswa->tgl_lahir)->format('Y-m-d') : null,
                                'no_wa' => $siswa->no_wa,
                                'no_wa_ortu' => $siswa->wa_ortu,
                                'wa_ortu' => $siswa->wa_ortu,
                                'foto_url' => $siswa->foto_url,
                                'kelas_id' => $siswa->kelas_id,
                                'kelas_name' => $siswa->kelas ? ($siswa->kelas->nama_kelas ?? $siswa->kelas->nama) : null,
                            ]
                        ],
                        'school' => $this->formatSchoolResponse($siswa->school, $siswa->school_id)
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Student login error: " . $e->getMessage());
        }

        // 3. Jika gagal autentikasi
        ApiLog::create([
            'school_id' => $user?->school_id ?? $siswa?->school_id,
            'api_key' => 'MOBILE_APP',
            'action' => 'mobile_login_failed',
            'uid' => $loginInput,
            'success' => false,
            'message' => 'Login gagal: NISN/Email atau Password/Tgl Lahir salah',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent() ?? 'Android Mobile App',
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'NISN/Email atau Password/Tanggal Lahir salah.'
        ], 401);
    }

    public function user(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $studentData = null;
        $s = $user->student ?? Siswa::where('user_id', $user->id)->first();
        if (!$s && in_array($user->role, ['student', 'siswa'])) {
            $cleanNis = str_replace('siswa_', '', $user->username);
            $s = Siswa::where('nis', $cleanNis)
                ->when($user->school_id, fn($q) => $q->where('school_id', $user->school_id))
                ->first() ?? Siswa::where('nis', $cleanNis)->first();
        }

        if ($s) {
            $studentData = [
                'id' => $s->id,
                'nama' => $s->nama,
                'nis' => $s->nis,
                'tgl_lahir' => $s->tgl_lahir ? Carbon::parse($s->tgl_lahir)->format('Y-m-d') : null,
                'no_wa' => $s->no_wa,
                'no_wa_ortu' => $s->wa_ortu,
                'wa_ortu' => $s->wa_ortu,
                'foto' => $s->foto,
                'foto_url' => $s->foto_url,
                'kelas_id' => $s->kelas_id,
                'kelas_name' => $s->kelas ? ($s->kelas->nama_kelas ?? $s->kelas->nama) : null,
                'kelas' => $s->kelas ? [
                    'id' => $s->kelas->id,
                    'nama' => $s->kelas->nama_kelas ?? $s->kelas->nama,
                    'nama_kelas' => $s->kelas->nama_kelas ?? $s->kelas->nama,
                ] : null,
            ];
        }

        $guruData = null;
        if ($user->guru) {
            $allKelasWali = Kelas::where('wali_kelas_id', $user->guru->id)
                ->orWhere('wali_kelas_2_id', $user->guru->id)
                ->get();
            $kelasWali = $allKelasWali->first();
            $isWaliKelas = $allKelasWali->isNotEmpty() || $user->role === 'wali_kelas';
            $waliKelases = $allKelasWali->map(function ($kw) use ($user) {
                $peran = ($kw->wali_kelas_id == $user->guru->id) ? 'Wali Kelas 1' : 'Wali Kelas 2';
                return [
                    'id' => $kw->id,
                    'nama' => $kw->nama_kelas ?? $kw->nama,
                    'nama_kelas' => $kw->nama_kelas ?? $kw->nama,
                    'peran' => $peran,
                    'is_utama' => ($kw->wali_kelas_id == $user->guru->id),
                ];
            })->values()->all();

            $guruData = [
                'id' => $user->guru->id,
                'nama' => $user->guru->nama,
                'nip' => $user->guru->nip,
                'foto' => $user->guru->foto,
                'foto_url' => $user->guru->foto_url,
                'is_wali_kelas' => $isWaliKelas,
                'kelas_id' => $kelasWali?->id,
                'kelas_name' => $kelasWali ? ($kelasWali->nama_kelas ?? $kelasWali->nama) : null,
                'wali_kelases' => $waliKelases,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'nip' => $user->guru?->nip,
                'nis' => $s?->nis ?? $user->student?->nis,
                'tgl_lahir' => $s?->tgl_lahir ? Carbon::parse($s->tgl_lahir)->format('Y-m-d') : ($user->guru?->tgl_lahir ? Carbon::parse($user->guru->tgl_lahir)->format('Y-m-d') : null),
                'no_wa' => $s?->no_wa ?? $user->guru?->no_wa,
                'no_wa_ortu' => $s?->wa_ortu,
                'wa_ortu' => $s?->wa_ortu,
                'school_id' => $user->school_id,
                'guru' => $guruData,
                'student' => $studentData,
                'school' => $this->formatSchoolResponse($user->school, $user->school_id)
            ]
        ]);
    }

    public function today(Request $request)
    {
        $user = $request->user();
        $schoolId = $user?->school_id ?? 0;
        $schoolTz = Setting::where('school_id', $schoolId)->where('setting_key', 'timezone')->value('setting_value') ?: config('app.timezone', 'Asia/Jakarta');
        $now = Carbon::now($schoolTz);
        $today = $now->format('Y-m-d');

        $isWaliKelas = false;
        $kelasWali = null;
        $waliKelases = [];
        if ($user && $user->guru) {
            $allKelasWali = Kelas::where('wali_kelas_id', $user->guru->id)
                ->orWhere('wali_kelas_2_id', $user->guru->id)
                ->get();
            $kelasWali = $allKelasWali->first();
            $isWaliKelas = $allKelasWali->isNotEmpty() || $user->role === 'wali_kelas';
            $waliKelases = $allKelasWali->map(function ($kw) use ($user) {
                return [
                    'id' => $kw->id,
                    'nama' => $kw->nama_kelas ?? $kw->nama,
                    'nama_kelas' => $kw->nama_kelas ?? $kw->nama,
                    'peran' => ($kw->wali_kelas_id == $user->guru->id) ? 'Wali Kelas 1' : 'Wali Kelas 2',
                    'is_utama' => ($kw->wali_kelas_id == $user->guru->id),
                ];
            })->values()->all();
        } elseif ($user && $user->role === 'wali_kelas') {
            $isWaliKelas = true;
        }

        $isMobileAttendanceEnabled = $this->isGeofenceEnabled($schoolId);

        // Case 1: Siswa (Student)
        if ($user && ($user->student || in_array($user->role, ['student', 'siswa']))) {
            $student = $user->student ?? Siswa::with('kelas')->where('user_id', $user->id)->orWhere('nis', str_replace('siswa_', '', $user->username))->first();
            if ($student) {
                $studentSchoolId = $student->school_id ?: $schoolId;
                $absen = Attendance::where('student_id', $student->id)
                    ->where('tanggal', $today)
                    ->first();

                // Cari jadwal harian siswa
                $indexHari = (int) $now->format('N'); // 1 (Senin) - 7 (Minggu)
                $jadwal = Jadwal::where('index_hari', $indexHari)
                    ->where('is_active', 1)
                    ->when($studentSchoolId > 0, function ($q) use ($studentSchoolId) {
                        $q->where('school_id', $studentSchoolId);
                    })
                    ->first();

                if (!$jadwal && $studentSchoolId > 0) {
                    $jadwal = Jadwal::where('index_hari', $indexHari)->where('is_active', 1)->first();
                }

                $jadwalMasuk = $jadwal?->jam_masuk ?? '07:00:00';
                $jadwalPulang = $jadwal?->jam_pulang ?? '15:30:00';
                $shiftName = $jadwal ? ($jadwal->hari ? "Reguler {$jadwal->hari}" : "Reguler Pagi") : "Reguler Pagi";

                return response()->json([
                    'success' => true,
                    'data' => [
                        'tanggal' => $today,
                        'jam_masuk' => $absen?->jam_masuk,
                        'jam_pulang' => $absen?->jam_pulang,
                        'jadwal_masuk' => $jadwalMasuk,
                        'jadwal_pulang' => $jadwalPulang,
                        'shift_name' => $shiftName,
                        'status_kehadiran' => $absen?->status ?? 'Belum Absen',
                        'is_checked_in' => !empty($absen?->jam_masuk),
                        'is_checked_out' => !empty($absen?->jam_pulang),
                        'is_wali_kelas' => false,
                        'wali_kelas' => false,
                        'kelas_name' => $student->kelas ? ($student->kelas->nama_kelas ?? $student->kelas->nama) : null,
                        'kelas_id' => $student->kelas_id,
                        'is_mobile_attendance_enabled' => $isMobileAttendanceEnabled,
                        'geofence_enabled' => $isMobileAttendanceEnabled,
                    ]
                ]);
            }
        }

        // Case 2: Guru / Pegawai / Admin
        if ($user && (in_array($user->role, ['guru', 'teacher', 'wali_kelas', 'admin']) || $user->guru) && $user->guru) {
            $absen = AbsensiGuru::where('guru_id', $user->guru->id)
                ->where('tanggal', $today)
                ->first();

            $shift = $user->guru->getShiftForDate($now);
            $jadwalMasuk = $shift?->jam_masuk ?? '07:00:00';
            $jadwalPulang = $shift?->jam_pulang ?? '15:30:00';
            $shiftName = $shift?->nama_shift ?? 'Reguler Pagi';

            return response()->json([
                'success' => true,
                'data' => [
                    'tanggal' => $today,
                    'jam_masuk' => $absen?->jam_masuk,
                    'jam_pulang' => $absen?->jam_pulang,
                    'jadwal_masuk' => $jadwalMasuk,
                    'jadwal_pulang' => $jadwalPulang,
                    'shift_name' => $shiftName,
                    'status_kehadiran' => $absen?->status_kehadiran ?? ($absen?->status ?? 'Belum Absen'),
                    'is_checked_in' => !empty($absen?->jam_masuk),
                    'is_checked_out' => !empty($absen?->jam_pulang),
                    'is_wali_kelas' => $isWaliKelas,
                    'kelas_name' => $kelasWali ? ($kelasWali->nama_kelas ?? $kelasWali->nama) : null,
                    'kelas_id' => $kelasWali?->id,
                    'wali_kelases' => $waliKelases,
                    'geofence_enabled' => $isMobileAttendanceEnabled,
                ]
            ]);
        }

        // Fallback
        return response()->json([
            'success' => true,
            'data' => [
                'tanggal' => $today,
                'jam_masuk' => null,
                'jam_pulang' => null,
                'jadwal_masuk' => '07:00:00',
                'jadwal_pulang' => '15:30:00',
                'shift_name' => 'Reguler Pagi',
                'status_kehadiran' => 'Belum Absen',
                'is_checked_in' => false,
                'is_checked_out' => false,
                'is_wali_kelas' => $isWaliKelas,
                'kelas_name' => null,
                'kelas_id' => null,
                'geofence_enabled' => $isMobileAttendanceEnabled,
            ]
        ]);
    }

    /**
     * Calculate distance between two GPS coordinates using Haversine formula (in meters)
     */
    private function calculateDistanceInMeters($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // Radius of Earth in meters

        $latFrom = deg2rad((float) $lat1);
        $lonFrom = deg2rad((float) $lon1);
        $latTo = deg2rad((float) $lat2);
        $lonTo = deg2rad((float) $lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }

    /**
     * Format data sekolah beserta koordinat lokasi latitude, longitude, dan logo untuk respon mobile.
     */
    private function formatSchoolResponse($school, ?int $schoolId = null): ?array
    {
        if (!$school && !$schoolId) {
            return null;
        }

        $id = $school?->id ?? $schoolId;

        if (!$school instanceof \App\Models\School && $id > 0) {
            $school = \App\Models\School::find($id);
        }

        $schoolLat = Setting::where('school_id', $id)->where('setting_key', 'school_latitude')->value('setting_value');
        if ($schoolLat === null && $id > 0) {
            $schoolLat = Setting::where('school_id', 0)->where('setting_key', 'school_latitude')->value('setting_value');
        }

        $schoolLng = Setting::where('school_id', $id)->where('setting_key', 'school_longitude')->value('setting_value');
        if ($schoolLng === null && $id > 0) {
            $schoolLng = Setting::where('school_id', 0)->where('setting_key', 'school_longitude')->value('setting_value');
        }

        $radiusVal = Setting::where('school_id', $id)->where('setting_key', 'geofence_radius')->value('setting_value');
        if ($radiusVal === null && $id > 0) {
            $radiusVal = Setting::where('school_id', 0)->where('setting_key', 'geofence_radius')->value('setting_value');
        }
        $radius = (float) ($radiusVal ?? 100);
        $isGeoEnabled = $this->isGeofenceEnabled($id);

        $lat = $schoolLat !== null ? (float) $schoolLat : null;
        $long = $schoolLng !== null ? (float) $schoolLng : null;

        $logoUrl = null;
        if ($school instanceof \App\Models\School) {
            $logoUrl = $school->logo_url;
        } else {
            $logoFilename = Setting::where('school_id', $id)->where('setting_key', 'logo_filename')->value('setting_value')
                ?? Setting::where('school_id', 0)->where('setting_key', 'logo_filename')->value('setting_value');

            if ($logoFilename) {
                if (\Illuminate\Support\Str::startsWith($logoFilename, ['http://', 'https://'])) {
                    $logoUrl = $logoFilename;
                } else {
                    $cleanLogo = ltrim(str_replace(['\\', '/'], '/', $logoFilename), '/');
                    $logoUrl = asset('storage/' . $cleanLogo);
                }
            } else {
                $logoUrl = asset('images/logo/logo.svg');
            }
        }

        $licenseService = app(\App\Services\LicenseService::class);
        $dokKbmEnabled = $licenseService->isDokKbmFeatureEnabled($school instanceof \App\Models\School ? $school : null);
        $storageCfg = config('services.storage_server', []);

        return [
            'id' => $id,
            'name' => $school?->name ?? 'Sekolah',
            'domain' => $school?->domain ?? null,
            'logo_url' => $logoUrl,
            'latitude' => $lat,
            'longitude' => $long,
            'radius' => $radius,
            'geofence_enabled' => $isGeoEnabled,
            'dok_kbm_enabled' => $dokKbmEnabled,
            'storage_server' => [
                'enabled' => $dokKbmEnabled,
                'url' => $storageCfg['url'] ?? 'https://storage.jagattech.my.id',
                'api_key' => $dokKbmEnabled ? ($storageCfg['key'] ?? '') : '',
                'folder' => $storageCfg['folder'] ?? 'kbm_docs',
            ],
        ];
    }

    /**
     * Check if geofence is enabled for school
     */
    private function isGeofenceEnabled($schoolId): bool
    {
        $schoolId = $schoolId ?? 0;
        $enabled = Setting::where('school_id', $schoolId)->where('setting_key', 'geofence_enabled')->value('setting_value') ?? 'false';

        // Fallback to global setting if school setting not found
        if ($enabled === 'false' && $schoolId > 0) {
            $enabled = Setting::where('school_id', 0)->where('setting_key', 'geofence_enabled')->value('setting_value') ?? 'false';
        }

        return in_array(strtolower((string)$enabled), ['true', '1', 'yes'], true);
    }

    /**
     * Validate geofence radius.
     * Aturan: Jika geolocation sekolah dinonaktifkan, maka absensi via mobile DITOLAK / NONAKTIF.
     * @return array [bool $isValid, string|null $errorMessage, float|null $distanceMeters, float|null $maxRadius]
     */
    private function validateGeofence($schoolId, $userLat, $userLng): array
    {
        $schoolId = $schoolId ?? 0;
        $isGeoEnabled = $this->isGeofenceEnabled($schoolId);

        // 1. Jika Geolocation dinonaktifkan: tolak absen via mobile
        if (!$isGeoEnabled) {
            return [
                false,
                'Absensi via mobile saat ini dinonaktifkan oleh sekolah (Geolocation tidak aktif). Silakan hubungi pihak sekolah atau gunakan mesin absensi (RFID/Fingerprint).',
                null,
                null
            ];
        }

        // 2. Jika Geolocation aktif, periksa kelengkapan koordinat sekolah
        $schoolLat = Setting::where('school_id', $schoolId)->where('setting_key', 'school_latitude')->value('setting_value');
        $schoolLng = Setting::where('school_id', $schoolId)->where('setting_key', 'school_longitude')->value('setting_value');
        $radius = (float) (Setting::where('school_id', $schoolId)->where('setting_key', 'geofence_radius')->value('setting_value') ?? 100);

        if (!$schoolLat || !$schoolLng) {
            return [
                false,
                'Titik lokasi sekolah belum dikonfigurasi oleh admin. Absensi via mobile tidak dapat diproses.',
                null,
                null
            ];
        }

        // 3. Validasi radius jarak pengguna ke sekolah
        $distance = $this->calculateDistanceInMeters($userLat, $userLng, $schoolLat, $schoolLng);
        $distanceRound = round($distance, 1);

        if ($distance > $radius) {
            $msg = "Anda berada di luar radius area sekolah. Jarak Anda saat ini: {$distanceRound} meter (Radius maksimal: {$radius} meter).";
            return [false, $msg, $distanceRound, $radius];
        }

        return [true, null, $distanceRound, $radius];
    }

    public function getGeofenceSetting(Request $request)
    {
        $user = $request->user();
        $schoolId = $user?->school_id ?? 0;

        $isGeoEnabled = $this->isGeofenceEnabled($schoolId);
        $schoolLat = Setting::where('school_id', $schoolId)->where('setting_key', 'school_latitude')->value('setting_value');
        $schoolLng = Setting::where('school_id', $schoolId)->where('setting_key', 'school_longitude')->value('setting_value');
        $radius = (float) (Setting::where('school_id', $schoolId)->where('setting_key', 'geofence_radius')->value('setting_value') ?? 100);

        return response()->json([
            'success' => true,
            'data' => [
                'geofence_enabled' => $isGeoEnabled,
                'is_mobile_attendance_enabled' => $isGeoEnabled,
                'latitude' => $schoolLat ? (float) $schoolLat : null,
                'longitude' => $schoolLng ? (float) $schoolLng : null,
                'radius_meters' => $radius,
                'message' => $isGeoEnabled 
                    ? 'Geolocation aktif. Absensi via mobile diizinkan dalam radius sekolah.' 
                    : 'Geolocation dinonaktifkan oleh sekolah. Absensi via mobile ditolak/nonaktif.',
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
        $schoolId = $user?->school_id ?? 0;
        $schoolTz = Setting::where('school_id', $schoolId)->where('setting_key', 'timezone')->value('setting_value') ?: config('app.timezone', 'Asia/Jakarta');
        $now = Carbon::now($schoolTz);
        $today = $now->format('Y-m-d');
        $nowTime = $now->format('H:i:s');

        // 1. Geofence Radius Validation
        [$isValidGeo, $geoError, $distance, $maxRadius] = $this->validateGeofence($schoolId, $request->latitude, $request->longitude);
        if (!$isValidGeo) {
            ApiLog::create([
                'school_id' => $schoolId,
                'api_key' => 'MOBILE_APP',
                'action' => 'mobile_checkin_geofence_rejected',
                'uid' => $user?->username ?? $user?->email,
                'success' => false,
                'message' => "Absen Ditolak (Di Luar Radius): {$user?->full_name} ({$distance}m > {$maxRadius}m)",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent() ?? 'Android Mobile App',
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $geoError,
                'data' => [
                    'distance_meters' => $distance,
                    'max_radius_meters' => $maxRadius
                ]
            ], 422);
        }

        // 2. Face Recognition & Liveness Verification
        $studentUser = $user?->student ?? Siswa::where('user_id', $user?->id)->orWhere('nis', str_replace('siswa_', '', $user?->username ?? ''))->first();
        $teacherUser = $user?->guru ?? Guru::where('user_id', $user?->id)->first();
        $faceData = UserFaceData::where(function ($q) use ($user, $studentUser, $teacherUser) {
            $q->where('user_id', $user?->id);
            if ($studentUser) {
                $q->orWhere('siswa_id', $studentUser->id);
            }
            if ($teacherUser) {
                $q->orWhere('guru_id', $teacherUser->id);
            }
        })->first();

        $faceSimilarity = null;
        $isFaceMatched = false;
        $isFaceEnabled = app(\App\Services\LicenseService::class)->isFaceFeatureEnabled($user?->school);

        if ($faceData && $isFaceEnabled) {
            if (!$request->hasFile('photo')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Absen ditolak: Foto selfie verifikasi wajah wajib disertakan saat absen masuk.'
                ], 422);
            }

            $compareResult = FaceRecognitionService::compareFace($faceData->face_embedding, $request->file('photo'));

            if (!$compareResult['success']) {
                ApiLog::create([
                    'school_id' => $schoolId,
                    'api_key' => 'MOBILE_APP',
                    'action' => 'mobile_checkin_face_failed',
                    'uid' => $user?->username ?? $user?->email,
                    'success' => false,
                    'message' => "Verifikasi Wajah Gagal: " . ($compareResult['message'] ?? 'Wajah tidak terdeteksi'),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent() ?? 'Android Mobile App',
                    'created_at' => now(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Absen ditolak: ' . ($compareResult['message'] ?? 'Wajah tidak terdeteksi saat absen')
                ], 400);
            }

            $faceSimilarity = $compareResult['similarity'];
            $isFaceMatched = (bool) $compareResult['is_match'];

            if (!$isFaceMatched) {
                $simPercent = round($faceSimilarity * 100);
                ApiLog::create([
                    'school_id' => $schoolId,
                    'api_key' => 'MOBILE_APP',
                    'action' => 'mobile_checkin_face_mismatch',
                    'uid' => $user?->username ?? $user?->email,
                    'success' => false,
                    'message' => "Wajah Tidak Cocok: {$user?->full_name} (Kemiripan: {$simPercent}%)",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent() ?? 'Android Mobile App',
                    'created_at' => now(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => "Absen ditolak: Wajah Anda tidak cocok dengan foto terdaftar (Kemiripan: {$simPercent}%)."
                ], 400);
            }
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('absensi_photos', 'public');
        }

        // Case 1: Guru / Admin / Wali Kelas
        if ($user && ($user->role === 'guru' || $user->guru) && $user->guru) {
            $teacher = $user->guru;

            // CEK SHIFT GURU
            $shift = $teacher->getShiftForDate($now);
            if (!$shift) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada jadwal shift kerja aktif untuk Anda hari ini.'
                ], 422);
            }

            // CEK RENTANG JAM SCAN MASUK SHIFT
            if (!$shift->isInCheckInWindow($nowTime)) {
                $windowStr = ($shift->awal_absen_masuk && $shift->akhir_absen_masuk)
                    ? Carbon::parse($shift->awal_absen_masuk)->format('H:i') . ' - ' . Carbon::parse($shift->akhir_absen_masuk)->format('H:i')
                    : '-';

                $msg = $nowTime < $shift->awal_absen_masuk
                    ? "Absen masuk belum dibuka (Jadwal masuk: {$windowStr})"
                    : "Batas waktu absen masuk sudah ditutup (Jadwal masuk: {$windowStr})";

                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'window' => $windowStr
                ], 422);
            }

            // Cek apakah sudah absen masuk hari ini
            $existingAbsen = AbsensiGuru::where('guru_id', $teacher->id)
                ->where('tanggal', $today)
                ->whereNull('jadwal_pelajaran_id')
                ->first();

            if ($existingAbsen && $existingAbsen->jam_masuk) {
                return response()->json([
                    'success' => true,
                    'message' => 'Anda sudah melakukan absen masuk hari ini',
                    'data' => [
                        'tanggal' => $today,
                        'jam_masuk' => $existingAbsen->jam_masuk,
                        'jam_pulang' => $existingAbsen->jam_pulang,
                        'status_kehadiran' => $existingAbsen->status_kehadiran,
                        'is_checked_in' => true,
                        'is_checked_out' => !empty($existingAbsen->jam_pulang)
                    ]
                ]);
            }

            // Hitung Keterlambatan
            $isLate = $shift->isLate($nowTime);
            $menitTerlambat = $isLate ? $shift->calculateLateMinutes($nowTime) : 0;
            $status = $isLate ? 'Terlambat' : 'Hadir';
            $statusKehadiran = $isLate ? 'terlambat' : 'tepat_waktu';
            $keterangan = $isLate
                ? "Terlambat {$menitTerlambat} m ({$shift->nama_shift}) - Mobile App"
                : "Tepat Waktu ({$shift->nama_shift}) - Mobile App";

            $absen = AbsensiGuru::updateOrCreate(
                [
                    'guru_id' => $teacher->id,
                    'tanggal' => $today,
                    'jadwal_pelajaran_id' => null,
                ],
                [
                    'school_id' => $schoolId,
                    'shift_id' => $shift->id,
                    'waktu_hadir' => $now,
                    'jam_masuk' => $nowTime,
                    'menit_terlambat' => $menitTerlambat,
                    'status_kehadiran' => $statusKehadiran,
                    'status' => $status,
                    'keterangan' => $keterangan,
                    'photo_checkin_path' => $photoPath,
                    'face_similarity_score' => $faceSimilarity,
                    'is_face_matched' => $isFaceMatched,
                    'created_at' => $now
                ]
            );

            // Send FCM Push Notification (Pop-up banner)
            try {
                FcmNotificationService::sendToGuru($teacher, "Presensi Masuk Tercatat", "Halo {$teacher->nama}, absensi masuk Anda berhasil dicatat pukul {$now->format('H:i')} (Status: {$status}).", [
                    'type' => 'checkin_guru',
                    'status' => $status,
                    'jam' => $now->format('H:i'),
                ]);
            } catch (\Throwable $e) {
                // Ignore FCM failure
            }

            ApiLog::create([
                'school_id' => $schoolId,
                'api_key' => 'MOBILE_APP',
                'action' => 'mobile_checkin',
                'uid' => $teacher->nip ?? ($user->username ?? $user->email),
                'success' => true,
                'message' => "Absen Masuk: {$user->full_name} ({$shift->nama_shift} - {$status})",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent() ?? 'Android Mobile App',
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Absen masuk berhasil dicatat ({$status})",
                'data' => [
                    'tanggal' => $today,
                    'jam_masuk' => $absen->jam_masuk,
                    'jam_pulang' => $absen->jam_pulang,
                    'status' => $status,
                    'status_kehadiran' => $statusKehadiran,
                    'menit_terlambat' => $menitTerlambat,
                    'shift' => $shift->nama_shift,
                    'face_similarity_score' => $faceSimilarity,
                    'is_face_matched' => $isFaceMatched,
                    'photo_checkin_url' => $photoPath ? asset('storage/' . $photoPath) : null,
                    'is_checked_in' => true,
                    'is_checked_out' => !empty($absen->jam_pulang)
                ]
            ]);
        }

        // Case 2: Siswa (Student)
        if ($user && ($user->student || in_array($user->role, ['student', 'siswa']))) {
            $student = $user->student ?? Siswa::where('user_id', $user->id)->orWhere('nis', str_replace('siswa_', '', $user->username))->first();
            if ($student) {
                // CHECK: Kelas Aktif
                if ($student->kelas && !$student->kelas->is_active_attendance) {
                    return response()->json(['success' => false, 'message' => 'Absensi dinonaktifkan untuk kelas Anda.'], 422);
                }

                // CHECK: Jadwal Harian Sekolah
                $indexHari = (int) $now->format('N'); // 1 (Senin) - 7 (Minggu)
                $jadwal = Jadwal::where('index_hari', $indexHari)
                    ->where('is_active', 1)
                    ->where('school_id', $schoolId)
                    ->first();

                if (!$jadwal) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Hari ini adalah hari libur sekolah (tidak ada jadwal aktif).'
                    ], 422);
                }

                // Cek Rentang Jam Masuk
                $awalAbsenMasuk = $jadwal->awal_absen_masuk;
                $akhirAbsenMasuk = $jadwal->akhir_absen_masuk;
                $jamMasuk = $jadwal->jam_masuk;

                if ($nowTime < $awalAbsenMasuk) {
                    $jamBuka = Carbon::parse($awalAbsenMasuk)->format('H:i');
                    return response()->json([
                        'success' => false,
                        'message' => "Absen masuk belum dibuka (Dibuka pukul {$jamBuka})"
                    ], 422);
                }

                if ($nowTime > $akhirAbsenMasuk) {
                    $jamTutup = Carbon::parse($akhirAbsenMasuk)->format('H:i');
                    return response()->json([
                        'success' => false,
                        'message' => "Batas waktu absen masuk sudah ditutup (Pukul {$jamTutup})"
                    ], 422);
                }

                $att = Attendance::where('student_id', $student->id)->where('tanggal', $today)->first();
                if ($att && $att->jam_masuk) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Anda sudah melakukan absen masuk hari ini',
                        'data' => [
                            'tanggal' => $today,
                            'jam_masuk' => $att->jam_masuk,
                            'jam_pulang' => $att->jam_pulang,
                            'status_kehadiran' => $att->status,
                            'is_checked_in' => true,
                            'is_checked_out' => !empty($att->jam_pulang)
                        ]
                    ]);
                }

                // Hitung Terlambat
                $status = 'H';
                $keterangan = "Tepat Waktu - Mobile App";
                if ($nowTime > $jamMasuk) {
                    $status = 'T';
                    $diffSeconds = Carbon::parse($today . ' ' . $nowTime)->diffInSeconds(Carbon::parse($today . ' ' . $jamMasuk));
                    $jam = floor($diffSeconds / 3600);
                    $menit = floor(($diffSeconds % 3600) / 60);
                    $durasi = $jam > 0 ? "{$jam} jam {$menit} menit" : "{$menit} menit";
                    $keterangan = "Telat {$durasi} - Mobile App";
                }

                $att = Attendance::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'tanggal' => $today,
                    ],
                    [
                        'jam_masuk' => $nowTime,
                        'status' => $status,
                        'keterangan' => $keterangan,
                        'photo_checkin_path' => $photoPath,
                        'face_similarity_score' => $faceSimilarity,
                        'is_face_matched' => $isFaceMatched,
                        'created_at' => $now
                    ]
                );

                // Send FCM Push Notification (Pop-up banner)
                try {
                    $readableStatus = $status === 'T' ? 'Terlambat' : 'Hadir';
                    FcmNotificationService::sendToSiswa($student, "Presensi Masuk Tercatat", "Halo {$student->nama}, absensi masuk Anda berhasil dicatat pukul {$now->format('H:i')} (Status: {$readableStatus}).", [
                        'type' => 'checkin_siswa',
                        'status' => $readableStatus,
                        'jam' => $now->format('H:i'),
                    ]);
                } catch (\Throwable $e) {
                    // Ignore FCM failure
                }

                ApiLog::create([
                    'school_id' => $schoolId,
                    'api_key' => 'MOBILE_APP',
                    'action' => 'mobile_checkin_student',
                    'uid' => $student->nis,
                    'success' => true,
                    'message' => "Absen Masuk Siswa: {$student->nama} ({$status} - {$keterangan})",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent() ?? 'Android Mobile App',
                    'created_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Absen masuk berhasil dicatat (" . ($status === 'T' ? 'Terlambat' : 'Hadir') . ")",
                    'data' => [
                        'tanggal' => $today,
                        'jam_masuk' => $att->jam_masuk,
                        'jam_pulang' => $att->jam_pulang,
                        'status' => $status,
                        'status_kehadiran' => $status === 'T' ? 'terlambat' : 'hadir',
                        'keterangan' => $keterangan,
                        'face_similarity_score' => $faceSimilarity,
                        'is_face_matched' => $isFaceMatched,
                        'photo_checkin_url' => $photoPath ? asset('storage/' . $photoPath) : null,
                        'is_checked_in' => true,
                        'is_checked_out' => !empty($att->jam_pulang)
                    ]
                ]);
            }
        }

        ApiLog::create([
            'school_id' => $schoolId,
            'api_key' => 'MOBILE_APP',
            'action' => 'mobile_checkin_failed',
            'uid' => $user?->username ?? $user?->email,
            'success' => false,
            'message' => 'Absen Masuk Gagal: Role tidak didukung untuk absen',
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
        $schoolId = $user?->school_id ?? 0;
        $schoolTz = Setting::where('school_id', $schoolId)->where('setting_key', 'timezone')->value('setting_value') ?: config('app.timezone', 'Asia/Jakarta');
        $now = Carbon::now($schoolTz);
        $today = $now->format('Y-m-d');
        $nowTime = $now->format('H:i:s');

        // 1. Geofence Radius Validation
        [$isValidGeo, $geoError, $distance, $maxRadius] = $this->validateGeofence($schoolId, $request->latitude, $request->longitude);
        if (!$isValidGeo) {
            ApiLog::create([
                'school_id' => $schoolId,
                'api_key' => 'MOBILE_APP',
                'action' => 'mobile_checkout_geofence_rejected',
                'uid' => $user?->username ?? $user?->email,
                'success' => false,
                'message' => "Absen Pulang Ditolak (Di Luar Radius): {$user?->full_name} ({$distance}m > {$maxRadius}m)",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent() ?? 'Android Mobile App',
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $geoError,
                'data' => [
                    'distance_meters' => $distance,
                    'max_radius_meters' => $maxRadius
                ]
            ], 422);
        }

        // Case 1: Guru
        if ($user && ($user->role === 'guru' || $user->guru) && $user->guru) {
            $teacher = $user->guru;

            $absen = AbsensiGuru::where('guru_id', $teacher->id)
                ->where('tanggal', $today)
                ->whereNull('jadwal_pelajaran_id')
                ->first();

            if (!$absen || !$absen->jam_masuk) {
                return response()->json(['success' => false, 'message' => 'Anda belum melakukan absen masuk hari ini'], 422);
            }

            // CEK SHIFT & RENTANG JAM PULANG
            $shift = $teacher->getShiftForDate($now);
            if ($shift && !$shift->isInCheckOutWindow($nowTime)) {
                $windowStr = ($shift->awal_absen_pulang && $shift->akhir_absen_pulang)
                    ? Carbon::parse($shift->awal_absen_pulang)->format('H:i') . ' - ' . Carbon::parse($shift->akhir_absen_pulang)->format('H:i')
                    : '-';

                $msg = $nowTime < $shift->awal_absen_pulang
                    ? "Belum masuk waktu absen pulang (Jadwal pulang: {$windowStr})"
                    : "Batas waktu absen pulang sudah ditutup (Jadwal pulang: {$windowStr})";

                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'window' => $windowStr
                ], 422);
            }

            $absen->update([
                'jam_pulang' => $nowTime,
                'updated_at' => $now
            ]);

            // Send FCM Push Notification (Pop-up banner)
            try {
                FcmNotificationService::sendToGuru($teacher, "Presensi Pulang Tercatat", "Halo {$teacher->nama}, absensi pulang Anda berhasil dicatat pukul {$now->format('H:i')}.", [
                    'type' => 'checkout_guru',
                    'jam' => $now->format('H:i'),
                ]);
            } catch (\Throwable $e) {
                // Ignore FCM failure
            }

            ApiLog::create([
                'school_id' => $schoolId,
                'api_key' => 'MOBILE_APP',
                'action' => 'mobile_checkout',
                'uid' => $teacher->nip ?? ($user->username ?? $user->email),
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

        // Case 2: Siswa (Student)
        if ($user && ($user->student || in_array($user->role, ['student', 'siswa']))) {
            $student = $user->student ?? Siswa::where('user_id', $user->id)->orWhere('nis', str_replace('siswa_', '', $user->username))->first();
            if ($student) {
                $att = Attendance::where('student_id', $student->id)->where('tanggal', $today)->first();
                if (!$att || !$att->jam_masuk) {
                    return response()->json(['success' => false, 'message' => 'Anda belum melakukan absen masuk hari ini'], 422);
                }

                // CHECK: Jadwal Harian Pulang Siswa
                $indexHari = (int) $now->format('N');
                $jadwal = Jadwal::where('index_hari', $indexHari)
                    ->where('is_active', 1)
                    ->where('school_id', $schoolId)
                    ->first();

                if ($jadwal) {
                    $jamPulang = $jadwal->jam_pulang;
                    $akhirAbsenPulang = $jadwal->akhir_absen_pulang;

                    if ($nowTime < $jamPulang) {
                        $jamBuka = Carbon::parse($jamPulang)->format('H:i');
                        return response()->json([
                            'success' => false,
                            'message' => "Belum masuk waktu absen pulang (Dibuka pukul {$jamBuka})"
                        ], 422);
                    }

                    if ($nowTime > $akhirAbsenPulang) {
                        $jamTutup = Carbon::parse($akhirAbsenPulang)->format('H:i');
                        return response()->json([
                            'success' => false,
                            'message' => "Batas waktu absen pulang sudah ditutup (Pukul {$jamTutup})"
                        ], 422);
                    }
                }

                $masuk = Carbon::parse($att->tanggal . ' ' . $att->jam_masuk);
                $totalSeconds = abs($masuk->diffInSeconds($now, false));

                $newStatus = $att->status;
                $newKeterangan = $att->keterangan;
                if ($att->status === 'B') {
                    $newStatus = ($att->jam_masuk > ($jadwal->jam_masuk ?? '07:30')) ? 'T' : 'H';
                    $newKeterangan = trim(str_replace('[Auto: Tidak Absen Pulang]', '', $newKeterangan ?? ''));
                }

                $att->update([
                    'jam_pulang' => $nowTime,
                    'total_seconds' => $totalSeconds,
                    'status' => $newStatus,
                    'keterangan' => $newKeterangan,
                    'updated_at' => $now
                ]);

                // Send FCM Push Notification (Pop-up banner)
                try {
                    FcmNotificationService::sendToSiswa($student, "Presensi Pulang Tercatat", "Halo {$student->nama}, absensi pulang Anda berhasil dicatat pukul {$now->format('H:i')}.", [
                        'type' => 'checkout_siswa',
                        'jam' => $now->format('H:i'),
                    ]);
                } catch (\Throwable $e) {
                    // Ignore FCM failure
                }

                ApiLog::create([
                    'school_id' => $schoolId,
                    'api_key' => 'MOBILE_APP',
                    'action' => 'mobile_checkout_student',
                    'uid' => $student->nis,
                    'success' => true,
                    'message' => "Absen Pulang Siswa: {$student->nama} (Lat: {$request->latitude}, Lng: {$request->longitude})",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent() ?? 'Android Mobile App',
                    'created_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Absen pulang siswa berhasil dicatat',
                    'data' => [
                        'tanggal' => $today,
                        'jam_masuk' => $att->jam_masuk,
                        'jam_pulang' => $att->jam_pulang,
                        'status' => $newStatus,
                        'status_kehadiran' => $newStatus === 'T' ? 'terlambat' : 'hadir',
                        'is_checked_in' => true,
                        'is_checked_out' => true
                    ]
                ]);
            }
        }

        ApiLog::create([
            'school_id' => $schoolId,
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

        if ($user && ($user->student || in_array($user->role, ['student', 'siswa']))) {
            $student = $user->student ?? Siswa::where('user_id', $user->id)->orWhere('nis', str_replace('siswa_', '', $user->username))->first();
            if ($student) {
                $query = Attendance::where('student_id', $student->id);

                if ($startDate && $endDate) {
                    try {
                        $startFormatted = Carbon::parse($startDate)->format('Y-m-d');
                        $endFormatted = Carbon::parse($endDate)->format('Y-m-d');
                        $query->whereBetween('tanggal', [$startFormatted, $endFormatted]);
                    } catch (\Exception $e) {
                        $query->whereBetween('tanggal', [$startDate, $endDate]);
                    }
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
                            'status_kehadiran' => $item->status,
                            'keterangan' => $item->keterangan
                        ];
                    });

                return response()->json(['success' => true, 'data' => $history]);
            }
        }

        if ($user && (in_array($user->role, ['guru', 'teacher', 'wali_kelas', 'admin']) || $user->role === 'guru') && $user->guru) {
            $query = AbsensiGuru::where('guru_id', $user->guru->id)
                ->whereNull('jadwal_pelajaran_id');

            // Jika ada filter range tanggal
            if ($startDate && $endDate) {
                try {
                    $startFormatted = Carbon::parse($startDate)->format('Y-m-d');
                    $endFormatted = Carbon::parse($endDate)->format('Y-m-d');
                    $query->whereBetween('tanggal', [$startFormatted, $endFormatted]);
                } catch (\Exception $e) {
                    $query->whereBetween('tanggal', [$startDate, $endDate]);
                }
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
        $months = (int) $request->query('months', 1);
        $startDateParam = $request->query('start_date');
        $endDateParam = $request->query('end_date');

        // Format tanggal ke Y-m-d agar cocok dengan kolom tanggal di database MySQL
        if ($startDateParam && $endDateParam) {
            try {
                $startDate = Carbon::parse($startDateParam)->format('Y-m-d');
                $endDate = Carbon::parse($endDateParam)->format('Y-m-d');
            } catch (\Exception $e) {
                $startDate = Carbon::now()->subDays(30 * $months)->format('Y-m-d');
                $endDate = Carbon::now()->format('Y-m-d');
            }
        } else {
            $startDate = Carbon::now()->subDays(30 * $months)->format('Y-m-d');
            $endDate = Carbon::now()->format('Y-m-d');
        }

        // Rekap Siswa
        $student = $user ? ($user->student ?? Siswa::where('user_id', $user->id)->orWhere('nis', str_replace('siswa_', '', $user->username))->first()) : null;
        if ($student) {
            $allStats = Attendance::where('student_id', $student->id)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->get();

            $totalCatatan = $allStats->count();
            $totalHadir = $allStats->whereIn('status', ['H', 'Hadir'])->count();
            $totalTerlambat = $allStats->whereIn('status', ['T', 'Terlambat'])->count();
            $totalIzin = $allStats->whereIn('status', ['I', 'Izin'])->count();
            $totalSakit = $allStats->whereIn('status', ['S', 'Sakit'])->count();
            $totalAlpha = $allStats->whereIn('status', ['A', 'Alpha', 'B', 'Bolos', 'Tidak Hadir'])->count();

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

        // Rekap Guru / Karyawan
        if ($user && (in_array($user->role, ['guru', 'teacher', 'wali_kelas', 'admin']) || $user->guru) && $user->guru) {
            $allStats = AbsensiGuru::where('guru_id', $user->guru->id)
                ->whereNull('jadwal_pelajaran_id')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->get();

            $totalCatatan = $allStats->count();
            $totalHadir = $allStats->where('status', 'Hadir')->count();
            $totalTerlambat = $allStats->where('status', 'Terlambat')->count();
            $totalIzin = $allStats->where('status', 'Izin')->count();
            $totalSakit = $allStats->where('status', 'Sakit')->count();
            $totalAlpha = $allStats->whereIn('status', ['Tidak Hadir', 'Alpha'])->count();

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

        $allowedRoles = ['guru', 'teacher', 'wali_kelas', 'waka', 'kurikulum', 'admin', 'super_admin'];
        $userRole = strtolower($user?->role ?? '');
        $isStaff = in_array($userRole, $allowedRoles) || !empty($user?->guru);

        if (!$user || !$isStaff) {
            return response()->json(['success' => false, 'message' => 'Akses khusus dewan guru dan staf sekolah'], 403);
        }

        $isWakaOrAdmin = in_array($userRole, ['admin', 'super_admin', 'waka', 'kurikulum'])
            || str_contains($userRole, 'waka')
            || str_contains($userRole, 'kurikulum');

        $guru = $user->guru;
        $requestedKelasId = $request->query('kelas_id');
        $schoolId = $user->school_id ?? ($guru?->school_id ?? null);

        // Ambil daftar kelas yang diwalikan oleh guru (1 atau 2 kelas)
        $waliKelasList = collect();
        if ($guru) {
            $waliKelasList = Kelas::where(function ($q) use ($guru) {
                $q->where('wali_kelas_id', $guru->id)
                  ->orWhere('wali_kelas_2_id', $guru->id);
            })
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->get();
        }

        $kelas = null;

        if ($isWakaOrAdmin) {
            // Waka Kurikulum & Admin: Bebas mengambil rekap untuk seluruh kelas di sekolah
            if ($requestedKelasId) {
                $kelasQuery = Kelas::where('id', $requestedKelasId);
                if ($schoolId) {
                    $kelasQuery->where('school_id', $schoolId);
                }
                $kelas = $kelasQuery->first();
            } else {
                $kelas = Kelas::when($schoolId, fn($q) => $q->where('school_id', $schoolId))->orderBy('nama_kelas', 'asc')->first();
            }
        } else {
            // Walas (Wali Kelas): HANYA kelas yang diwalikannya (1 atau 2 kelas miliknya)
            if ($waliKelasList->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak: Anda belum ditugaskan sebagai wali kelas untuk kelas manapun.'
                ], 403);
            }

            if ($requestedKelasId) {
                // Pastikan requestedKelasId adalah kelas yang memang diampu guru tersebut
                $kelas = $waliKelasList->firstWhere('id', (int)$requestedKelasId);
                if (!$kelas) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Akses ditolak: Anda hanya dapat mengakses rekap kelas yang Anda walikan.'
                    ], 403);
                }
            } else {
                // Default ke kelas wali pertama
                $kelas = $waliKelasList->first();
            }
        }

        if (!$kelas) {
            return response()->json(['success' => false, 'message' => 'Kelas tidak ditemukan'], 404);
        }

        $startDate = $request->query('start_date', Carbon::today()->format('Y-m-d'));
        $endDate = $request->query('end_date', Carbon::today()->format('Y-m-d'));

        try {
            $startDate = Carbon::parse($startDate)->format('Y-m-d');
            $endDate = Carbon::parse($endDate)->format('Y-m-d');
        } catch (\Exception $e) {
            // Keep default
        }

        // Ambil daftar siswa di kelas tersebut
        $siswaList = Siswa::where('kelas_id', $kelas->id)->orderBy('nama', 'asc')->get();
        $siswaIds = $siswaList->pluck('id');

        $allAttendances = Attendance::whereIn('student_id', $siswaIds)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->get()
            ->groupBy('student_id');

        $studentsRecap = $siswaList->map(function ($siswa) use ($allAttendances) {
            $attendances = $allAttendances->get($siswa->id, collect());

            return [
                'student_id' => $siswa->id,
                'nama' => $siswa->nama,
                'nis' => $siswa->nis,
                'hadir' => $attendances->whereIn('status', ['H', 'Hadir'])->count(),
                'terlambat' => $attendances->whereIn('status', ['T', 'Terlambat'])->count(),
                'izin' => $attendances->whereIn('status', ['I', 'Izin'])->count(),
                'sakit' => $attendances->whereIn('status', ['S', 'Sakit'])->count(),
                'alpha' => $attendances->whereIn('status', ['A', 'Alpha', 'B', 'Bolos', 'Tidak Hadir'])->count(),
            ];
        });

        $namaKelas = $kelas->nama_kelas ?? $kelas->nama;
        $isWali = $guru && ($kelas->wali_kelas_id == $guru->id || $kelas->wali_kelas_2_id == $guru->id);
        $waliLabel = $isWali ? " (Wali Kelas)" : "";
        $formattedKelasName = str_starts_with($namaKelas, 'Kelas') ? "{$namaKelas}{$waliLabel}" : "Kelas {$namaKelas}{$waliLabel}";

        return response()->json([
            'success' => true,
            'data' => [
                'kelas_id' => $kelas->id,
                'kelas_name' => $formattedKelasName,
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

        $allowedRoles = ['guru', 'teacher', 'wali_kelas', 'waka', 'kurikulum', 'admin', 'super_admin'];
        $userRole = strtolower($user?->role ?? '');
        $isStaff = in_array($userRole, $allowedRoles) || !empty($user?->guru);

        if (!$user || !$isStaff) {
            return response()->json(['success' => false, 'message' => 'Akses khusus dewan guru dan staf sekolah'], 403);
        }

        $isWakaOrAdmin = in_array($userRole, ['admin', 'super_admin', 'waka', 'kurikulum'])
            || str_contains($userRole, 'waka')
            || str_contains($userRole, 'kurikulum');

        $rawTanggal = $request->query('tanggal', Carbon::today()->format('Y-m-d'));
        try {
            $tanggal = Carbon::parse($rawTanggal)->format('Y-m-d');
        } catch (\Exception $e) {
            $tanggal = Carbon::today()->format('Y-m-d');
        }

        $kelasId = $request->query('kelas_id');
        $queryStr = trim($request->query('q', ''));
        $hasSearchQuery = strlen($queryStr) >= 2;

        $schoolId = $user->school_id ?? ($user->guru ? $user->guru->school_id : null);

        // Ambil daftar ID kelas yang diwalikan guru (1 atau 2 kelas)
        $waliKelasIds = [];
        if ($user->guru) {
            $waliKelasIds = Kelas::where(function ($q) use ($user) {
                $q->where('wali_kelas_id', $user->guru->id)
                  ->orWhere('wali_kelas_2_id', $user->guru->id);
            })
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->pluck('id')
            ->all();
        }

        $isWalas = !empty($waliKelasIds);

        // Jika tidak ada kelas_id DAN tidak ada kata kunci pencarian
        if (empty($kelasId) && !$hasSearchQuery) {
            if ($isWalas) {
                // Untuk Walas, default ke kelas wali pertama jika belum memilih kelas
                $kelasId = $waliKelasIds[0];
            } else {
                // Untuk Waka Kurikulum dan Guru non-walas, default kosong (harus pilih kelas dulu)
                return response()->json(['success' => true, 'data' => []]);
            }
        }

        $siswaQuery = Siswa::with('kelas');
        if ($schoolId) {
            $siswaQuery->where('school_id', $schoolId);
        }

        if ($isWakaOrAdmin) {
            // Waka Kurikulum & Admin: Bisa mencari ke seluruh siswa di sekolah
            if (!empty($kelasId) && $kelasId !== 'all' && $kelasId !== 'semua' && $kelasId !== '0') {
                $strictClass = $request->boolean('strict_class') || $request->boolean('filter_by_class');
                // Jika sedang mencari dan tidak strict_class, biarkan cari global satu sekolah
                if (!$hasSearchQuery || $strictClass) {
                    $siswaQuery->where('kelas_id', (int)$kelasId);
                }
            }
        } else {
            // Walas: WAJIB HANYA DI DALAM KELAS WALINYA (1 ATAU 2 KELAS SAJA)!
            if (!empty($kelasId) && $kelasId !== 'all' && $kelasId !== 'semua' && $kelasId !== '0') {
                // Jika walas memilih 1 kelas spesifik dari 2 kelas walinya
                $siswaQuery->where('kelas_id', (int)$kelasId);
            } else {
                // Jika walas punya 2 kelas dan mencari tanpa filter kelas tunggal, cari di 2 kelas tersebut
                $siswaQuery->whereIn('kelas_id', $waliKelasIds);
            }
        }

        if ($hasSearchQuery) {
            $siswaQuery->where(function ($q) use ($queryStr) {
                $q->where('nama', 'like', "%{$queryStr}%")
                  ->orWhere('nis', 'like', "%{$queryStr}%");
                if (\Illuminate\Support\Facades\Schema::hasColumn('siswa', 'nisn')) {
                    $q->orWhere('nisn', 'like', "%{$queryStr}%");
                }
            });
        }

        $siswaList = $siswaQuery->orderBy('nama', 'asc')->get();

        $studentIds = $siswaList->pluck('id');
        $attendances = Attendance::whereIn('student_id', $studentIds)
            ->where('tanggal', $tanggal)
            ->get()
            ->keyBy('student_id');

        $data = $siswaList->map(function ($siswa) use ($attendances) {
            $absen = $attendances->get($siswa->id);

            $kelasName = $siswa->kelas ? ($siswa->kelas->nama_kelas ?? $siswa->kelas->nama) : 'Siswa';
            if ($siswa->kelas && !str_starts_with($kelasName, 'Kelas')) {
                $kelasName = 'Kelas ' . $kelasName;
            }

            return [
                'student_id' => $siswa->id,
                'nama' => $siswa->nama,
                'nis' => $siswa->nis,
                'foto' => $siswa->foto,
                'foto_url' => $siswa->foto_url,
                'kelas_id' => $siswa->kelas_id,
                'kelas_name' => $kelasName,
                'status' => $absen ? $absen->status : 'H',
                'status_kehadiran' => $absen ? $absen->status : 'Belum Absen',
                'keterangan' => $absen ? $absen->keterangan : null,
                'jam_masuk' => $absen?->jam_masuk,
                'jam_pulang' => $absen?->jam_pulang,
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function submitStudentsAttendance(Request $request)
    {
        $user = $request->user();

        $allowedRoles = ['guru', 'teacher', 'wali_kelas', 'waka', 'kurikulum', 'admin', 'super_admin'];
        $userRole = strtolower($user?->role ?? '');
        $isStaff = in_array($userRole, $allowedRoles) || !empty($user?->guru);

        if (!$user || !$isStaff) {
            return response()->json(['success' => false, 'message' => 'Akses khusus dewan guru dan staf sekolah'], 403);
        }

        $request->validate([
            'tanggal' => 'required',
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|integer',
            'attendances.*.status' => 'required|string',
        ]);

        try {
            $tanggal = Carbon::parse($request->tanggal)->format('Y-m-d');
        } catch (\Exception $e) {
            $tanggal = $request->tanggal;
        }

        $schoolId = $user->school_id ?? ($user->guru ? $user->guru->school_id : null);
        $schoolTz = Setting::where('school_id', $schoolId ?? 0)->where('setting_key', 'timezone')->value('setting_value') ?: config('app.timezone', 'Asia/Jakarta');
        $now = Carbon::now($schoolTz);
        $currentTime = $now->format('H:i:s');

        $studentIds = collect($request->attendances)->pluck('student_id')->all();
        $existingRecords = Attendance::whereIn('student_id', $studentIds)
            ->where('tanggal', $tanggal)
            ->get()
            ->keyBy('student_id');

        $count = 0;
        foreach ($request->attendances as $item) {
            $studentId = $item['student_id'];
            $status = $item['status'];
            $isHadir = in_array($status, ['H', 'Hadir', 'T', 'Terlambat']);

            $existing = $existingRecords->get($studentId);
            $jamMasuk = $existing?->jam_masuk;
            if (!$jamMasuk && $isHadir) {
                $jamMasuk = $currentTime;
            } elseif (!$isHadir) {
                $jamMasuk = null;
            }

            Attendance::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'tanggal' => $tanggal
                ],
                [
                    'school_id' => $schoolId,
                    'status' => $status,
                    'keterangan' => $item['keterangan'] ?? null,
                    'jam_masuk' => $jamMasuk,
                    'updated_at' => $now
                ]
            );
            $count++;
        }

        ApiLog::create([
            'school_id' => $schoolId,
            'api_key' => 'MOBILE_APP',
            'action' => 'mobile_bulk_attendance',
            'uid' => $user->guru?->nip ?? $user->username,
            'success' => true,
            'message' => "Input Absensi Siswa Massal oleh {$user->full_name}: {$count} siswa (Tgl: {$tanggal})",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent() ?? 'Android Mobile App',
            'created_at' => $now,
        ]);

        // Send FCM push notification for Alpha and Bolos
        try {
            $targetStudentIds = collect($request->attendances)
                ->filter(fn($it) => in_array($it['status'] ?? '', ['A', 'Alpha', 'B', 'Bolos']))
                ->pluck('student_id')
                ->unique();

            if ($targetStudentIds->isNotEmpty()) {
                $targetStudents = Siswa::whereIn('id', $targetStudentIds)->get()->keyBy('id');
                foreach ($request->attendances as $item) {
                    $st = $item['status'] ?? '';
                    if (!in_array($st, ['A', 'Alpha', 'B', 'Bolos'])) continue;

                    $studentObj = $targetStudents->get($item['student_id']);
                    if (!$studentObj) continue;

                    if (in_array($st, ['A', 'Alpha'])) {
                        FcmNotificationService::sendToSiswa(
                            $studentObj,
                            "Pemberitahuan Tidak Hadir (Alpha)",
                            "Halo {$studentObj->nama}, status absensi Anda untuk tanggal {$tanggal} telah dicatat sebagai Alpha.",
                            [
                                'type' => 'checkin_siswa',
                                'status' => 'Alpha',
                                'jam' => now()->format('H:i'),
                                'tanggal' => $tanggal,
                                'sub_type' => 'attendance_alpha',
                                'category' => 'alpha_siswa',
                            ]
                        );
                    } else {
                        FcmNotificationService::sendToSiswa(
                            $studentObj,
                            "Peringatan Bolos",
                            "Halo {$studentObj->nama}, status absensi Anda untuk tanggal {$tanggal} telah dicatat sebagai Bolos.",
                            [
                                'type' => 'checkout_siswa',
                                'status' => 'Bolos',
                                'jam' => now()->format('H:i'),
                                'tanggal' => $tanggal,
                                'sub_type' => 'attendance_bolos',
                                'category' => 'bolos_siswa',
                            ]
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return response()->json([
            'success' => true,
            'message' => 'Absensi seluruh siswa berhasil disimpan',
            'total_updated' => $count
        ]);
    }

    public function updateSingleStudentAttendance(Request $request)
    {
        $user = $request->user();

        $allowedRoles = ['guru', 'teacher', 'wali_kelas', 'waka', 'kurikulum', 'admin', 'super_admin'];
        $userRole = strtolower($user?->role ?? '');
        $isStaff = in_array($userRole, $allowedRoles) || !empty($user?->guru);

        if (!$user || !$isStaff) {
            return response()->json(['success' => false, 'message' => 'Akses khusus dewan guru dan staf sekolah'], 403);
        }

        $request->validate([
            'tanggal' => 'required',
            'student_id' => 'required|integer',
            'status' => 'required|string',
        ]);

        try {
            $tanggal = Carbon::parse($request->tanggal)->format('Y-m-d');
        } catch (\Exception $e) {
            $tanggal = $request->tanggal;
        }

        $schoolId = $user->school_id ?? ($user->guru ? $user->guru->school_id : null);
        $schoolTz = Setting::where('school_id', $schoolId ?? 0)->where('setting_key', 'timezone')->value('setting_value') ?: config('app.timezone', 'Asia/Jakarta');
        $now = Carbon::now($schoolTz);
        $currentTime = $now->format('H:i:s');

        $status = $request->status;
        $isHadir = in_array($status, ['H', 'Hadir', 'T', 'Terlambat']);

        $existing = Attendance::where('student_id', $request->student_id)->where('tanggal', $tanggal)->first();
        $jamMasuk = $existing?->jam_masuk;
        if (!$jamMasuk && $isHadir) {
            $jamMasuk = $currentTime;
        } elseif (!$isHadir) {
            $jamMasuk = null;
        }

        Attendance::updateOrCreate(
            [
                'student_id' => $request->student_id,
                'tanggal' => $tanggal
            ],
            [
                'school_id' => $schoolId,
                'status' => $status,
                'keterangan' => $request->keterangan ?? null,
                'jam_masuk' => $jamMasuk,
                'updated_at' => $now
            ]
        );

        // Send FCM push notification if marked as Alpha or Bolos
        try {
            $student = Siswa::find($request->student_id);
            if ($student) {
                if (in_array($status, ['A', 'Alpha'])) {
                    FcmNotificationService::sendToSiswa(
                        $student,
                        "Pemberitahuan Tidak Hadir (Alpha)",
                        "Halo {$student->nama}, status absensi Anda untuk tanggal {$tanggal} telah dicatat sebagai Alpha.",
                        [
                            'type' => 'checkin_siswa',
                            'status' => 'Alpha',
                            'jam' => now()->format('H:i'),
                            'tanggal' => $tanggal,
                            'sub_type' => 'attendance_alpha',
                            'category' => 'alpha_siswa',
                        ]
                    );
                } elseif (in_array($status, ['B', 'Bolos'])) {
                    FcmNotificationService::sendToSiswa(
                        $student,
                        "Peringatan Bolos",
                        "Halo {$student->nama}, status absensi Anda untuk tanggal {$tanggal} telah dicatat sebagai Bolos.",
                        [
                            'type' => 'checkout_siswa',
                            'status' => 'Bolos',
                            'jam' => now()->format('H:i'),
                            'tanggal' => $tanggal,
                            'sub_type' => 'attendance_bolos',
                            'category' => 'bolos_siswa',
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return response()->json(['success' => true, 'message' => 'Absensi siswa tersimpan secara realtime']);
    }

    public function getKelases(Request $request)
    {
        $user = $request->user();
        $schoolId = $user?->school_id ?? ($user?->guru?->school_id ?? null);
        $userRole = strtolower($user?->role ?? '');
        $isWakaOrAdmin = in_array($userRole, ['admin', 'super_admin', 'waka', 'kurikulum'])
            || str_contains($userRole, 'waka')
            || str_contains($userRole, 'kurikulum');

        $query = Kelas::query();
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        // Ambil kelas yang diwalikan
        $waliKelasIds = [];
        if ($user?->guru) {
            $waliKelasIds = Kelas::where(function ($q) use ($user) {
                $q->where('wali_kelas_id', $user->guru->id)
                  ->orWhere('wali_kelas_2_id', $user->guru->id);
            })
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->pluck('id')
            ->all();

            // Jika walas dan tidak meminta all, default tampilkan kelas walinya saja
            if (!$isWakaOrAdmin && !empty($waliKelasIds) && !$request->boolean('all')) {
                $query->whereIn('id', $waliKelasIds);
            }
        }

        $kelases = $query->orderBy('nama_kelas', 'asc')->get();

        $data = $kelases->map(function ($k) use ($user) {
            $peran = null;
            if ($user?->guru) {
                if ($k->wali_kelas_id == $user->guru->id) {
                    $peran = 'Wali Kelas 1';
                } elseif ($k->wali_kelas_2_id == $user->guru->id) {
                    $peran = 'Wali Kelas 2';
                }
            }

            return [
                'id' => $k->id,
                'nama' => $k->nama_kelas ?? $k->nama,
                'nama_kelas' => $k->nama_kelas ?? $k->nama,
                'peran' => $peran,
                'is_wali' => !empty($peran),
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function schoolStudentsRecap(Request $request)
    {
        $user = $request->user();
        $userRole = strtolower($user?->role ?? '');
        $isWakaOrAdmin = in_array($userRole, ['admin', 'super_admin', 'waka', 'kurikulum'])
            || str_contains($userRole, 'waka')
            || str_contains($userRole, 'kurikulum');

        if (!$isWakaOrAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Fitur rekapitulasi siswa seluruh sekolah khusus untuk Waka Kurikulum dan Administrator.'
            ], 403);
        }

        $schoolId = $user?->school_id ?? ($user?->guru?->school_id ?? null);
        $kelasId = $request->query('kelas_id');
        $months = (int) $request->query('months', 1);
        $startDateParam = $request->query('start_date');
        $endDateParam = $request->query('end_date');

        if ($startDateParam && $endDateParam) {
            try {
                $startDate = Carbon::parse($startDateParam)->format('Y-m-d');
                $endDate = Carbon::parse($endDateParam)->format('Y-m-d');
            } catch (\Exception $e) {
                $startDate = Carbon::now()->subDays(30 * $months)->format('Y-m-d');
                $endDate = Carbon::now()->format('Y-m-d');
            }
        } else {
            $endDate = Carbon::now()->format('Y-m-d');
            $startDate = Carbon::now()->subDays(30 * $months)->format('Y-m-d');
        }

        // Ambil daftar ID siswa (seluruh sekolah atau kelas tertentu)
        $siswaQuery = Siswa::query();
        if ($schoolId) {
            $siswaQuery->where('school_id', $schoolId);
        }
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
        $totalAlpha = $attendances->whereIn('status', ['A', 'Alpha', 'B', 'Bolos', 'Tidak Hadir'])->count();

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

    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();
        if ($user) {
            $this->assignFcmToken($request->fcm_token, $user);
        }

        return response()->json([
            'success' => true,
            'message' => 'FCM Token berhasil diperbarui'
        ]);
    }

    /**
     * Endpoint Registrasi Wajah Master (POST /api/mobile/face-register)
     * 1. Laravel menerima foto pendaftaran dari Android
     * 2. Laravel mengirim foto ke Python Service http://127.0.0.1:8000/extract-face-vector
     * 3. Simpan file foto di storage/app/public/faces/
     * 4. Simpan array embedding JSON ke tabel user_face_data
     * 5. Kembalikan respon sukses ke Android
     */
    public function registerFace(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|max:10240', // max 10MB
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $isFaceEnabled = app(\App\Services\LicenseService::class)->isFaceFeatureEnabled($user?->school);
        if (!$isFaceEnabled) {
            return response()->json([
                'success' => false,
                'message' => 'Fitur Face Recognition dinonaktifkan oleh administrator.'
            ], 403);
        }

        $student = $user->student ?? Siswa::where('user_id', $user->id)->first();
        $teacher = $user->guru ?? Guru::where('user_id', $user->id)->first();

        // Khusus Face ID: Jika sudah terdaftar, tolak pendaftaran berikutnya
        $existingFace = UserFaceData::where('user_id', $user->id)
            ->when($student, fn($q) => $q->orWhere('siswa_id', $student->id))
            ->when($teacher, fn($q) => $q->orWhere('guru_id', $teacher->id))
            ->first();

        if ($existingFace && !empty($existingFace->face_embedding)) {
            return response()->json([
                'success' => false,
                'message' => 'Pendaftaran ditolak: Wajah Anda sudah terdaftar di sistem. Silakan hubungi admin sekolah jika ingin mereset/mendaftarkan ulang data Face ID.'
            ], 422);
        }

        // 1. Ekstraksi vektor wajah via Python Service
        $extractResult = FaceRecognitionService::extractVector($request->file('photo'));

        if (!$extractResult['success'] || empty($extractResult['embedding'])) {
            return response()->json([
                'success' => false,
                'message' => $extractResult['message'] ?? 'Wajah tidak terdeteksi pada foto. Pastikan wajah terlihat jelas menghadap kamera.'
            ], 400);
        }

        // 2. Simpan file foto di storage/app/public/faces/
        $storedPath = $request->file('photo')->store('faces', 'public');

        // 3. Simpan data embedding ke user_face_data
        $faceData = UserFaceData::updateOrCreate(
            ['user_id' => $user->id],
            [
                'siswa_id' => $student?->id,
                'guru_id' => $teacher?->id,
                'face_embedding' => json_encode($extractResult['embedding']),
                'sample_photo_path' => $storedPath,
                'is_verified' => true,
            ]
        );

        ApiLog::create([
            'school_id' => $user->school_id,
            'api_key' => 'MOBILE_APP',
            'action' => 'face_registered',
            'uid' => $user->username ?? $user->email,
            'success' => true,
            'message' => "Registrasi Wajah Master Berhasil: {$user->full_name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent() ?? 'Android Mobile App',
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registrasi wajah master berhasil disimpan.',
            'data' => [
                'is_verified' => true,
                'sample_photo_url' => asset('storage/' . $storedPath),
                'registered_at' => $faceData->updated_at?->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * Endpoint Status Registrasi Wajah (GET /api/mobile/face-status)
     */
    public function getFaceStatus(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $faceData = UserFaceData::where('user_id', $user->id)->first();
        if (!$faceData) {
            if ($user->student || in_array($user->role, ['student', 'siswa'])) {
                $student = $user->student ?? Siswa::where('user_id', $user->id)->first();
                if ($student) {
                    $faceData = UserFaceData::where('siswa_id', $student->id)->first();
                }
            } elseif ($user->guru || in_array($user->role, ['guru', 'teacher', 'wali_kelas'])) {
                $teacher = $user->guru ?? Guru::where('user_id', $user->id)->first();
                if ($teacher) {
                    $faceData = UserFaceData::where('guru_id', $teacher->id)->first();
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'has_face_registered' => !empty($faceData),
                'is_verified' => $faceData?->is_verified ?? false,
                'sample_photo_url' => $faceData?->sample_photo_path ? asset('storage/' . $faceData->sample_photo_path) : null,
                'registered_at' => $faceData?->updated_at?->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * Endpoint Scan Wajah Siswa 1:N (POST /api/mobile/student/scan-face)
     * Otomatis mencari data siswa berdasarkan kecocokan vektor wajah,
     * serta menentukan absensi masuk atau pulang tanpa menyimpan file foto ke server.
     */
    public function scanFaceStudent(Request $request)
    {
        $request->validate([
            'photo' => 'required|file|image|max:10240',
            'tanggal' => 'nullable|string',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'data' => null
            ], 401);
        }

        $isFaceEnabled = app(\App\Services\LicenseService::class)->isFaceFeatureEnabled($user?->school);
        if (!$isFaceEnabled) {
            return response()->json([
                'success' => false,
                'message' => 'Fitur Face Recognition scanner dinonaktifkan oleh administrator.',
                'data' => null
            ], 403);
        }

        $schoolId = $user->school_id;
        $schoolTz = Setting::where('school_id', $schoolId)->where('setting_key', 'timezone')->value('setting_value') ?: config('app.timezone', 'Asia/Jakarta');
        $now = Carbon::now($schoolTz);
        $tzSuffix = match ($schoolTz) {
            'Asia/Jayapura' => 'WIT',
            'Asia/Makassar' => 'WITA',
            default => 'WIB',
        };

        // 1. Parsing Tanggal Absensi (opsional, contoh: "13-10-2026" atau "2026-10-13")
        $rawTanggal = trim((string) $request->input('tanggal', ''));
        if (!empty($rawTanggal)) {
            try {
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $rawTanggal)) {
                    $targetCarbon = Carbon::createFromFormat('d-m-Y', $rawTanggal, $schoolTz)->startOfDay();
                } else {
                    $targetCarbon = Carbon::parse($rawTanggal, $schoolTz)->startOfDay();
                }
                $targetDate = $targetCarbon->format('Y-m-d');
            } catch (\Throwable $e) {
                $targetCarbon = $now->copy()->startOfDay();
                $targetDate = $now->format('Y-m-d');
            }
        } else {
            $targetCarbon = $now->copy()->startOfDay();
            $targetDate = $now->format('Y-m-d');
        }
        $nowTime = $now->format('H:i:s');

        // 2. Ekstraksi Vektor Wajah via Python Face Engine
        $extractResult = FaceRecognitionService::extractVector($request->file('photo'));
        if (empty($extractResult['success']) || empty($extractResult['embedding'])) {
            return response()->json([
                'success' => false,
                'message' => 'Wajah siswa tidak terdaftar atau tidak cocok dalam database',
                'data' => null
            ], 404);
        }
        $snapVector = $extractResult['embedding'];

        // 3. Pencarian 1:N Master Face ID Siswa di Sekolah Tersebut
        $candidates = UserFaceData::whereNotNull('face_embedding')
            ->whereNotNull('siswa_id')
            ->when($schoolId, function ($q) use ($schoolId) {
                $q->whereHas('siswa', fn($s) => $s->where('school_id', $schoolId));
            })
            ->with(['siswa.kelas', 'siswa.school'])
            ->get();

        $threshold = FaceRecognitionService::getThreshold(); // Default 0.75 (75%)
        $bestScore = 0.0;
        $matchedRecord = null;

        foreach ($candidates as $cand) {
            $candEmb = json_decode($cand->face_embedding, true);
            if (is_string($candEmb)) {
                $candEmb = json_decode($candEmb, true);
            }
            if (!is_array($candEmb) || count($candEmb) !== 512) {
                continue;
            }

            $sim = FaceRecognitionService::cosineSimilarity($snapVector, $candEmb);
            if ($sim > $bestScore) {
                $bestScore = $sim;
                $matchedRecord = $cand;
            }
        }

        if (!$matchedRecord || !$matchedRecord->siswa || $bestScore < $threshold) {
            return response()->json([
                'success' => false,
                'message' => 'Wajah siswa tidak terdaftar atau tidak cocok dalam database',
                'data' => null
            ], 404);
        }

        $student = $matchedRecord->siswa;
        $studentSchoolId = $student->school_id ?: $schoolId;
        $kelasName = $student->kelas ? ($student->kelas->nama_kelas ?? $student->kelas->nama) : '-';

        // 4. Cek Jadwal Harian Sekolah
        $indexHari = (int) $targetCarbon->format('N');
        $jadwal = Jadwal::where('index_hari', $indexHari)
            ->where('is_active', 1)
            ->when($studentSchoolId, fn($q, $sId) => $q->where('school_id', $sId))
            ->first();

        if (!$jadwal && $studentSchoolId > 0) {
            $jadwal = Jadwal::where('index_hari', $indexHari)->where('is_active', 1)->first();
        }

        if (!$jadwal) {
            return response()->json([
                'success' => false,
                'message' => 'Hari ini adalah hari libur sekolah (tidak ada jadwal aktif)',
                'data' => null
            ], 422);
        }

        // Cek apakah absensi dinonaktifkan untuk kelas siswa
        if ($student->kelas && isset($student->kelas->is_active_attendance) && !$student->kelas->is_active_attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Absensi dinonaktifkan untuk kelas ' . ($student->kelas->nama_kelas ?? $student->kelas->nama),
                'data' => null
            ], 422);
        }

        // 5. Penentuan Otomatis Masuk vs Pulang Mengikuti Jadwal Sekolah
        $reqMode = $request->input('mode', 'auto');
        $att = Attendance::where('student_id', $student->id)
            ->where('tanggal', $targetDate)
            ->first();

        // Tentukan apakah proses ini Pulang atau Masuk
        $isPulangAction = false;
        if ($reqMode === 'pulang') {
            $isPulangAction = true;
        } elseif ($reqMode === 'masuk') {
            $isPulangAction = false;
        } else {
            // Mode Auto: Jika sudah memiliki jam masuk, aksinya adalah Pulang
            $isPulangAction = ($att && !empty($att->jam_masuk));
        }

        $timeFormatted = $now->format('H:i');
        $msgTime = "{$timeFormatted} {$tzSuffix}";

        // ==========================================
        // PROSES ABSEN PULANG (CHECK-OUT)
        // ==========================================
        if ($isPulangAction) {
            // A. Siswa belum pernah absen masuk
            if (!$att || empty($att->jam_masuk)) {
                return response()->json([
                    'success' => false,
                    'message' => "Siswa {$student->nama} belum melakukan absen masuk hari ini",
                    'data' => null
                ], 422);
            }

            // B. Siswa sudah absen pulang sebelumnya (lengkap)
            if (!empty($att->jam_pulang)) {
                $alreadyMsg = "Siswa sudah lengkap absen Masuk & Pulang hari ini";
                return response()->json([
                    'success' => true,
                    'message' => $alreadyMsg,
                    'data' => [
                        'student_id' => $student->id,
                        'nama' => $student->nama,
                        'nis' => (string) ($student->nis ?? '-'),
                        'kelas_name' => $kelasName,
                        'confidence' => round($bestScore, 3),
                        'status' => $att->status,
                        'jam_masuk' => $att->jam_masuk,
                        'jam_pulang' => $att->jam_pulang,
                        'message' => $alreadyMsg,
                    ]
                ], 200);
            }

            // C. Cek fitur kepulangan di pengaturan sekolah
            $checkoutEnabled = Setting::where('school_id', $studentSchoolId)
                ->where('setting_key', 'enable_checkout_attendance')
                ->value('setting_value') ?? 'true';

            if ($checkoutEnabled === 'false') {
                return response()->json([
                    'success' => false,
                    'message' => 'Fitur absensi kepulangan dinonaktifkan oleh sekolah.',
                    'data' => null
                ], 422);
            }

            // D. Validasi Jadwal Kepulangan (jam_pulang & akhir_absen_pulang)
            $jamPulang = $jadwal->jam_pulang;
            $akhirAbsenPulang = $jadwal->akhir_absen_pulang;

            if ($jamPulang && $nowTime < $jamPulang) {
                $jamBuka = Carbon::parse($jamPulang)->format('H:i');
                return response()->json([
                    'success' => false,
                    'message' => "Belum masuk waktu absen pulang (Dibuka pukul {$jamBuka})",
                    'data' => null
                ], 422);
            }

            if ($akhirAbsenPulang && $nowTime > $akhirAbsenPulang) {
                $jamTutup = Carbon::parse($akhirAbsenPulang)->format('H:i');
                return response()->json([
                    'success' => false,
                    'message' => "Batas waktu absen pulang sudah ditutup (Pukul {$jamTutup})",
                    'data' => null
                ], 422);
            }

            // Simpan data kepulangan
            $masukCarbon = Carbon::parse($att->tanggal . ' ' . $att->jam_masuk);
            $totalSeconds = abs($masukCarbon->diffInSeconds($now, false));
            $hours = floor($totalSeconds / 3600);
            $mins = floor(($totalSeconds % 3600) / 60);

            $newStatus = $att->status;
            $newKeterangan = $att->keterangan;
            if ($att->status === 'B') {
                $newStatus = ($att->jam_masuk > ($jadwal->jam_masuk ?? '07:30')) ? 'T' : 'H';
                $newKeterangan = trim(str_replace('[Auto: Tidak Absen Pulang]', '', $newKeterangan ?? ''));
            }

            $successMsg = "Absen Pulang Berhasil ({$msgTime})";

            $att->update([
                'jam_pulang' => $nowTime,
                'total_seconds' => $totalSeconds,
                'status' => $newStatus,
                'keterangan' => $newKeterangan,
                'face_similarity_score' => round($bestScore, 4),
                'is_face_matched' => true,
                'updated_at' => $now,
            ]);

            // Notifikasi WhatsApp Checkout
            try {
                $wa = app(\App\Services\WhatsAppService::class);
                $wa->sendCheckOut($student->nama, $student->no_wa, $now->format('H:i'), $hours, $mins, 'Face Scan Mobile', $studentSchoolId, Carbon::parse($att->jam_masuk)->format('H:i'), $student->wa_ortu, $targetCarbon->format('d/m/Y'));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("WA Face Scan Checkout Error: " . $e->getMessage());
            }

            // Notifikasi FCM Checkout
            try {
                FcmNotificationService::sendToSiswa($student, "Presensi Pulang Tercatat", "Halo {$student->nama}, absensi pulang Anda berhasil dicatat pukul {$now->format('H:i')}.", [
                    'type' => 'checkout_siswa',
                    'jam' => $now->format('H:i'),
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("FCM Face Scan Checkout Error: " . $e->getMessage());
            }

            ApiLog::create([
                'school_id' => $studentSchoolId,
                'api_key' => 'MOBILE_APP',
                'action' => 'mobile_student_scan_face_out',
                'uid' => $student->nis ?? $student->nama,
                'success' => true,
                'message' => "Face Scan Pulang: {$student->nama} (" . round($bestScore * 100, 1) . "%)",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent() ?? 'Android Mobile App',
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'data' => [
                    'student_id' => $student->id,
                    'nama' => $student->nama,
                    'nis' => (string) ($student->nis ?? '-'),
                    'kelas_name' => $kelasName,
                    'confidence' => round($bestScore, 3),
                    'status' => $att->status,
                    'jam_pulang' => $nowTime,
                    'message' => $successMsg,
                ]
            ], 200);
        }

        // ==========================================
        // PROSES ABSEN MASUK (CHECK-IN)
        // ==========================================

        // A. Cek apakah sudah pernah absen masuk hari ini
        if ($att && !empty($att->jam_masuk)) {
            $jamMasukFormatted = Carbon::parse($att->jam_masuk)->format('H:i');
            return response()->json([
                'success' => false,
                'message' => "Siswa {$student->nama} sudah melakukan absen masuk hari ini (Pukul {$jamMasukFormatted})",
                'data' => [
                    'student_id' => $student->id,
                    'nama' => $student->nama,
                    'nis' => (string) ($student->nis ?? '-'),
                    'kelas_name' => $kelasName,
                    'confidence' => round($bestScore, 3),
                    'status' => $att->status,
                    'jam_masuk' => $att->jam_masuk,
                    'jam_pulang' => $att->jam_pulang,
                    'message' => "Sudah absen masuk pukul {$jamMasukFormatted}",
                ]
            ], 422);
        }

        // B. Validasi Jadwal Waktu Masuk (awal_absen_masuk & akhir_absen_masuk)
        $awalAbsenMasuk = $jadwal->awal_absen_masuk;
        $akhirAbsenMasuk = $jadwal->akhir_absen_masuk;
        $jamMasukJadwal = $jadwal->jam_masuk;

        if ($awalAbsenMasuk && $nowTime < $awalAbsenMasuk) {
            $jamBuka = Carbon::parse($awalAbsenMasuk)->format('H:i');
            return response()->json([
                'success' => false,
                'message' => "Absen masuk belum dibuka (Dibuka pukul {$jamBuka})",
                'data' => null
            ], 422);
        }

        if ($akhirAbsenMasuk && $nowTime > $akhirAbsenMasuk) {
            $jamTutup = Carbon::parse($akhirAbsenMasuk)->format('H:i');
            return response()->json([
                'success' => false,
                'message' => "Batas waktu absen masuk sudah ditutup (Pukul {$jamTutup})",
                'data' => null
            ], 422);
        }

        // C. Hitung Tepat Waktu ('H') vs Terlambat ('T')
        $status = 'H';
        if ($jamMasukJadwal && $nowTime > $jamMasukJadwal) {
            $status = 'T';
            $diffSeconds = Carbon::parse($targetDate . ' ' . $nowTime)->diffInSeconds(Carbon::parse($targetDate . ' ' . $jamMasukJadwal));
            $jam = floor($diffSeconds / 3600);
            $menit = floor(($diffSeconds % 3600) / 60);
            $durasi = $jam > 0 ? "{$jam} jam {$menit} menit" : "{$menit} menit";
            $keterangan = "Telat {$durasi} - Face Scan Mobile";
        } else {
            $status = 'H';
            $keterangan = "Tepat Waktu - Face Scan Mobile";
        }

        $successMsg = "Absen Masuk Berhasil ({$msgTime})";

        // Simpan record attendance tanpa simpan foto fisik ke storage
        $att = Attendance::updateOrCreate(
            [
                'student_id' => $student->id,
                'tanggal' => $targetDate,
            ],
            [
                'school_id' => $studentSchoolId,
                'jam_masuk' => $nowTime,
                'status' => $status,
                'keterangan' => $keterangan,
                'face_similarity_score' => round($bestScore, 4),
                'is_face_matched' => true,
                'photo_checkin_path' => null,
                'updated_at' => $now,
            ]
        );

        // Notifikasi WhatsApp
        try {
            $wa = app(\App\Services\WhatsAppService::class);
            $wa->sendCheckIn($student->nama, $student->no_wa, $now->format('H:i'), $status, $studentSchoolId, $keterangan, $student->wa_ortu, $kelasName);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("WA Face Scan Checkin Error: " . $e->getMessage());
        }

        // Notifikasi FCM
        try {
            $readableStatus = $status === 'T' ? 'Terlambat' : 'Hadir';
            FcmNotificationService::sendToSiswa($student, "Presensi Masuk Tercatat", "Halo {$student->nama}, absensi masuk Anda berhasil dicatat pukul {$now->format('H:i')} (Status: {$readableStatus}).", [
                'type' => 'checkin_siswa',
                'status' => $readableStatus,
                'jam' => $now->format('H:i'),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("FCM Face Scan Checkin Error: " . $e->getMessage());
        }

        ApiLog::create([
            'school_id' => $studentSchoolId,
            'api_key' => 'MOBILE_APP',
            'action' => 'mobile_student_scan_face_in',
            'uid' => $student->nis ?? $student->nama,
            'success' => true,
            'message' => "Face Scan Masuk: {$student->nama} (" . round($bestScore * 100, 1) . "%)",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent() ?? 'Android Mobile App',
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $successMsg,
            'data' => [
                'student_id' => $student->id,
                'nama' => $student->nama,
                'nis' => (string) ($student->nis ?? '-'),
                'kelas_name' => $kelasName,
                'confidence' => round($bestScore, 3),
                'status' => $status,
                'jam_masuk' => $nowTime,
                'message' => $successMsg,
            ]
        ], 200);
    }

    /**
     * Kompresi dan simpan foto profil siswa (GD Library, auto-orient, max 600x800).
     */
    private function processAndStoreSiswaFoto($file): string
    {
        $externalPath = config('filesystems.siswa_photo_path');
        $randomName = \Illuminate\Support\Str::random(40) . '.jpg';

        if (!empty($externalPath)) {
            $destDir = rtrim($externalPath, '/\\');
            $destinationPath = $destDir . DIRECTORY_SEPARATOR . $randomName;
            $dbValue = $randomName;
        } else {
            $destDir = storage_path('app/public/siswa');
            $destinationPath = $destDir . DIRECTORY_SEPARATOR . $randomName;
            $dbValue = 'siswa/' . $randomName;
        }

        if (!file_exists($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        if (extension_loaded('gd')) {
            try {
                $filePath = $file->getRealPath();
                $imageInfo = @getimagesize($filePath);

                if ($imageInfo) {
                    $mime = $imageInfo['mime'];
                    $src = match ($mime) {
                        'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($filePath),
                        'image/png' => @imagecreatefrompng($filePath),
                        'image/webp' => @imagecreatefromwebp($filePath),
                        default => null,
                    };

                    if ($src) {
                        if (function_exists('exif_read_data') && ($mime === 'image/jpeg' || $mime === 'image/jpg')) {
                            $exif = @exif_read_data($filePath);
                            if (!empty($exif['Orientation'])) {
                                $src = match ($exif['Orientation']) {
                                    3 => imagerotate($src, 180, 0),
                                    6 => imagerotate($src, -90, 0),
                                    8 => imagerotate($src, 90, 0),
                                    default => $src,
                                };
                            }
                        }

                        $origW = imagesx($src);
                        $origH = imagesy($src);

                        $maxW = 600;
                        $maxH = 800;

                        $ratio = min($maxW / $origW, $maxH / $origH, 1.0);
                        $targetW = (int) round($origW * $ratio);
                        $targetH = (int) round($origH * $ratio);

                        $dst = imagecreatetruecolor($targetW, $targetH);

                        $white = imagecolorallocate($dst, 255, 255, 255);
                        imagefill($dst, 0, 0, $white);

                        imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);
                        imagejpeg($dst, $destinationPath, 80);

                        imagedestroy($src);
                        imagedestroy($dst);

                        return $dbValue;
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Gagal kompresi foto siswa di API: ' . $e->getMessage());
            }
        }

        if (!empty($externalPath)) {
            $ext = $file->getClientOriginalExtension() ?: 'jpg';
            $fallbackName = \Illuminate\Support\Str::random(40) . '.' . $ext;
            $file->move($destDir, $fallbackName);
            return $fallbackName;
        }

        return $file->store('siswa', 'public');
    }

    /**
     * Endpoint Upload Foto Siswa (POST /api/mobile/student/photo atau /api/mobile/student/upload-photo)
     * Dapat dipanggil oleh siswa sendiri atau guru/admin dengan menyertakan student_id.
     * Otomatis melakukan sinkronisasi dengan registrasi Face ID jika wajah terdeteksi.
     */
    public function uploadStudentPhoto(Request $request)
    {
        $fileKey = $request->hasFile('photo') ? 'photo' : ($request->hasFile('foto') ? 'foto' : null);
        if (!$fileKey) {
            return response()->json([
                'success' => false,
                'message' => 'File foto wajib diunggah (gunakan key: photo atau foto).'
            ], 422);
        }

        $request->validate([
            $fileKey => 'required|image|max:10240', // max 10MB
            'student_id' => 'nullable|integer',
            'sync_face' => 'nullable|boolean',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $studentId = $request->input('student_id');
        $siswa = null;
        $isStaff = in_array($user->role, ['guru', 'teacher', 'wali_kelas', 'admin', 'super_admin']) || !empty($user->guru);

        if ($studentId) {
            $siswa = Siswa::find($studentId);
            if (!$siswa) {
                return response()->json(['success' => false, 'message' => 'Data siswa tidak ditemukan.'], 404);
            }

            // Jika bukan staf/guru, pastikan siswa yang diedit adalah akunnya sendiri
            if (!$isStaff && $siswa->user_id !== $user->id) {
                $cleanNis = str_replace('siswa_', '', $user->username);
                if ($siswa->nis !== $cleanNis) {
                    return response()->json(['success' => false, 'message' => 'Akses ditolak: Anda hanya dapat mengubah foto profil Anda sendiri.'], 403);
                }
            }
        } else {
            // Ambil dari profil login user siswa
            $siswa = $user->student ?? Siswa::where('user_id', $user->id)->first();
            if (!$siswa && in_array($user->role, ['student', 'siswa'])) {
                $cleanNis = str_replace('siswa_', '', $user->username);
                $siswa = Siswa::where('nis', $cleanNis)->first();
            }

            if (!$siswa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter student_id diperlukan jika diunggah oleh akun dewan guru/admin.'
                ], 422);
            }
        }

        // Hapus file foto lama dari disk jika ada
        if (!empty($siswa->foto)) {
            Siswa::deletePhotoFile($siswa->foto);
        }

        $uploadedFile = $request->file($fileKey);
        $newPhotoPath = $this->processAndStoreSiswaFoto($uploadedFile);

        $siswa->foto = $newPhotoPath;
        $siswa->save();

        // Sinkronisasi otomatis ke Face ID jika wajah terdeteksi (opsional, default: true)
        $faceRegistered = false;
        $faceMessage = null;
        $syncFace = $request->boolean('sync_face', true);
        $isFaceEnabled = app(\App\Services\LicenseService::class)->isFaceFeatureEnabled($siswa?->school);

        if ($syncFace && $isFaceEnabled) {
            // Cek apakah siswa sudah punya data Face ID terdaftar. Jika sudah, tolak pendaftaran berikutnya!
            $existingFace = UserFaceData::where('siswa_id', $siswa->id)
                ->when($siswa->user_id, fn($q) => $q->orWhere('user_id', $siswa->user_id))
                ->first();

            if ($existingFace && !empty($existingFace->face_embedding)) {
                $faceRegistered = false;
                $faceMessage = 'Wajah sudah terdaftar sebelumnya. Pendaftaran Face ID baru ditolak (hubungi admin untuk reset Face ID).';
            } else {
                try {
                $extractResult = FaceRecognitionService::extractVector($uploadedFile);
                if (!empty($extractResult['success']) && !empty($extractResult['embedding'])) {
                    // Cari atau buat target User untuk siswa agar memiliki relasi di user_face_data
                    $targetUser = null;
                    if ($siswa->user_id) {
                        $targetUser = User::find($siswa->user_id);
                    }
                    if (!$targetUser) {
                        $targetUser = User::where('username', 'siswa_' . $siswa->nis)
                            ->orWhere('username', $siswa->nis)
                            ->first();
                    }
                    if (!$targetUser) {
                        $targetUser = User::create([
                            'full_name' => $siswa->nama,
                            'username' => 'siswa_' . $siswa->nis,
                            'email' => $siswa->nis . '@siswa.local',
                            'password_hash' => Hash::make($siswa->tgl_lahir ? Carbon::parse($siswa->tgl_lahir)->format('Ymd') : '123456'),
                            'role' => 'student',
                            'school_id' => $siswa->school_id,
                        ]);
                    }

                    if (\Illuminate\Support\Facades\Schema::hasColumn('siswa', 'user_id')) {
                        if ($siswa->user_id !== $targetUser->id) {
                            $siswa->user_id = $targetUser->id;
                            $siswa->save();
                        }
                    }

                    UserFaceData::updateOrCreate(
                        ['user_id' => $targetUser->id],
                        [
                            'siswa_id' => $siswa->id,
                            'face_embedding' => json_encode($extractResult['embedding']),
                            'sample_photo_path' => $newPhotoPath,
                            'is_verified' => true,
                        ]
                    );
                    $faceRegistered = true;
                    $faceMessage = 'Wajah berhasil dideteksi dan didaftarkan sebagai master Face ID.';
                } else {
                    $faceMessage = $extractResult['message'] ?? 'Wajah tidak terdeteksi untuk pendaftaran Face ID otomatis.';
                }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::info('Sync face error during student photo upload: ' . $e->getMessage());
                    $faceMessage = 'Layanan Face Engine offline, foto siswa tetap tersimpan.';
                }
            }
        }

        ApiLog::create([
            'school_id' => $siswa->school_id ?: $user->school_id,
            'api_key' => 'MOBILE_APP',
            'action' => 'mobile_upload_student_photo',
            'uid' => $siswa->nis,
            'success' => true,
            'message' => "Upload foto siswa berhasil: {$siswa->nama} (NIS: {$siswa->nis})" . ($faceRegistered ? " + Face ID terdaftar" : ""),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent() ?? 'Android Mobile App',
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Foto siswa berhasil diperbarui' . ($faceRegistered ? ' dan didaftarkan untuk Face ID.' : '.'),
            'data' => [
                'student_id' => $siswa->id,
                'nama' => $siswa->nama,
                'nis' => $siswa->nis,
                'foto' => $siswa->foto,
                'foto_url' => $siswa->foto_url,
                'face_registered' => $faceRegistered,
                'face_message' => $faceMessage,
            ]
        ]);
    }

    /**
     * Endpoint Upload Foto Guru / Pegawai (POST /api/mobile/guru/photo atau /api/mobile/teacher/photo)
     * Dapat dipanggil oleh dewan guru sendiri atau admin dengan menyertakan guru_id.
     * Otomatis sinkronkan ke Face ID jika wajah terdeteksi dan belum pernah didaftarkan.
     */
    public function uploadTeacherPhoto(Request $request)
    {
        $fileKey = $request->hasFile('photo') ? 'photo' : ($request->hasFile('foto') ? 'foto' : null);
        if (!$fileKey) {
            return response()->json([
                'success' => false,
                'message' => 'File foto wajib diunggah (gunakan key: photo atau foto).'
            ], 422);
        }

        $request->validate([
            $fileKey => 'required|image|max:10240', // max 10MB
            'guru_id' => 'nullable|integer',
            'teacher_id' => 'nullable|integer',
            'sync_face' => 'nullable|boolean',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $teacherId = $request->input('guru_id', $request->input('teacher_id'));
        $guru = null;
        $isAdmin = in_array($user->role, ['admin', 'super_admin']);

        if ($teacherId) {
            $guru = Guru::find($teacherId);
            if (!$guru) {
                return response()->json(['success' => false, 'message' => 'Data guru tidak ditemukan.'], 404);
            }

            if (!$isAdmin && $guru->user_id !== $user->id && $user->guru?->id !== $guru->id) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak: Anda hanya dapat mengubah foto profil Anda sendiri.'], 403);
            }
        } else {
            $guru = $user->guru ?? Guru::where('user_id', $user->id)->first();
            if (!$guru) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter guru_id diperlukan jika diunggah oleh akun admin.'
                ], 422);
            }
        }

        // Hapus file foto lama dari disk jika ada
        if (!empty($guru->foto)) {
            Guru::deletePhotoFile($guru->foto);
        }

        $uploadedFile = $request->file($fileKey);
        $randomName = \Illuminate\Support\Str::random(40) . '.jpg';
        $destDir = storage_path('app/public/guru');
        $destinationPath = $destDir . DIRECTORY_SEPARATOR . $randomName;
        $newPhotoPath = 'guru/' . $randomName;

        if (!file_exists($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        if (extension_loaded('gd')) {
            try {
                $filePath = $uploadedFile->getRealPath();
                $imageInfo = @getimagesize($filePath);
                if ($imageInfo) {
                    $mime = $imageInfo['mime'];
                    $src = match ($mime) {
                        'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($filePath),
                        'image/png' => @imagecreatefrompng($filePath),
                        'image/webp' => @imagecreatefromwebp($filePath),
                        default => null,
                    };

                    if ($src) {
                        if (function_exists('exif_read_data') && ($mime === 'image/jpeg' || $mime === 'image/jpg')) {
                            $exif = @exif_read_data($filePath);
                            if (!empty($exif['Orientation'])) {
                                $src = match ($exif['Orientation']) {
                                    3 => imagerotate($src, 180, 0),
                                    6 => imagerotate($src, -90, 0),
                                    8 => imagerotate($src, 90, 0),
                                    default => $src,
                                };
                            }
                        }

                        $origW = imagesx($src);
                        $origH = imagesy($src);
                        $maxW = 600;
                        $maxH = 800;
                        $ratio = min($maxW / $origW, $maxH / $origH, 1.0);
                        $targetW = (int) round($origW * $ratio);
                        $targetH = (int) round($origH * $ratio);

                        $dst = imagecreatetruecolor($targetW, $targetH);
                        $white = imagecolorallocate($dst, 255, 255, 255);
                        imagefill($dst, 0, 0, $white);
                        imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);
                        imagejpeg($dst, $destinationPath, 80);
                        imagedestroy($src);
                        imagedestroy($dst);
                    } else {
                        $uploadedFile->storeAs('guru', $randomName, 'public');
                    }
                } else {
                    $uploadedFile->storeAs('guru', $randomName, 'public');
                }
            } catch (\Throwable $e) {
                $uploadedFile->storeAs('guru', $randomName, 'public');
            }
        } else {
            $uploadedFile->storeAs('guru', $randomName, 'public');
        }

        $guru->foto = $newPhotoPath;
        $guru->save();

        // Sinkronisasi otomatis ke Face ID jika wajah terdeteksi (opsional, default: true)
        $faceRegistered = false;
        $faceMessage = null;
        $syncFace = $request->boolean('sync_face', true);
        $isFaceEnabled = app(\App\Services\LicenseService::class)->isFaceFeatureEnabled($guru?->school);

        if ($syncFace && $isFaceEnabled) {
            $existingFace = UserFaceData::where('guru_id', $guru->id)
                ->when($guru->user_id, fn($q) => $q->orWhere('user_id', $guru->user_id))
                ->first();

            if ($existingFace && !empty($existingFace->face_embedding)) {
                $faceRegistered = false;
                $faceMessage = 'Wajah sudah terdaftar sebelumnya. Pendaftaran Face ID baru ditolak (hubungi admin untuk reset Face ID).';
            } else {
                try {
                    $extractResult = FaceRecognitionService::extractVector($uploadedFile);
                    if (!empty($extractResult['success']) && !empty($extractResult['embedding'])) {
                        $targetUserId = $guru->user_id ?: ($user->guru?->id === $guru->id ? $user->id : null);
                        if (!$targetUserId) {
                            $targetUserId = $user->id;
                        }

                        UserFaceData::updateOrCreate(
                            ['user_id' => $targetUserId],
                            [
                                'guru_id' => $guru->id,
                                'face_embedding' => json_encode($extractResult['embedding']),
                                'sample_photo_path' => $newPhotoPath,
                                'is_verified' => true,
                            ]
                        );
                        $faceRegistered = true;
                        $faceMessage = 'Wajah berhasil dideteksi dan didaftarkan sebagai master Face ID.';
                    } else {
                        $faceMessage = $extractResult['message'] ?? 'Wajah tidak terdeteksi untuk pendaftaran Face ID otomatis.';
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::info('Sync face error during teacher photo upload: ' . $e->getMessage());
                    $faceMessage = 'Layanan Face Engine offline, foto guru tetap tersimpan.';
                }
            }
        }

        ApiLog::create([
            'school_id' => $guru->school_id ?: $user->school_id,
            'api_key' => 'MOBILE_APP',
            'action' => 'mobile_upload_teacher_photo',
            'uid' => $guru->nip ?: $guru->nama,
            'success' => true,
            'message' => "Upload foto guru berhasil: {$guru->nama}" . ($faceRegistered ? " + Face ID terdaftar" : ""),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent() ?? 'Android Mobile App',
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Foto guru berhasil diperbarui' . ($faceRegistered ? ' dan didaftarkan untuk Face ID.' : '.'),
            'data' => [
                'guru_id' => $guru->id,
                'nama' => $guru->nama,
                'nip' => $guru->nip,
                'foto' => $guru->foto,
                'foto_url' => $guru->foto_url,
                'face_registered' => $faceRegistered,
                'face_message' => $faceMessage,
            ]
        ]);
    }

    /**
     * Endpoint Ganti Password Akun Mobile (POST /api/mobile/change-password atau /api/mobile/ganti-password)
     * Dapat digunakan oleh akun Guru, Pegawai, maupun Siswa.
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        // Support format key bahasa Inggris dan Indonesia
        $currentPassword = (string) $request->input('current_password', $request->input('password_lama', ''));
        $newPassword = (string) $request->input('new_password', $request->input('password_baru', ''));
        $newPasswordConfirmation = (string) $request->input('new_password_confirmation', $request->input('konfirmasi_password', $request->input('password_baru_confirmation', '')));

        if (trim($currentPassword) === '') {
            return response()->json([
                'success' => false,
                'message' => 'Password saat ini (lama) wajib diisi.'
            ], 422);
        }

        if (trim($newPassword) === '') {
            return response()->json([
                'success' => false,
                'message' => 'Password baru wajib diisi.'
            ], 422);
        }

        if (strlen($newPassword) < 6) {
            return response()->json([
                'success' => false,
                'message' => 'Password baru minimal terdiri dari 6 karakter.'
            ], 422);
        }

        if ($newPasswordConfirmation !== '' && $newPassword !== $newPasswordConfirmation) {
            return response()->json([
                'success' => false,
                'message' => 'Konfirmasi password baru tidak cocok.'
            ], 422);
        }

        // 1. Verifikasi kecocokan password saat ini
        $isCurrentValid = Hash::check($currentPassword, $user->password_hash ?? $user->password);

        // Fallback untuk Siswa: jika hash belum cocok dan user adalah siswa, cek format tanggal lahir siswa
        if (!$isCurrentValid && ($user->student || in_array($user->role, ['student', 'siswa']))) {
            $siswa = $user->student ?? Siswa::where('user_id', $user->id)->first();
            if (!$siswa) {
                $cleanNis = str_replace('siswa_', '', $user->username);
                $siswa = Siswa::where('nis', $cleanNis)->first();
            }

            if ($siswa && !empty($siswa->tgl_lahir)) {
                try {
                    $birthDate = Carbon::parse($siswa->tgl_lahir);
                    $cleanInput = preg_replace('/[^0-9]/', '', $currentPassword);
                    $validFormats = [
                        $birthDate->format('Y-m-d'),
                        $birthDate->format('d-m-Y'),
                        $birthDate->format('Y/m/d'),
                        $birthDate->format('d/m/Y'),
                        $birthDate->format('Ymd'),
                        $birthDate->format('dmY'),
                    ];
                    $isCurrentValid = in_array($currentPassword, $validFormats) ||
                                      in_array($cleanInput, [$birthDate->format('Ymd'), $birthDate->format('dmY')]);
                } catch (\Exception $e) {
                    $isCurrentValid = false;
                }
            }
        }

        if (!$isCurrentValid) {
            return response()->json([
                'success' => false,
                'message' => 'Password saat ini (lama) yang Anda masukkan salah.'
            ], 422);
        }

        // 2. Pastikan password baru tidak sama persis dengan password saat ini
        if ($currentPassword === $newPassword) {
            return response()->json([
                'success' => false,
                'message' => 'Password baru tidak boleh sama dengan password saat ini.'
            ], 422);
        }

        // 3. Update password hash
        $hashed = Hash::make($newPassword);
        $user->password_hash = $hashed;
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'password')) {
            $user->password = $hashed;
        }
        $user->save();

        ApiLog::create([
            'school_id' => $user->school_id,
            'api_key' => 'MOBILE_APP',
            'action' => 'mobile_change_password',
            'uid' => $user->username ?? $user->email,
            'success' => true,
            'message' => "Ganti password berhasil untuk pengguna: {$user->full_name} ({$user->role})",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent() ?? 'Android Mobile App',
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password akun berhasil diubah. Silakan gunakan password baru ini untuk login berikutnya.'
        ]);
    }

    /**
     * Endpoint Update Data Profil & Kontak Siswa (POST /api/mobile/student/profile atau /contact)
     * Dapat digunakan oleh Siswa sendiri (mengubah profil pribadinya)
     * atau oleh Guru / Wali Kelas / Admin (dengan menyertakan parameter student_id).
     *
     * Menggabungkan update Tanggal Lahir, Nomor WA Siswa, Nomor WA Ortu, dan Email dalam 1 endpoint.
     *
     * Parameter (minimal salah satu):
     * - student_id (opsional jika siswa sendiri, wajib jika diakses guru/staf)
     * - tgl_lahir (opsional, string tanggal lahir format: YYYY-MM-DD, DD-MM-YYYY, DD/MM/YYYY)
     * - email (opsional, string valid email)
     * - no_wa (opsional, string nomor WA siswa, misal: 08123456789 atau 628123456789)
     * - wa_ortu (opsional, string nomor WA orang tua, misal: 08123456789 atau 628123456789)
     */
    public function updateStudentContact(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $studentId = $request->input('student_id');
        $siswa = null;
        $isStaff = in_array($user->role, ['guru', 'teacher', 'wali_kelas', 'admin', 'super_admin']) || !empty($user->guru);

        if ($studentId) {
            $siswa = Siswa::find($studentId);
            if (!$siswa) {
                return response()->json(['success' => false, 'message' => 'Data siswa tidak ditemukan.'], 404);
            }

            // Validasi hak akses sekolah
            if ($user->school_id && $siswa->school_id && $user->school_id != $siswa->school_id && !$user->isSuperAdmin()) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak: Siswa berada di sekolah yang berbeda.'], 403);
            }

            // Jika bukan staf/guru, pastikan hanya dapat mengedit profil dirinya sendiri
            if (!$isStaff && $siswa->user_id !== $user->id) {
                $cleanNis = str_replace('siswa_', '', $user->username);
                if ($siswa->nis !== $cleanNis) {
                    return response()->json(['success' => false, 'message' => 'Akses ditolak: Anda hanya dapat mengubah profil Anda sendiri.'], 403);
                }
            }
        } else {
            // Jika login sebagai Guru / Pegawai dan tidak menyertakan student_id,
            // maka perbarui data profil akun Guru tersebut sendiri!
            if ($user->guru || in_array($user->role, ['guru', 'teacher', 'wali_kelas'])) {
                return $this->updateGuruProfile($request, $user);
            }

            // Ambil dari profil login user siswa
            $siswa = $user->student ?? Siswa::where('user_id', $user->id)->first();
            if (!$siswa && in_array($user->role, ['student', 'siswa'])) {
                $cleanNis = str_replace('siswa_', '', $user->username);
                $siswa = Siswa::where('nis', $cleanNis)
                    ->when($user->school_id, fn($q) => $q->where('school_id', $user->school_id))
                    ->first() ?? Siswa::where('nis', $cleanNis)->first();
            }

            if (!$siswa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data siswa tidak ditemukan atau sertakan student_id jika diakses oleh akun dewan guru/admin.'
                ], 422);
            }
        }

        // Cari user account yang terhubung ke siswa ini
        $studentUser = $siswa->user ?? ($siswa->user_id ? User::find($siswa->user_id) : null);
        if (!$studentUser) {
            $studentUser = User::where('school_id', $siswa->school_id)
                ->where('role', 'student')
                ->where(function ($q) use ($siswa) {
                    $q->where('username', 'siswa_' . $siswa->nis)
                      ->orWhere('username', $siswa->nis);
                })->first();
        }

        // Deteksi input yang dikirimkan (mendukung alias key)
        $hasEmail = $request->has('email') || $request->has('email_siswa');
        $hasNoWa = $request->has('no_wa') || $request->has('nomor_wa') || $request->has('phone') || $request->has('wa_siswa') || $request->has('wa');
        $hasWaOrtu = $request->has('wa_ortu') || $request->has('nomor_wa_ortu') || $request->has('no_wa_ortu') || $request->has('wa_orang_tua');
        $hasTglLahir = $request->has('tgl_lahir') || $request->has('tanggal_lahir') || $request->has('birth_date');

        if (!$hasEmail && !$hasNoWa && !$hasWaOrtu && !$hasTglLahir) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data yang dikirim untuk diperbarui. Sertakan minimal salah satu dari: tgl_lahir, no_wa, wa_ortu, atau email.'
            ], 422);
        }

        $emailInput = trim((string) $request->input('email', $request->input('email_siswa', '')));
        $rawNoWa = $request->input('no_wa', $request->input('nomor_wa', $request->input('phone', $request->input('wa_siswa', $request->input('wa')))));
        $rawWaOrtu = $request->input('wa_ortu', $request->input('nomor_wa_ortu', $request->input('no_wa_ortu', $request->input('wa_orang_tua'))));
        $rawTglLahir = $request->input('tgl_lahir', $request->input('tanggal_lahir', $request->input('birth_date')));

        // 1. Validasi Email jika dikirim
        if ($hasEmail) {
            if ($emailInput === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Email tidak boleh dikosongkan.'
                ], 422);
            }

            if (!filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format email tidak valid.'
                ], 422);
            }

            // Pastikan email unik di tabel users kecuali untuk user siswa ini
            $emailTaken = User::where('email', $emailInput)
                ->when($studentUser, function ($q) use ($studentUser) {
                    $q->where('id', '!=', $studentUser->id);
                })
                ->exists();

            if ($emailTaken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email sudah digunakan oleh akun lain. Silakan gunakan email lain.'
                ], 422);
            }
        }

        // 2. Validasi Nomor WA Siswa jika dikirim
        $cleanNoWa = null;
        if ($hasNoWa) {
            $rawStr = trim((string) $rawNoWa);
            if ($rawStr !== '') {
                if (!preg_match('/^(\+?62|08|628)[0-9\-\s]{7,18}$/', $rawStr)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Format nomor WhatsApp siswa tidak valid. Gunakan awalan 08 atau 62 (contoh: 08123456789).'
                    ], 422);
                }
                $cleanNoWa = preg_replace('/[^0-9]/', '', $rawStr);
                if (strlen($cleanNoWa) < 9 || strlen($cleanNoWa) > 16) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nomor WhatsApp siswa harus terdiri dari 9 hingga 16 digit angka.'
                    ], 422);
                }
            }
        }

        // 3. Validasi Nomor WA Orang Tua jika dikirim
        $cleanWaOrtu = null;
        if ($hasWaOrtu) {
            $rawOrtuStr = trim((string) $rawWaOrtu);
            if ($rawOrtuStr !== '') {
                if (!preg_match('/^(\+?62|08|628)[0-9\-\s]{7,18}$/', $rawOrtuStr)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Format nomor WhatsApp orang tua tidak valid. Gunakan awalan 08 atau 62 (contoh: 08123456789).'
                    ], 422);
                }
                $cleanWaOrtu = preg_replace('/[^0-9]/', '', $rawOrtuStr);
                if (strlen($cleanWaOrtu) < 9 || strlen($cleanWaOrtu) > 16) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nomor WhatsApp orang tua harus terdiri dari 9 hingga 16 digit angka.'
                    ], 422);
                }
            }
        }

        // 4. Validasi Tanggal Lahir jika dikirim
        $cleanTglLahir = null;
        if ($hasTglLahir) {
            $rawDateStr = trim((string) $rawTglLahir);
            if ($rawDateStr !== '') {
                try {
                    $parsedDate = Carbon::parse($rawDateStr);
                    if ($parsedDate->isFuture()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Tanggal lahir tidak boleh di masa depan.'
                        ], 422);
                    }
                    $cleanTglLahir = $parsedDate->format('Y-m-d');
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Format tanggal lahir tidak valid (gunakan format YYYY-MM-DD, contoh: 2008-05-15).'
                    ], 422);
                }
            }
        }

        // 5. Update Email pada Akun User Siswa
        if ($hasEmail) {
            if ($studentUser) {
                $studentUser->email = $emailInput;
                $studentUser->save();
            } else {
                $defaultPass = $cleanTglLahir ?? ($siswa->tgl_lahir ? Carbon::parse($siswa->tgl_lahir)->format('dmY') : $siswa->nis);
                $studentUser = User::create([
                    'full_name' => $siswa->nama,
                    'username' => 'siswa_' . $siswa->nis,
                    'email' => $emailInput,
                    'password_hash' => Hash::make($defaultPass),
                    'role' => 'student',
                    'school_id' => $siswa->school_id,
                ]);
                $siswa->user_id = $studentUser->id;
            }
        }

        // 6. Update Data pada Tabel Siswa
        if ($hasTglLahir) {
            $siswa->tgl_lahir = $cleanTglLahir;
        }

        if ($hasNoWa) {
            $siswa->no_wa = $cleanNoWa;
        }

        if ($hasWaOrtu) {
            $siswa->wa_ortu = $cleanWaOrtu;
        }

        if ($studentUser && \Illuminate\Support\Facades\Schema::hasColumn('siswa', 'user_id')) {
            if ($siswa->user_id !== $studentUser->id) {
                $siswa->user_id = $studentUser->id;
            }
        }

        $siswa->save();

        ApiLog::create([
            'school_id' => $siswa->school_id,
            'api_key' => 'MOBILE_APP',
            'action' => 'mobile_update_student_profile',
            'uid' => $user->username ?? $user->email,
            'success' => true,
            'message' => "Update profil siswa berhasil: {$siswa->nama} (NIS: {$siswa->nis})",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent() ?? 'Android Mobile App',
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data profil siswa berhasil diperbarui.',
            'data' => [
                'student_id' => $siswa->id,
                'nama' => $siswa->nama,
                'nis' => $siswa->nis,
                'tgl_lahir' => $siswa->tgl_lahir ? Carbon::parse($siswa->tgl_lahir)->format('Y-m-d') : null,
                'email' => $studentUser?->email,
                'no_wa' => $siswa->no_wa,
                'wa_ortu' => $siswa->wa_ortu,
            ]
        ]);
    }

    /**
     * Helper update profil akun Guru (Tanggal Lahir, No WA, Email)
     */
    protected function updateGuruProfile(Request $request, User $user)
    {
        $guru = $user->guru ?? Guru::where('user_id', $user->id)->first();
        if (!$guru && in_array($user->role, ['guru', 'teacher', 'wali_kelas'])) {
            $guru = Guru::where('school_id', $user->school_id)
                ->where(function ($q) use ($user) {
                    $q->where('nama', $user->full_name)
                      ->orWhere('nip', $user->username);
                })->first();
        }

        if (!$guru) {
            return response()->json([
                'success' => false,
                'message' => 'Data guru tidak ditemukan untuk akun ini.'
            ], 404);
        }

        $hasEmail = $request->has('email') || $request->has('email_guru');
        $hasNoWa = $request->has('no_wa') || $request->has('nomor_wa') || $request->has('phone') || $request->has('wa_guru') || $request->has('wa');
        $hasTglLahir = $request->has('tgl_lahir') || $request->has('tanggal_lahir') || $request->has('birth_date');

        if (!$hasEmail && !$hasNoWa && !$hasTglLahir) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data yang dikirim untuk diperbarui. Sertakan minimal salah satu dari: tgl_lahir, no_wa, atau email.'
            ], 422);
        }

        $emailInput = trim((string) $request->input('email', $request->input('email_guru', '')));
        $rawNoWa = $request->input('no_wa', $request->input('nomor_wa', $request->input('phone', $request->input('wa_guru', $request->input('wa')))));
        $rawTglLahir = $request->input('tgl_lahir', $request->input('tanggal_lahir', $request->input('birth_date')));

        // 1. Validasi Email
        if ($hasEmail) {
            if ($emailInput === '') {
                return response()->json(['success' => false, 'message' => 'Email tidak boleh dikosongkan.'], 422);
            }
            if (!filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
                return response()->json(['success' => false, 'message' => 'Format email tidak valid.'], 422);
            }
            $emailTaken = User::where('email', $emailInput)->where('id', '!=', $user->id)->exists();
            if ($emailTaken) {
                return response()->json(['success' => false, 'message' => 'Email sudah digunakan oleh akun lain. Silakan gunakan email lain.'], 422);
            }
        }

        // 2. Validasi Nomor WA
        $cleanNoWa = null;
        if ($hasNoWa) {
            $rawStr = trim((string) $rawNoWa);
            if ($rawStr !== '') {
                if (!preg_match('/^(\+?62|08|628)[0-9\-\s]{7,18}$/', $rawStr)) {
                    return response()->json(['success' => false, 'message' => 'Format nomor WhatsApp tidak valid. Gunakan awalan 08 atau 62 (contoh: 08123456789).'], 422);
                }
                $cleanNoWa = preg_replace('/[^0-9]/', '', $rawStr);
                if (strlen($cleanNoWa) < 9 || strlen($cleanNoWa) > 16) {
                    return response()->json(['success' => false, 'message' => 'Nomor WhatsApp harus terdiri dari 9 hingga 16 digit angka.'], 422);
                }
            }
        }

        // 3. Validasi Tanggal Lahir
        $cleanTglLahir = null;
        if ($hasTglLahir) {
            $rawDateStr = trim((string) $rawTglLahir);
            if ($rawDateStr !== '') {
                try {
                    $parsedDate = Carbon::parse($rawDateStr);
                    if ($parsedDate->isFuture()) {
                        return response()->json(['success' => false, 'message' => 'Tanggal lahir tidak boleh di masa depan.'], 422);
                    }
                    $cleanTglLahir = $parsedDate->format('Y-m-d');
                } catch (\Exception $e) {
                    return response()->json(['success' => false, 'message' => 'Format tanggal lahir tidak valid (gunakan format YYYY-MM-DD, contoh: 1985-07-15).'], 422);
                }
            }
        }

        // 4. Update Email di User
        if ($hasEmail) {
            $user->email = $emailInput;
            $user->save();
        }

        // 5. Update di Tabel Guru
        if ($hasTglLahir) {
            $guru->tgl_lahir = $cleanTglLahir;
        }
        if ($hasNoWa) {
            $guru->no_wa = $cleanNoWa;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('guru', 'user_id')) {
            if ($guru->user_id !== $user->id) {
                $guru->user_id = $user->id;
            }
        }
        $guru->save();

        ApiLog::create([
            'school_id' => $user->school_id,
            'api_key' => 'MOBILE_APP',
            'action' => 'mobile_update_guru_profile',
            'uid' => $user->username ?? $user->email,
            'success' => true,
            'message' => "Update profil guru berhasil: {$guru->nama} (NIP: {$guru->nip})",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent() ?? 'Android Mobile App',
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data profil guru berhasil diperbarui.',
            'data' => [
                'guru_id' => $guru->id,
                'nama' => $guru->nama,
                'nip' => $guru->nip,
                'tgl_lahir' => $guru->tgl_lahir ? Carbon::parse($guru->tgl_lahir)->format('Y-m-d') : null,
                'email' => $user->email,
                'no_wa' => $guru->no_wa,
            ]
        ]);
    }

    /**
     * Daftarkan token FCM ke user & siswa aktif, serta hapus token tersebut
     * dari akun user/siswa lain sebelumnya untuk menghindari notifikasi ganda ke perangkat yang sama.
     */
    protected function assignFcmToken(string $fcmToken, User $user, ?Siswa $siswa = null): void
    {
        $fcmToken = trim($fcmToken);
        if ($fcmToken === '') {
            return;
        }

        $targetSiswaId = $siswa?->id ?? $user->student?->id;
        if (!$targetSiswaId && in_array($user->role, ['student', 'siswa'])) {
            $cleanNis = str_replace('siswa_', '', $user->username);
            $foundSiswa = Siswa::where('user_id', $user->id)
                ->orWhere('nis', $cleanNis)
                ->first();
            $targetSiswaId = $foundSiswa?->id;
            if (!$siswa) {
                $siswa = $foundSiswa;
            }
        }

        // 1. Hapus token FCM yang sama dari seluruh akun user lain sebelumnya
        User::where('fcm_token', $fcmToken)
            ->where('id', '!=', $user->id)
            ->update(['fcm_token' => null]);

        // 2. Hapus token FCM yang sama dari seluruh data siswa lain sebelumnya
        if (\Illuminate\Support\Facades\Schema::hasColumn('siswa', 'fcm_token')) {
            Siswa::where('fcm_token', $fcmToken)
                ->when($targetSiswaId, function ($q) use ($targetSiswaId) {
                    $q->where('id', '!=', $targetSiswaId);
                })
                ->update(['fcm_token' => null]);
        }

        // 3. Simpan token ke user aktif saat ini
        $user->fcm_token = $fcmToken;
        $user->save();

        // 4. Simpan token ke siswa aktif saat ini (jika ada)
        if ($siswa && \Illuminate\Support\Facades\Schema::hasColumn('siswa', 'fcm_token')) {
            $siswa->fcm_token = $fcmToken;
            $siswa->save();
        }
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $currentToken = $user->fcm_token;
            $user->fcm_token = null;
            $user->save();

            if ($currentToken) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('siswa', 'fcm_token')) {
                    Siswa::where('fcm_token', $currentToken)->update(['fcm_token' => null]);
                }
            }
            if ($user->student && \Illuminate\Support\Facades\Schema::hasColumn('siswa', 'fcm_token')) {
                $user->student->fcm_token = null;
                $user->student->save();
            }

            if ($user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }
        }
        return response()->json(['success' => true, 'message' => 'Logout berhasil']);
    }
}
