<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Device;
use App\Models\ApiLog;
use App\Models\Guru;
use App\Models\GuruFingerprint;
use App\Models\Siswa;
use App\Models\SiswaFingerprint;
use App\Models\GateCard;
use App\Models\GateCardFingerprint;
use App\Models\TeacherCheckoutSession;
use App\Models\Attendance;
use App\Models\AbsensiGuru;
use App\Models\Setting;
use App\Models\Jadwal;
use App\Services\FcmNotificationService;

class R307FingerprintController extends Controller
{
private $currentApiKey = null;
private $currentId = null;
private $currentSchoolId = null;
protected $wa;
protected $telegram;

protected \App\Services\AttendanceCoreService $attendanceCore;

public function __construct(\App\Services\WhatsAppService $wa, \App\Services\TelegramService $telegram, \App\Services\AttendanceCoreService $attendanceCore)
{
    $this->wa = $wa;
    $this->telegram = $telegram;
    $this->attendanceCore = $attendanceCore;
}

// ... (handle and checkEnrollRequest methods omitted for brevity as they are unchanged) ...

public function handle(Request $request)
    {
        try {

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

    // 1. Auth: Get Device
    if ($apiKey === '') {
        $this->logFailedAuth('', 'API Key Kosong', $request);
        return $this->response(false, 'gagal', 'API Key Kosong');
    }

    $device = $this->authenticate($apiKey, $request);
    if (!$device) {
        return $this->response(false, 'gagal', 'API Key Invalid');
    }

    // Parse scanned_at (offline sync) or current time with school timezone
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

// 2. Input
$fingerId = $request->input('finger_id');
$this->currentId = $fingerId;

// Check for Enroll Stage Progress Update (Real-time hardware stage)
if ($request->has('enroll_stage')) {
    $stage = $request->input('enroll_stage');
    \Illuminate\Support\Facades\Cache::put('enroll_stage_' . $device->school_id, $stage, 60);
    return $this->response(true, 'ok', 'Stage updated');
}

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

// Check if this is an Enroll Confirmation
if ($request->has('enroll_success') && $request->input('enroll_success') == true) {
    return $this->finalizeEnrollment($fingerId, $device);
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

    // GateCard
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

    return $this->response(true, 'ok', 'No active enroll request found');
}

// 3. Scan Logic
if ($fingerId) {
return $this->handleScan($fingerId, $device, $now);
}

return $this->response(false, 'gagal', 'Finger ID required');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("R307 Handle Critical Error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkEnrollRequest(Request $request)
    {
        $apiKey = $request->input('api_key');
        // Validate API Key
        $device = $this->authenticate($apiKey);
        if (!$device) {
            return $this->response(false, 'gagal', 'Auth Failed');
        }

        // Cek apakah ada perintah delete di cache (mendukung antrean delete_finger_queue)
        $deleteQueue = \Illuminate\Support\Facades\Cache::get('delete_finger_queue_' . $device->id, []);
        $deleteId = null;
        if (!empty($deleteQueue) && is_array($deleteQueue)) {
            $deleteId = array_shift($deleteQueue);
            if (!empty($deleteQueue)) {
                \Illuminate\Support\Facades\Cache::put('delete_finger_queue_' . $device->id, $deleteQueue, now()->addMinutes(30));
                \Illuminate\Support\Facades\Cache::put('delete_finger_' . $device->id, $deleteQueue[0], now()->addMinutes(30));
            } else {
                \Illuminate\Support\Facades\Cache::forget('delete_finger_queue_' . $device->id);
                \Illuminate\Support\Facades\Cache::forget('delete_finger_' . $device->id);
            }
        }
        if (!$deleteId) {
            $deleteId = \Illuminate\Support\Facades\Cache::pull('delete_finger_' . $device->id);
        }
        if ($deleteId) {
            return $this->response(true, 'delete_mode', 'Delete Mode Active', 'ok', [
                'enroll_id' => (int)$deleteId, // reuse parameter enroll_id untuk id yang akan dihapus
                'type' => 'delete'
            ]);
        }

        // Cek jika enroll ditargetkan untuk device tertentu
        $targetDeviceId = \Illuminate\Support\Facades\Cache::get('enroll_target_device_' . $device->school_id);
        if ($targetDeviceId && $targetDeviceId != $device->id) {
            return $this->response(false, 'standby', 'Enrollment active for another device');
        }

        // Check Guru Enroll Request first SCOPED
$guru = Guru::where('enroll_finger_status', 'requested')
->where('school_id', $device->school_id)
->where('updated_at', '>=', now()->subMinutes(15))
->orderBy('updated_at', 'desc')
->first();

if ($guru) {
    $nextId = $this->getNextFreeFingerId($device->id);
    if ($nextId === -1) return $this->response(false, 'standby', 'Sensor Full');
    return $this->response(true, 'enroll_mode', 'Enroll Mode Active (Guru)', 'ok', [
        'enroll_id' => $nextId,
        'nama' => $guru->nama,
        'type' => 'guru'
    ]);
}

// Check Siswa Enroll Request SCOPED
$siswa = Siswa::where('enroll_finger_status', 'requested')
->where('school_id', $device->school_id)
->where('updated_at', '>=', now()->subMinutes(15))
->orderBy('updated_at', 'desc')
->first();

if ($siswa) {
    $nextId = $this->getNextFreeFingerId($device->id);
    if ($nextId === -1) return $this->response(false, 'standby', 'Sensor Full');
    return $this->response(true, 'enroll_mode', 'Enroll Mode Active (Siswa)', 'ok', [
        'enroll_id' => $nextId,
        'nama' => $siswa->nama,
        'type' => 'siswa'
    ]);
}

// Check Gate Card Enroll Request SCOPED
        $gate = GateCard::where('enroll_finger_status', 'requested')
            ->where('school_id', $device->school_id)
            ->where('updated_at', '>=', now()->subMinutes(15))
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($gate) {
            $nextId = $this->getNextFreeFingerId($device->id);
            if ($nextId === -1) return $this->response(false, 'standby', 'Sensor Full');
            return $this->response(true, 'enroll_mode', 'Enroll Mode Active (Gerbang)', 'ok', [
                'enroll_id' => $nextId,
                'nama' => $gate->name,
                'type' => 'gate_card'
            ]);
        }

        return $this->response(false, 'standby', 'No Enrollment');
}

private function getNextFreeFingerId($deviceId) {
    $device = Device::find($deviceId);
    $min = $device && $device->finger_id_min ? (int)$device->finger_id_min : 1;
    $max = $device && $device->finger_id_max ? (int)$device->finger_id_max : 200;

    $usedSiswa = SiswaFingerprint::where('device_id', $deviceId)->pluck('finger_id')->toArray();
    $usedGuru = GuruFingerprint::where('device_id', $deviceId)->pluck('finger_id')->toArray();
    $usedGate = GateCardFingerprint::where('device_id', $deviceId)->pluck('finger_id')->toArray();
    
    $allUsed = array_merge($usedSiswa, $usedGuru, $usedGate);
    $allUsed = array_map('intval', $allUsed);
    
    for ($i = $min; $i <= $max; $i++) {
        if (!in_array($i, $allUsed)) {
            return $i;
        }
    }
    return -1;
}

private function finalizeEnrollment($fingerId, $device)
{
$templateData = request()->input('template_data');
DB::beginTransaction();
try {
        // PRE-CHECK DUPLIKASI FINGER ID (Hanya cek jika pemilik sidik jari masih aktif di database)
        $conflictName = null;
        $conflictId = null;
        $conflictType = null;
        
        $usedBySiswa = SiswaFingerprint::where('device_id', $device->id)
            ->where('finger_id', $fingerId)
            ->whereHas('student')
            ->with('student')
            ->latest('id')
            ->first();
        if ($usedBySiswa && $usedBySiswa->student) {
            $conflictName = $usedBySiswa->student->nama ?? 'Siswa Lain';
            $conflictId = $usedBySiswa->student_id;
            $conflictType = 'siswa';
        }

        if (!$conflictName) {
            $usedByGuru = GuruFingerprint::where('device_id', $device->id)
                ->where('finger_id', $fingerId)
                ->whereHas('guru')
                ->with('guru')
                ->latest('id')
                ->first();
            if ($usedByGuru && $usedByGuru->guru) {
                $conflictName = $usedByGuru->guru->nama ?? 'Guru Lain';
                $conflictId = $usedByGuru->guru_id;
                $conflictType = 'guru';
            }
        }
        if (!$conflictName) {
            $usedByGate = GateCardFingerprint::where('device_id', $device->id)
                ->where('finger_id', $fingerId)
                ->whereHas('gateCard')
                ->with('gateCard')
                ->latest('id')
                ->first();
            if ($usedByGate && $usedByGate->gateCard) {
                $conflictName = $usedByGate->gateCard->name ?? 'Gerbang Lain';
                $conflictId = $usedByGate->gate_card_id;
                $conflictType = 'gate';
            }
        }

        $schoolDeviceIds = Device::where('school_id', $device->school_id)->pluck('id')->toArray();

// Check Guru first SCOPED
$guru = Guru::where('enroll_finger_status', 'requested')
->where('school_id', $device->school_id)
->where('updated_at', '>=', now()->subMinutes(15))
->orderBy('updated_at', 'desc')
->lockForUpdate()
->first();

        if ($guru) {
            if ($conflictName && ($conflictType !== 'guru' || $conflictId != $guru->id)) {
                $guru->update(['enroll_finger_status' => null]);
                \Illuminate\Support\Facades\Cache::forget('enroll_target_device_' . $device->school_id);
                \Illuminate\Support\Facades\Cache::forget('enroll_stage_' . $device->school_id);
                DB::commit();
                ApiLog::create([
                    'school_id' => $this->currentSchoolId,
                    'api_key' => $this->currentApiKey,
                    'action' => 'enroll_failed',
                    'uid' => $fingerId,
                    'success' => false,
                    'message' => "Ditolak: Sidik jari sudah terdaftar atas nama $conflictName (ID #$fingerId)",
                    'created_at' => now()
                ]);
                return $this->response(false, 'gagal', "Ditolak: Sidik jari sudah terdaftar atas nama $conflictName (ID #$fingerId)");
            }

            // Bersihkan template lama yang mungkin masih ada pada slot finger_id ini di seluruh device sekolah
            GuruFingerprint::whereIn('device_id', $schoolDeviceIds)->where('finger_id', $fingerId)->where('guru_id', '!=', $guru->id)->delete();
            SiswaFingerprint::whereIn('device_id', $schoolDeviceIds)->where('finger_id', $fingerId)->delete();
            GateCardFingerprint::whereIn('device_id', $schoolDeviceIds)->where('finger_id', $fingerId)->delete();

            GuruFingerprint::updateOrCreate(
                ['guru_id' => $guru->id, 'device_id' => $device->id, 'finger_id' => $fingerId],
                ['template_data' => $templateData, 'created_at' => now()]
            );

            $guru->update([
                'enroll_finger_status' => 'done',
                'id_finger' => $fingerId,
            ]);

            \Illuminate\Support\Facades\Cache::forget('enroll_target_device_' . $device->school_id);
            \Illuminate\Support\Facades\Cache::forget('enroll_stage_' . $device->school_id);

            // Telegram Notification
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

        // Check Siswa SCOPED
        $siswa = Siswa::where('enroll_finger_status', 'requested')
            ->where('school_id', $device->school_id)
            ->where('updated_at', '>=', now()->subMinutes(15))
            ->orderBy('updated_at', 'desc')
            ->lockForUpdate()
            ->first();

        if ($siswa) {
            if ($conflictName && ($conflictType !== 'siswa' || $conflictId != $siswa->id)) {
                $siswa->update(['enroll_finger_status' => null]);
                \Illuminate\Support\Facades\Cache::forget('enroll_target_device_' . $device->school_id);
                \Illuminate\Support\Facades\Cache::forget('enroll_stage_' . $device->school_id);
                DB::commit();
                ApiLog::create([
                    'school_id' => $this->currentSchoolId,
                    'api_key' => $this->currentApiKey,
                    'action' => 'enroll_failed',
                    'uid' => $fingerId,
                    'success' => false,
                    'message' => "Ditolak: Sidik jari sudah terdaftar atas nama $conflictName (ID #$fingerId)",
                    'created_at' => now()
                ]);
                return $this->response(false, 'gagal', "Ditolak: Sidik jari sudah terdaftar atas nama $conflictName (ID #$fingerId)");
            }

            // Bersihkan template lama yang mungkin masih ada pada slot finger_id ini di seluruh device sekolah
            SiswaFingerprint::whereIn('device_id', $schoolDeviceIds)->where('finger_id', $fingerId)->where('student_id', '!=', $siswa->id)->delete();
            GuruFingerprint::whereIn('device_id', $schoolDeviceIds)->where('finger_id', $fingerId)->delete();
            GateCardFingerprint::whereIn('device_id', $schoolDeviceIds)->where('finger_id', $fingerId)->delete();

            SiswaFingerprint::updateOrCreate(
                ['student_id' => $siswa->id, 'device_id' => $device->id, 'finger_id' => $fingerId],
                ['template_data' => $templateData, 'created_at' => now()]
            );

            $siswa->update([
                'enroll_finger_status' => 'done',
                'id_finger' => $fingerId,
            ]);

            \Illuminate\Support\Facades\Cache::forget('enroll_target_device_' . $device->school_id);
            \Illuminate\Support\Facades\Cache::forget('enroll_stage_' . $device->school_id);

            // Telegram Notification
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

        // Check Gate Card SCOPED
        $gate = GateCard::where('enroll_finger_status', 'requested')
            ->where('school_id', $device->school_id)
            ->where('updated_at', '>=', now()->subMinutes(15))
            ->orderBy('updated_at', 'desc')
            ->lockForUpdate()
            ->first();

        if ($gate) {
            if ($conflictName && ($conflictType !== 'gate' || $conflictId != $gate->id)) {
                $gate->update(['enroll_finger_status' => null]);
                \Illuminate\Support\Facades\Cache::forget('enroll_target_device_' . $device->school_id);
                \Illuminate\Support\Facades\Cache::forget('enroll_stage_' . $device->school_id);
                DB::commit();
                ApiLog::create([
                    'school_id' => $this->currentSchoolId,
                    'api_key' => $this->currentApiKey,
                    'action' => 'enroll_failed',
                    'uid' => $fingerId,
                    'success' => false,
                    'message' => "Ditolak: Sidik jari sudah terdaftar atas nama $conflictName (ID #$fingerId)",
                    'created_at' => now()
                ]);
                return $this->response(false, 'gagal', "Ditolak: ID telah dipakai oleh $conflictName");
            }

            // Bersihkan template lama yang mungkin masih ada pada slot finger_id ini di seluruh device sekolah
            GateCardFingerprint::whereIn('device_id', $schoolDeviceIds)->where('finger_id', $fingerId)->where('gate_card_id', '!=', $gate->id)->delete();
            GuruFingerprint::whereIn('device_id', $schoolDeviceIds)->where('finger_id', $fingerId)->delete();
            SiswaFingerprint::whereIn('device_id', $schoolDeviceIds)->where('finger_id', $fingerId)->delete();

            GateCardFingerprint::updateOrCreate(
                ['gate_card_id' => $gate->id, 'device_id' => $device->id, 'finger_id' => $fingerId],
                ['template_data' => $templateData, 'created_at' => now()]
            );

            $gate->update([
                'enroll_finger_status' => 'done',
                'id_finger' => $fingerId,
            ]);

            \Illuminate\Support\Facades\Cache::forget('enroll_target_device_' . $device->school_id);
            \Illuminate\Support\Facades\Cache::forget('enroll_stage_' . $device->school_id);

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

        // Neither found
        DB::rollBack();
        return $this->response(false, 'gagal', 'Enroll Timeout / No Request');

} catch (\Exception $e) {
    DB::rollBack();
    Log::error("Finalize Enroll Error: " . $e->getMessage());
    ApiLog::create([
        'school_id' => $device->school_id,
        'api_key' => $device->api_key,
        'action' => 'enroll_failed',
        'uid' => $fingerId,
        'success' => false,
        'message' => 'Exception Finalize Enroll: ' . $e->getMessage(),
        'created_at' => now()
    ]);
    return $this->response(false, 'error', 'Enroll Gagal');
}
}

    private function logFailedAuth(string $apiKey, string $reason, $request = null)
    {
        $req = $request ?? request();
        if ($req && !$req->isMethod('post')) {
            return;
        }

        $ip = $request ? $request->ip() : request()->ip();

        ApiLog::create([
            'school_id'  => null,
            'api_key'    => $apiKey,
            'action'     => 'auth_failed',
            'uid'        => null,
            'success'    => false,
            'message'    => $reason,
            'ip_address' => $ip,
            'user_agent' => $request ? $request->userAgent() : request()->userAgent(),
            'created_at' => now(),
        ]);

        \Illuminate\Support\Facades\Cache::forget("ip_blocked_" . $ip);
    }

    private function authenticate($apiKey, $request = null)
    {
        if (empty($apiKey))
            return null;
        $device = Device::where('api_key', $apiKey)->where('active', true)->first();
        if (!$device) {
            $this->logFailedAuth($apiKey, 'API key tidak valid / tidak aktif', $request);
        }
        if ($device) {
            $this->currentSchoolId = $device->school_id;
            $schoolTz = \App\Models\Setting::where('school_id', $device->school_id)
                ->where('setting_key', 'timezone')
                ->value('setting_value');
            if ($schoolTz) {
                date_default_timezone_set($schoolTz);
                config(['app.timezone' => $schoolTz]);
            }
            DB::table('api_keys')->where('id', $device->id)->update(['last_used_at' => now()]);
        }
        return $device;
    }

    private function handleScan($fingerId, $device, $now = null)
    {
        $now = $now ?? now();
        


        $schoolDeviceIds = Device::where('school_id', $device->school_id)->pluck('id')->toArray();

        // Check Gate Card first (scoped to school)
        $gateCard = null;
        $gateCardFingerprint = GateCardFingerprint::whereIn('device_id', $schoolDeviceIds)
            ->where('finger_id', $fingerId)
            ->with('gateCard.guru')
            ->first();

        if ($gateCardFingerprint && $gateCardFingerprint->gateCard) {
            $gateCard = $gateCardFingerprint->gateCard;
        } else {
            $gateCard = GateCard::where('id_finger', $fingerId)->where('school_id', $device->school_id)->with('guru')->first();
        }

        if ($gateCard) {
            $res = $this->attendanceCore->handleGateCardScan($gateCard, [
                'school_id' => $device->school_id,
                'uid' => $fingerId,
                'now' => $now,
            ]);

            $gateName = $res['data']['nama'] ?? ($gateCard->name ?: 'Kartu Gerbang');
            $isClosed = ($res['type'] ?? '') === 'gate_closed';
            $action = $isClosed ? 'gate_closed' : 'gate_access';
            $logMsg = ($isClosed ? 'Sesi Kepulangan Ditutup: ' : 'Sesi Kepulangan Dibuka: ') . $gateName;

            ApiLog::create([
                'school_id' => $this->currentSchoolId,
                'api_key' => $this->currentApiKey,
                'action' => $action,
                'uid' => $fingerId,
                'success' => $res['ok'],
                'message' => $res['ok'] ? $logMsg : ($res['message'] ?? 'Gagal Sesi Gerbang'),
                'created_at' => $now,
            ]);

            $msg = $res['ok']
                ? ($isClosed ? "Gerbang Ditutup." : "Gerbang Dibuka (30 Menit).")
                : ($res['message'] ?? "Gagal memproses sesi gerbang.");

            return $this->response(
                $res['ok'],
                $res['ok'] ? 'success' : 'gagal',
                $msg,
                $res['sound'] ?? ($res['ok'] ? 'ok' : 'error'),
                [
                    'type' => $res['type'] ?? ($isClosed ? 'gate_closed' : 'gate_opened'),
                    'nama' => $gateName,
                ]
            );
        }

        // Check Guru (scoped to school)
        $guru = null;
        $guruFingerprint = GuruFingerprint::whereIn('device_id', $schoolDeviceIds)
            ->where('finger_id', $fingerId)
            ->with('guru')
            ->first();

        if ($guruFingerprint && $guruFingerprint->guru) {
            $guru = $guruFingerprint->guru;
        } else {
            $guru = Guru::where('id_finger', $fingerId)->where('school_id', $device->school_id)->first();
        }

        if ($guru) {
            $res = $this->attendanceCore->recordTeacherAttendance($guru, [
                'school_id' => $device->school_id,
                'uid' => $fingerId,
                'now' => $now,
                'channel' => 'fingerprint',
                'mode' => 'auto',
            ]);

            $action = match ($res['type'] ?? '') {
                'no_shift', 'outside_checkin_window', 'checkout_closed_guru', 'checkout_early_rejected_guru' => 'scan_rejected',
                'absen_pulang_guru' => 'checkout_success',
                'checkout_session_closed' => 'gate_closed',
                'checkout_session_open' => 'gate_access',
                default => $res['ok'] ? 'checkin_success' : 'scan_rejected',
            };

            ApiLog::create([
                'school_id' => $this->currentSchoolId,
                'api_key' => $this->currentApiKey,
                'action' => $action,
                'uid' => $fingerId,
                'success' => $res['ok'],
                'message' => $res['message'] ?? 'Absensi Guru',
                'created_at' => $now,
            ]);

            $extra = [
                'type' => $res['type'] ?? 'absen_guru',
                'nama' => $guru->nama,
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

        // Check Siswa (scoped to school)
        $siswa = null;
        $siswaFingerprint = SiswaFingerprint::whereIn('device_id', $schoolDeviceIds)
            ->where('finger_id', $fingerId)
            ->with('student.kelas')
            ->first();

        if ($siswaFingerprint && $siswaFingerprint->student) {
            $siswa = $siswaFingerprint->student;
        } else {
            $siswa = Siswa::where('id_finger', $fingerId)->where('school_id', $device->school_id)->with('kelas')->first();
        }

        if ($siswa) {

            try {
                return $this->handleStudentAttendance($siswa, $fingerId, $device, $now);
            } catch (\Exception $e) {
                Log::error("Student finger scan error: " . $e->getMessage());
                ApiLog::create([
                    'school_id' => $device->school_id,
                    'api_key' => $device->api_key,
                    'action' => 'scan_failed',
                    'uid' => $fingerId,
                    'success' => false,
                    'message' => 'System Error (Siswa Finger): ' . $e->getMessage(),
                    'created_at' => now()
                ]);
                return $this->response(false, 'error', 'System Error');
            }
        }

        // Neither found
        ApiLog::create(['school_id' => $device->school_id, 'api_key' => $this->currentApiKey, 'action' => 'scan_failed', 'uid' => $fingerId, 'success' => false, 'message' => 'Sidik Jari Tidak Dikenal', 'created_at' => now()]);
        return $this->response(false, 'gagal', 'ID Sidik Jari Tidak Dikenal di Device Ini');
    }

    private function handleStudentAttendance($siswa, $fingerId, $device, $now = null)
    {
        $res = $this->attendanceCore->recordStudentAttendance($siswa, [
            'school_id' => $device->school_id,
            'uid' => $fingerId,
            'now' => $now,
            'channel' => 'fingerprint',
            'mode' => 'auto',
        ]);

        $action = match ($res['type'] ?? '') {
            'hari_libur', 'di_luar_jam', 'checkout_closed', 'checkout_early_rejected' => 'scan_rejected',
            'pulang_normal', 'pulang_cepat' => 'checkout_success',
            'absen_kegiatan' => 'kegiatan_success',
            default => $res['ok'] ? 'checkin_success' : 'scan_rejected',
        };

        ApiLog::create([
            'school_id' => $device->school_id,
            'api_key' => $this->currentApiKey,
            'action' => $action,
            'uid' => $fingerId,
            'success' => $res['ok'],
            'message' => $res['message'] ?? 'Absensi Siswa',
            'created_at' => $now ?? now(),
        ]);

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
     * Catat kehadiran siswa pada kegiatan yang sedang aktif dan relevan saat scan sidik jari.
     */
    private function recordActiveKegiatans($schoolId, $siswa, $now, $today, $keterangan = 'Auto dari Scan Mesin'): array
    {
        return $this->attendanceCore->recordActiveKegiatans($schoolId, $siswa, $now, $today, $keterangan);
    }

private function response($ok, $status, $message, $sound = 'ok', $extra = [])
{
return response()->json(array_merge([
'ok' => $ok,
'status' => $status,
'message' => $message,
'sound' => $sound
], $extra));
}

    /**
     * Mengambil daftar ID sidik jari yang aktif di sekolah ini untuk sinkronisasi multi-device
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

        // Ambil sidik jari siswa di sekolah ini yang memiliki template dan siswanya masih aktif
        $siswaFingers = SiswaFingerprint::whereIn('device_id', $schoolDeviceIds)
            ->whereNotNull('template_data')
            ->where('template_data', '!=', '')
            ->whereHas('student')
            ->pluck('finger_id')
            ->toArray();

        // Ambil sidik jari guru di sekolah ini yang memiliki template dan gurunya masih aktif
        $guruFingers = GuruFingerprint::whereIn('device_id', $schoolDeviceIds)
            ->whereNotNull('template_data')
            ->where('template_data', '!=', '')
            ->whereHas('guru')
            ->pluck('finger_id')
            ->toArray();

        // Ambil sidik jari gerbang di sekolah ini yang memiliki template dan kartunya masih aktif
        $gateFingers = GateCardFingerprint::whereIn('device_id', $schoolDeviceIds)
            ->whereNotNull('template_data')
            ->where('template_data', '!=', '')
            ->whereHas('gateCard')
            ->pluck('finger_id')
            ->toArray();

        $allIds = array_values(array_unique(array_merge($siswaFingers, $guruFingers, $gateFingers)));
        sort($allIds);

        // Filter berdasarkan mapping rentang slot ID device jika diatur
        $min = $device->finger_id_min ? (int)$device->finger_id_min : 1;
        $max = $device->finger_id_max ? (int)$device->finger_id_max : 200;

        $filteredIds = array_values(array_filter($allIds, function($id) use ($min, $max) {
            return $id >= $min && $id <= $max;
        }));

        return response()->json([
            'ok' => true,
            'school_id' => $schoolId,
            'device_id' => $device->id,
            'id_min' => $min,
            'id_max' => $max,
            'total' => count($filteredIds),
            'allowed_ids' => $filteredIds
        ]);
    }

    /**
     * Mengambil data template sidik jari (512-byte HEX) untuk diinjeksikan ke sensor R307
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

        // Cari di Siswa (hanya siswa aktif, ambil template terbaru)
        $siswaFp = SiswaFingerprint::whereIn('device_id', $schoolDeviceIds)
            ->where('finger_id', $fingerId)
            ->whereHas('student')
            ->with('student')
            ->latest('id')
            ->first();

        if ($siswaFp && !empty($siswaFp->template_data) && $siswaFp->student) {
            return response()->json([
                'ok' => true,
                'finger_id' => $fingerId,
                'type' => 'siswa',
                'name' => $siswaFp->student->nama ?? 'Siswa',
                'template' => $siswaFp->template_data
            ]);
        }

        // Cari di Guru (hanya guru aktif, ambil template terbaru)
        $guruFp = GuruFingerprint::whereIn('device_id', $schoolDeviceIds)
            ->where('finger_id', $fingerId)
            ->whereHas('guru')
            ->with('guru')
            ->latest('id')
            ->first();

        if ($guruFp && !empty($guruFp->template_data) && $guruFp->guru) {
            return response()->json([
                'ok' => true,
                'finger_id' => $fingerId,
                'type' => 'guru',
                'name' => $guruFp->guru->nama ?? 'Guru',
                'template' => $guruFp->template_data
            ]);
        }

        // Cari di Gate Card (hanya gate aktif, ambil template terbaru)
        $gateFp = GateCardFingerprint::whereIn('device_id', $schoolDeviceIds)
            ->where('finger_id', $fingerId)
            ->whereHas('gateCard')
            ->with('gateCard')
            ->latest('id')
            ->first();

        if ($gateFp && !empty($gateFp->template_data) && $gateFp->gateCard) {
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
            'message' => 'Template sidik jari ID #' . $fingerId . ' tidak ditemukan di database'
        ], 404);
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
