<?php

namespace AppHttpControllersApi;

use AppHttpControllersController;
use IlluminateHttpRequest;
use IlluminateSupportFacadesDB;
use IlluminateSupportFacadesLog;
use CarbonCarbon;
use AppModelsDevice;
use AppModelsApiLog;
use AppModelsGuru;
use AppModelsGuruFingerprint;
use AppModelsSiswa;
use AppModelsSiswaFingerprint;
use AppModelsAttendance;
use AppModelsTeacherCheckoutSession;
use AppModelsGateCard;
use AppModelsGateCardFingerprint;

class R307FingerprintController extends Controller
{
    private $currentApiKey = null;
    private $currentId = null;
    private $currentSchoolId = null;
    protected $wa;
    protected $telegram;

    public function __construct(\AppServices\WhatsAppService $wa, \App\Services\TelegramService $telegram)
    {
        $this->wa = $wa;
        $this->telegram = $telegram;
    }

    public function handle(Request $request)
    {
        $ip = $request->ip();
        $isBlocked = \Illuminate\Support\Facades\Cache::remember("ip_blocked_" . $ip, 300, function () use ($ip) {
            $failedCount = \App\Models\ApiLog::where('ip_address', $ip)
                ->where('action', 'auth_failed')
                ->where('created_at', '>=', now()->subHours(24))
                ->count();
            return $failedCount >= 10;
        });

        if ($isBlocked) {
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'message' => 'IP Blocked'
            ], 403);
        }

        $apiKey = trim($request->input('api_key', ''));
        $this->currentApiKey = $apiKey;

        // Parse scanned_at (offline sync)
        $now = now();
        $scannedAt = trim($request->input('scanned_at', ''));
        if ($scannedAt !== '') {
            try {
                $parsed = Carbon::parse($scannedAt);
                if ($parsed->lte(now()) && $parsed->gte(now()->subDays(7))) {
                    $now = $parsed;
                }
            } catch (\Exception $e) {}
        }

        // 1. Auth: Get Device
        if ($apiKey === '') {
            $this->logFailedAuth('', 'API Key Kosong', $request);
            return $this->response(false, 'gagal', 'API Key Kosong');
        }

        $device = $this->authenticate($apiKey, $request);
        if (!$device) {
            return $this->response(false, 'gagal', 'API Key Invalid');
        }

        // 2. Input
        $fingerId = $request->input('finger_id');
        $this->currentId = $fingerId;

        // Check for Ping (Boot Notification)
        if ($request->has('ping')) {
            ApiLog::create([
                'school_id' => $this->currentSchoolId,
                'api_key' => $apiKey,
                'action' => 'ping',
                'uid' => null,
                'success' => true,
                'message' => 'Boot Ping (IP Record)',
                'ip_address' => $request->ip(),
                'created_at' => now()
            ]);
            return $this->response(true, 'ok', 'Pong');
        }

        // Check if this is an Enroll Confirmation (with template_data backup)
        if ($request->has('enroll_success') && $request->input('enroll_success') == true) {
            return $this->finalizeEnrollment($fingerId, $device, $request);
        }

        // Check if this is an Enroll Error Notification
        if ($request->has('enroll_error') && $request->input('enroll_error') == true) {
            $schoolId = $device->school_id;
            $message = $request->input('message', 'Enroll Gagal');

            // Guru
            $guru = Guru::where('enroll_finger_status', 'requested')
                ->where('school_id', $schoolId)
                ->first();
            if ($guru) {
                $guru->update(['enroll_finger_status' => null]);
                ApiLog::create([
                    'school_id' => $schoolId,
                    'api_key' => $apiKey,
                    'action' => 'enroll_failed',
                    'uid' => $fingerId,
                    'success' => false,
                    'message' => 'Enroll Gagal (Guru) ' . $guru->nama . ': ' . $message,
                    'created_at' => now()
                ]);
                return $this->response(true, 'ok', 'Status Guru Reset');
            }

            // Siswa
            $siswa = Siswa::where('enroll_finger_status', 'requested')
                ->where('school_id', $schoolId)
                ->first();
            if ($siswa) {
                $siswa->update(['enroll_finger_status' => null]);
                ApiLog::create([
                    'school_id' => $schoolId,
                    'api_key' => $apiKey,
                    'action' => 'enroll_failed',
                    'uid' => $fingerId,
                    'success' => false,
                    'message' => 'Enroll Gagal (Siswa) ' . $siswa->nama . ': ' . $message,
                    'created_at' => now()
                ]);
                return $this->response(true, 'ok', 'Status Siswa Reset');
            }

            // Gate Card
            $gate = GateCard::where('enroll_finger_status', 'requested')
                ->where('school_id', $schoolId)
                ->first();
            if ($gate) {
                $gate->update(['enroll_finger_status' => null]);
                ApiLog::create([
                    'school_id' => $schoolId,
                    'api_key' => $apiKey,
                    'action' => 'enroll_failed',
                    'uid' => $fingerId,
                    'success' => false,
                    'message' => 'Enroll Gagal (Gerbang) ' . $gate->name . ': ' . $message,
                    'created_at' => now()
                ]);
                return $this->response(true, 'ok', 'Status Gerbang Reset');
            }

            return $this->response(true, 'ok', 'Enroll Gagal Tercatat');
        }

        // 3. Scan Sidik Jari Normal
        return $this->handleScan($fingerId, $device, $now);
    }

    public function checkEnrollRequest(Request $request)
    {
        $apiKey = $request->input('api_key');
        $device = $this->authenticate($apiKey);
        if (!$device) {
            return $this->response(false, 'gagal', 'Auth Failed');
        }

        // 1. Cek apakah ada perintah Sync dari Server
        $syncRequested = \Illuminate\Support\Facades\Cache::pull('sync_finger_' . $device->id);
        if ($syncRequested) {
            return $this->response(true, 'sync_mode', 'Sync Mode Active', 'ok', [
                'type' => 'sync'
            ]);
        }

        // 2. Cek apakah ada perintah delete di cache
        $deleteId = \Illuminate\Support\Facades\Cache::pull('delete_finger_' . $device->id);
        if ($deleteId) {
            return $this->response(true, 'delete_mode', 'Delete Mode Active', 'ok', [
                'enroll_id' => $deleteId,
                'type' => 'delete'
            ]);
        }

        // 3. Check Guru Enroll Request
        $guru = Guru::where('enroll_finger_status', 'requested')
            ->where('school_id', $device->school_id)
            ->where('updated_at', '>=', now()->subMinutes(15))
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($guru) {
            $nextId = $this->getNextFreeFingerId($device->id);
            if ($nextId === -1) return $this->response(false, 'standby', 'Sensor Full (1000 ID)');
            return $this->response(true, 'enroll_mode', 'Enroll Mode Active (Guru)', 'ok', [
                'enroll_id' => $nextId,
                'nama' => $guru->nama,
                'type' => 'guru'
            ]);
        }

        // 4. Check Siswa Enroll Request
        $siswa = Siswa::where('enroll_finger_status', 'requested')
            ->where('school_id', $device->school_id)
            ->where('updated_at', '>=', now()->subMinutes(15))
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($siswa) {
            $nextId = $this->getNextFreeFingerId($device->id);
            if ($nextId === -1) return $this->response(false, 'standby', 'Sensor Full (1000 ID)');
            return $this->response(true, 'enroll_mode', 'Enroll Mode Active (Siswa)', 'ok', [
                'enroll_id' => $nextId,
                'nama' => $siswa->nama,
                'type' => 'siswa'
            ]);
        }

        // 5. Check Gate Card Enroll Request
        $gate = GateCard::where('enroll_finger_status', 'requested')
            ->where('school_id', $device->school_id)
            ->where('updated_at', '>=', now()->subMinutes(15))
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($gate) {
            $nextId = $this->getNextFreeFingerId($device->id);
            if ($nextId === -1) return $this->response(false, 'standby', 'Sensor Full (1000 ID)');
            return $this->response(true, 'enroll_mode', 'Enroll Mode Active (Gerbang)', 'ok', [
                'enroll_id' => $nextId,
                'nama' => $gate->name,
                'type' => 'gate_card'
            ]);
        }

        return $this->response(false, 'standby', 'No Enrollment');
    }

    /**
     * Endpoint untuk meminta daftar ID yang aktif pada perangkat/sekolah ini
     */
    public function getSyncList(Request $request)
    {
        $apiKey = $request->input('api_key');
        $device = $this->authenticate($apiKey);
        if (!$device) {
            return response()->json(['ok' => false, 'message' => 'Auth Failed'], 401);
        }

        $schoolId = $device->school_id;
        $schoolDeviceIds = Device::where('school_id', $schoolId)->pluck('id')->toArray();

        // Ambil sidik jari siswa di sekolah ini yang memiliki template
        $siswaFingers = SiswaFingerprint::whereIn('device_id', $schoolDeviceIds)
            ->whereNotNull('template_data')
            ->where('template_data', '!=', '')
            ->pluck('finger_id')
            ->toArray();

        // Ambil sidik jari guru di sekolah ini yang memiliki template
        $guruFingers = GuruFingerprint::whereIn('device_id', $schoolDeviceIds)
            ->whereNotNull('template_data')
            ->where('template_data', '!=', '')
            ->pluck('finger_id')
            ->toArray();

        // Ambil sidik jari gerbang di sekolah ini yang memiliki template
        $gateFingers = GateCardFingerprint::whereIn('device_id', $schoolDeviceIds)
            ->whereNotNull('template_data')
            ->where('template_data', '!=', '')
            ->pluck('finger_id')
            ->toArray();

        $activeIds = array_values(array_unique(array_merge($siswaFingers, $guruFingers, $gateFingers)));
        sort($activeIds);

        return response()->json([
            'ok' => true,
            'device_id' => $device->id,
            'school_id' => $schoolId,
            'total' => count($activeIds),
            'allowed_ids' => $activeIds
        ]);
    }

    /**
     * Endpoint untuk mengunduh template 512-byte per finger_id
     */
    public function getSyncTemplate(Request $request)
    {
        $apiKey = $request->input('api_key');
        $fingerId = (int) $request->input('finger_id');
        $device = $this->authenticate($apiKey);
        if (!$device) {
            return response()->json(['ok' => false, 'message' => 'Auth Failed'], 401);
        }

        $schoolDeviceIds = Device::where('school_id', $device->school_id)->pluck('id')->toArray();

        // Cari di Siswa (scoped per school)
        $siswaFp = SiswaFingerprint::whereIn('device_id', $schoolDeviceIds)
            ->where('finger_id', $fingerId)
            ->with('student')
            ->first();

        if ($siswaFp && !empty($siswaFp->template_data)) {
            return response()->json([
                'ok' => true,
                'finger_id' => $fingerId,
                'type' => 'siswa',
                'name' => $siswaFp->student->nama ?? 'Siswa',
                'template' => $siswaFp->template_data
            ]);
        }

        // Cari di Guru (scoped per school)
        $guruFp = GuruFingerprint::whereIn('device_id', $schoolDeviceIds)
            ->where('finger_id', $fingerId)
            ->with('guru')
            ->first();

        if ($guruFp && !empty($guruFp->template_data)) {
            return response()->json([
                'ok' => true,
                'finger_id' => $fingerId,
                'type' => 'guru',
                'name' => $guruFp->guru->nama ?? 'Guru',
                'template' => $guruFp->template_data
            ]);
        }

        // Cari di Gate Card (scoped per school)
        $gateFp = GateCardFingerprint::whereIn('device_id', $schoolDeviceIds)
            ->where('finger_id', $fingerId)
            ->with('gateCard')
            ->first();

        if ($gateFp && !empty($gateFp->template_data)) {
            return response()->json([
                'ok' => true,
                'finger_id' => $fingerId,
                'type' => 'gate_card',
                'name' => $gateFp->gateCard->name ?? 'Gerbang',
                'template' => $gateFp->template_data
            ]);
        }

        return response()->json([
            'ok' => false,
            'message' => 'Template not found for ID #' . $fingerId
        ], 404);
    }

    /**
     * Cari ID kosong berikutnya (Kapasitas R307: 1 - 1000 ID)
     */
    private function getNextFreeFingerId($deviceId) {
        $usedSiswa = SiswaFingerprint::where('device_id', $deviceId)->pluck('finger_id')->toArray();
        $usedGuru = GuruFingerprint::where('device_id', $deviceId)->pluck('finger_id')->toArray();
        $usedGate = GateCardFingerprint::where('device_id', $deviceId)->pluck('finger_id')->toArray();
        
        $allUsed = array_merge($usedSiswa, $usedGuru, $usedGate);
        $allUsed = array_map('intval', $allUsed);
        
        for ($i = 1; $i <= 1000; $i++) {
            if (!in_array($i, $allUsed)) {
                return $i;
            }
        }
        return -1;
    }

    private function finalizeEnrollment($fingerId, $device, Request $request = null)
    {
        $templateData = null;
        if ($request) {
            $templateData = $request->input('template_data', $request->input('template', null));
            if ($templateData && strlen($templateData) > 2000) {
                $templateData = substr($templateData, 0, 2048);
            }
        }

        DB::beginTransaction();
        try {
            $conflictName = null;
            $conflictId = null;
            $conflictType = null;
            
            $usedBySiswa = SiswaFingerprint::where('device_id', $device->id)->where('finger_id', $fingerId)->with('student')->first();
            if ($usedBySiswa) {
                $conflictName = $usedBySiswa->student->nama ?? 'Siswa Lain';
                $conflictId = $usedBySiswa->student_id;
                $conflictType = 'siswa';
            }

            if (!$conflictName) {
                $usedByGuru = GuruFingerprint::where('device_id', $device->id)->where('finger_id', $fingerId)->with('guru')->first();
                if ($usedByGuru) {
                    $conflictName = $usedByGuru->guru->nama ?? 'Guru Lain';
                    $conflictId = $usedByGuru->guru_id;
                    $conflictType = 'guru';
                }
            }
            if (!$conflictName) {
                $usedByGate = GateCardFingerprint::where('device_id', $device->id)->where('finger_id', $fingerId)->with('gateCard')->first();
                if ($usedByGate) {
                    $conflictName = $usedByGate->gateCard->name ?? 'Gerbang Lain';
                    $conflictId = $usedByGate->gate_card_id;
                    $conflictType = 'gate';
                }
            }

            // Check Guru first
            $guru = Guru::where('enroll_finger_status', 'requested')
                ->where('school_id', $device->school_id)
                ->where('updated_at', '>=', now()->subMinutes(15))
                ->orderBy('updated_at', 'desc')
                ->lockForUpdate()
                ->first();

            if ($guru) {
                if ($conflictName && ($conflictType !== 'guru' || $conflictId != $guru->id)) {
                    $guru->update(['enroll_finger_status' => null]);
                    DB::commit();
                    return $this->response(false, 'gagal', "Ditolak: ID telah dipakai oleh $conflictName");
                }

                GuruFingerprint::updateOrCreate(
                    ['guru_id' => $guru->id, 'device_id' => $device->id, 'finger_id' => $fingerId],
                    ['template_data' => $templateData, 'created_at' => now()]
                );

                $guru->update([
                    'enroll_finger_status' => 'done',
                    'id_finger' => $fingerId,
                ]);

                try {
                    $this->telegram->sendEnrollSuccess($guru->nama, $guru->telegram_chat_id, $fingerId, $device->school_id, 'Sidik Jari Guru');
                } catch (\Exception $e) {
                    Log::error("Telegram Enroll Error: " . $e->getMessage());
                }

                DB::commit();
                ApiLog::create([
                    'school_id' => $this->currentSchoolId,
                    'api_key' => $this->currentApiKey,
                    'action' => 'enroll_success',
                    'uid' => $fingerId,
                    'success' => true,
                    'message' => 'Enroll Berhasil (Guru): ' . $guru->nama,
                    'created_at' => now()
                ]);
                return $this->response(true, 'success', 'Enroll Berhasil (Guru): ' . $guru->nama, 'success');
            }

            // Check Siswa
            $siswa = Siswa::where('enroll_finger_status', 'requested')
                ->where('school_id', $device->school_id)
                ->where('updated_at', '>=', now()->subMinutes(15))
                ->orderBy('updated_at', 'desc')
                ->lockForUpdate()
                ->first();

            if ($siswa) {
                if ($conflictName && ($conflictType !== 'siswa' || $conflictId != $siswa->id)) {
                    $siswa->update(['enroll_finger_status' => null]);
                    DB::commit();
                    return $this->response(false, 'gagal', "Ditolak: ID telah dipakai oleh $conflictName");
                }

                SiswaFingerprint::updateOrCreate(
                    ['student_id' => $siswa->id, 'device_id' => $device->id, 'finger_id' => $fingerId],
                    ['template_data' => $templateData, 'created_at' => now()]
                );

                $siswa->update([
                    'enroll_finger_status' => 'done',
                    'id_finger' => $fingerId,
                ]);

                try {
                    $this->telegram->sendEnrollSuccess($siswa->nama, $siswa->telegram_chat_id, $fingerId, $device->school_id, 'Sidik Jari Siswa', $siswa->telegram_ortu_chat_id);
                } catch (\Exception $e) {
                    Log::error("Telegram Enroll Error: " . $e->getMessage());
                }

                DB::commit();
                ApiLog::create([
                    'school_id' => $this->currentSchoolId,
                    'api_key' => $this->currentApiKey,
                    'action' => 'enroll_success',
                    'uid' => $fingerId,
                    'success' => true,
                    'message' => 'Enroll Berhasil (Siswa): ' . $siswa->nama,
                    'created_at' => now()
                ]);
                return $this->response(true, 'success', 'Enroll Berhasil (Siswa): ' . $siswa->nama, 'success');
            }

            // Check Gate Card
            $gate = GateCard::where('enroll_finger_status', 'requested')
                ->where('school_id', $device->school_id)
                ->where('updated_at', '>=', now()->subMinutes(15))
                ->orderBy('updated_at', 'desc')
                ->lockForUpdate()
                ->first();

            if ($gate) {
                if ($conflictName && ($conflictType !== 'gate' || $conflictId != $gate->id)) {
                    $gate->update(['enroll_finger_status' => null]);
                    DB::commit();
                    return $this->response(false, 'gagal', "Ditolak: ID telah dipakai oleh $conflictName");
                }

                GateCardFingerprint::updateOrCreate(
                    ['gate_card_id' => $gate->id, 'device_id' => $device->id, 'finger_id' => $fingerId],
                    ['template_data' => $templateData, 'created_at' => now()]
                );

                $gate->update([
                    'enroll_finger_status' => 'done',
                    'id_finger' => $fingerId,
                ]);

                DB::commit();
                ApiLog::create([
                    'school_id' => $this->currentSchoolId,
                    'api_key' => $this->currentApiKey,
                    'action' => 'enroll_success',
                    'uid' => $fingerId,
                    'success' => true,
                    'message' => 'Enroll Berhasil (Gerbang): ' . $gate->name,
                    'created_at' => now()
                ]);
                return $this->response(true, 'success', 'Enroll Berhasil (Gerbang): ' . $gate->name, 'success');
            }

            DB::rollBack();
            return $this->response(false, 'gagal', 'Tidak ada data enroll yang diminta');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("R307 finalizeEnrollment error: " . $e->getMessage());
            return $this->response(false, 'error', 'System Error: ' . $e->getMessage());
        }
    }

    private function handleScan($fingerId, $device, $now = null)
    {
        if (!$now) $now = now();

        // 1. Check Gate Card
        $gateFingerprint = GateCardFingerprint::where('device_id', $device->id)
            ->where('finger_id', $fingerId)
            ->with('gateCard')
            ->first();

        if ($gateFingerprint && $gateFingerprint->gateCard) {
            $gateCard = $gateFingerprint->gateCard;
            $gateName = $gateCard->name ?? 'Gerbang';
            $sessionUid = 'GATE_FINGER_' . $fingerId . '_' . $gateCard->id;

            try {
                DB::beginTransaction();

                $schoolGateCardUids = GateCard::where('school_id', $device->school_id)
                    ->pluck('uid')
                    ->filter()
                    ->toArray();

                $activeSession = TeacherCheckoutSession::where(function ($q) use ($sessionUid, $schoolGateCardUids) {
                    $q->where('uid_rfid', $sessionUid)
                      ->orWhereIn('uid_rfid', $schoolGateCardUids);
                })
                ->where('expires_at', '>=', $now)
                ->first();

                if ($activeSession) {
                    $activeSession->delete();
                    DB::commit();

                    ApiLog::create([
                        'school_id' => $this->currentSchoolId,
                        'api_key' => $this->currentApiKey,
                        'action' => 'gate_closed',
                        'uid' => $fingerId,
                        'success' => true,
                        'message' => 'Sesi Kepulangan Ditutup: ' . $gateName,
                        'created_at' => $now
                    ]);

                    return $this->response(true, 'success', "Gerbang Ditutup.", 'ok', [
                        'type' => 'gate_closed',
                        'nama' => $gateName
                    ]);
                }

                TeacherCheckoutSession::create([
                    'teacher_id' => $gateCard->guru_id,
                    'teacher_name' => $gateName,
                    'uid_rfid' => $sessionUid,
                    'status' => 'open',
                    'expires_at' => $now->copy()->addMinutes(30),
                    'created_at' => $now
                ]);

                DB::commit();
                
                ApiLog::create([
                    'school_id' => $this->currentSchoolId,
                    'api_key' => $this->currentApiKey,
                    'action' => 'gate_access',
                    'uid' => $fingerId,
                    'success' => true,
                    'message' => 'Sesi Kepulangan Dibuka: ' . $gateName,
                    'created_at' => $now
                ]);

                return $this->response(true, 'success', "Gerbang Dibuka (30 Menit).", 'ok', [
                    'type' => 'gate_opened',
                    'nama' => $gateName
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("R307 Gate finger scan error: " . $e->getMessage());
                return $this->response(false, 'error', 'System Error');
            }
        }

        // 2. Check Guru
        $guruFingerprint = GuruFingerprint::where('device_id', $device->id)
            ->where('finger_id', $fingerId)
            ->with('guru')
            ->first();

        if ($guruFingerprint && $guruFingerprint->guru) {
            $guru = $guruFingerprint->guru;

            try {
                DB::beginTransaction();
                $today = $now->format('Y-m-d');

                $absensi = \App\Models\AbsensiGuru::where('guru_id', $guru->id)
                    ->where('tanggal', $today)
                    ->where('school_id', $device->school_id)
                    ->whereNull('jadwal_pelajaran_id')
                    ->lockForUpdate()
                    ->first();

                if (!$absensi) {
                    \App\Models\AbsensiGuru::create([
                        'guru_id' => $guru->id,
                        'school_id' => $device->school_id,
                        'jadwal_pelajaran_id' => null,
                        'tanggal' => $today,
                        'jam_masuk' => $now->toTimeString(),
                        'waktu_hadir' => $now,
                        'status' => 'Hadir',
                        'keterangan' => null,
                        'created_at' => $now
                    ]);

                    DB::commit();

                    try {
                        $this->wa->sendCheckIn($guru->nama, $guru->no_wa, $now->format('H:i'), 'Hadir', $device->school_id, '-', null, '-');
                    } catch (\Exception $e) {
                        Log::error("WA Guru Checkin Error: " . $e->getMessage());
                    }

                    try {
                        $this->telegram->sendCheckIn($guru->nama, $guru->telegram_chat_id, $now->format('H:i'), 'Hadir', $device->school_id, '-', null, '-');
                    } catch (\Exception $e) {
                        Log::error("Telegram Guru Checkin Error: " . $e->getMessage());
                    }

                    ApiLog::create([
                        'school_id' => $this->currentSchoolId,
                        'api_key' => $this->currentApiKey,
                        'action' => 'checkin_success',
                        'uid' => $fingerId,
                        'success' => true,
                        'message' => 'Guru Masuk: ' . $guru->nama,
                        'created_at' => $now
                    ]);

                    return $this->response(true, 'success', "Selamat Pagi, {$guru->nama}.", 'ok', [
                        'type' => 'absen_masuk_guru',
                        'nama' => $guru->nama,
                        'jam' => $now->format('H:i')
                    ]);
                } else {
                    $checkoutEnabled = \App\Models\Setting::where('school_id', $device->school_id)
                        ->where('setting_key', 'enable_checkout_teacher')
                        ->value('setting_value') ?? 'false';

                    if ($checkoutEnabled === 'false') {
                        DB::commit();
                        return $this->response(true, 'success', "Sudah Absen Masuk.", 'ok', [
                            'type' => 'absen_sudah_masuk_guru',
                            'nama' => $guru->nama
                        ]);
                    }

                    $gateSession = TeacherCheckoutSession::where('expires_at', '>', $now)
                        ->where('status', 'open')
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if (!$gateSession) {
                        DB::rollBack();
                        return $this->response(false, 'gagal', 'Belum ada izin gerbang.', 'warning', ['type' => 'no_authorization', 'nama' => $guru->nama]);
                    }

                    if ($absensi->jam_pulang) {
                        DB::commit();
                        return $this->response(true, 'success', "Sudah Absen Pulang.", 'ok', [
                            'type' => 'absen_sudah_pulang_guru',
                            'nama' => $guru->nama
                        ]);
                    }

                    $absensi->update([
                        'jam_pulang' => $now->toTimeString(),
                        'updated_at' => $now
                    ]);

                    DB::commit();

                    try {
                        $this->wa->sendCheckOut($guru->nama, $guru->no_wa, $now->format('H:i'), $device->school_id);
                    } catch (\Exception $e) {}

                    try {
                        $this->telegram->sendCheckOut($guru->nama, $guru->telegram_chat_id, $now->format('H:i'), $device->school_id);
                    } catch (\Exception $e) {}

                    return $this->response(true, 'success', "Selamat Pulang, {$guru->nama}.", 'ok', [
                        'type' => 'absen_pulang_guru',
                        'nama' => $guru->nama,
                        'jam' => $now->format('H:i')
                    ]);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("R307 Guru Scan Error: " . $e->getMessage());
                return $this->response(false, 'error', 'System Error');
            }
        }

        // 3. Check Siswa
        $siswaFingerprint = SiswaFingerprint::where('device_id', $device->id)
            ->where('finger_id', $fingerId)
            ->with('student')
            ->first();

        if ($siswaFingerprint && $siswaFingerprint->student) {
            return $this->handleStudentAttendance($siswaFingerprint->student, $fingerId, $device, $now);
        }

        // ID tidak ditemukan
        ApiLog::create([
            'school_id' => $device->school_id,
            'api_key' => $device->api_key,
            'action' => 'scan_failed',
            'uid' => $fingerId,
            'success' => false,
            'message' => 'Sidik jari ID #' . $fingerId . ' belum terdaftar di sistem',
            'created_at' => $now
        ]);

        return $this->response(false, 'gagal', 'Sidik Jari Belum Terdaftar', 'error', ['type' => 'unregistered']);
    }

    private function handleStudentAttendance($siswa, $fingerId, $device, $now = null)
    {
        if (!$now) $now = now();
        $today = $now->format('Y-m-d');

        try {
            DB::beginTransaction();

            $attendance = Attendance::where('siswa_id', $siswa->id)
                ->where('tanggal', $today)
                ->where('school_id', $device->school_id)
                ->lockForUpdate()
                ->first();

            if (!$attendance) {
                // Check-in
                Attendance::create([
                    'siswa_id' => $siswa->id,
                    'school_id' => $device->school_id,
                    'tanggal' => $today,
                    'jam_masuk' => $now->toTimeString(),
                    'status' => 'Hadir',
                    'created_at' => $now
                ]);

                DB::commit();

                // WA & Telegram
                try {
                    $this->wa->sendCheckIn($siswa->nama, $siswa->no_wa_ortu, $now->format('H:i'), 'Hadir', $device->school_id, $siswa->kelas->nama ?? '-', null, '-');
                } catch (\Exception $e) {}

                try {
                    $this->telegram->sendCheckIn($siswa->nama, $siswa->telegram_chat_id, $now->format('H:i'), 'Hadir', $device->school_id, $siswa->kelas->nama ?? '-', null, '-', $siswa->telegram_ortu_chat_id);
                } catch (\Exception $e) {}

                ApiLog::create([
                    'school_id' => $this->currentSchoolId,
                    'api_key' => $this->currentApiKey,
                    'action' => 'checkin_success',
                    'uid' => $fingerId,
                    'success' => true,
                    'message' => 'Siswa Masuk: ' . $siswa->nama,
                    'created_at' => $now
                ]);

                return $this->response(true, 'success', "Selamat Pagi, {$siswa->nama}.", 'ok', [
                    'type' => 'absen_masuk',
                    'nama' => $siswa->nama,
                    'jam' => $now->format('H:i')
                ]);
            } else {
                if ($attendance->jam_pulang) {
                    DB::commit();
                    return $this->response(true, 'success', "Absen Lengkap.", 'ok', [
                        'type' => 'sudah_lengkap',
                        'nama' => $siswa->nama
                    ]);
                }

                // Pulang
                $attendance->update([
                    'jam_pulang' => $now->toTimeString(),
                    'updated_at' => $now
                ]);

                DB::commit();

                try {
                    $this->wa->sendCheckOut($siswa->nama, $siswa->no_wa_ortu, $now->format('H:i'), $device->school_id);
                } catch (\Exception $e) {}

                try {
                    $this->telegram->sendCheckOut($siswa->nama, $siswa->telegram_chat_id, $now->format('H:i'), $device->school_id, $siswa->telegram_ortu_chat_id);
                } catch (\Exception $e) {}

                return $this->response(true, 'success', "Selamat Pulang, {$siswa->nama}.", 'ok', [
                    'type' => 'absen_pulang',
                    'nama' => $siswa->nama,
                    'jam' => $now->format('H:i')
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("R307 Student attendance error: " . $e->getMessage());
            return $this->response(false, 'error', 'System Error');
        }
    }

    private function authenticate($apiKey, $request = null)
    {
        if (empty($apiKey)) return null;

        $device = Device::where('api_key', $apiKey)->first();
        if ($device) {
            $this->currentSchoolId = $device->school_id;
            $device->update(['last_ping' => now()]);
            return $device;
        }

        if ($request) {
            $this->logFailedAuth($apiKey, 'Device Tidak Ditemukan', $request);
        }
        return null;
    }

    private function logFailedAuth(string $apiKey, string $reason, $request = null)
    {
        ApiLog::create([
            'school_id' => null,
            'api_key' => $apiKey,
            'action' => 'auth_failed',
            'uid' => null,
            'success' => false,
            'message' => 'Auth Gagal: ' . $reason,
            'ip_address' => $request ? $request->ip() : null,
            'created_at' => now()
        ]);
    }

    private function response($ok, $status, $message, $sound = 'ok', $extra = [])
    {
        $payload = array_merge([
            'ok' => $ok,
            'status' => $status,
            'message' => $message,
            'sound' => $sound
        ], $extra);

        return response()->json($payload);
    }

    /**
     * Endpoint untuk memicu sinkronisasi manual (On-Demand) dari Web Dashboard
     */
    public function triggerSync(Request $request)
    {
        $deviceId = $request->input('device_id');
        $apiKey = $request->input('api_key');

        $device = null;
        if ($deviceId) {
            $device = Device::find($deviceId);
        } elseif ($apiKey) {
            $device = Device::where('api_key', $apiKey)->first();
        }

        if (!$device) {
            return response()->json(['ok' => false, 'message' => 'Device not found'], 404);
        }

        // Set cache flag on-demand sync (berlaku 10 menit)
        \Illuminate\Support\Facades\Cache::put('sync_finger_' . $device->id, true, 600);

        ApiLog::create([
            'school_id' => $device->school_id,
            'api_key' => $device->api_key,
            'action' => 'sync_requested',
            'uid' => null,
            'success' => true,
            'message' => 'Permintaan Sinkronisasi Manual (On-Demand) Dikirim ke Alat: ' . ($device->name ?? 'Scanner'),
            'created_at' => now()
        ]);

        return response()->json([
            'ok' => true,
            'device_id' => $device->id,
            'message' => 'Perintah sinkronisasi manual berhasil dikirim. Alat akan segera menyinkronkan data.'
        ]);
    }

}
