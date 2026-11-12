<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Device;
use App\Models\ApiLog;
use App\Models\ScanHistory;
use App\Models\Jadwal;
use App\Models\Guru;
use App\Models\Siswa;
use App\Models\Attendance;
use App\Models\AbsensiGuru;
use App\Models\MessageQueue;
use App\Models\GateCard;
use App\Models\TeacherCheckoutSession;
use App\Models\Setting;
use App\Services\FcmNotificationService;

class RfidController extends Controller
{
    // Config
    const MAX_REQUESTS_PER_MINUTE = 180;
    const SCAN_COOLDOWN_SECONDS = 0;

    protected $wa;
    protected \App\Services\AttendanceCoreService $attendanceCore;
    protected \App\Services\PrayerTimeService $prayerTimeService;

    // Logging context
    protected $apiVersion = 1;
    private $currentApiKey = null;
    private $currentUid = null;
    private $currentSchoolId = null;
    private $hasLogged = false;
    private $isOfflineSync = false;

    public function __construct(
        \App\Services\WhatsAppService $wa,
        \App\Services\AttendanceCoreService $attendanceCore,
        \App\Services\PrayerTimeService $prayerTimeService
    ) {
        $this->wa = $wa;
        $this->attendanceCore = $attendanceCore;
        $this->prayerTimeService = $prayerTimeService;
    }

    private function handleGateScan($uid, $gateCard, $apiKey, $device, $now = null)
    {
        $res = $this->attendanceCore->handleGateCardScan($gateCard, [
            'school_id' => $device->school_id,
            'uid' => $uid,
            'now' => $now,
        ]);

        $gateName = $res['data']['nama'] ?? ($gateCard->name ?: 'Kartu Gerbang');
        $isClosed = ($res['type'] ?? '') === 'gate_closed';
        $action = $isClosed ? 'gate_closed' : 'gate_access';
        $logMsg = ($isClosed ? 'Sesi Kepulangan Ditutup: ' : 'Sesi Kepulangan Dibuka: ') . $gateName;

        $this->logRequest($apiKey, $action, $uid, $res['ok'], $logMsg);

        return $this->response(
            $res['ok'],
            $res['ok'] ? 'success' : 'gagal',
            $isClosed ? "Gerbang Ditutup." : "Gerbang Dibuka (30 Menit).",
            $res['sound'] ?? 'ok',
            [
                'type' => $res['type'] ?? ($isClosed ? 'gate_closed' : 'gate_opened'),
                'nama' => $gateName,
            ]
        );
    }

    private function handleTeacherScan($uid, $teacher, $apiKey, $device, $now = null)
    {
        $res = $this->attendanceCore->recordTeacherAttendance($teacher, [
            'school_id' => $device->school_id,
            'uid' => $uid,
            'now' => $now,
            'channel' => 'rfid',
            'mode' => 'auto',
        ]);

        $action = match ($res['type'] ?? '') {
            'no_shift', 'outside_checkin_window', 'checkout_closed_guru', 'checkout_early_rejected_guru' => 'scan_rejected',
            'absen_pulang_guru' => 'checkout_success',
            'checkout_session_closed' => 'gate_closed',
            'checkout_session_open' => 'gate_access',
            default => $res['ok'] ? 'checkin_success' : 'scan_rejected',
        };

        $this->logRequest($apiKey, $action, $uid, $res['ok'], $res['message'] ?? 'Absensi Guru');

        $extra = [
            'type' => $res['type'] ?? 'absen_guru',
            'nama' => $teacher->nama,
            'status' => $res['data']['status'] ?? '',
            'jam' => $res['data']['jam'] ?? ($now ? $now->format('H:i') : now()->format('H:i')),
        ];
        if (isset($res['data']['shift_name'])) {
            $extra['shift_name'] = $res['data']['shift_name'];
        }
        if (isset($res['data']['menit_terlambat'])) {
            $extra['menit_terlambat'] = $res['data']['menit_terlambat'];
        }
        if (isset($res['data']['session_id'])) {
            $extra['session_id'] = $res['data']['session_id'];
        }

        return $this->response(
            $res['ok'],
            $res['status'] === 'success' ? 'success' : 'gagal',
            $res['message'] ?? 'Absensi Guru',
            $res['sound'] ?? 'ok',
            $extra
        );
    }

    public function handleV2(Request $request)
    {
        $this->apiVersion = 2;
        return $this->handle($request);
    }

