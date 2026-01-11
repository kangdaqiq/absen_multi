'school_id' => $device->school_id, // Ensure API logs also track school_id if possible, but WA service needs it.
// Wait, this is ApiLog, not WA.
// Checking where WA is called in FingerprintController...
// It seems FingerprintController calls handleStudentAttendance, which calls WA?
// Let's check handleStudentAttendance method in FingerprintController.
// Actually, handleStudentAttendance logic is inside FingerprintController.php (lines 282-446)
// But wait, the file I viewed for FingerprintController DOES NOT seem to have specific WA calls inside handleScan for
TEACHER?
// Line 223: $this->attendanceService->handleAttendance($guru);
// This service might handle WA?
// Let's check TeacherAttendanceService.
// But for Student, it calls handleStudentAttendance (line 271).
// Inside handleStudentAttendance (lines 282+):
// It does NOT seem to call $this->wa->send... in the code I viewed earlier?
// Let me re-verify FingerprintController content.
// Ah, I see sendCheckOut call at line 461 in RfidController.
// Does FingerprintController have it?
// Yes, I should check the file content of FingerprintController again.
// Viewing file 1434...
// Line 510: $this->wa->sendCheckIn(...)
// Line 461? No, logic is similar but maybe lines differ.
// Let's check the code snippet I have for FingerprintController.


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

