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
Route::any('/rfid/v2', [RfidController::class, 'handleV2']);

// --- Legacy AS608 Fingerprint Endpoints (200 ID) ---
Route::any('/fingerprint', [App\Http\Controllers\Api\FingerprintController::class, 'handle']);
Route::any('/fingerprint/check-enroll', [App\Http\Controllers\Api\FingerprintController::class, 'checkEnrollRequest']);

// --- Dedicated R307 Fingerprint Endpoints (1000 ID + Auto-Backup & Multi-Device Sync) ---
Route::any('/r307', [App\Http\Controllers\Api\R307FingerprintController::class, 'handle']);
Route::any('/r307/check-enroll', [App\Http\Controllers\Api\R307FingerprintController::class, 'checkEnrollRequest']);
Route::any('/r307/sync-list', [App\Http\Controllers\Api\R307FingerprintController::class, 'getSyncList']);
Route::any('/r307/sync-template', [App\Http\Controllers\Api\R307FingerprintController::class, 'getSyncTemplate']);
Route::any('/r307/trigger-sync', [App\Http\Controllers\Api\R307FingerprintController::class, 'triggerSync']);

// License validation, Client Backup & Cloud Storage Relay (public - for self-hosted clients)
Route::post('/license/validate', [App\Http\Controllers\Api\LicenseValidateController::class, 'validate']);
Route::post('/license/backup/upload', [App\Http\Controllers\Api\ClientBackupController::class, 'upload']);
Route::post('/license/storage/upload', [App\Http\Controllers\Api\ClientStorageController::class, 'upload']);
Route::post('/license/storage/delete', [App\Http\Controllers\Api\ClientStorageController::class, 'delete']);
Route::post('/license/storage/presigned-url', [App\Http\Controllers\Api\ClientStorageController::class, 'presignedUrl']);
Route::post('/license/storage/confirm-upload', [App\Http\Controllers\Api\ClientStorageController::class, 'confirmUpload']);
Route::post('/license/storage/sync', [App\Http\Controllers\Api\ClientStorageController::class, 'sync']);