    public function handle(Request $request)
    {
        // 0. Quick Ping / Heartbeat Check (Tanpa query DB / Log bloat)
        if ($request->has('ping') || ($request->isMethod('get') && !$request->has('uid'))) {
            return response()->json([
                'ok' => true,
                'message' => 'pong',
                'version' => $this->apiVersion === 2 ? 'v2' : 'v1'
            ], 200);
        }

        $apiKey = trim($request->input('api_key', ''));
        $this->currentApiKey = $apiKey;

        // 1. Auth API Key
        if ($apiKey === '') {
            $this->logFailedAuth('', 'API key kosong', $request);
            return $this->response(false, 'gagal', 'API key tidak boleh kosong', 'error');
        }

        $device = $this->authenticate($apiKey, $request);
        if (!$device) {
            return $this->response(false, 'gagal', 'API key tidak valid', 'error');
        }

        // 2. Input Validation
        $uid = trim($request->input('uid', ''));
        $this->currentUid = $uid;

        if ($uid === '') {
            $this->logRequest($apiKey, 'validation_error', '', false, 'UID kosong');
            return $this->response(false, 'gagal', 'UID kosong', 'error');
        }

        $uid = $this->validateUID($uid);

        // 3. Parse scanned_at (offline sync dari device)
        // Jika device mengirim scanned_at, gunakan sebagai waktu absen
        // Dibatasi maks 7 hari ke belakang untuk keamanan
        $now = now();
        $scannedAt = trim($request->input('scanned_at', ''));
        if ($scannedAt !== '') {
            try {
                $parsed = Carbon::parse($scannedAt);
                $maxBack = now()->subDays(7);
                if ($parsed->lte(now()) && $parsed->gte($maxBack)) {
                    $now = $parsed;
                    $this->isOfflineSync = true;
                    Log::info("[OFFLINE SYNC] uid={$uid} scanned_at={$scannedAt}");
                }
            } catch (\Exception $e) {
                // scanned_at tidak valid, pakai now()
            }
        }

        // 4. Cooldown
        if ($res = $this->checkScanCooldown($uid)) {
            return $res;
        }

        // 5. Mode Detection

        // Check Enrollment - SCOPED
        // Process enrollment FIRST to catch duplicates of already registered cards
        if ($this->hasEnrollmentRequest($device->school_id)) {
            return $this->handleEnroll($uid, $apiKey, $device);
        }

        // Check Scanner Sholat - SCOPED (Dibedakan melalui API Key / device_category = 'sholat')
        if ($device->isSholatDevice()) {
            return $this->handlePrayerScan($uid, $apiKey, $device, $now);
        }

        // Check Gate Card - SCOPED
        $gateCard = GateCard::with('guru')
            ->whereRaw('UPPER(uid_rfid) = ?', [$uid])
            ->where('school_id', $device->school_id)
            ->first();
        if ($gateCard) {
            return $this->handleGateScan($uid, $gateCard, $apiKey, $device, $now);
        }

        // Check Teacher - SCOPED
        $teacher = $this->checkTeacherCard($uid, $device->school_id);
        if ($teacher) {
            return $this->handleTeacherScan($uid, $teacher, $apiKey, $device, $now);
        }

        // Default: Scan Absensi - SCOPED
        return $this->handleScan($uid, $apiKey, $device, $now);
    }

    // ... Helper Methods ...

