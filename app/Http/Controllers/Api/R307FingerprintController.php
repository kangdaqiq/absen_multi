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

class R307FingerprintController extends Controller
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

        // Cek apakah ada perintah delete di cache
        $deleteId = \Illuminate\Support\Facades\Cache::pull('delete_finger_' . $device->id);
        if ($deleteId) {
            return $this->response(true, 'delete_mode', 'Delete Mode Active', 'ok', [
                'enroll_id' => $deleteId, // reuse parameter enroll_id untuk id yang akan dihapus
                'type' => 'delete'
            ]);
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
    $usedSiswa = SiswaFingerprint::where('device_id', $deviceId)->pluck('finger_id')->toArray();
    $usedGuru = GuruFingerprint::where('device_id', $deviceId)->pluck('finger_id')->toArray();
    $usedGate = GateCardFingerprint::where('device_id', $deviceId)->pluck('finger_id')->toArray();
    
    $allUsed = array_merge($usedSiswa, $usedGuru, $usedGate);
    $allUsed = array_map('intval', $allUsed);
    
    for ($i = 1; $i <= 200; $i++) {
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
        // PRE-CHECK DUPLIKASI FINGER ID
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
                DB::commit();
                return $this->response(false, 'gagal', "Ditolak: ID telah dipakai oleh $conflictName");
            }

            GateCardFingerprint::updateOrCreate(
                ['gate_card_id' => $gate->id, 'device_id' => $device->id, 'finger_id' => $fingerId],
                ['created_at' => now()]
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

                // ABSENSI HARIAN (Daily)
                $absensi = \App\Models\AbsensiGuru::where('guru_id', $guru->id)
                    ->where('tanggal', $today)
                    ->where('school_id', $device->school_id) // Scope
                    ->whereNull('jadwal_pelajaran_id')
                    ->lockForUpdate()
                    ->first();

                if (!$absensi) {
                    // CASE: CHECK-IN
                    \App\Models\AbsensiGuru::create([
                        'guru_id' => $guru->id,
                        'school_id' => $device->school_id, // Scope
                        'jadwal_pelajaran_id' => null, // Daily
                        'tanggal' => $today,
                        'jam_masuk' => $now->toTimeString(),
                        'waktu_hadir' => $now, // Legacy field
                        'status' => 'Hadir', // Default Hadir
                        'keterangan' => null,
                        'created_at' => $now
                    ]);

                    DB::commit();

                    // Send WA Check-in
                    try {
                        $this->wa->sendCheckIn($guru->nama, $guru->no_wa, $now->format('H:i'), 'Hadir', $device->school_id, '-', null, '-');
                    } catch (\Exception $e) {
                        Log::error("WA Guru Checkin Error: " . $e->getMessage());
                    }

                    // Send Telegram Check-in
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

                    // Check for active gate session
                    $gateSession = TeacherCheckoutSession::where('expires_at', '>', $now)
                        ->where('status', 'open')
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if (!$gateSession) {
                        DB::rollBack();
                        ApiLog::create([
                            'school_id' => $device->school_id,
                            'api_key' => $device->api_key,
                            'action' => 'scan_failed',
                            'uid' => $fingerId,
                            'success' => false,
                            'message' => 'Belum ada izin gerbang: ' . $guru->nama,
                            'created_at' => now()
                        ]);
                        return $this->response(false, 'gagal', 'Belum ada izin gerbang.', 'warning', ['type' => 'no_authorization', 'nama' => $guru->nama]);
                    }

                    // Process Pulang
                    $masuk = \Carbon\Carbon::parse($absensi->tanggal . ' ' . $absensi->jam_masuk);
                    $totalSeconds = $now->diffInSeconds($masuk);
                    
                    $absensi->update([
                        'jam_pulang' => $now->toTimeString(),
                        'updated_at' => now(),
                    ]);
                    DB::commit();

                    $hours = floor($totalSeconds / 3600);
                    $mins = floor(($totalSeconds % 3600) / 60);

                    try {
                        $this->wa->sendCheckOut($guru->nama, $guru->no_wa, $now->format('H:i'), $hours, $mins, $gateSession->teacher_name, $device->school_id, $masuk->format('H:i'), null, $now->format('d/m/Y'));
                    } catch (\Exception $e) {
                        Log::error("WA Guru Checkout Error: " . $e->getMessage());
                    }

                    // Send Telegram Checkout
                    try {
                        $this->telegram->sendCheckOut($guru->nama, $guru->telegram_chat_id, $now->format('H:i'), $hours, $mins, $gateSession->teacher_name, $device->school_id, $masuk->format('H:i'), null, $now->format('d/m/Y'));
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
                        'authorized_by' => $gateSession->teacher_name
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

        // Case 1: Already complete
        if ($att && $att->jam_pulang) {
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
                 DB::rollBack();
                 ApiLog::create(['school_id' => $device->school_id, 'api_key' => $this->currentApiKey, 'action' => 'scan_failed', 'uid' => $fingerId, 'success' => false, 'message' => 'Pulang Ditutup: ' . $siswa->nama, 'created_at' => now()]);
                 return $this->response(false, 'gagal', 'Pulang Ditutup', 'warning', ['type' => 'checkout_closed', 'nama' => $siswa->nama]);
            }

            if (!$isAutoCheckoutTime && !$teacherSession) {
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

            // OTOMATIS CATAT ABSEN KEGIATAN JIKA TERDAPAT JADWAL KEGIATAN AKTIF
            $activeKegiatans = \App\Models\Kegiatan::where('school_id', $device->school_id)
                ->where('is_active', 1)
                ->get()
                ->filter(function ($keg) use ($now) {
                    return $keg->isScheduledNow($now);
                });

            foreach ($activeKegiatans as $keg) {
                $alreadyKeg = \App\Models\KegiatanAttendance::where('kegiatan_id', $keg->id)
                    ->where('student_id', $siswa->id)
                    ->where('tanggal', $today)
                    ->exists();

                if (!$alreadyKeg) {
                    \App\Models\KegiatanAttendance::create([
                        'school_id'   => $device->school_id,
                        'kegiatan_id' => $keg->id,
                        'student_id'  => $siswa->id,
                        'tanggal'     => $today,
                        'jam_masuk'   => $now->toTimeString(),
                        'status'      => 'H',
                        'keterangan'  => 'Auto dari Absen Masuk',
                    ]);
                }
            }

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

        $allIds = array_values(array_unique(array_merge($siswaFingers, $guruFingers, $gateFingers)));
        sort($allIds);

        return response()->json([
            'ok' => true,
            'school_id' => $schoolId,
            'total' => count($allIds),
            'allowed_ids' => $allIds
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

        // Cari di Siswa
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

        // Cari di Guru
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

        // Cari di Gate Card
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
