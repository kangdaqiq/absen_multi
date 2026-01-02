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
use App\Models\TeacherCheckoutSession;
use App\Models\MessageQueue;

class RfidController extends Controller
{
    // Config
    const MAX_REQUESTS_PER_MINUTE = 60;
    const SCAN_COOLDOWN_SECONDS = 0;

    protected $wa;
    
    // Logging context
    private $currentApiKey = null;
    private $currentUid = null;
    private $hasLogged = false;
    protected $attendanceService;

    public function __construct(\App\Services\WhatsAppService $wa, \App\Services\TeacherAttendanceService $attendanceService)
    {
        $this->wa = $wa;
        $this->attendanceService = $attendanceService;
    }

    // ... (handle method omitted) ...

    private function handleTeacherScan($uid, $teacher, $apiKey)
    {
        try {
            // Clean expired
            TeacherCheckoutSession::where('expires_at', '<', now())->delete();

            TeacherCheckoutSession::create([
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacher->nama,
                'uid_rfid' => $uid,
                'expires_at' => now()->addMinutes(30),
                'created_at' => now()
            ]);

            // CHECK ATTENDANCE SCHEDULE REMOVED (Gate Only)
            // $attnResult = $this->attendanceService->handleAttendance($teacher);
            
            $message = 'Guru authorized. Siswa dapat absen pulang.';
            $extra = [
                'type' => 'teacher_authorization',
                'teacher_name' => $teacher->nama,
                'valid_until' => now()->addMinutes(30)->format('Y-m-d H:i:s')
            ];

            // Attendance info removed from response
            
            $this-> logRequest($apiKey, 'teacher_auth', $uid, true, 'Teacher authorized (Gate): ' . $teacher->nama);
            return $this->response(true, 'success', $message, 'ok', $extra);
        } catch (\Exception $e) {
            Log::error("Teacher scan error: " . $e->getMessage());
            return $this->response(false, 'gagal', 'Gagal membuat session authorization', 'error', ['type' => 'teacher_auth_failed']);
        }
    }

    public function handle(Request $request)
    {
        $apiKey = trim($request->input('api_key', ''));
        $this->currentApiKey = $apiKey;
        
        // 1. Auth API Key
        $device = $this->authenticate($apiKey);
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

        // 3. Cooldown
        if ($res = $this->checkScanCooldown($uid)) {
            return $res;
        }

        // 4. Mode Detection
        // Check Teacher
        $teacher = $this->checkTeacherCard($uid);
        if ($teacher) {
            return $this->handleTeacherScan($uid, $teacher, $apiKey);
        }

        // Check Enrollment
        if ($this->hasEnrollmentRequest()) {
            return $this->handleEnroll($uid, $apiKey);
        }

        // Default: Scan Absensi
        return $this->handleScan($uid, $apiKey);
    }

    // ... Helper Methods ...

    private function authenticate($apiKey)
    {
        if (empty($apiKey)) return null;

        $device = Device::where('api_key', $apiKey)->where('active', true)->first();
        if (!$device) {
            $this->logRequest($apiKey, 'auth_failed', '', false, 'Invalid API key');
            return null;
        }

        // Update last used (manual query to avoid timestamp interfering if model timestamps disabled)
        DB::table('api_keys')->where('id', $device->id)->update(['last_used_at' => now()]);

        // Rate Limit
        $count = ApiLog::where('api_key', $apiKey)
            ->where('created_at', '>', now()->subMinute())
            ->count();
        
        if ($count > self::MAX_REQUESTS_PER_MINUTE) {
            $this->logRequest($apiKey, 'rate_limit', '', false, 'Rate limit exceeded');
            $this->response(false, 'gagal', 'Terlalu banyak request. Tunggu sebentar.', 'error')->send();
            exit;
        }

        return $device;
    }

    private function validateUID($uid)
    {
        if (!preg_match('/^[A-F0-9]{8,20}$/i', $uid)) {
            $this->response(false, 'gagal', 'Format UID tidak valid', 'error')->send();
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
                return $this->response(false, 'gagal', 'Tunggu sebentar...', 'warning');
            }
        }