class FingerprintController extends Controller
{
private $currentApiKey = null;
private $currentId = null;
private $currentSchoolId = null;
protected $wa;
protected $attendanceService;

public function __construct(\App\Services\WhatsAppService $wa, \App\Services\TeacherAttendanceService
$attendanceService)
{
$this->wa = $wa;
$this->attendanceService = $attendanceService;
}

// ... (handle and checkEnrollRequest methods omitted for brevity as they are unchanged) ...

public function handle(Request $request)
{
$apiKey = trim($request->input('api_key', ''));
$this->currentApiKey = $apiKey;

// 1. Auth: Get Device
$device = $this->authenticate($apiKey);
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

// Check if this is an Enroll Confirmation
if ($request->has('enroll_success') && $request->input('enroll_success') == true) {
return $this->finalizeEnrollment($fingerId, $device);
}

// 3. Scan Logic
if ($fingerId) {
return $this->handleScan($fingerId, $device);
}

return $this->response(false, 'gagal', 'Finger ID required');
}

public function checkEnrollRequest(Request $request)
{
$apiKey = $request->input('api_key');
// Validate API Key
$device = $this->authenticate($apiKey);
if (!$device) {
return $this->response(false, 'gagal', 'Auth Failed');
}

// Check Guru Enroll Request first SCOPED
$guru = Guru::where('enroll_finger_status', 'requested')
->where('school_id', $device->school_id)
->where('updated_at', '>=', now()->subMinutes(15))
->orderBy('updated_at', 'desc')
->first();

if ($guru) {
return $this->response(true, 'enroll_mode', 'Enroll Mode Active (Guru)', 'ok', [
'enroll_id' => $guru->id,
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
return $this->response(true, 'enroll_mode', 'Enroll Mode Active (Siswa)', 'ok', [
'enroll_id' => $siswa->id,
'nama' => $siswa->nama,
'type' => 'siswa'
]);
}

return $this->response(false, 'standby', 'No Enrollment');
}

private function finalizeEnrollment($fingerId, $device)
{
DB::beginTransaction();
try {
// Check Guru first SCOPED
$guru = Guru::where('enroll_finger_status', 'requested')
->where('school_id', $device->school_id)
->where('updated_at', '>=', now()->subMinutes(15))
->orderBy('updated_at', 'desc')
->lockForUpdate()
->first();

if ($guru) {
GuruFingerprint::updateOrCreate(
['guru_id' => $guru->id, 'device_id' => $device->id, 'finger_id' => $fingerId],
['created_at' => now()]
);

$guru->update([
'enroll_finger_status' => 'done',
'id_finger' => $fingerId,
]);

DB::commit();
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
SiswaFingerprint::updateOrCreate(
['student_id' => $siswa->id, 'device_id' => $device->id, 'finger_id' => $fingerId],
['created_at' => now()]
);

$siswa->update([
'enroll_finger_status' => 'done',
'id_finger' => $fingerId,
]);

DB::commit();
return $this->response(true, 'success', 'Enroll Berhasil (Siswa): ' . $siswa->nama, 'success');
}

// Neither found
DB::rollBack();
return $this->response(false, 'gagal', 'Enroll Timeout / No Request');

} catch (\Exception $e) {
DB::rollBack();
Log::error("Finalize Enroll Error: " . $e->getMessage());
return $this->response(false, 'error', 'Enroll Gagal');
}
}

private function handleScan($fingerId, $device)
{
// Check Guru first
// Note: GuruFingerprint links to Device -> School is implicit, but better to check
$guruFingerprint = GuruFingerprint::where('device_id', $device->id)
->where('finger_id', $fingerId)
->with('guru')
->first();

if ($guruFingerprint && $guruFingerprint->guru) {
$guru = $guruFingerprint->guru;

try {
// Check Gate Authorization Window SCOPED
$gateAuth = $this->checkGateAuthorization($guru, $device->school_id);

if ($gateAuth['authorized']) {
// Create Teacher Checkout Session
TeacherCheckoutSession::create([
'school_id' => $this->currentSchoolId,
'teacher_id' => $guru->id,
'teacher_name' => $guru->nama,
'uid_rfid' => $guru->uid_rfid ?? 'FINGERPRINT',
'expires_at' => now()->addMinutes(30),
'created_at' => now()
]);

ApiLog::create([
'school_id' => $this->currentSchoolId,
'api_key' => $this->currentApiKey,
'action' => 'teacher_gate_auth_finger',
'uid' => $fingerId,
'success' => true,
'message' => 'Gate Auth: ' . $guru->nama,
'created_at' => now()
]);

return $this->response(true, 'success', 'Gerbang Dibuka: ' . $guru->nama, 'ok', [
'type' => 'teacher_gate_auth',
'teacher_name' => $guru->nama,
'valid_until' => now()->addMinutes(30)->format('Y-m-d H:i:s'),
'message' => $gateAuth['message']
]);
}

// Not in gate window, process normal attendance
$attnResult = $this->attendanceService->handleAttendance($guru);

if ($attnResult['mapel']) {
$message = $attnResult['message'];
$extra = ['attendance_info' => $attnResult, 'type' => 'teacher_attendance'];

ApiLog::create([
'school_id' => $this->currentSchoolId,
'api_key' => $this->currentApiKey,
'action' => 'teacher_attendance_finger',
'uid' => $fingerId,
'success' => true,
'message' => 'Attendance: ' . $guru->nama . ' - ' . $attnResult['mapel'],
'created_at' => now()
]);

return $this->response(true, 'success', $message, 'ok', $extra);
} else {
ApiLog::create([
'school_id' => $this->currentSchoolId,
'api_key' => $this->currentApiKey,
'action' => 'teacher_attendance_finger',
'uid' => $fingerId,
'success' => true,
'message' => 'Attendance Checked: ' . $guru->nama . ' (No Schedule)',
'created_at' => now()
]);

return $this->response(true, 'success', 'Fingerprint Valid (No Schedule)', 'ok', [
'teacher_name' => $guru->nama,
'type' => 'teacher_check'
]);
}

} catch (\Exception $e) {
Log::error("Teacher finger scan error: " . $e->getMessage());
return $this->response(false, 'error', 'System Error');
}
}

// Check Siswa
$siswaFingerprint = SiswaFingerprint::where('device_id', $device->id)
->where('finger_id', $fingerId)
->with('student.kelas')
->first();

if ($siswaFingerprint && $siswaFingerprint->student) {
$siswa = $siswaFingerprint->student;

try {
return $this->handleStudentAttendance($siswa, $fingerId, $device);
} catch (\Exception $e) {
Log::error("Student finger scan error: " . $e->getMessage());
return $this->response(false, 'error', 'System Error');
}
}

// Neither found
return $this->response(false, 'gagal', 'ID Sidik Jari Tidak Dikenal di Device Ini');
}

private function handleStudentAttendance($siswa, $fingerId, $device)
{
// Use similar logic to RfidController for student check-in/out
$now = now();
$today = $now->format('Y-m-d');

// Get Jadwal (Schedule) SCOPED
$indexHari = date('N');
$jadwal = \App\Models\Jadwal::where('index_hari', $indexHari)
->where('school_id', $device->school_id)
->where('is_active', 1)
->first();

if (!$jadwal) {
return $this->response(false, 'gagal', 'Jadwal Libur/Kosong', 'warning');
}

$jamMasuk = \Carbon\Carbon::parse($now->format('Y-m-d') . ' ' . $jadwal->jam_masuk);
$jamPulang = \Carbon\Carbon::parse($now->format('Y-m-d') . ' ' . $jadwal->jam_pulang);
$toleransi = $jadwal->toleransi;

$batasTelat = $jamMasuk->copy()->addMinutes($toleransi);
$awalAbsenMasuk = $jamMasuk->copy()->subHour();
$akhirAbsenMasuk = $jamMasuk->copy()->addHours(2);

DB::beginTransaction();

$att = Attendance::where('student_id', $siswa->id)
->where('tanggal', $today)
->lockForUpdate()
->first();

// Case 1: Already complete
if ($att && $att->jam_pulang) {
DB::rollBack();
return $this->response(true, 'success', 'Absen Lengkap', 'ok', [
'type' => 'sudah_lengkap',
'nama' => $siswa->nama
]);
}

// Case 2: Check-out
if ($att && !$att->jam_pulang) {
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

// Check teacher authorization SCOPED (Join Guru table)
$teacherSession = TeacherCheckoutSession::select('teacher_checkout_sessions.*')
->join('guru', 'teacher_checkout_sessions.teacher_id', '=', 'guru.id')
->where('guru.school_id', $device->school_id)
->where('teacher_checkout_sessions.expires_at', '>', now())
->where('teacher_checkout_sessions.status', 'open')
->orderBy('teacher_checkout_sessions.created_at', 'desc')
->first();

if (!$teacherSession && $now->between($awalAbsenMasuk, $akhirAbsenMasuk)) {
DB::rollBack();
return $this->response(true, 'success', 'Sudah Absen Masuk', 'ok', [
'type' => 'sudah_absen_masuk',
'nama' => $siswa->nama
]);
}

if (!$teacherSession) {
DB::rollBack();
return $this->response(false, 'gagal', 'Belum ada izin guru.', 'warning', [
'type' => 'no_authorization',
'nama' => $siswa->nama
]);
}

// Process check-out
$masuk = \Carbon\Carbon::parse($att->jam_masuk);
$totalSeconds = $now->diffInSeconds($masuk);

$att->update([
'jam_pulang' => $now->toTimeString(),
'total_seconds' => abs($totalSeconds),
'updated_at' => now(),
]);
DB::commit();

ApiLog::create([
'school_id' => $this->currentSchoolId,
'api_key' => $this->currentApiKey,
'action' => 'student_checkout_finger',
'uid' => $fingerId,
'success' => true,
'message' => 'Pulang: ' . $siswa->nama,
'created_at' => now()
]);

return $this->response(true, 'success', 'Absen pulang berhasil', 'ok', [
'type' => 'absen_pulang',
'nama' => $siswa->nama,
'authorized_by' => $teacherSession->teacher_name
]);
}

// Case 3: Check-in
if (!$att) {
if ($now->lt($awalAbsenMasuk)) {
DB::rollBack();
return $this->response(false, 'gagal', 'Absen Tutup (Terlalu Pagi)', 'warning', ['type' => 'too_early']);
}
if ($now->gt($akhirAbsenMasuk)) {
DB::rollBack();
return $this->response(false, 'gagal', 'Absen Masuk Ditutup', 'warning', ['type' => 'checkin_closed']);
}

$status = 'H';
$keterangan = null;

if ($now->gt($batasTelat)) {
$diff = $now->timestamp - $awalAbsenMasuk->timestamp; // Should be diff with batasTelat logic?
// Logic in RfidController was different: $now->timestamp - $batasTelat->timestamp
// Here it calculates from awalAbsenMasuk? Let's align with RfidController logic for consistency.
// Rfid logic: $diff = $now->timestamp - $batasTelat->timestamp;
$diff = $now->timestamp - $batasTelat->timestamp;

$jam = floor($diff / 3600);
$menit = floor(($diff % 3600) / 60);
if ($jam > 0) {
$keterangan = "Telat {$jam} jam {$menit} menit";
} else {
$keterangan = "Telat {$menit} menit";
}
}

Attendance::create([
'student_id' => $siswa->id,
'tanggal' => $today,
'jam_masuk' => $now->toTimeString(),
'status' => $status,
'keterangan' => $keterangan,
'created_at' => now(),
]);
DB::commit();

ApiLog::create([
'school_id' => $this->currentSchoolId,
'api_key' => $this->currentApiKey,
'action' => 'student_checkin_finger',
'uid' => $fingerId,
'success' => true,
'message' => 'Masuk: ' . $siswa->nama,
'created_at' => now()
]);

return $this->response(true, 'success', 'Absen masuk berhasil', 'ok', [
'type' => 'absen_masuk',
'nama' => $siswa->nama,
'attendance_status' => $status
]);
}
}

private function checkGateAuthorization($guru, $schoolId)
{
// Get today's schedule SCOPED
$now = now();
$indexHari = date('N');
$jadwal = \App\Models\Jadwal::where('index_hari', $indexHari)
->where('school_id', $schoolId)
->where('is_active', 1)
->first();

if (!$jadwal) {
return ['authorized' => false, 'message' => 'Tidak ada jadwal'];
}

// Get tolerance from settings SCOPED
$tolerance = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key',
'checkout_tolerance_minutes')->value('setting_value') ?? 15;

$jamPulang = \Carbon\Carbon::parse($now->format('Y-m-d') . ' ' . $jadwal->jam_pulang);
$gateOpenTime = $jamPulang->copy()->subMinutes($tolerance);

// Check if current time is within gate authorization window
if ($now->gte($gateOpenTime) && $now->lte($jamPulang->copy()->addMinutes(30))) {
$minutesUntil = $now->diffInMinutes($jamPulang, false);
if ($minutesUntil > 0) {
$message = "Gerbang dibuka ({$minutesUntil} menit sebelum jam pulang)";
} else {
$message = "Gerbang dibuka (sudah jam pulang)";
}

return [
'authorized' => true,
'message' => $message,
'jam_pulang' => $jamPulang->format('H:i')
];
}

return [
'authorized' => false,
'message' => 'Belum waktunya buka gerbang',
'jam_pulang' => $jamPulang->format('H:i'),
'gate_opens_at' => $gateOpenTime->format('H:i')
];
}

private function authenticate($apiKey)
{
if (empty($apiKey))
return null;
// Use full namespace model if needed, or imported. Imported 'Device' above.
$device = Device::where('api_key', $apiKey)->where('active', true)->first();
if ($device) {
$this->currentSchoolId = $device->school_id;
DB::table('api_keys')->where('id', $device->id)->update(['last_used_at' => now()]);
}
return $device;
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