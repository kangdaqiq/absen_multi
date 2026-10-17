<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\RfidController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// --- RFID Endpoints ---
Route::any('/rfid', [RfidController::class, 'handle']);

// --- Legacy AS608 Fingerprint Endpoints (200 ID) ---
Route::any('/fingerprint', [App\Http\Controllers\Api\FingerprintController::class, 'handle']);
Route::any('/fingerprint/check-enroll', [App\Http\Controllers\Api\FingerprintController::class, 'checkEnrollRequest']);

// --- Dedicated R307 Fingerprint Endpoints (1000 ID + Auto-Backup & Multi-Device Sync) ---
Route::any('/r307', [App\Http\Controllers\Api\R307FingerprintController::class, 'handle']);
Route::any('/r307/check-enroll', [App\Http\Controllers\Api\R307FingerprintController::class, 'checkEnrollRequest']);
Route::any('/r307/sync-list', [App\Http\Controllers\Api\R307FingerprintController::class, 'getSyncList']);
Route::any('/r307/sync-template', [App\Http\Controllers\Api\R307FingerprintController::class, 'getSyncTemplate']);
Route::any('/r307/trigger-sync', [App\Http\Controllers\Api\R307FingerprintController::class, 'triggerSync']);

// License validation & Client Backup (public - for self-hosted clients)
Route::post('/license/validate', [App\Http\Controllers\Api\LicenseValidateController::class, 'validate']);
Route::post('/license/backup/upload', [App\Http\Controllers\Api\ClientBackupController::class, 'upload']);

// Qiospay QRIS Callback Endpoint (POST /api/callback/accept/{secret_key})
Route::any('/callback/accept/{key?}', [App\Http\Controllers\Api\QrisCallbackController::class, 'accept']);
Route::any('/endpoint/accept/{key?}', [App\Http\Controllers\Api\QrisCallbackController::class, 'accept']);

// Mobile App REST API Routes
use App\Http\Controllers\Api\MobileAttendanceController;

Route::post('/mobile/login', [MobileAttendanceController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/mobile/user', [MobileAttendanceController::class, 'user']);
    Route::get('/mobile/today', [MobileAttendanceController::class, 'today']);
    Route::post('/mobile/checkin', [MobileAttendanceController::class, 'checkIn']);
    Route::post('/mobile/checkout', [MobileAttendanceController::class, 'checkOut']);
    Route::get('/mobile/history', [MobileAttendanceController::class, 'history']);
    Route::get('/mobile/rekap', [MobileAttendanceController::class, 'recap']);
    Route::get('/mobile/rekap-kelas', [MobileAttendanceController::class, 'classRecap']);
    Route::get('/mobile/students-attendance', [MobileAttendanceController::class, 'getStudentsForAttendance']);
    Route::post('/mobile/students-attendance', [MobileAttendanceController::class, 'submitStudentsAttendance']);
    Route::post('/mobile/student-attendance-single', [MobileAttendanceController::class, 'updateSingleStudentAttendance']);
    Route::get('/mobile/kelases', [MobileAttendanceController::class, 'getKelases']);
    Route::get('/mobile/kelas', [MobileAttendanceController::class, 'getKelases']);
    Route::get('/mobile/rekap-siswa-sekolah', [MobileAttendanceController::class, 'schoolStudentsRecap']);
    Route::get('/mobile/geofence', [MobileAttendanceController::class, 'getGeofenceSetting']);
    Route::get('/mobile/location-setting', [MobileAttendanceController::class, 'getGeofenceSetting']);
    Route::post('/mobile/fcm-token', [MobileAttendanceController::class, 'updateFcmToken']);
    Route::post('/mobile/student/contact', [MobileAttendanceController::class, 'updateStudentContact']);
    Route::post('/mobile/student/profile', [MobileAttendanceController::class, 'updateStudentContact']);
    Route::post('/mobile/student/update-contact', [MobileAttendanceController::class, 'updateStudentContact']);
    Route::post('/mobile/student/update-profile', [MobileAttendanceController::class, 'updateStudentContact']);
    Route::post('/mobile/update-contact', [MobileAttendanceController::class, 'updateStudentContact']);
    Route::post('/mobile/update-profile', [MobileAttendanceController::class, 'updateStudentContact']);
    Route::post('/mobile/teacher/contact', [MobileAttendanceController::class, 'updateStudentContact']);
    Route::post('/mobile/teacher/profile', [MobileAttendanceController::class, 'updateStudentContact']);
    Route::post('/mobile/guru/contact', [MobileAttendanceController::class, 'updateStudentContact']);
    Route::post('/mobile/guru/profile', [MobileAttendanceController::class, 'updateStudentContact']);
    Route::post('/mobile/student/photo', [MobileAttendanceController::class, 'uploadStudentPhoto']);
    Route::post('/mobile/student/upload-photo', [MobileAttendanceController::class, 'uploadStudentPhoto']);
    Route::post('/mobile/student/scan-face', [MobileAttendanceController::class, 'scanFaceStudent']);
    Route::post('/mobile/teacher/photo', [MobileAttendanceController::class, 'uploadTeacherPhoto']);
    Route::post('/mobile/guru/photo', [MobileAttendanceController::class, 'uploadTeacherPhoto']);
    Route::post('/mobile/face-register', [MobileAttendanceController::class, 'registerFace']);
    Route::get('/mobile/face-status', [MobileAttendanceController::class, 'getFaceStatus']);
    Route::post('/mobile/change-password', [MobileAttendanceController::class, 'changePassword']);
    Route::post('/mobile/ganti-password', [MobileAttendanceController::class, 'changePassword']);

    // --- Fitur Jadwal Pembelajaran & Absensi Siswa Per Jam Pelajaran (Apps Guru & Siswa) ---
    Route::get('/mobile/jadwal-pelajaran/hari-ini', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'getHariIni']);
    Route::get('/mobile/jadwal-pelajaran/mingguan', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'getMingguan']);
    Route::get('/mobile/student/jadwal-pelajaran/hari-ini', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'getStudentHariIni']);
    Route::get('/mobile/student/jadwal-pelajaran/mingguan', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'getStudentMingguan']);
    Route::get('/mobile/siswa/jadwal-pelajaran/hari-ini', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'getStudentHariIni']);
    Route::get('/mobile/siswa/jadwal-pelajaran/mingguan', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'getStudentMingguan']);
    Route::get('/mobile/jadwal-pelajaran/{id}/presensi', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'getPresensiDetail']);
    Route::post('/mobile/jadwal-pelajaran/{id}/presensi', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'submitPresensi']);
    Route::post('/mobile/jadwal-pelajaran/{id}/presensi-single', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'updateSinglePresensi']);
    Route::get('/mobile/mapels', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'getMapels']);
    Route::get('/mobile/jam-pelajaran', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'getJamPelajaran']);

    Route::post('/mobile/logout', [MobileAttendanceController::class, 'logout']);
});