// Mobile Storage Direct PUT upload (single-use presigned token authenticated, for internal storage driver)
Route::put('/mobile/storage/direct-put/{token}', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'directPutUpload']);

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
    Route::get('/mobile/student/kbm-history', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'getStudentKbmHistory']);
    Route::get('/mobile/siswa/kbm-history', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'getStudentKbmHistory']);
    Route::get('/mobile/student/riwayat-mapel', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'getStudentKbmHistory']);
    Route::get('/mobile/siswa/riwayat-mapel', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'getStudentKbmHistory']);
    Route::get('/mobile/jadwal-pelajaran/{id}/presensi', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'getPresensiDetail']);
    Route::post('/mobile/jadwal-pelajaran/{id}/presensi', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'submitPresensi']);
    Route::post('/mobile/jadwal-pelajaran/{id}/presensi-single', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'updateSinglePresensi']);
    Route::post('/mobile/jadwal-pelajaran/upload-dokumentasi', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'uploadDokumentasi']);
    Route::post('/mobile/storage/presigned-url', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'getPresignedUploadUrl']);
    Route::post('/mobile/storage/confirm-upload', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'confirmDirectUpload']);
    Route::post('/mobile/storage/delete-file', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'deleteStorageFile']);
    Route::post('/mobile/jadwal-pelajaran/presigned-url', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'getPresignedUploadUrl']);
    Route::get('/mobile/mapels', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'getMapels']);
    Route::get('/mobile/jam-pelajaran', [App\Http\Controllers\Api\MobileJadwalPelajaranController::class, 'getJamPelajaran']);

    // --- Fitur Pengajuan & Pengelolaan Izin Siswa (Apps Siswa & Guru) ---
    Route::get('/mobile/izin/my', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'myLeaves']);
    Route::get('/mobile/student/izin', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'myLeaves']);
    Route::get('/mobile/siswa/izin', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'myLeaves']);
    Route::post('/mobile/izin', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'submitStudentLeave']);
    Route::post('/mobile/student/izin', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'submitStudentLeave']);
    Route::post('/mobile/siswa/izin', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'submitStudentLeave']);

    Route::get('/mobile/guru/izin', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'teacherIndex']);
    Route::get('/mobile/teacher/izin', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'teacherIndex']);
    Route::get('/mobile/guru/izin/students', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'teacherStudents']);
    Route::get('/mobile/teacher/izin/students', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'teacherStudents']);
    Route::post('/mobile/guru/izin', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'teacherStore']);
    Route::post('/mobile/teacher/izin', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'teacherStore']);
    Route::post('/mobile/guru/izin/{id}/approve', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'approve']);
    Route::post('/mobile/teacher/izin/{id}/approve', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'approve']);
    Route::post('/mobile/izin/{id}/approve', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'approve']);
    Route::post('/mobile/guru/izin/{id}/reject', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'reject']);
    Route::post('/mobile/teacher/izin/{id}/reject', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'reject']);
    Route::post('/mobile/izin/{id}/reject', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'reject']);
    Route::delete('/mobile/guru/izin/{id}', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'destroy']);
    Route::delete('/mobile/teacher/izin/{id}', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'destroy']);

    Route::get('/mobile/izin/{id}', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'show']);
    Route::get('/mobile/student/izin/{id}', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'show']);
    Route::get('/mobile/siswa/izin/{id}', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'show']);
    Route::delete('/mobile/izin/{id}', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'destroy']);
    Route::delete('/mobile/student/izin/{id}', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'destroy']);
    Route::delete('/mobile/siswa/izin/{id}', [App\Http\Controllers\Api\MobileStudentLeaveController::class, 'destroy']);

    // --- Fitur Catatan Pelanggaran Siswa (Apps Siswa & Guru) ---
    Route::get('/mobile/pelanggaran/my', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'myViolations']);
    Route::get('/mobile/student/pelanggaran', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'myViolations']);
    Route::get('/mobile/siswa/pelanggaran', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'myViolations']);
    Route::get('/mobile/pelanggaran/kategori', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'kategoriList']);

    Route::get('/mobile/guru/pelanggaran', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'teacherIndex']);
    Route::get('/mobile/teacher/pelanggaran', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'teacherIndex']);
    Route::get('/mobile/guru/pelanggaran/kategori', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'kategoriList']);
    Route::get('/mobile/teacher/pelanggaran/kategori', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'kategoriList']);
    Route::get('/mobile/guru/pelanggaran/students', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'teacherStudents']);
    Route::get('/mobile/teacher/pelanggaran/students', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'teacherStudents']);
    Route::post('/mobile/guru/pelanggaran', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'teacherStore']);
    Route::post('/mobile/teacher/pelanggaran', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'teacherStore']);
    Route::match(['put', 'patch', 'post'], '/mobile/guru/pelanggaran/{id}', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'teacherUpdate']);
    Route::match(['put', 'patch', 'post'], '/mobile/teacher/pelanggaran/{id}', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'teacherUpdate']);
    Route::delete('/mobile/guru/pelanggaran/{id}', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'destroy']);
    Route::delete('/mobile/teacher/pelanggaran/{id}', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'destroy']);

    Route::get('/mobile/pelanggaran/{id}', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'show']);
    Route::get('/mobile/student/pelanggaran/{id}', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'show']);
    Route::get('/mobile/siswa/pelanggaran/{id}', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'show']);

    // --- Rekap Pelanggaran Siswa & Akumulasi Poin (Apps Guru & Admin) ---
    Route::get('/mobile/guru/pelanggaran/rekap', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'rekapStudents']);
    Route::get('/mobile/teacher/pelanggaran/rekap', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'rekapStudents']);
    Route::get('/mobile/pelanggaran/rekap', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'rekapStudents']);

    // --- Riwayat Penanganan & Konseling BK (SP 1-4, GDrive) (Apps Guru, BK & Siswa) ---
    Route::get('/mobile/guru/penanganan', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'penangananIndex']);
    Route::get('/mobile/teacher/penanganan', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'penangananIndex']);
    Route::get('/mobile/penanganan', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'penangananIndex']);
    Route::get('/mobile/guru/penanganan/students', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'penangananStudents']);
    Route::get('/mobile/teacher/penanganan/students', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'penangananStudents']);
    Route::post('/mobile/guru/penanganan', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'storePenanganan']);
    Route::post('/mobile/teacher/penanganan', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'storePenanganan']);
    Route::post('/mobile/penanganan', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'storePenanganan']);
    Route::get('/mobile/guru/penanganan/student/{id}', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'studentHistory']);
    Route::get('/mobile/teacher/penanganan/student/{id}', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'studentHistory']);
    Route::delete('/mobile/guru/penanganan/{id}', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'destroyPenanganan']);
    Route::delete('/mobile/teacher/penanganan/{id}', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'destroyPenanganan']);
    Route::delete('/mobile/penanganan/{id}', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'destroyPenanganan']);

    Route::get('/mobile/student/penanganan/my', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'myPenanganans']);
    Route::get('/mobile/siswa/penanganan/my', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'myPenanganans']);
    Route::get('/mobile/penanganan/my', [App\Http\Controllers\Api\MobilePelanggaranController::class, 'myPenanganans']);

    Route::post('/mobile/logout', [MobileAttendanceController::class, 'logout']);
});

// --- CBT / Exam App Integration Endpoints ---
Route::prefix('cbt-sync')->group(function () {
    Route::get('/ping', [App\Http\Controllers\Api\ExamSyncApiController::class, 'ping']);
    Route::get('/check-school', [App\Http\Controllers\Api\ExamSyncApiController::class, 'checkSchool']);
    Route::get('/data', [App\Http\Controllers\Api\ExamSyncApiController::class, 'exportData']);
    Route::get('/rekap-absensi', [App\Http\Controllers\Api\ExamSyncApiController::class, 'exportRekapAbsensi']);
});