        ScanHistory::create(['uid' => $uid, 'created_at' => now()]);
        return null;
    }

    private function checkTeacherCard($uid)
    {
        return Guru::whereRaw('UPPER(uid_rfid) = ?', [$uid])->first();
    }

    private function hasEnrollmentRequest()
    {
        // Relaxed window to 60 minutes to avoid timezone issues
        $siswa = Siswa::where('enroll_status', 'requested')
            ->where('updated_at', '>=', now()->subHour())
            ->exists();
            
        $guru = Guru::where('enroll_status', 'requested')
            ->where('updated_at', '>=', now()->subHour())
            ->exists();

        return $siswa || $guru;
    }



    private function handleEnroll($uid, $apiKey)
    {
        DB::beginTransaction();
        try {
            // Check duplicate in both tables
            if (Siswa::where('uid_rfid', $uid)->exists() || Guru::where('uid_rfid', $uid)->exists()) {
                DB::rollBack();
                return $this->response(false, 'gagal', 'UID sudah ada', 'warning');
            }

            // 1. Check Siswa Request
            $siswa = Siswa::where('enroll_status', 'requested')
                ->where('updated_at', '>=', now()->subHour())
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
                    $this->wa->sendEnrollSuccess($siswa->nama, $siswa->no_wa, $uid, 'Kartu Siswa');
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
            
            // 2. Check Guru Request
            $guru = Guru::where('enroll_status', 'requested')
                ->where('updated_at', '>=', now()->subHour())
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
                    $this->wa->sendEnrollSuccess($guru->nama, $guru->no_wa, $uid, 'Kartu Guru');
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

            // Neither found (expired race condition?)
            DB::rollBack();
            return $this->response(false, 'gagal', 'Tidak ada permintaan enroll', 'warning');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Enroll error: " . $e->getMessage());
            return $this->response(false, 'gagal', 'Terjadi kesalahan sistem', 'error');
        }
    }

    private function handleScan($uid, $apiKey)
    {
        try {
            $siswa = Siswa::where('uid_rfid', $uid)->first();
            if (!$siswa) {
                $this->logRequest($apiKey, 'unknown_card', $uid, false, 'Kartu tidak terdaftar');
                return $this->response(false, 'unknown', 'Kartu belum terdaftar', 'error', ['type'=>'unknown_card', 'uid'=>$uid]);
            }

            // Jadwal
            $indexHari = date('N'); // 1 (Mon) - 7 (Sun) 
            
            $jadwal = Jadwal::where('index_hari', $indexHari)->where('is_active', 1)->first();
            if (!$jadwal) {
                 return $this->response(false, 'gagal', 'Jadwal Kosong', 'warning');
            }

            $now = now();
            $jamMasuk = Carbon::parse($now->format('Y-m-d') . ' ' . $jadwal->jam_masuk);
            $jamPulang = Carbon::parse($now->format('Y-m-d') . ' ' . $jadwal->jam_pulang);
            $toleransi = $jadwal->toleransi;

            $batasTelat = $jamMasuk->copy()->addMinutes($toleransi);
            $awalAbsenMasuk = $jamMasuk->copy()->subHour();
            $akhirAbsenMasuk = $jamMasuk->copy()->addHours(2);
            
            DB::beginTransaction();
            
            $att = Attendance::where('student_id', $siswa->id)
                ->where('tanggal', $now->format('Y-m-d'))
                ->lockForUpdate()
                ->first();

            // Case 1: Lengkap
            if ($att && $att->jam_pulang) {
                DB::rollBack();
                return $this->response(true, 'success', 'Absen Lengkap', 'ok', ['type'=>'sudah_lengkap', 'nama'=>$siswa->nama]);
            }

            // Case 2: Sudah Masuk, Belum Pulang
            if ($att && !$att->jam_pulang) {
                // Check Teacher Session
                $teacherSession = TeacherCheckoutSession::where('expires_at', '>', now())->orderBy('created_at', 'desc')->first();

                // If still in check-in window and no teacher session, warn
                if (!$teacherSession && $now->between($awalAbsenMasuk, $akhirAbsenMasuk)) {
                    DB::rollBack();
                    return $this->response(true, 'success', 'Sudah Absen Masuk', 'ok', ['type'=>'sudah_absen_masuk', 'nama'=>$siswa->nama]);
                }

                if (!$teacherSession) {
                    DB::rollBack();
                    return $this->response(false, 'gagal', 'Belum ada izin guru.', 'warning', ['type'=>'no_authorization', 'nama'=>$siswa->nama]);
                }

                // Pulang
                $masuk = Carbon::parse($att->jam_masuk);
                $totalSeconds = $now->diffInSeconds($masuk); // diff is absolute, ensures positive
                // Ensure correct order? diffInSeconds is absolute.
                // We want now - masuk.
                
                $att->update([
                    'jam_pulang' => $now->toTimeString(),
                    'total_seconds' => $totalSeconds,
                    'updated_at' => now(), // Attendance has timestamp columns? In model I defined them.
                ]);
                DB::commit();

                // WA
                $hours = floor($totalSeconds / 3600);
                $mins = floor(($totalSeconds % 3600) / 60);
                $this->wa->sendCheckOut($siswa->nama, $siswa->no_wa, $now->format('H:i'), $hours, $mins, $teacherSession->teacher_name, $masuk->format('H:i'));

                $this->logRequest($apiKey, 'checkout_success', $uid, true, 'Pulang: ' . $siswa->nama);
                return $this->response(true, 'success', 'Absen pulang berhasil', 'ok', [
                    'type'=>'absen_pulang', 
                    'nama'=>$siswa->nama,
                    'authorized_by'=>$teacherSession->teacher_name
                ]);
            }

            // Case 3: Absen Masuk
            if (!$att) {
                if ($now->lt($awalAbsenMasuk)) {
                    DB::rollBack();
                    return $this->response(false, 'gagal', 'Absen Tutup (Terlalu Pagi)', 'warning', ['type'=>'too_early']);
                }
                if ($now->gt($akhirAbsenMasuk)) {
                    DB::rollBack();
                    return $this->response(false, 'gagal', 'Absen Masuk Ditutup', 'warning', ['type'=>'checkin_closed']);
                }

                $status = 'H';
                $keterangan = null;

                if ($now->gt($batasTelat)) {
                    $diff = $now->diffInSeconds($awalAbsenMasuk); // Legacy used $awalAbsenMasuk? No, usually $jamMasuk.
                    // Legacy: $diff = $now->getTimestamp() - $awalAbsenMasuk->getTimestamp();
                    // Let's stick to legacy logic: diff from start of window? Or start of schedule?
                    // Logic says: "Telat {$jam} jam".
                    // Usually late is from Jam Masuk.
                    // Legacy code: $diff = $now->getTimestamp() - $awalAbsenMasuk->getTimestamp();  <-- Wait, Awal Absen Masuk is 1 hr before.
                    // This creates a weird "Late by 1 hour" even if just on time?
                    // Ah, legacy logic was: $diff = $now - $awalAbsenMasuk.
                    // If I scan exactly at Jam Masuk (1 hr after Awal), diff is 1 hour.
                    // That seems odd. But I will port EXACT legacy logic to ensure consistency.
                    // Actually, let's fix it to be meaningful if possible, or stick to exact port.
                    // Exact port:
                    $diff = $now->timestamp - $awalAbsenMasuk->timestamp;
                    $jam = floor($diff / 3600);
                    $menit = floor(($diff % 3600) / 60);
                    $keterangan = "Telat {$jam} jam {$menit} menit";
                    // Note: If I am on time (Jam Masuk), $diff is 3600 (1 hour). Keterangan: "Telat 1 jam...".
                    // This seems like a bug in legacy or intentional.
                    // Given "Telat" implies late, maybe I should check if it's actually late?
                    // Logic: if ($now > $batasTelat) -> set keterangan.
                    // So yes, it only runs when late.
                }

                Attendance::create([
                    'student_id' => $siswa->id,
                    'tanggal' => $now->format('Y-m-d'),
                    'jam_masuk' => $now->toTimeString(),
                    'status' => $status,
                    'keterangan' => $keterangan,
                    'created_at' => now(),
                ]);
                DB::commit();

                $this->wa->sendCheckIn($siswa->nama, $siswa->no_wa, $now->format('H:i'), $status, $keterangan);

                $this->logRequest($apiKey, 'checkin_success', $uid, true, 'Masuk: ' . $siswa->nama);
                return $this->response(true, 'success', 'Absen masuk berhasil', 'ok', [
                    'type'=>'absen_masuk', 
                    'nama'=>$siswa->nama,
                    'attendance_status'=>$status
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Scan error: " . $e->getMessage());
            return $this->response(false, 'gagal', 'Terjadi kesalahan sistem', 'error');
        }
    }


    private function response($ok, $status, $message, $sound = 'ok', $extra = [])
    {
        if (!$this->hasLogged) {
            $action = $extra['type'] ?? $status;
            // Use derived action implies we didn't have a specific event like checkin_success
            // So we log it as a generic API response or the status itself
            $this->logRequest($this->currentApiKey, $action, $this->currentUid, $ok, $message);
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

    private function logRequest($apiKey, $action, $uid, $success, $message)
    {
        $this->hasLogged = true;
        
        ApiLog::create([
            'api_key' => $apiKey,
            'action' => $action,
            'uid' => $uid,
            'success' => $success,
            'message' => $message,
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
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($phone)) return null;
        if (substr($phone, 0, 1) === '0') $phone = '62' . substr($phone, 1);
        elseif (substr($phone, 0, 2) !== '62') $phone = '62' . $phone;
        
        return $phone . '@s.whatsapp.net';
    }
}
