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
use App\Models\Siswa;
use App\Models\Jadwal;
use App\Models\Attendance;
use App\Models\Kegiatan;
use App\Models\KegiatanAttendance;
use App\Models\TeacherCheckoutSession;
use App\Models\GateCard;

class RfidController extends Controller
{
    private $currentApiKey = null;
    private $currentUid = null;
    private $currentSchoolId = null;
    private $currentScannedAt = null;
    private $hasLogged = false;
    protected $wa;
    protected $telegram;

    public function __construct(\App\Services\WhatsAppService $wa, \App\Services\TelegramService $telegram)
    {
        $this->wa = $wa;
        $this->telegram = $telegram;
    }

    public function handle(Request $request)
    {
        $apiKey = trim($request->input('api_key', ''));
        $uid = trim($request->input('uid', ''));
        $this->currentApiKey = $apiKey;
        $this->currentUid = $uid;

        $device = Device::where('api_key', $apiKey)->where('is_active', 1)->first();
        if (!$device) {
            return $this->response(false, 'gagal', 'API Key Invalid');
        }

        $this->currentSchoolId = $device->school_id;

        // Parse scanned_at untuk dukungan sync offline
        $now = now();
        $scannedAt = trim($request->input('scanned_at', ''));
        if ($scannedAt !== '') {
            try {
                $parsed = Carbon::parse($scannedAt);
                if ($parsed->lte(now()) && $parsed->gte(now()->subDays(7))) {
                    $now = $parsed;
                    $this->currentScannedAt = $now;
                }
            } catch (\Exception $e) {}
        }
        $today = $now->format('Y-m-d');

        // 1. Cek Gate Card (Kartu Pembuka Gerbang Kepulangan)
        $gateCard = GateCard::where('school_id', $device->school_id)
            ->where(function($q) use ($uid) {
                $q->where('uid_rfid', $uid)
                  ->orWhere('id', str_replace('gate_card_', '', $uid));
            })
            ->first();

        if ($gateCard) {
            try {
                DB::beginTransaction();
                $gateName = $gateCard->guru_id ? ($gateCard->guru->nama ?? $gateCard->name) : $gateCard->name;
                $sessionUid = $gateCard->uid_rfid ?: "gate_card_{$gateCard->id}";

                TeacherCheckoutSession::where('expires_at', '<', $now)->delete();

                $activeSession = TeacherCheckoutSession::where('uid_rfid', $sessionUid)
                    ->where('expires_at', '>=', $now)
                    ->first();

                if ($activeSession) {
                    $activeSession->delete();
                    DB::commit();

                    $this->logRequest($apiKey, 'gate_closed', $uid, true, 'Sesi Kepulangan Ditutup: ' . $gateName);
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

                $this->logRequest($apiKey, 'gate_access', $uid, true, 'Sesi Kepulangan Dibuka: ' . $gateName);
                return $this->response(true, 'success', "Gerbang Dibuka (30 Menit).", 'ok', [
                    'type' => 'gate_opened',
                    'nama' => $gateName
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Gate RFID scan error: " . $e->getMessage());
                return $this->response(false, 'error', 'System Error');
            }
        }

        // 2. Cek Guru
        $guru = Guru::where('school_id', $device->school_id)
            ->where('uid_rfid', $uid)
            ->first();

        if ($guru) {
            try {
                DB::beginTransaction();
                $absensiGuru = \App\Models\AbsensiGuru::where('guru_id', $guru->id)
                    ->where('tanggal', $today)
                    ->where('school_id', $device->school_id)
                    ->whereNull('jadwal_pelajaran_id')
                    ->lockForUpdate()
                    ->first();

                if (!$absensiGuru) {
                    \App\Models\AbsensiGuru::create([
                        'guru_id' => $guru->id,
                        'school_id' => $device->school_id,
                        'jadwal_pelajaran_id' => null,
                        'tanggal' => $today,
                        'jam_masuk' => $now->toTimeString(),
                        'waktu_hadir' => $now,
                        'status' => 'Hadir',
                        'created_at' => $now
                    ]);

                    DB::commit();

                    $this->wa->sendCheckIn($guru->nama, $guru->no_wa, $now->format('H:i'), 'Hadir', $device->school_id, '-', null, '-');
                    $this->logRequest($apiKey, 'checkin_success', $uid, true, 'Guru Masuk RFID: ' . $guru->nama);

                    return $this->response(true, 'success', "Selamat Pagi, {$guru->nama}.", 'ok', [
                        'type' => 'absen_masuk_guru',
                        'nama' => $guru->nama
                    ]);
                } else {
                    DB::rollBack();
                    return $this->response(true, 'success', "Sudah Absen Masuk, {$guru->nama}.", 'ok', [
                        'type' => 'sudah_absen_masuk_guru',
                        'nama' => $guru->nama
                    ]);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Guru RFID scan error: " . $e->getMessage());
                return $this->response(false, 'gagal', 'Terjadi kesalahan sistem', 'error');
            }
        }

        // 3. Cek Siswa berdasarkan RFID UID
        $siswa = Siswa::where('school_id', $device->school_id)
            ->where('uid_rfid', $uid)
            ->with('kelas')
            ->first();

        if (!$siswa) {
            $this->logRequest($apiKey, 'scan_failed', $uid, false, 'Kartu RFID Tidak Dikenal');
            return $this->response(false, 'gagal', 'Kartu RFID Tidak Dikenal');
        }

        // Ambil Jadwal KBM
        $indexHari = (int) $now->format('N');
        $jadwal = Jadwal::where('index_hari', $indexHari)
            ->where('school_id', $device->school_id)
            ->where('is_active', 1)
            ->first();

        if (!$jadwal) {
            $this->logRequest($apiKey, 'scan_failed', $uid, false, 'Jadwal Libur/Kosong');
            return $this->response(false, 'gagal', 'Jadwal Libur/Kosong', 'warning');
        }

        $jamMasukDefault = Carbon::parse($today . ' ' . $jadwal->jam_masuk);
        $jamPulang        = Carbon::parse($today . ' ' . $jadwal->jam_pulang);
        $awalAbsenMasuk  = Carbon::parse($today . ' ' . $jadwal->awal_absen_masuk);
        $akhirAbsenMasuk = Carbon::parse($today . ' ' . $jadwal->akhir_absen_masuk);
        $akhirAbsenPulang = Carbon::parse($today . ' ' . $jadwal->akhir_absen_pulang);

        try {
            DB::beginTransaction();

            $att = Attendance::where('student_id', $siswa->id)
                ->where('tanggal', $today)
                ->lockForUpdate()
                ->first();

            // Jika record ada tapi jam_masuk NULL (Sakit, Izin, Alpha sistem), hapus agar bisa absen masuk baru
            if ($att && $att->jam_masuk === null && in_array($att->status, ['S', 'I', 'A', 'B'])) {
                $att->delete();
                $att = null;
            }

            // Case 1: Absen Lengkap (Sudah Masuk & Sudah Pulang)
            if ($att && $att->jam_pulang) {
                DB::rollBack();
                return $this->response(true, 'success', 'Absen Lengkap', 'ok', [
                    'type' => 'sudah_lengkap',
                    'nama' => $siswa->nama
                ]);
            }

            // Case 2: Absen Pulang
            if ($att && $att->jam_masuk && !$att->jam_pulang) {
                $checkoutEnabled = \App\Models\Setting::where('school_id', $device->school_id)
                    ->where('setting_key', 'enable_checkout_attendance')
                    ->value('setting_value') ?? 'true';

                if ($checkoutEnabled === 'false') {
                    DB::rollBack();
                    return $this->response(true, 'success', 'Absen Lengkap', 'ok', [
                        'type' => 'sudah_lengkap',
                        'nama' => $siswa->nama
                    ]);
                }

                $teacherSession = TeacherCheckoutSession::where('expires_at', '>', $now)
                    ->where('status', 'open')
                    ->first();

                $isAutoCheckoutTime = $now->between($jamPulang, $akhirAbsenPulang);

                if ($now->gt($akhirAbsenPulang) && !$teacherSession) {
                    DB::rollBack();
                    $this->logRequest($apiKey, 'scan_failed', $uid, false, 'Pulang Ditutup: ' . $siswa->nama);
                    return $this->response(false, 'gagal', 'Pulang Ditutup', 'warning', ['type' => 'checkout_closed', 'nama' => $siswa->nama]);
                }

                if (!$isAutoCheckoutTime && !$teacherSession) {
                    if ($now->between($awalAbsenMasuk, $akhirAbsenMasuk)) {
                        DB::rollBack();
                        return $this->response(true, 'success', 'Sudah Absen Masuk', 'ok', ['type' => 'sudah_absen_masuk', 'nama' => $siswa->nama]);
                    }

                    DB::rollBack();
                    $this->logRequest($apiKey, 'scan_failed', $uid, false, 'Belum waktu pulang: ' . $siswa->nama);
                    return $this->response(false, 'gagal', 'Belum waktu pulang', 'warning', ['type' => 'no_authorization', 'nama' => $siswa->nama]);
                }

                // Proses Pulang
                $masuk = Carbon::parse($att->tanggal . ' ' . $att->jam_masuk);
                $totalSeconds = max(0, $now->diffInSeconds($masuk, false));

                $newStatus = $att->status;
                $newKeterangan = $att->keterangan;

                if ($att->status === 'B') {
                    $newStatus = $masuk->gt($jamMasukDefault) ? 'T' : 'H';
                    if ($newKeterangan) {
                        $newKeterangan = trim(str_replace('[Auto: Tidak Absen Pulang]', '', $newKeterangan));
                        if (empty($newKeterangan)) $newKeterangan = null;
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

                $this->wa->sendCheckOut($siswa->nama, $siswa->no_wa, $now->format('H:i'), $hours, $mins, $authorizedBy, $device->school_id, $masuk->format('H:i'), $siswa->wa_ortu, $now->format('d/m/Y'));

                $this->logRequest($apiKey, 'checkout_success', $uid, true, 'Pulang RFID: ' . $siswa->nama);
                return $this->response(true, 'success', 'Absen pulang berhasil', 'ok', [
                    'type' => 'absen_pulang',
                    'nama' => $siswa->nama,
                    'authorized_by' => $authorizedBy
                ]);
            }

            // Case 3: Absen Masuk
            if (!$att || !$att->jam_masuk) {
                if ($now->lt($awalAbsenMasuk)) {
                    DB::rollBack();
                    $this->logRequest($apiKey, 'scan_failed', $uid, false, 'Terlalu Pagi: ' . $siswa->nama);
                    return $this->response(false, 'gagal', 'Absen Tutup (Terlalu Pagi)', 'warning', ['type' => 'too_early']);
                }

                if ($now->gt($akhirAbsenMasuk)) {
                    DB::rollBack();
                    $this->logRequest($apiKey, 'scan_failed', $uid, false, 'Masuk Ditutup: ' . $siswa->nama);
                    return $this->response(false, 'gagal', 'Absen Masuk Ditutup', 'warning', ['type' => 'checkin_closed']);
                }

                $statusKbm = 'H';
                $keteranganKbm = null;

                if ($now->gt($jamMasukDefault)) {
                    $statusKbm = 'T';
                    $diff = $now->timestamp - $jamMasukDefault->timestamp;
                    $jam = floor($diff / 3600);
                    $menit = floor(($diff % 3600) / 60);
                    if ($jam > 0) {
                        $keteranganKbm = "Telat {$jam} jam {$menit} menit";
                    } else {
                        $keteranganKbm = "Telat {$menit} menit";
                    }
                }

                if ($att) {
                    $att->update([
                        'jam_masuk'  => $now->toTimeString(),
                        'status'     => $statusKbm,
                        'keterangan' => $keteranganKbm,
                        'updated_at' => now(),
                    ]);
                } else {
                    Attendance::create([
                        'student_id' => $siswa->id,
                        'tanggal'    => $today,
                        'jam_masuk'  => $now->toTimeString(),
                        'status'     => $statusKbm,
                        'keterangan' => $keteranganKbm,
                        'created_at' => now(),
                    ]);
                }

                // OTOMATIS CATAT ABSEN KEGIATAN JIKA TERDAPAT JADWAL KEGIATAN AKTIF
                $activeKegiatans = Kegiatan::where('school_id', $device->school_id)
                    ->where('is_active', 1)
                    ->get()
                    ->filter(fn($k) => $k->isScheduledNow($now));

                foreach ($activeKegiatans as $keg) {
                    $alreadyKeg = KegiatanAttendance::where('kegiatan_id', $keg->id)
                        ->where('student_id', $siswa->id)
                        ->where('tanggal', $today)
                        ->exists();

                    if (!$alreadyKeg) {
                        KegiatanAttendance::create([
                            'school_id'   => $device->school_id,
                            'kegiatan_id' => $keg->id,
                            'student_id'  => $siswa->id,
                            'tanggal'     => $today,
                            'jam_masuk'   => $now->toTimeString(),
                            'status'      => 'H',
                            'keterangan'  => 'Auto dari Absen Masuk RFID',
                        ]);
                    }
                }

                DB::commit();

                // Send WA Check-in
                $this->wa->sendCheckIn($siswa->nama, $siswa->no_wa, $now->format('H:i'), $statusKbm, $device->school_id, $keteranganKbm, $siswa->wa_ortu, $siswa->kelas->nama_kelas ?? '-');

                $this->logRequest($apiKey, 'checkin_success', $uid, true, 'Masuk RFID: ' . $siswa->nama);
                return $this->response(true, 'success', 'Absen Masuk Berhasil', 'ok', [
                    'type' => 'absen_masuk',
                    'nama' => $siswa->nama,
                    'attendance_status' => $statusKbm
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("RFID student scan error: " . $e->getMessage());
            return $this->response(false, 'gagal', 'Terjadi kesalahan sistem', 'error');
        }
    }

    private function response($ok, $status, $message, $sound = 'ok', $extra = [])
    {
        if (!$this->hasLogged) {
            $action = $extra['type'] ?? $status;
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

        $logTime = $this->currentScannedAt ?? now();

        ApiLog::create([
            'school_id' => $this->currentSchoolId,
            'api_key' => $apiKey,
            'action' => $action,
            'uid' => $uid,
            'success' => $success,
            'message' => substr($message, 0, 500),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => $logTime
        ]);
    }
}