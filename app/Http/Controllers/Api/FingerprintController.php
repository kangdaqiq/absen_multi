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
use App\Models\Attendance;
use App\Models\TeacherCheckoutSession;
use App\Models\GateCard;
use App\Models\GateCardFingerprint;

class FingerprintController extends Controller
{
private $currentApiKey = null;
private $currentId = null;
private $currentSchoolId = null;
protected $wa;
protected $telegram;

public function __construct(\App\Services\WhatsAppService $wa, \App\Services\TelegramService $telegram)
{
$this->wa = $wa;
$this->telegram = $telegram;
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

    // Parse scanned_at (offline sync) or use current time with school timezone
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
        \Illuminate\Support\Facades\Log::error("FingerprintController Handle Critical Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
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

        // Cek apakah ada perintah delete di cache
        $deleteId = \Illuminate\Support\Facades\Cache::pull('delete_finger_' . $device->id);
        if ($deleteId) {
            return $this->response(true, 'delete_mode', 'Delete Mode Active', 'ok', [
                'enroll_id' => $deleteId, // reuse parameter enroll_id untuk id yang akan dihapus
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
                ['created_at' => now()]
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
                ['created_at' => now()]
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
                ['created_at' => now()]
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
        $gateCardFingerprint = GateCardFingerprint::whereIn('device_id', $schoolDeviceIds)
            ->where('finger_id', $fingerId)
            ->with('gateCard.guru')
            ->first();

        if ($gateCardFingerprint && $gateCardFingerprint->gateCard) {
            $gateCard = $gateCardFingerprint->gateCard;
            try {
                DB::beginTransaction();
                
                $gateName = $gateCard->guru_id ? ($gateCard->guru->nama ?? $gateCard->name) : $gateCard->name;
                $sessionUid = $gateCard->uid_rfid ?: "gate_card_{$gateCard->id}";
                
                TeacherCheckoutSession::where('expires_at', '<', $now)->delete();

                // Cek apakah gerbang sekolah ini sedang terbuka
                $schoolGateCardUids = GateCard::where('school_id', $this->currentSchoolId)
                    ->pluck('uid_rfid')
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
                Log::error("Gate finger scan error: " . $e->getMessage());
                ApiLog::create([
                    'school_id' => $device->school_id,
                    'api_key' => $device->api_key,
                    'action' => 'scan_failed',
                    'uid' => $fingerId,
                    'success' => false,
                    'message' => 'System Error (Gate Finger): ' . $e->getMessage(),
                    'created_at' => now()
                ]);
                return $this->response(false, 'error', 'System Error');
            }
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

            try {
                DB::beginTransaction();
                $today = $now->format('Y-m-d');

                // 0. CEK SHIFT GURU (Wajib memiliki shift aktif hari ini)
                $shift = $guru->getShiftForDate($now);
                if (!$shift) {
                    DB::rollBack();
                    ApiLog::create([
                        'school_id' => $this->currentSchoolId,
                        'api_key' => $this->currentApiKey,
                        'action' => 'scan_rejected',
                        'uid' => $fingerId,
                        'success' => false,
                        'message' => 'Guru Tanpa Shift: ' . $guru->nama,
                        'created_at' => $now
                    ]);
                    return $this->response(false, 'gagal', 'Tidak ada jadwal shift.', 'error', [
                        'type' => 'no_shift',
                        'nama' => $guru->nama
                    ]);
                }

                // ABSENSI HARIAN (Daily)
                $absensi = \App\Models\AbsensiGuru::where('guru_id', $guru->id)
                    ->where('tanggal', $today)
                    ->where('school_id', $device->school_id) // Scope
                    ->whereNull('jadwal_pelajaran_id')
                    ->lockForUpdate()
                    ->first();

                if (!$absensi) {
                    // CASE: CHECK-IN
                    // Cek Rentang Jam Scan Masuk
                    if (!$shift->isInCheckInWindow($now->format('H:i:s'))) {
                        DB::rollBack();
                        $windowStr = ($shift->awal_absen_masuk && $shift->akhir_absen_masuk)
                            ? \Carbon\Carbon::parse($shift->awal_absen_masuk)->format('H:i') . '-' . \Carbon\Carbon::parse($shift->akhir_absen_masuk)->format('H:i')
                            : '';
                        ApiLog::create([
                            'school_id' => $this->currentSchoolId,
                            'api_key' => $this->currentApiKey,
                            'action' => 'scan_rejected',
                            'uid' => $fingerId,
                            'success' => false,
                            'message' => "Di luar jam absen masuk ({$windowStr}): {$guru->nama}",
                            'created_at' => $now
                        ]);
                        return $this->response(false, 'gagal', 'Di luar jam absen masuk.', 'warning', [
                            'type' => 'outside_checkin_window',
                            'nama' => $guru->nama,
                            'window' => $windowStr
                        ]);
                    }

                    $shiftId = $shift->id;
                    $status = 'Hadir';
                    $statusKehadiran = 'tepat_waktu';
                    $menitTerlambat = 0;
                    $keterangan = null;

                    if ($shift->isLate($now->format('H:i:s'))) {
                        $status = 'Terlambat';
                        $statusKehadiran = 'terlambat';
                        $menitTerlambat = $shift->calculateLateMinutes($now->format('H:i:s'));
                        $keterangan = "Terlambat {$menitTerlambat} m ({$shift->nama_shift})";
                    } else {
                        $status = 'Hadir';
                        $statusKehadiran = 'tepat_waktu';
                        $keterangan = "Tepat Waktu ({$shift->nama_shift})";
                    }

                    \App\Models\AbsensiGuru::create([
                        'guru_id' => $guru->id,
                        'school_id' => $device->school_id, // Scope
                        'jadwal_pelajaran_id' => null, // Daily
                        'shift_id' => $shiftId,
                        'tanggal' => $today,
                        'jam_masuk' => $now->toTimeString(),
                        'waktu_hadir' => $now, // Legacy field
                        'menit_terlambat' => $menitTerlambat,
                        'status' => $status,
                        'status_kehadiran' => $statusKehadiran,
                        'keterangan' => $keterangan,
                        'created_at' => $now
                    ]);

                    DB::commit();

                    // Send WA Check-in
                    try {
                        $this->wa->sendCheckIn($guru->nama, $guru->no_wa, $now->format('H:i'), $status, $device->school_id, $keterangan, null, '-');
                    } catch (\Exception $e) {
                        Log::error("WA Guru Checkin Error: " . $e->getMessage());
                    }

                    // Send Telegram Check-in
                    try {
                        $this->telegram->sendCheckIn($guru->nama, $guru->telegram_chat_id, $now->format('H:i'), $status, $device->school_id, $keterangan, null, '-');
                    } catch (\Exception $e) {
                        Log::error("Telegram Guru Checkin Error: " . $e->getMessage());
                    }

                    $msgLog = "Guru Masuk: {$guru->nama} ({$shift->nama_shift} - {$status})";
                    ApiLog::create([
                        'school_id' => $this->currentSchoolId,
                        'api_key' => $this->currentApiKey,
                        'action' => 'checkin_success',
                        'uid' => $fingerId,
                        'success' => true,
                        'message' => $msgLog,
                        'created_at' => $now
                    ]);

                    $respMsg = $status === 'Terlambat'
                        ? "Masuk ({$status}): {$guru->nama} (+{$menitTerlambat}m)"
                        : "Selamat Pagi, {$guru->nama}.";

                    return $this->response(true, 'success', $respMsg, 'ok', [
                        'type' => 'absen_masuk_guru',
                        'nama' => $guru->nama,
                        'shift' => $shift->nama_shift,
                        'status' => $status,
                        'menit_terlambat' => $menitTerlambat,
                        'jam' => $now->format('H:i')
                    ]);

                } else {
                    // CASE: ALREADY CHECKED IN
                    $checkoutEnabled = \App\Models\Setting::where('school_id', $device->school_id)
                        ->where('setting_key', 'enable_checkout_teacher')
                        ->value('setting_value') ?? 'false';

                    if ($checkoutEnabled === 'false') {
                        DB::commit();
                        ApiLog::create([
                            'school_id' => $this->currentSchoolId,
                            'api_key' => $this->currentApiKey,
                            'action' => 'checkin_success',
                            'uid' => $fingerId,
                            'success' => true,
                            'message' => 'Sudah Absen Masuk: ' . $guru->nama,
                            'created_at' => $now
                        ]);
                        return $this->response(true, 'success', "Sudah Absen Masuk.", 'ok', [
                            'type' => 'absen_sudah_masuk_guru',
                            'nama' => $guru->nama
                        ]);
                    }

                    // Teacher Check-Out is ENABLED
                    // 1. Cek apakah sedang berada di dalam rentang jam scan pulang shift
                    $inCheckoutWindow = $shift->isInCheckOutWindow($now->format('H:i:s'));

                    // 2. Cek sesi gerbang aktif (untuk pulang cepat / izin gerbang)
                    $schoolGateCardUids = GateCard::where('school_id', $device->school_id)
                        ->pluck('uid_rfid')
                        ->filter()
                        ->toArray();

                    $gateSession = TeacherCheckoutSession::where('expires_at', '>', $now)
                        ->where('status', 'open')
                        ->where(function ($q) use ($device, $schoolGateCardUids) {
                            $q->whereIn('uid_rfid', $schoolGateCardUids)
                              ->orWhereHas('teacher', fn($t) => $t->where('school_id', $device->school_id));
                        })
                        ->orderBy('created_at', 'desc')
                        ->first();

                    // 3. Jika di luar rentang jam pulang DAN tidak ada izin gerbang -> Tolak
                    if (!$inCheckoutWindow && !$gateSession) {
                        // Jika masih dalam rentang jam masuk, anggap tap ulang (mencegah salah paham)
                        if ($shift->isInCheckInWindow($now->format('H:i:s'))) {
                            DB::commit();
                            ApiLog::create([
                                'school_id' => $this->currentSchoolId,
                                'api_key' => $this->currentApiKey,
                                'action' => 'checkin_success',
                                'uid' => $fingerId,
                                'success' => true,
                                'message' => 'Sudah Absen Masuk: ' . $guru->nama,
                                'created_at' => $now
                            ]);
                            return $this->response(true, 'success', "Sudah Absen Masuk.", 'ok', [
                                'type' => 'absen_sudah_masuk_guru',
                                'nama' => $guru->nama
                            ]);
                        }

                        DB::rollBack();
                        $windowStr = ($shift->awal_absen_pulang && $shift->akhir_absen_pulang)
                            ? \Carbon\Carbon::parse($shift->awal_absen_pulang)->format('H:i') . '-' . \Carbon\Carbon::parse($shift->akhir_absen_pulang)->format('H:i')
                            : '';
                        ApiLog::create([
                            'school_id' => $this->currentSchoolId,
                            'api_key' => $this->currentApiKey,
                            'action' => 'scan_rejected',
                            'uid' => $fingerId,
                            'success' => false,
                            'message' => "Di luar jam absen pulang ({$windowStr}): {$guru->nama}",
                            'created_at' => $now
                        ]);
                        return $this->response(false, 'gagal', 'Di luar jam absen pulang.', 'warning', [
                            'type' => 'outside_checkout_window',
                            'nama' => $guru->nama,
                            'window' => $windowStr
                        ]);
                    }

                    // 4. Proses Pulang
                    $masuk = \Carbon\Carbon::parse($absensi->tanggal . ' ' . $absensi->jam_masuk);
                    $totalSeconds = $now->diffInSeconds($masuk);
                    
                    $absensi->update([
                        'jam_pulang' => $now->toTimeString(),
                        'updated_at' => now(),
                    ]);
                    DB::commit();

                    $authorizedBy = $gateSession ? $gateSession->teacher_name : 'Sistem';

                    $hours = floor($totalSeconds / 3600);
                    $mins = floor(($totalSeconds % 3600) / 60);

                    try {
                        $this->wa->sendCheckOut($guru->nama, $guru->no_wa, $now->format('H:i'), $hours, $mins, $authorizedBy, $device->school_id, $masuk->format('H:i'), null, $now->format('d/m/Y'));
                    } catch (\Exception $e) {
                        Log::error("WA Guru Checkout Error: " . $e->getMessage());
                    }

                    // Send Telegram Checkout
                    try {
                        $this->telegram->sendCheckOut($guru->nama, $guru->telegram_chat_id, $now->format('H:i'), $hours, $mins, $authorizedBy, $device->school_id, $masuk->format('H:i'), null, $now->format('d/m/Y'));
                    } catch (\Exception $e) {
                        Log::error("Telegram Guru Checkout Error: " . $e->getMessage());
                    }

                    ApiLog::create([
                        'school_id' => $this->currentSchoolId,
                        'api_key' => $this->currentApiKey,
                        'action' => 'checkout_success',
                        'uid' => $fingerId,
                        'success' => true,
                        'message' => 'Guru Pulang: ' . $guru->nama,
                        'created_at' => $now
                    ]);

                    return $this->response(true, 'success', "Absen pulang berhasil.", 'ok', [
                        'type' => 'absen_pulang_guru',
                        'nama' => $guru->nama,
                        'authorized_by' => $authorizedBy
                    ]);
                }

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Teacher finger scan error: " . $e->getMessage());
                ApiLog::create([
                    'school_id' => $device->school_id,
                    'api_key' => $device->api_key,
                    'action' => 'scan_failed',
                    'uid' => $fingerId,
                    'success' => false,
                    'message' => 'System Error (Guru Finger): ' . $e->getMessage(),
                    'created_at' => now()
                ]);
                return $this->response(false, 'error', 'System Error');
            }
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
        $now = $now ?? now();
        $today = $now->format('Y-m-d');

        // Get Jadwal (Schedule) SCOPED
        $indexHari = $now->format('N');
        $jadwal = \App\Models\Jadwal::where('index_hari', $indexHari)
            ->where('school_id', $device->school_id)
            ->where('is_active', 1)
            ->first();

        if (!$jadwal) {
            ApiLog::create(['school_id' => $device->school_id, 'api_key' => $this->currentApiKey, 'action' => 'scan_failed', 'uid' => $fingerId, 'success' => false, 'message' => 'Jadwal Libur/Kosong', 'created_at' => now()]);
            return $this->response(false, 'gagal', 'Jadwal Libur/Kosong', 'warning');
        }

        $jamMasuk = \Carbon\Carbon::parse($now->format('Y-m-d') . ' ' . $jadwal->jam_masuk);
        $jamPulang = \Carbon\Carbon::parse($now->format('Y-m-d') . ' ' . $jadwal->jam_pulang);
        
        $awalAbsenMasuk = \Carbon\Carbon::parse($now->format('Y-m-d') . ' ' . $jadwal->awal_absen_masuk);
        $akhirAbsenMasuk = \Carbon\Carbon::parse($now->format('Y-m-d') . ' ' . $jadwal->akhir_absen_masuk);
        $akhirAbsenPulang = \Carbon\Carbon::parse($now->format('Y-m-d') . ' ' . $jadwal->akhir_absen_pulang);
        $batasTelat = $jamMasuk;

        DB::beginTransaction();

        $att = Attendance::where('student_id', $siswa->id)
            ->where('tanggal', $today)
            ->lockForUpdate()
            ->first();

        // If record exists but jam_masuk is NULL (Sakit, Izin, or Alpha from system)
        // allow a fresh check-in to override the system record
        if ($att && $att->jam_masuk === null && in_array($att->status, ['S', 'I', 'A', 'B'])) {
            $att->delete();
            $att = null;
        }

        // Case 1: Already complete (Jika sudah absen lengkap, tapi scan di jam kegiatan aktif, catat kegiatan)
        if ($att && $att->jam_pulang) {
            $recordedKegiatans = $this->recordActiveKegiatans($device->school_id, $siswa, $now, $today, 'Scan Mandiri Kegiatan');
            if (!empty($recordedKegiatans)) {
                DB::commit();
                $namaKeg = implode(', ', $recordedKegiatans);
                ApiLog::create(['school_id' => $device->school_id, 'api_key' => $this->currentApiKey, 'action' => 'kegiatan_success', 'uid' => $fingerId, 'success' => true, 'message' => "Kegiatan ({$namaKeg}): " . $siswa->nama, 'created_at' => now()]);
                return $this->response(true, 'success', 'Absen Kegiatan Berhasil', 'ok', [
                    'type' => 'absen_kegiatan',
                    'nama' => $siswa->nama,
                    'kegiatan' => $namaKeg,
                ]);
            }
            DB::rollBack();
            return $this->response(true, 'success', 'Absen Lengkap', 'ok', [
                'type' => 'sudah_lengkap',
                'nama' => $siswa->nama
            ]);
        }

        // Case 2: Check-out
        if ($att && $att->jam_masuk && !$att->jam_pulang) {
            // Check if checkout is enabled in settings SCOPED
            $checkoutEnabled = \App\Models\Setting::where('school_id', $device->school_id)
                ->where('setting_key', 'enable_checkout_attendance')
                ->value('setting_value') ?? 'true';

            // If checkout is disabled, treat as complete attendance
            if ($checkoutEnabled === 'false') {
                $this->recordActiveKegiatans($device->school_id, $siswa, $now, $today, 'Auto dari Tap Sidik Jari');
                DB::rollBack();
                return $this->response(true, 'success', 'Absen Lengkap', 'ok', [
                    'type' => 'sudah_lengkap',
                    'nama' => $siswa->nama
                ]);
            }

            // Check if there is an active session opened by a teacher of this school,
            // or by a Gate Card belonging to this school
            $teacherSession = TeacherCheckoutSession::where('teacher_checkout_sessions.expires_at', '>', $now)
                ->where('teacher_checkout_sessions.status', 'open')
                ->where(function ($query) use ($device) {
                    $query->whereExists(function ($q) use ($device) {
                        $q->select(DB::raw(1))
                            ->from('guru')
                            ->whereColumn('guru.id', 'teacher_checkout_sessions.teacher_id')
                            ->where('guru.school_id', $device->school_id);
                    })
                    ->orWhereExists(function ($q) use ($device) {
                        $q->select(DB::raw(1))
                            ->from('gate_cards')
                            ->where(function ($q2) {
                                $q2->whereRaw("gate_cards.uid_rfid COLLATE utf8mb4_unicode_ci = teacher_checkout_sessions.uid_rfid COLLATE utf8mb4_unicode_ci")
                                    ->orWhereRaw("CONCAT('gate_card_', gate_cards.id) COLLATE utf8mb4_unicode_ci = teacher_checkout_sessions.uid_rfid COLLATE utf8mb4_unicode_ci");
                            })
                            ->where('gate_cards.school_id', $device->school_id);
                    });
                })
                ->orderBy('teacher_checkout_sessions.created_at', 'desc')
                ->first();

            $isAutoCheckoutTime = $now->between($jamPulang, $akhirAbsenPulang);

            if ($now->gt($akhirAbsenPulang) && !$teacherSession) {
                 $recordedKegiatans = $this->recordActiveKegiatans($device->school_id, $siswa, $now, $today, 'Auto dari Tap Sidik Jari');
                 if (!empty($recordedKegiatans)) {
                     DB::commit();
                     $namaKeg = implode(', ', $recordedKegiatans);
                     ApiLog::create(['school_id' => $device->school_id, 'api_key' => $this->currentApiKey, 'action' => 'kegiatan_success', 'uid' => $fingerId, 'success' => true, 'message' => "Kegiatan ({$namaKeg}): " . $siswa->nama, 'created_at' => now()]);
                     return $this->response(true, 'success', 'Absen Kegiatan Berhasil', 'ok', [
                         'type' => 'absen_kegiatan',
                         'nama' => $siswa->nama,
                         'kegiatan' => $namaKeg,
                     ]);
                 }
                 DB::rollBack();
                 ApiLog::create(['school_id' => $device->school_id, 'api_key' => $this->currentApiKey, 'action' => 'scan_failed', 'uid' => $fingerId, 'success' => false, 'message' => 'Pulang Ditutup: ' . $siswa->nama, 'created_at' => now()]);
                 return $this->response(false, 'gagal', 'Pulang Ditutup', 'warning', ['type' => 'checkout_closed', 'nama' => $siswa->nama]);
            }

            if (!$isAutoCheckoutTime && !$teacherSession) {
                $recordedKegiatans = $this->recordActiveKegiatans($device->school_id, $siswa, $now, $today, 'Auto dari Tap Sidik Jari');
                if (!empty($recordedKegiatans)) {
                    DB::commit();
                    $namaKeg = implode(', ', $recordedKegiatans);
                    ApiLog::create(['school_id' => $device->school_id, 'api_key' => $this->currentApiKey, 'action' => 'kegiatan_success', 'uid' => $fingerId, 'success' => true, 'message' => "Kegiatan ({$namaKeg}): " . $siswa->nama, 'created_at' => now()]);
                    return $this->response(true, 'success', 'Absen Kegiatan Berhasil', 'ok', [
                        'type' => 'absen_kegiatan',
                        'nama' => $siswa->nama,
                        'kegiatan' => $namaKeg,
                    ]);
                }

                if ($now->between($awalAbsenMasuk, $akhirAbsenMasuk)) {
                    DB::rollBack();
                    return $this->response(true, 'success', 'Sudah Absen Masuk', 'ok', ['type' => 'sudah_absen_masuk', 'nama' => $siswa->nama]);
                }

                DB::rollBack();
                ApiLog::create(['school_id' => $device->school_id, 'api_key' => $this->currentApiKey, 'action' => 'scan_failed', 'uid' => $fingerId, 'success' => false, 'message' => 'Belum waktu pulang: ' . $siswa->nama, 'created_at' => now()]);
                return $this->response(false, 'gagal', 'Belum waktu pulang', 'warning', ['type' => 'no_authorization', 'nama' => $siswa->nama]);
            }

            // Process check-out
            $masuk = \Carbon\Carbon::parse($att->tanggal . ' ' . $att->jam_masuk);
            $totalSeconds = $now->diffInSeconds($masuk, false);
            if ($totalSeconds < 0) $totalSeconds = abs($totalSeconds);

            $newStatus = $att->status;
            $newKeterangan = $att->keterangan;

            // Jika sebelumnya terkena Auto Bolos (B), kembalikan statusnya ke H (Hadir) atau T (Terlambat)
            if ($att->status === 'B') {
                $waktuMasuk = \Carbon\Carbon::parse($att->tanggal . ' ' . $att->jam_masuk);
                $newStatus = $waktuMasuk->gt($batasTelat) ? 'T' : 'H';
                
                // Bersihkan teks keterangan dari Auto Bolos
                if ($newKeterangan) {
                    $newKeterangan = trim(str_replace('[Auto: Tidak Absen Pulang]', '', $newKeterangan));
                    if (empty($newKeterangan)) {
                        $newKeterangan = null;
                    }
                }
            }

            $att->update([
                'jam_pulang' => $now->toTimeString(),
                'total_seconds' => $totalSeconds,
                'status' => $newStatus,
                'keterangan' => $newKeterangan,
                'updated_at' => now(),
            ]);

            // OTOMATIS CATAT ABSEN KEGIATAN JIKA TERDAPAT JADWAL KEGIATAN AKTIF SAAT PULANG
            $this->recordActiveKegiatans($device->school_id, $siswa, $now, $today, 'Auto dari Absen Pulang');

            DB::commit();

            $hours = floor($totalSeconds / 3600);
            $mins = floor(($totalSeconds % 3600) / 60);
            $authorizedBy = $teacherSession ? $teacherSession->teacher_name : 'Sistem Otomatis';
            
            try {
                $this->wa->sendCheckOut($siswa->nama, $siswa->no_wa, $now->format('H:i'), $hours, $mins, $authorizedBy, $device->school_id, $masuk->format('H:i'), $siswa->wa_ortu, $now->format('d/m/Y'));
            } catch (\Throwable $e) {
                Log::error("WA Student Checkout Error: " . $e->getMessage());
            }

            // Telegram
            try {
                $this->telegram->sendCheckOut($siswa->nama, $siswa->telegram_chat_id, $now->format('H:i'), $hours, $mins, $authorizedBy, $device->school_id, $masuk->format('H:i'), $siswa->telegram_ortu_chat_id, $now->format('d/m/Y'));
            } catch (\Exception $e) {
                Log::error("Telegram Checkout Error: " . $e->getMessage());
            }

            ApiLog::create([
                'school_id' => $this->currentSchoolId,
                'api_key' => $this->currentApiKey,
                'action' => 'checkout_success',
                'uid' => $fingerId,
                'success' => true,
                'message' => 'Pulang: ' . $siswa->nama,
                'created_at' => $now
            ]);

            return $this->response(true, 'success', 'Absen pulang berhasil', 'ok', [
                'type' => 'absen_pulang',
                'nama' => $siswa->nama,
                'authorized_by' => $authorizedBy
            ]);
        }

        // Case 3: Check-in
        if (!$att || !$att->jam_masuk) {
            if ($now->lt($awalAbsenMasuk)) {
                DB::rollBack();
                ApiLog::create(['school_id' => $device->school_id, 'api_key' => $this->currentApiKey, 'action' => 'scan_failed', 'uid' => $fingerId, 'success' => false, 'message' => 'Terlalu Pagi: ' . $siswa->nama, 'created_at' => now()]);
                return $this->response(false, 'gagal', 'Absen Tutup (Terlalu Pagi)', 'warning', ['type' => 'too_early']);
            }
            if ($now->gt($akhirAbsenMasuk)) {
                DB::rollBack();
                ApiLog::create(['school_id' => $device->school_id, 'api_key' => $this->currentApiKey, 'action' => 'scan_failed', 'uid' => $fingerId, 'success' => false, 'message' => 'Masuk Ditutup: ' . $siswa->nama, 'created_at' => now()]);
                return $this->response(false, 'gagal', 'Absen Masuk Ditutup', 'warning', ['type' => 'checkin_closed']);
            }

            $status = 'H';
            $keterangan = null;

            if ($now->gt($batasTelat)) {
                $status = 'T'; // Set status to Terlambat
                $diff = $now->timestamp - $batasTelat->timestamp;
                $jam = floor($diff / 3600);
                $menit = floor(($diff % 3600) / 60);
                if ($jam > 0) {
                    $keterangan = "Telat {$jam} jam {$menit} menit";
                } else {
                    $keterangan = "Telat {$menit} menit";
                }
            }

            if ($att) {
                $att->update([
                    'jam_masuk' => $now->toTimeString(),
                    'status' => $status,
                    'keterangan' => $keterangan,
                    'updated_at' => now(),
                ]);
            } else {
                Attendance::create([
                    'student_id' => $siswa->id,
                    'tanggal' => $today,
                    'jam_masuk' => $now->toTimeString(),
                    'status' => $status,
                    'keterangan' => $keterangan,
                    'created_at' => $now,
                ]);
            }

            // OTOMATIS CATAT ABSEN KEGIATAN JIKA TERDAPAT JADWAL KEGIATAN AKTIF SAAT MASUK
            $this->recordActiveKegiatans($device->school_id, $siswa, $now, $today, 'Auto dari Absen Masuk');

            DB::commit();

            try {
                $this->wa->sendCheckIn($siswa->nama, $siswa->no_wa, $now->format('H:i'), $status, $device->school_id, $keterangan, $siswa->wa_ortu, $siswa->kelas->nama_kelas ?? '-');
            } catch (\Throwable $e) {
                Log::error("WA Student CheckIn Error: " . $e->getMessage());
            }

            // Telegram
            try {
                $this->telegram->sendCheckIn($siswa->nama, $siswa->telegram_chat_id, $now->format('H:i'), $status, $device->school_id, $keterangan, $siswa->telegram_ortu_chat_id, $siswa->kelas->nama_kelas ?? '-');
            } catch (\Exception $e) {
                Log::error("Telegram CheckIn Error: " . $e->getMessage());
            }

            ApiLog::create([
                'school_id' => $this->currentSchoolId,
                'api_key' => $this->currentApiKey,
                'action' => 'checkin_success',
                'uid' => $fingerId,
                'success' => true,
                'message' => 'Masuk: ' . $siswa->nama,
                'created_at' => $now
            ]);

            return $this->response(true, 'success', 'Absen masuk berhasil', 'ok', [
                'type' => 'absen_masuk',
                'nama' => $siswa->nama,
                'attendance_status' => $status
            ]);
        }
    }

    /**
     * Catat kehadiran siswa pada kegiatan yang sedang aktif dan relevan saat scan sidik jari.
     */
    private function recordActiveKegiatans($schoolId, $siswa, $now, $today, $keterangan = 'Auto dari Scan Mesin'): array
    {
        $activeKegiatans = \App\Models\Kegiatan::where('school_id', $schoolId)
            ->where('is_active', 1)
            ->get()
            ->filter(function ($keg) use ($now) {
                return $keg->isScheduledNow($now);
            });

        $recordedKegiatans = [];
        foreach ($activeKegiatans as $keg) {
            if (!$keg->isStudentEligible($siswa)) {
                continue;
            }

            $alreadyKeg = \App\Models\KegiatanAttendance::where('kegiatan_id', $keg->id)
                ->where('student_id', $siswa->id)
                ->where('tanggal', $today)
                ->exists();

            if (!$alreadyKeg) {
                \App\Models\KegiatanAttendance::create([
                    'school_id'   => $schoolId,
                    'kegiatan_id' => $keg->id,
                    'student_id'  => $siswa->id,
                    'tanggal'     => $today,
                    'jam_masuk'   => $now->toTimeString(),
                    'status'      => 'H',
                    'keterangan'  => $keterangan,
                ]);
                $recordedKegiatans[] = $keg->nama_kegiatan;

                // Kirim notifikasi Telegram ke siswa & ortu (WA tidak dikirim)
                try {
                    $telegramService = app(\App\Services\TelegramService::class);
                    $telegramService->sendKegiatanCheckIn(
                        namaSiswa: $siswa->nama,
                        namaKegiatan: $keg->nama_kegiatan,
                        jam: $now->format('H:i'),
                        tanggal: $now->translatedFormat('l, d F Y'),
                        schoolId: $schoolId,
                        chatIdSiswa: $siswa->telegram_chat_id ?: null,
                        chatIdOrtu: $siswa->telegram_ortu_chat_id ?: null
                    );
                } catch (\Throwable $e) {
                    \Log::error("Failed to send Telegram for kegiatan attendance: " . $e->getMessage());
                }
            }
        }
        return $recordedKegiatans;
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
}