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

// License validation (public - for self-hosted clients)
Route::post('/license/validate', [App\Http\Controllers\Api\LicenseValidateController::class, 'validate']);

// Qiospay QRIS Callback Endpoint (POST /api/callback/accept/{secret_key})
Route::any('/callback/accept/{key?}', [App\Http\Controllers\Api\QrisCallbackController::class, 'accept']);
Route::any('/endpoint/accept/{key?}', [App\Http\Controllers\Api\QrisCallbackController::class, 'accept']);

Route::get('/debug-db', function () {
    return DB::connection()->getDatabaseName();
});

Route::get('/debug-db-full', function () {
    return response()->json([
        'db' => DB::connection()->getDatabaseName(),
        'host' => config('database.connections.mysql.host'),
        'user' => config('database.connections.mysql.username'),
        'port' => config('database.connections.mysql.port')
    ]);
});

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
    Route::get('/mobile/rekap-siswa-sekolah', [MobileAttendanceController::class, 'schoolStudentsRecap']);
    Route::post('/mobile/fcm-token', [MobileAttendanceController::class, 'updateFcmToken']);
    Route::post('/mobile/logout', [MobileAttendanceController::class, 'logout']);
});
