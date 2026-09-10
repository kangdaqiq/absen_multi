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
use App\Models\Setting;
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

            // Save FCM token if provided
            if ($request->filled('fcm_token')) {
                $user->fcm_token = $request->fcm_token;
                $user->save();
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

                    // Save FCM token if provided
                    if ($request->filled('fcm_token')) {
                        $user->fcm_token = $request->fcm_token;
                        $user->save();
                        if (\Illuminate\Support\Facades\Schema::hasColumn('siswa', 'fcm_token')) {
                            $siswa->fcm_token = $request->fcm_token;
                            $siswa->save();
                        }
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
                            'school_id' => $siswa->school_id,
                            'is_wali_kelas' => false,
                            'wali_kelas' => false,
                            'guru' => null,
                            'student' => [
                                'id' => $siswa->id,
                                'nama' => $siswa->nama,
                                'nis' => $siswa->nis,
                                'tgl_lahir' => $siswa->tgl_lahir ? Carbon::parse($siswa->tgl_lahir)->format('Y-m-d') : null,
                                'kelas_id' => $siswa->kelas_id,
                                'kelas_name' => $siswa->kelas ? ($siswa->kelas->nama_kelas ?? $siswa->kelas->nama) : null,
                                'kelas' => $siswa->kelas ? [
                                    'id' => $siswa->kelas->id,
                                    'nama' => $siswa->kelas->nama_kelas ?? $siswa->kelas->nama,
                                    'nama_kelas' => $siswa->kelas->nama_kelas ?? $siswa->kelas->nama,
                                ] : null,
                            ]
                        ],
                        'is_wali_kelas' => false,
                        'school' => $siswa->school ? [
                            'id' => $siswa->school->id,
                            'nama' => $siswa->school->name ?? $siswa->school->nama,
                            'domain' => $siswa->school->domain ?? null,
                        ] : null
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
        if ($user->student) {
            $s = $user->student;
            $studentData = [
                'id' => $s->id,
                'nama' => $s->nama,
                'nis' => $s->nis,
                'tgl_lahir' => $s->tgl_lahir ? Carbon::parse($s->tgl_lahir)->format('Y-m-d') : null,
                'kelas_id' => $s->kelas_id,
                'kelas_name' => $s->kelas ? ($s->kelas->nama_kelas ?? $s->kelas->nama) : null,
                'kelas' => $s->kelas ? [
                    'id' => $s->kelas->id,
                    'nama' => $s->kelas->nama_kelas ?? $s->kelas->nama,
                    'nama_kelas' => $s->kelas->nama_kelas ?? $s->kelas->nama,
                ] : null,
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
                'school_id' => $user->school_id,
                'guru' => $user->guru ? [
                    'id' => $user->guru->id,
                    'nama' => $user->guru->nama,
                    'nip' => $user->guru->nip,
                ] : null,
                'student' => $studentData
            ]
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

        if ($user && ($user->student || in_array($user->role, ['student', 'siswa']))) {
            $student = $user->student ?? Siswa::with('kelas')->where('user_id', $user->id)->orWhere('nis', str_replace('siswa_', '', $user->username))->first();
            if ($student) {
                $absen = Attendance::where('student_id', $student->id)
                    ->where('tanggal', $today)
                    ->first();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'tanggal' => $today,
                        'jam_masuk' => $absen?->jam_masuk,
                        'jam_pulang' => $absen?->jam_pulang,
                        'status_kehadiran' => $absen?->status ?? 'Belum Absen',
                        'is_checked_in' => !empty($absen?->jam_masuk),
                        'is_checked_out' => !empty($absen?->jam_pulang),
                        'is_wali_kelas' => false,
                        'wali_kelas' => false,
                        'kelas_name' => $student->kelas ? ($student->kelas->nama_kelas ?? $student->kelas->nama) : null,
                        'kelas_id' => $student->kelas_id,
                    ]
                ]);
            }
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
     * Validate geofence radius if enabled
     * @return array [bool $isValid, string|null $errorMessage, float|null $distanceMeters, float|null $maxRadius]
     */
    private function validateGeofence($schoolId, $userLat, $userLng): array
    {
        $schoolId = $schoolId ?? 0;
        $enabled = Setting::where('school_id', $schoolId)->where('setting_key', 'geofence_enabled')->value('setting_value') ?? 'false';

        // Fallback to global setting if school setting not found
        if ($enabled === 'false' && $schoolId > 0) {
            $enabled = Setting::where('school_id', 0)->where('setting_key', 'geofence_enabled')->value('setting_value') ?? 'false';
        }

        if ($enabled !== 'true' && $enabled !== '1') {
            return [true, null, null, null];
        }

        $schoolLat = Setting::where('school_id', $schoolId)->where('setting_key', 'school_latitude')->value('setting_value');
        $schoolLng = Setting::where('school_id', $schoolId)->where('setting_key', 'school_longitude')->value('setting_value');
        $radius = (float) (Setting::where('school_id', $schoolId)->where('setting_key', 'geofence_radius')->value('setting_value') ?? 100);

        if (!$schoolLat || !$schoolLng) {
            // Coordinate not configured yet, don't block
            return [true, null, null, null];
        }

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

        $enabled = Setting::where('school_id', $schoolId)->where('setting_key', 'geofence_enabled')->value('setting_value') ?? 'false';
        $schoolLat = Setting::where('school_id', $schoolId)->where('setting_key', 'school_latitude')->value('setting_value');
        $schoolLng = Setting::where('school_id', $schoolId)->where('setting_key', 'school_longitude')->value('setting_value');
        $radius = (float) (Setting::where('school_id', $schoolId)->where('setting_key', 'geofence_radius')->value('setting_value') ?? 100);

        return response()->json([
            'success' => true,
            'data' => [
                'geofence_enabled' => ($enabled === 'true' || $enabled === '1'),
                'latitude' => $schoolLat ? (float) $schoolLat : null,
                'longitude' => $schoolLng ? (float) $schoolLng : null,
                'radius_meters' => $radius,
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

        // Geofence Radius Validation
        [$isValidGeo, $geoError, $distance, $maxRadius] = $this->validateGeofence($user?->school_id, $request->latitude, $request->longitude);
        if (!$isValidGeo) {
            ApiLog::create([
                'school_id' => $user?->school_id,
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

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('absensi_photos', 'public');
        }

        // Case 1: Guru / Admin / Wali Kelas
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

        // Case 2: Siswa (Student)
        if ($user && ($user->student || in_array($user->role, ['student', 'siswa']))) {
            $student = $user->student ?? Siswa::where('user_id', $user->id)->orWhere('nis', str_replace('siswa_', '', $user->username))->first();
            if ($student) {
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

                $att = Attendance::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'tanggal' => $today,
                    ],
                    [
                        'jam_masuk' => $nowTime,
                        'status' => 'H',
                        'keterangan' => "Absen via Mobile App (Lat: {$request->latitude}, Lng: {$request->longitude})"
                    ]
                );

                ApiLog::create([
                    'school_id' => $user->school_id,
                    'api_key' => 'MOBILE_APP',
                    'action' => 'mobile_checkin_student',
                    'uid' => $student->nis,
                    'success' => true,
                    'message' => "Absen Masuk Siswa: {$student->nama} (Lat: {$request->latitude}, Lng: {$request->longitude})",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent() ?? 'Android Mobile App',
                    'created_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Absen masuk siswa berhasil dicatat',
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
        }

        ApiLog::create([
            'school_id' => $user?->school_id,
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
        $today = Carbon::today()->format('Y-m-d');
        $nowTime = Carbon::now()->format('H:i:s');

        // Geofence Radius Validation
        [$isValidGeo, $geoError, $distance, $maxRadius] = $this->validateGeofence($user?->school_id, $request->latitude, $request->longitude);
        if (!$isValidGeo) {
            ApiLog::create([
                'school_id' => $user?->school_id,
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

        // Case 2: Siswa (Student)
        if ($user && ($user->student || in_array($user->role, ['student', 'siswa']))) {
            $student = $user->student ?? Siswa::where('user_id', $user->id)->orWhere('nis', str_replace('siswa_', '', $user->username))->first();
            if ($student) {
                $att = Attendance::where('student_id', $student->id)->where('tanggal', $today)->first();
                if (!$att || !$att->jam_masuk) {
                    return response()->json(['success' => false, 'message' => 'Anda belum melakukan absen masuk hari ini'], 400);
                }

                $att->update([
                    'jam_pulang' => $nowTime,
                ]);

                ApiLog::create([
                    'school_id' => $user->school_id,
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
                        'status_kehadiran' => $att->status,
                        'is_checked_in' => true,
                        'is_checked_out' => true
                    ]
                ]);
            }
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

        try {
            $startDate = Carbon::parse($startDate)->format('Y-m-d');
            $endDate = Carbon::parse($endDate)->format('Y-m-d');
        } catch (\Exception $e) {
            // Keep default
        }

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

        $namaKelas = $kelas->nama_kelas ?? $kelas->nama;
        $formattedKelasName = str_starts_with($namaKelas, 'Kelas') ? "{$namaKelas} (Wali Kelas)" : "Kelas {$namaKelas} (Wali Kelas)";

        return response()->json([
            'success' => true,
            'data' => [
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

        if ($user && (!in_array($user->role, ['guru', 'teacher', 'wali_kelas', 'admin']) && $user->role !== 'guru') || !$user->guru) {
            return response()->json(['success' => false, 'message' => 'Akses khusus dewan guru'], 403);
        }

        $rawTanggal = $request->query('tanggal', Carbon::today()->format('Y-m-d'));
        try {
            $tanggal = Carbon::parse($rawTanggal)->format('Y-m-d');
        } catch (\Exception $e) {
            $tanggal = Carbon::today()->format('Y-m-d');
        }

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

            $kelasName = $siswa->kelas ? ($siswa->kelas->nama_kelas ?? $siswa->kelas->nama) : 'Siswa';
            if ($siswa->kelas && !str_starts_with($kelasName, 'Kelas')) {
                $kelasName = 'Kelas ' . $kelasName;
            }

            return [
                'student_id' => $siswa->id,
                'nama' => $siswa->nama,
                'nis' => $siswa->nis,
                'kelas_name' => $kelasName,
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

        foreach ($request->attendances as $item) {
            $status = $item['status'];
            $isHadir = in_array($status, ['H', 'Hadir', 'T', 'Terlambat']);

            Attendance::updateOrCreate(
                [
                    'student_id' => $item['student_id'],
                    'tanggal' => $tanggal
                ],
                [
                    'school_id' => $user->school_id,
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
            'tanggal' => 'required',
            'student_id' => 'required|integer',
            'status' => 'required|string',
        ]);

        try {
            $tanggal = Carbon::parse($request->tanggal)->format('Y-m-d');
        } catch (\Exception $e) {
            $tanggal = $request->tanggal;
        }

        $status = $request->status;
        $isHadir = in_array($status, ['H', 'Hadir', 'T', 'Terlambat']);

        Attendance::updateOrCreate(
            [
                'student_id' => $request->student_id,
                'tanggal' => $tanggal
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

        if ($startDate && $endDate) {
            try {
                $startDate = Carbon::parse($startDate)->format('Y-m-d');
                $endDate = Carbon::parse($endDate)->format('Y-m-d');
            } catch (\Exception $e) {
                // Keep as is
            }
        } else {
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

    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();
        if ($user) {
            $user->fcm_token = $request->fcm_token;
            $user->save();

            if ($user->student) {
                $user->student->fcm_token = $request->fcm_token;
                $user->student->save();
            } elseif (in_array($user->role, ['student', 'siswa'])) {
                $cleanNis = str_replace('siswa_', '', $user->username);
                $siswa = Siswa::where('user_id', $user->id)->orWhere('nis', $cleanNis)->first();
                if ($siswa) {
                    $siswa->fcm_token = $request->fcm_token;
                    $siswa->save();
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'FCM Token berhasil diperbarui'
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