    private function logFailedAuth(string $apiKey, string $reason, $request = null)
    {
        ApiLog::create([
            'school_id' => null,
            'api_key' => $apiKey,
            'action' => 'auth_failed',
            'uid' => null,
            'success' => false,
            'message' => $reason,
            'ip_address' => $request ? $request->ip() : request()->ip(),
            'user_agent' => $request ? $request->userAgent() : request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    private function authenticate($apiKey, $request = null)
    {
        if (empty($apiKey))
            return null;

        $device = Device::where('api_key', $apiKey)->where('active', true)->first();
        if (!$device) {
            // Log auth failure dengan IP — visible ke SuperAdmin
            $this->logFailedAuth($apiKey, 'API key tidak valid / tidak aktif', $request);
            return null;
        }

        $this->currentSchoolId = $device->school_id;

        $schoolTz = \App\Models\Setting::where('school_id', $device->school_id)
            ->where('setting_key', 'timezone')
            ->value('setting_value');
        if ($schoolTz) {
            date_default_timezone_set($schoolTz);
            config(['app.timezone' => $schoolTz]);
        }

        // Update last used (manual query to avoid timestamp interfering if model timestamps disabled)
        DB::table('api_keys')->where('id', $device->id)->update(['last_used_at' => now()]);

        // Rate Limit (Hanya hitung request non-rate_limit agar penalti tidak menyebabkan self-fulfilling loop)
        $count = ApiLog::where('api_key', $apiKey)
            ->where('action', '!=', 'rate_limit')
            ->where('created_at', '>', now()->subMinute())
            ->count();

        if ($count > self::MAX_REQUESTS_PER_MINUTE) {
            // Cegah penumpukan log berulang dalam rentang 10 detik jika perangkat melakukan spam retry
            $recentRateLimitLog = ApiLog::where('api_key', $apiKey)
                ->where('action', 'rate_limit')
                ->where('created_at', '>', now()->subSeconds(10))
                ->exists();

            if (!$recentRateLimitLog) {
                $this->logRequest($apiKey, 'rate_limit', '', false, 'Rate limit exceeded');
            }

            $this->response(false, 'gagal', 'Terlalu banyak request. Tunggu sebentar.', 'error')->send();
            exit;
        }

        return $device;
    }

    private function validateUID($uid)
    {
        if (!preg_match('/^[A-F0-9]{8,20}$/i', $uid)) {
            $this->response(false, 'gagal', 'Format UID tidak valid', 'error', ['type' => 'invalid_uid'])->send();
            exit;
        }
        return strtoupper($uid);
    }

    private function checkScanCooldown($uid)
    {
        if (self::SCAN_COOLDOWN_SECONDS > 0) {
            $lastScan = ScanHistory::where('uid', $uid)
                ->where('created_at', '>', now()->subSeconds(self::SCAN_COOLDOWN_SECONDS))
                ->first();

            if ($lastScan) {
                return $this->response(false, 'gagal', 'Tunggu sebentar...', 'warning', ['type' => 'scan_cooldown']);
            }
        }

        ScanHistory::create(['uid' => $uid, 'created_at' => now()]);
        return null;
    }

    private function checkTeacherCard($uid, $schoolId)
    {
        return Guru::whereRaw('UPPER(uid_rfid) = ?', [$uid])
            ->where('school_id', $schoolId)
            ->first();
    }

    private function hasEnrollmentRequest($schoolId)
    {
        $siswa = Siswa::where('enroll_status', 'requested')
            ->where('school_id', $schoolId)
            ->exists();

        $guru = Guru::where('enroll_status', 'requested')
            ->where('school_id', $schoolId)
            ->exists();

        $gate = GateCard::where('enroll_status', 'requested')
            ->where('school_id', $schoolId)
            ->exists();

        return $siswa || $guru || $gate;
    }



    private function handleEnroll($uid, $apiKey, $device)
    {
        DB::beginTransaction();
        try {
            // Check duplicate in both tables
            // Optionally, check duplicate globally?
            // Better to enforce unique RFID globally to prevent confusion,
            // OR if cards are reused, verify they aren't active in THIS school.
            // Let's enforce Global Uniqueness for UID to prevent security issues.
            /*
            if (Siswa::where('uid_rfid', $uid)->exists() || Guru::where('uid_rfid', $uid)->exists()) {
                DB::rollBack();
                return $this->response(false, 'gagal', 'UID sudah ada', 'warning');
            }
            */
            // Actually, for multi-tenant, maybe same card IS allowed in diff schools?
            // But usually cards are unique tokens. Let's check THIS school first.
            if (
                Siswa::where('uid_rfid', $uid)->where('school_id', $device->school_id)->exists() ||
                Guru::where('uid_rfid', $uid)->where('school_id', $device->school_id)->exists() ||
                GateCard::where('uid_rfid', $uid)->where('school_id', $device->school_id)->exists()
            ) {
                DB::rollBack();

                Siswa::where('enroll_status', 'requested')
                    ->where('school_id', $device->school_id)
                    ->update(['enroll_status' => 'error:UID Dipakai']);
                Guru::where('enroll_status', 'requested')
                    ->where('school_id', $device->school_id)
                    ->update(['enroll_status' => 'error:UID Dipakai']);
                GateCard::where('enroll_status', 'requested')
                    ->where('school_id', $device->school_id)
                    ->update(['enroll_status' => 'error:UID Dipakai']);

                return $this->response(false, 'gagal', 'Kartu sudah terdaftar di sekolah ini', 'warning');
            }

            // 1. Check Siswa Request (Scoped to School)
            $siswa = Siswa::where('enroll_status', 'requested')
                ->where('school_id', $device->school_id)
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            if ($siswa) {
                $siswa->update([
                    'uid_rfid' => $uid,
                    'enroll_status' => 'done'
                ]);
                DB::commit();

                // WA Notification
                try {
                    $this->wa->sendEnrollSuccess($siswa->nama, $siswa->no_wa, $uid, $device->school_id, 'Kartu Siswa', $siswa->wa_ortu);
                } catch (\Exception $e) {
                    Log::error("WA Enroll Error: " . $e->getMessage());
                }

                $this->logRequest($apiKey, 'enroll_success', $uid, true, 'Enroll Siswa berhasil: ' . $siswa->nama);
                return $this->response(true, 'success', 'Enroll Siswa berhasil', 'ok', [
                    'type' => 'enroll_rfid',
                    'nama' => $siswa->nama,
                    'uid' => $uid
                ]);
            }

            // 2. Check Guru Request (Scoped to School)
            $guru = Guru::where('enroll_status', 'requested')
                ->where('school_id', $device->school_id)
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            if ($guru) {
                $guru->update([
                    'uid_rfid' => $uid,
                    'enroll_status' => 'done'
                ]);
                DB::commit();

                // WA Notification
                try {
                    $this->wa->sendEnrollSuccess($guru->nama, $guru->no_wa, $uid, $device->school_id, 'Kartu Guru');
                } catch (\Exception $e) {
                    Log::error("WA Enroll Error: " . $e->getMessage());
                }

                $this->logRequest($apiKey, 'enroll_success', $uid, true, 'Enroll Guru berhasil: ' . $guru->nama);
                return $this->response(true, 'success', 'Enroll Guru berhasil', 'ok', [
                    'type' => 'enroll_rfid',
                    'nama' => $guru->nama,
                    'uid' => $uid
                ]);
            }

            // 3. Check Gate Card Request (Scoped to School)
            $gate = GateCard::where('enroll_status', 'requested')
                ->where('school_id', $device->school_id)
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            if ($gate) {
                $gate->update([
                    'uid_rfid' => $uid,
                    'enroll_status' => 'done'
                ]);
                DB::commit();

                $this->logRequest($apiKey, 'enroll_success', $uid, true, 'Enroll Kartu Gerbang berhasil: ' . $gate->name);
                return $this->response(true, 'success', 'Enroll Kartu Gerbang berhasil', 'ok', [
                    'type' => 'enroll_rfid',
                    'nama' => $gate->name,
                    'uid' => $uid
                ]);
            }

            // Neither found (expired race condition?)
            DB::rollBack();
            return $this->response(false, 'gagal', 'Tidak ada permintaan enroll', 'warning', ['type' => 'enroll_request_not_found']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Enroll error: " . $e->getMessage());
            return $this->response(false, 'gagal', 'Terjadi kesalahan sistem', 'error');
        }
    }

    private function handleScan($uid, $apiKey, $device, $now = null)
    {
        // Find Student SCOPED to Device's School
        $siswa = Siswa::with('kelas')
            ->where('uid_rfid', $uid)
            ->where('school_id', $device->school_id)
            ->first();

        if (!$siswa) {
            $this->logRequest($apiKey, 'unknown_card', $uid, false, 'Kartu tidak terdaftar di sekolah ini');
            return $this->response(false, 'unknown', 'Kartu tdk dikenal', 'error', ['type' => 'unknown_card', 'uid' => $uid]);
        }

        // CHECK: Is Class Attendance Active?
        if ($siswa->kelas && !$siswa->kelas->is_active_attendance) {
            return $this->response(false, 'gagal', 'Absensi dimatikan untuk kelas ini.', 'error', ['type' => 'class_disabled']);
        }

        $res = $this->attendanceCore->recordStudentAttendance($siswa, [
            'school_id' => $device->school_id,
            'uid' => $uid,
            'now' => $now,
            'channel' => 'rfid',
            'mode' => 'auto',
        ]);

        $action = match ($res['type'] ?? '') {
            'hari_libur', 'di_luar_jam', 'checkout_closed', 'checkout_early_rejected' => 'scan_rejected',
            'pulang_normal', 'pulang_cepat' => 'checkout_success',
            'absen_kegiatan' => 'kegiatan_success',
            default => $res['ok'] ? 'checkin_success' : 'scan_rejected',
        };

        $this->logRequest($apiKey, $action, $uid, $res['ok'], $res['message']);

        $extra = [
            'type' => $res['type'] ?? 'absen_siswa',
            'nama' => $siswa->nama,
            'status' => $res['data']['status'] ?? '',
            'jam' => $res['data']['jam'] ?? ($now ? $now->format('H:i') : now()->format('H:i')),
        ];
        if (isset($res['data']['kegiatan'])) {
            $extra['kegiatan'] = $res['data']['kegiatan'];
        }
        if (isset($res['data']['jam_masuk'])) {
            $extra['jam_masuk'] = $res['data']['jam_masuk'];
        }
        if (isset($res['data']['jam_pulang'])) {
            $extra['jam_pulang'] = $res['data']['jam_pulang'];
        }

        return $this->response(
            $res['ok'],
            $res['status'] === 'success' ? 'success' : 'gagal',
            $res['message'],
            $res['sound'],
            $extra
        );
    }

    /**
     * Handle scan khusus perangkat Absensi Sholat (di Musholla/Masjid)
     * Dibedakan berdasarkan API Key perangkat ($device->isSholatDevice())
     */
    private function handlePrayerScan($uid, $apiKey, $device, $now = null)
    {
        $now = $now ? $now->copy() : now();
        $schoolId = $device->school_id;

        // 1. Identifikasi Pemilik Kartu (Siswa atau Guru di sekolah terkait)
        $siswa = Siswa::with('kelas')
            ->where('uid_rfid', $uid)
            ->where('school_id', $schoolId)
            ->first();

        $guru = null;
        if (!$siswa) {
            $guru = Guru::where('uid_rfid', $uid)
                ->where('school_id', $schoolId)
                ->first();
        }

        if (!$siswa && !$guru) {
            $this->logRequest($apiKey, 'unknown_card', $uid, false, 'Kartu tidak terdaftar di sekolah ini (Scanner Sholat)');
            return $this->response(false, 'unknown', "Kartu tdk dikenal\nSilakan Daftar", 'error', [
                'type' => 'unknown_card',
                'uid' => $uid
            ]);
        }

        $isSiswa = ($siswa !== null);
        $name = $isSiswa ? $siswa->nama : $guru->nama;
        $personId = $isSiswa ? $siswa->id : $guru->id;
        $personType = $isSiswa ? 'siswa' : 'guru';

        // 2. Evaluasi Rentang Waktu Sholat Aktif Berdasarkan Koordinat & Offset
        $activeWindow = $this->prayerTimeService->determineActivePrayerWindow($schoolId, $now);
        if (!$activeWindow) {
            $this->logRequest($apiKey, 'sholat_outside_window', $uid, false, "Di luar waktu absensi sholat ({$name})");
            return $this->response(
                false,
                'gagal',
                "Di Luar Waktu\nSholat",
                'error',
                [
                    'type' => 'sholat_outside_window',
                    'nama' => $name,
                    'jam'  => $now->format('H:i')
                ]
            );
        }

        $sholatCode  = $activeWindow['sholat'];
        $sholatLabel = $activeWindow['sholat_label'];
        $today       = $now->format('Y-m-d');

        // 3. Cek Duplikasi Absen Sholat Hari Ini
        $existing = \App\Models\AbsensiSholat::where('school_id', $schoolId)
            ->where('tanggal', $today)
            ->where('sholat', $sholatCode)
            ->where(function ($q) use ($isSiswa, $personId) {
                if ($isSiswa) {
                    $q->where('student_id', $personId);
                } else {
                    $q->where('guru_id', $personId);
                }
            })
            ->first();

        if ($existing) {
            $this->logRequest($apiKey, 'sholat_duplicate', $uid, false, "Sudah absen sholat {$sholatLabel}: {$name}");
            return $this->response(
                false,
                'gagal',
                "Sudah Absen!\nSholat {$sholatLabel}",
                'error',
                [
                    'type'      => 'sholat_duplicate',
                    'sholat'    => $sholatLabel,
                    'nama'      => $name,
                    'jam_absen' => substr($existing->jam_absen, 0, 5),
                ]
            );
        }

        // 4. Catat Absensi Sholat
        $attendance = \App\Models\AbsensiSholat::create([
            'school_id'    => $schoolId,
            'device_id'    => $device->id,
            'person_type'  => $personType,
            'student_id'   => $isSiswa ? $personId : null,
            'guru_id'      => $isSiswa ? null : $personId,
            'tanggal'      => $today,
            'sholat'       => $sholatCode,
            'jam_absen'    => $now->format('H:i:s'),
            'waktu_sholat' => $activeWindow['waktu_sholat'],
            'status'       => $activeWindow['status'], // 'hadir' atau 'terlambat'
            'keterangan'   => $activeWindow['is_ontime'] ? 'Tepat Waktu' : 'Terlambat/Masbuk',
        ]);

        $this->logRequest($apiKey, 'sholat_success', $uid, true, "Absen sholat {$sholatLabel} berhasil: {$name}");

        $shortName = \Illuminate\Support\Str::limit($name, 16, '');
        $msg = "{$sholatLabel} Berhasil\n{$shortName}";

        return $this->response(
            true,
            'success',
            $msg,
            'ok',
            [
                'type'         => 'sholat_success',
                'sholat'       => $sholatLabel,
                'nama'         => $name,
                'jam'          => $now->format('H:i'),
                'waktu_sholat' => $activeWindow['waktu_sholat'],
                'status'       => $activeWindow['status'],
                'role'         => $personType,
            ]
        );
    }

    private function response($ok, $status, $message, $sound = 'ok', $extra = [])
    {
        if (!$this->hasLogged) {
            $action = $extra['type'] ?? $status;
            // Use derived action implies we didn't have a specific event like checkin_success
            // So we log it as a generic API response or the status itself
            $this->logRequest($this->currentApiKey, $action, $this->currentUid, $ok, $message);
        }

        if ($this->apiVersion === 2) {
            $display = $this->formatLcdDisplay($ok, $status, $message, $extra);
            $v2Sound = $this->determineV2Sound($ok, $sound, $extra['type'] ?? null);

            $resV2 = [
                'ok' => $ok,
                'status' => $status,
                'message' => $message,
                'display' => $display,
                'sound' => $v2Sound,
                'timestamp' => now()->toDateTimeString()
            ];
            return response()->json(array_merge($resV2, $extra));
        }

        $res = [
            'ok' => $ok,
            'status' => $status,
            'message' => $message,
            'sound' => $sound,
            'timestamp' => now()->toDateTimeString()
        ];
        return response()->json(array_merge($res, $extra));
    }

    private function formatLcdDisplay(bool $ok, string $status, string $message, array $extra = []): array
    {
        $type = $extra['type'] ?? '';
        $nama = $extra['nama'] ?? '';

        // Baris 1: Judul status / aksi (maks 16 karakter)
        $line1 = 'SUKSES';
        if ($ok) {
            switch ($type) {
                case 'absen_masuk':
                case 'absen_masuk_guru':
                    $isLate = ($extra['status'] ?? '') === 'Terlambat' || (!empty($extra['menit_terlambat']) && $extra['menit_terlambat'] > 0);
                    $line1 = $isLate ? 'MASUK (TELAT)' : 'ABSEN MASUK';
                    break;
                case 'absen_pulang':
                case 'absen_pulang_guru':
                    $line1 = 'ABSEN PULANG';
                    break;
                case 'sudah_absen_masuk':
                case 'absen_sudah_masuk_guru':
                    $line1 = 'SUDAH ABSEN';
                    break;
                case 'sudah_lengkap':
                    $line1 = 'ABSEN LENGKAP';
                    break;
                case 'absen_kegiatan':
                    $line1 = 'ABSEN KEGIATAN';
                    break;
                case 'enroll_rfid':
                    $line1 = 'ENROLL SUKSES';
                    break;
                case 'gate_opened':
                    $line1 = 'PULANG DIBUKA';
                    break;
                case 'gate_closed':
                    $line1 = 'PULANG DITUTUP';
                    break;
                case 'sholat_success':
                    $sholatName = strtoupper($extra['sholat'] ?? 'SHOLAT');
                    $line1 = "SHOLAT {$sholatName}";
                    break;
                default:
                    $line1 = 'BERHASIL';
                    break;
            }
        } else {
            switch ($type) {
                case 'unknown_card':
                    $line1 = 'KARTU TDK DIKENAL';
                    break;
                case 'scan_cooldown':
                    $line1 = 'TUNGGU SEBENTAR';
                    break;
                case 'schedule_empty':
                    $line1 = 'JADWAL LIBUR';
                    break;
                case 'class_disabled':
                    $line1 = 'ABSEN NONAKTIF';
                    break;
                case 'no_shift':
                    $line1 = 'TIDAK ADA SHIFT';
                    break;
                case 'outside_checkin_window':
                    $line1 = 'DILUAR JAM MASUK';
                    break;
                case 'outside_checkout_window':
                    $line1 = 'DILUAR PULANG';
                    break;
                case 'rate_limit':
                    $line1 = 'TERLALU CEPAT';
                    break;
                case 'invalid_uid':
                    $line1 = 'UID TDK VALID';
                    break;
                case 'sholat_outside_window':
                    $line1 = 'DILUAR JAM';
                    break;
                case 'sholat_duplicate':
                    $line1 = 'SUDAH ABSEN';
                    break;
                default:
                    $line1 = 'ABSEN GAGAL!';
                    break;
            }
        }

        // Baris 2: Nama atau rincian (maks 16 karakter)
        $line2 = '';
        if ($type === 'sholat_outside_window') {
            $line2 = 'WAKTU SHOLAT';
        } elseif ($type === 'sholat_duplicate') {
            $sholatName = strtoupper($extra['sholat'] ?? 'SHOLAT');
            $line2 = "SHOLAT {$sholatName}";
        } elseif (!empty($nama)) {
            $menitTerlambat = $extra['menit_terlambat'] ?? 0;
            if ($menitTerlambat > 0) {
                $suffix = " (+{$menitTerlambat}m)";
                $maxNamaLen = 16 - strlen($suffix);
                if (strlen($nama) > $maxNamaLen) {
                    $nama = substr($nama, 0, $maxNamaLen);
                }
                $line2 = $nama . $suffix;
            } else {
                $line2 = strlen($nama) > 16 ? substr($nama, 0, 13) . '...' : $nama;
            }
        } else {
            $msg = !empty($message) ? $message : ($ok ? 'Berhasil' : 'Gagal');
            $line2 = strlen($msg) > 16 ? substr($msg, 0, 13) . '...' : $msg;
        }

        return [
            'line1' => substr($line1, 0, 16),
            'line2' => substr($line2, 0, 16),
            'duration_ms' => 3000,
        ];
    }

    private function determineV2Sound(bool $ok, string $defaultSound = 'ok', ?string $type = null): string
    {
        if (!$ok) {
            if ($type === 'scan_cooldown') {
                return 'short';
            }
            return 'error';
        }

        switch ($type) {
            case 'enroll_rfid':
            case 'gate_opened':
            case 'gate_closed':
                return 'triple';
            case 'sudah_absen_masuk':
            case 'absen_sudah_masuk_guru':
            case 'sudah_lengkap':
                return 'short';
            case 'absen_masuk':
            case 'absen_masuk_guru':
            case 'absen_pulang':
            case 'absen_pulang_guru':
            case 'absen_kegiatan':
            default:
                return 'ok';
        }
    }

    private function logRequest($apiKey, $action, $uid, $success, $message)
    {
        $this->hasLogged = true;

        if ($this->isOfflineSync && !str_contains($message, '[Sync]')) {
            $message = '[Sync] ' . $message;
        }

        ApiLog::create([
            'school_id' => $this->currentSchoolId,
            'api_key' => $apiKey,
            'action' => $action,
            'uid' => $uid,
            'success' => $success,
            'message' => substr($message, 0, 500),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now()
        ]);
    }

    private function sendWhatsApp($phone, $message)
    {
        $phone = $this->formatWhatsApp($phone);
        if ($phone) {
            MessageQueue::create([
                'phone_number' => $phone,
                'message' => $message,
                'status' => 'pending',
                'created_at' => now()
            ]);
        }
    }

    private function formatWhatsApp($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', (string)$phone);
        if (empty($phone))
            return null;
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        return $phone;
    }

    /**
     * Catat kehadiran siswa pada kegiatan yang sedang aktif dan relevan saat scan.
     */
    private function recordActiveKegiatans($schoolId, $siswa, $now, $today, $keterangan = 'Auto dari Scan Mesin'): array
    {
        return $this->attendanceCore->recordActiveKegiatans($schoolId, $siswa, $now, $today, $keterangan);
    }
}
