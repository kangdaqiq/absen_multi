<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\DeviceController;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Super Admin Routes
Route::middleware(['auth'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\SuperAdmin\DashboardController::class, 'index'])->name('dashboard');

        // Schools Management
        Route::resource('schools', App\Http\Controllers\SuperAdmin\SchoolController::class);

        // School Admins Management (nested resource)
        Route::resource('schools.admins', App\Http\Controllers\SuperAdmin\SchoolAdminController::class);
    });
});


Route::middleware('auth')->group(function () {
    // Common Routes (All Authenticated)
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

    // Admin & Teacher Routes
    Route::middleware('role:admin,teacher')->group(function () {
        // Master Data (Siswa/Guru) - Ideally Teacher is Read Only, but for now we allow access
        Route::post('kelas/{id}/toggle-status', [KelasController::class, 'toggleStatus'])->name('kelas.toggle-status');
        Route::post('kelas/{id}/toggle-report', [KelasController::class, 'toggleReport'])->name('kelas.toggle-report');
        Route::resource('kelas', KelasController::class)->except(['create', 'show', 'edit']);
        Route::resource('guru', GuruController::class)->except(['create', 'show', 'edit']);
        Route::resource('siswa', SiswaController::class)->except(['create', 'show', 'edit']);
        Route::resource('mapel', App\Http\Controllers\MapelController::class)->except(['create', 'show', 'edit']);
        Route::resource('jadwal-pelajaran', App\Http\Controllers\JadwalPelajaranController::class)->except(['create', 'show', 'edit']);

        // Absensi
        Route::get('/absensi', [App\Http\Controllers\AttendanceController::class, 'index'])->name('absensi.index');
        Route::get('/absensi/guru', [App\Http\Controllers\TeacherAttendanceReportController::class, 'index'])->name('absensi-guru.index');
        Route::post('/absensi/guru/store', [App\Http\Controllers\TeacherAttendanceReportController::class, 'store'])->name('absensi-guru.store');
        Route::post('/absensi/update', [App\Http\Controllers\AttendanceController::class, 'update'])->name('absensi.update');
        Route::delete('/absensi/destroy', [App\Http\Controllers\AttendanceController::class, 'destroy'])->name('absensi.destroy');

        // Rekap
        Route::get('/rekap', [App\Http\Controllers\RekapController::class, 'index'])->name('rekap.index');
        Route::get('/rekap/export', [App\Http\Controllers\RekapController::class, 'export'])->name('rekap.export');
        Route::get('/rekap/pdf', [App\Http\Controllers\RekapController::class, 'printPdf'])->name('rekap.pdf');

        Route::get('/rekap-guru', [App\Http\Controllers\RekapGuruController::class, 'index'])->name('rekap-guru.index');
        Route::get('/rekap-guru/export', [App\Http\Controllers\RekapGuruController::class, 'export'])->name('rekap-guru.export');
        Route::get('/rekap-guru/pdf', [App\Http\Controllers\RekapGuruController::class, 'printPdf'])->name('rekap-guru.pdf');
        Route::get('/rekap/{id}', [App\Http\Controllers\RekapController::class, 'show'])->name('rekap.show');
        Route::get('/rekap/{id}/export', [App\Http\Controllers\RekapController::class, 'exportDetail'])->name('rekap.exportDetail');
    });

    // Admin & Super Admin Routes
    Route::middleware('role:admin,super_admin')->group(function () {
        // Guru Import
        Route::post('/guru/import', [GuruController::class, 'import'])->name('guru.import');
        Route::get('/guru/template', [GuruController::class, 'downloadTemplate'])->name('guru.template');

        // Enrollement Actions (Sensitive)
        Route::post('/guru/{id}/enroll', [GuruController::class, 'enrollRequest']);
        Route::post('/guru/{id}/enroll-cancel', [GuruController::class, 'cancelEnroll']);
        Route::get('/guru/{id}/enroll-check', [GuruController::class, 'enrollCheck']);
        Route::post('/guru/{id}/delete-uid', [GuruController::class, 'deleteUid']);

        Route::post('/guru/{id}/enroll-finger', [GuruController::class, 'enrollFingerRequest']);
        Route::post('/guru/{id}/enroll-finger-cancel', [GuruController::class, 'cancelFingerEnroll']);
        Route::get('/guru/{id}/enroll-finger-check', [GuruController::class, 'enrollFingerCheck']);
        Route::post('/guru/{id}/delete-finger', [GuruController::class, 'deleteFingerId']);

        Route::post('/siswa/import', [SiswaController::class, 'import'])->name('siswa.import');
        Route::get('/siswa/template', [SiswaController::class, 'downloadTemplate'])->name('siswa.template');
        Route::post('/siswa/generate-accounts', [SiswaController::class, 'generateAccounts'])->name('siswa.generateAccounts');
        Route::post('/siswa/{id}/enroll', [SiswaController::class, 'enrollRequest']);
        Route::post('/siswa/{id}/enroll-cancel', [SiswaController::class, 'cancelEnroll']);
        Route::get('/siswa/{id}/enroll-check', [SiswaController::class, 'enrollCheck']);
        Route::post('/siswa/{id}/delete-uid', [SiswaController::class, 'deleteUid']);

        Route::post('/siswa/{id}/enroll-finger', [SiswaController::class, 'enrollFingerRequest']);
        Route::post('/siswa/{id}/enroll-finger-cancel', [SiswaController::class, 'cancelFingerEnroll']);
        Route::get('/siswa/{id}/enroll-finger-check', [SiswaController::class, 'enrollFingerCheck']);
        Route::post('/siswa/{id}/delete-finger', [SiswaController::class, 'deleteFingerId']);

        // Management
        Route::resource('devices', DeviceController::class)->except(['create', 'show', 'edit']);
        Route::post('/jadwal/update-all', [App\Http\Controllers\JadwalController::class, 'updateAll'])->name('jadwal.update-all');
        Route::resource('jadwal', App\Http\Controllers\JadwalController::class)->except(['create', 'show', 'edit']);
        Route::resource('hari-libur', App\Http\Controllers\HariLiburController::class)->only(['index', 'store', 'destroy']);

        // Setting
        Route::resource('settings', App\Http\Controllers\SettingsController::class);

        // Backup & Restore
        Route::get('/backup', [App\Http\Controllers\SchoolBackupController::class, 'index'])->name('backup.index');
        Route::get('/backup/download', [App\Http\Controllers\SchoolBackupController::class, 'download'])->name('backup.download');
        Route::post('/backup/restore', [App\Http\Controllers\SchoolBackupController::class, 'restore'])->name('backup.restore');

        // Backups
        Route::get('/backups', [App\Http\Controllers\BackupController::class, 'index'])->name('backups.index');
        Route::post('/backups', [App\Http\Controllers\BackupController::class, 'create'])->name('backups.create');
        Route::get('/backups/{filename}', [App\Http\Controllers\BackupController::class, 'download'])->name('backups.download');
        Route::delete('/backups/{filename}', [App\Http\Controllers\BackupController::class, 'delete'])->name('backups.delete');

        // Users
        Route::delete('/users/bulk-destroy', [App\Http\Controllers\UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
        Route::resource('users', App\Http\Controllers\UserController::class)->except(['show']);

        // WhatsApp Logs
        Route::get('/whatsapp-logs', [App\Http\Controllers\WhatsappLogController::class, 'index'])->name('whatsapp-logs.index');

        // API Logs
        Route::get('/api-logs', [App\Http\Controllers\ApiLogController::class, 'index'])->name('api-logs.index');

        // Broadcast WA
        Route::get('/broadcast', [App\Http\Controllers\BroadcastController::class, 'index'])->name('broadcast.index');
        Route::post('/broadcast/send', [App\Http\Controllers\BroadcastController::class, 'send'])->name('broadcast.send');

        // Absence Report
        Route::get('/absence-report', [App\Http\Controllers\AbsenceReportController::class, 'index'])->name('absence-report.index');
        Route::get('/absence-report/export', [App\Http\Controllers\AbsenceReportController::class, 'export'])->name('absence-report.export');

        // WA Groups Proxy
        Route::get('/api/whatsapp/groups', [App\Http\Controllers\Api\WhatsappController::class, 'getGroups'])->name('api.whatsapp.groups');
    });
});


// Route::get('/', function () {
//     return redirect()->route('login');
// });

Route::get('/test-enroll-trigger', function () {
    $guru = \App\Models\Guru::first();
    if ($guru) {
        $guru->update(['enroll_status' => 'requested']);
        return "OK: " . $guru->nama . " requested";
    }
    return "No guru found";
});
