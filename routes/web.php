<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\DeviceController;

// ── License Pages (no auth, no license check — must be first) ────────────
Route::get('/license/invalid', fn () => view('license.invalid'))->name('license.invalid');
Route::post('/license/update-key', function (\Illuminate\Http\Request $request) {
    if (config('app.mode') !== 'self_hosted') abort(403);
    $key = trim($request->input('license_key'));
    if ($key) {
        $path = base_path('.env');
        if (file_exists($path)) {
            $env = file_get_contents($path);
            if (str_contains($env, 'LICENSE_KEY=')) {
                $env = preg_replace('/^LICENSE_KEY=.*$/m', 'LICENSE_KEY=' . $key, $env);
            } else {
                $env .= "\nLICENSE_KEY=" . $key . "\n";
            }
            file_put_contents($path, $env);
        }
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        app(\App\Services\LicenseService::class)->clearCache();
    }
    return redirect('/');
})->name('license.update-key');
Route::get('/license/expired', fn () => view('license.expired', [
    'licenseExpiredAt' => cache()->get('license_expired_at'),
]))->name('license.expired');


Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Super Admin Routes
Route::middleware(['auth', 'self_hosted_guard'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::middleware('role:super_admin')->group(function () {

        Route::get('/dashboard', [App\Http\Controllers\SuperAdmin\DashboardController::class, 'index'])->name('dashboard');

        // Schools Management
        Route::resource('schools', App\Http\Controllers\SuperAdmin\SchoolController::class);
        Route::patch('schools/{school}/toggle-bot', [App\Http\Controllers\SuperAdmin\SchoolController::class, 'toggleBot'])->name('schools.toggle-bot');

        // School Admins Management (nested resource)
        Route::resource('schools.admins', App\Http\Controllers\SuperAdmin\SchoolAdminController::class);
        
        // School Devices Management (nested resource)
        Route::resource('schools.devices', App\Http\Controllers\SuperAdmin\SchoolDeviceController::class)->except(['create', 'show', 'edit']);


        // Announcements
        Route::resource('announcements', App\Http\Controllers\SuperAdmin\AnnouncementController::class)->except(['show']);

        // Packages (Subscriptions)
        Route::resource('packages', App\Http\Controllers\SuperAdmin\PackageController::class)->except(['show']);
        
        // Active Subscriptions per School
        Route::post('schools/{school}/subscriptions/quick-renew', [App\Http\Controllers\SuperAdmin\SchoolSubscriptionController::class, 'quickRenew'])->name('schools.subscriptions.quick-renew');
        Route::post('schools/{school}/subscriptions/{subscription}/confirm', [App\Http\Controllers\SuperAdmin\SchoolSubscriptionController::class, 'confirm'])->name('schools.subscriptions.confirm');
        Route::resource('schools.subscriptions', App\Http\Controllers\SuperAdmin\SchoolSubscriptionController::class)->except(['show']);

        // Licenses (Self-Hosted)
        Route::patch('licenses/{license}/regenerate', [App\Http\Controllers\SuperAdmin\LicenseController::class, 'regenerate'])->name('licenses.regenerate');
        Route::resource('licenses', App\Http\Controllers\SuperAdmin\LicenseController::class)->only(['index', 'store', 'update', 'destroy']);

        // WhatsApp Devices Overview
        Route::get('/whatsapp-devices', [App\Http\Controllers\SuperAdmin\WhatsappDevicesController::class, 'index'])->name('whatsapp-devices.index');
        Route::get('/whatsapp-devices/{schoolId}/status', [App\Http\Controllers\SuperAdmin\WhatsappDevicesController::class, 'status'])->name('whatsapp-devices.status');

        // OTA Update Management
        Route::get('/ota', [App\Http\Controllers\SuperAdmin\OtaController::class, 'index'])->name('ota.index');
        Route::post('/ota/upload', [App\Http\Controllers\SuperAdmin\OtaController::class, 'upload'])->name('ota.upload');
        Route::delete('/ota/{filename}', [App\Http\Controllers\SuperAdmin\OtaController::class, 'destroy'])->name('ota.destroy');
    });
});


// Simulator RFID (Protected programmatically in controller)
Route::get('/simulator/rfid', [App\Http\Controllers\RfidSimulatorController::class, 'index'])->name('simulator.rfid');

Route::middleware('auth')->group(function () {
    // Common Routes (All Authenticated)
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::get('/support', [App\Http\Controllers\SupportController::class, 'index'])->name('support.index');
    Route::get('/live', [App\Http\Controllers\LiveDashboardController::class, 'index'])->name('live.index');
    Route::get('/live/fullscreen', [App\Http\Controllers\LiveDashboardController::class, 'fullscreen'])->name('live.fullscreen');
    Route::get('/live/data', [App\Http\Controllers\LiveDashboardController::class, 'data'])->name('live.data');

    // Admin & Teacher Routes
    Route::middleware('role:admin,teacher')->group(function () {
        // Master Data (Siswa/Guru) - Ideally Teacher is Read Only, but for now we allow access
        Route::post('kelas/{id}/toggle-status', [KelasController::class, 'toggleStatus'])->name('kelas.toggle-status');
        Route::post('kelas/{id}/toggle-report', [KelasController::class, 'toggleReport'])->name('kelas.toggle-report');
        Route::put('kelas/bulk-update', [KelasController::class, 'bulkUpdate'])->name('kelas.bulk-update');
        Route::resource('jurusan', App\Http\Controllers\JurusanController::class)->except(['create', 'show', 'edit']);
        Route::resource('kelas', KelasController::class)->except(['create', 'show', 'edit']);
        Route::resource('guru', GuruController::class)->except(['create', 'show', 'edit']);
        Route::patch('guru/{id}/toggle-bot-access', [GuruController::class, 'toggleBotAccess'])->name('guru.toggle-bot-access');
        Route::delete('siswa/bulk-destroy', [SiswaController::class, 'bulkDestroy'])->name('siswa.bulk-destroy');
        Route::put('siswa/bulk-update-kelas', [SiswaController::class, 'bulkUpdateKelas'])->name('siswa.bulk-update-kelas');
        Route::resource('siswa', SiswaController::class)->except(['create', 'show', 'edit']);
    });

    // Absensi & Rekap Routes (Includes Wali Kelas & Waka Kurikulum)
    Route::middleware('role:admin,teacher,wali_kelas,waka_kurikulum')->group(function () {
        // Absensi
        Route::get('/absensi', [App\Http\Controllers\AttendanceController::class, 'index'])->name('absensi.index');
        Route::get('/absensi/guru', [App\Http\Controllers\TeacherAttendanceReportController::class, 'index'])->name('absensi-guru.index');
        Route::post('/absensi/guru/store', [App\Http\Controllers\TeacherAttendanceReportController::class, 'store'])->name('absensi-guru.store');
        Route::delete('/absensi/guru/destroy', [App\Http\Controllers\TeacherAttendanceReportController::class, 'destroy'])->name('absensi-guru.destroy');
        Route::post('/absensi/update', [App\Http\Controllers\AttendanceController::class, 'update'])->name('absensi.update');
        Route::post('/absensi/bulk-update', [App\Http\Controllers\AttendanceController::class, 'bulkUpdate'])->name('absensi.bulkUpdate');
        Route::delete('/absensi/destroy', [App\Http\Controllers\AttendanceController::class, 'destroy'])->name('absensi.destroy');

        // Rekap Siswa
        Route::get('/rekap', [App\Http\Controllers\RekapController::class, 'index'])->name('rekap.index');
        Route::get('/rekap/export', [App\Http\Controllers\RekapController::class, 'export'])->name('rekap.export');
        Route::get('/rekap/pdf', [App\Http\Controllers\RekapController::class, 'printPdf'])->name('rekap.pdf');
        
        // Rekap Kelas
        Route::get('/rekap-kelas', [App\Http\Controllers\RekapKelasController::class, 'index'])->name('rekap-kelas.index');
        Route::get('/rekap-kelas/export', [App\Http\Controllers\RekapKelasController::class, 'export'])->name('rekap-kelas.export');
        Route::get('/rekap-kelas/pdf', [App\Http\Controllers\RekapKelasController::class, 'printPdf'])->name('rekap-kelas.pdf');

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

        Route::post('/kelas/import', [KelasController::class, 'import'])->name('kelas.import');
        Route::get('/kelas/template', [KelasController::class, 'downloadTemplate'])->name('kelas.template');

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

        // Shifts & Plotting Jadwal Guru
        Route::patch('shifts/{id}/toggle-active', [App\Http\Controllers\ShiftController::class, 'toggleActive'])->name('shifts.toggle-active');
        Route::resource('shifts', App\Http\Controllers\ShiftController::class)->except(['create', 'show', 'edit']);
        Route::get('shifts-mapping', [App\Http\Controllers\GuruShiftMappingController::class, 'index'])->name('shifts.mapping');
        Route::post('shifts-mapping/assign', [App\Http\Controllers\GuruShiftMappingController::class, 'assignGurusToShift'])->name('shifts.mapping.assign');
        Route::post('shifts-mapping/add-guru', [App\Http\Controllers\GuruShiftMappingController::class, 'addGuruToShift'])->name('shifts.mapping.add-guru');
        Route::post('shifts-mapping/add-all', [App\Http\Controllers\GuruShiftMappingController::class, 'addAllGurusToShift'])->name('shifts.mapping.add-all');
        Route::post('shifts-mapping/remove-guru', [App\Http\Controllers\GuruShiftMappingController::class, 'removeGuruFromShift'])->name('shifts.mapping.remove');
        Route::post('shifts-mapping/bulk-unassigned', [App\Http\Controllers\GuruShiftMappingController::class, 'bulkAssignUnassigned'])->name('shifts.mapping.bulk-unassigned');

        // Kegiatan (Absensi Per Kegiatan)
        Route::get('kegiatan/absen', [App\Http\Controllers\KegiatanController::class, 'absen'])->name('kegiatan.absen');
        Route::post('kegiatan/absen/update', [App\Http\Controllers\KegiatanController::class, 'updateAttendance'])->name('kegiatan.absen.update');
        Route::post('kegiatan/absen/bulk', [App\Http\Controllers\KegiatanController::class, 'bulkUpdateAttendance'])->name('kegiatan.absen.bulk');
        Route::get('kegiatan/rekap', [App\Http\Controllers\KegiatanController::class, 'rekap'])->name('kegiatan.rekap');
        Route::get('kegiatan/rekap/export', [App\Http\Controllers\KegiatanController::class, 'exportRekap'])->name('kegiatan.rekap.export');
        Route::get('kegiatan/{id}/attendance', [App\Http\Controllers\KegiatanController::class, 'attendance'])->name('kegiatan.attendance');
        Route::resource('kegiatan', App\Http\Controllers\KegiatanController::class)->except(['create', 'show', 'edit']);


        // Setting
        Route::get('settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');
        Route::get('settings/telegram', [App\Http\Controllers\SettingsController::class, 'telegramIndex'])->name('settings.telegram');
        Route::put('settings/telegram', [App\Http\Controllers\SettingsController::class, 'telegramUpdate'])->name('settings.telegram.update');

        // Subscription / Paket Langganan
        Route::get('subscription', [App\Http\Controllers\SubscriptionController::class, 'index'])->name('subscription.index');
        Route::post('subscription', [App\Http\Controllers\SubscriptionController::class, 'store'])->name('subscription.store');
        Route::get('subscription/status/{subscription}', [App\Http\Controllers\SubscriptionController::class, 'checkStatus'])->name('subscription.status');
        Route::post('subscription/cancel/{subscription}', [App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('subscription.cancel');

        // Backup & Restore
        Route::get('/backup', [App\Http\Controllers\SchoolBackupController::class, 'index'])->name('backup.index');
        Route::get('/backup/download', [App\Http\Controllers\SchoolBackupController::class, 'download'])->name('backup.download');
        Route::post('/backup/restore', [App\Http\Controllers\SchoolBackupController::class, 'restore'])->name('backup.restore');

        // Schedule Broadcast
        Route::get('broadcast/schedule', [App\Http\Controllers\BroadcastController::class, 'createSchedule'])->name('broadcast.schedule.create');
        Route::post('broadcast/schedule', [App\Http\Controllers\BroadcastController::class, 'storeSchedule'])->name('broadcast.schedule.store');
        Route::delete('broadcast/schedule/{id}', [App\Http\Controllers\BroadcastController::class, 'destroySchedule'])->name('broadcast.schedule.destroy');

        // Gate Cards
        Route::resource('gate-cards', App\Http\Controllers\GateCardController::class)->except(['show']);
        Route::get('gate-cards/{gateCard}/request-enroll', [App\Http\Controllers\GateCardController::class, 'requestEnroll'])->name('gate-cards.request-enroll');
        Route::post('/gate-cards/{id}/enroll', [App\Http\Controllers\GateCardController::class, 'enrollRequest']);
        Route::post('/gate-cards/{id}/enroll-cancel', [App\Http\Controllers\GateCardController::class, 'cancelEnroll']);
        Route::get('/gate-cards/{id}/enroll-check', [App\Http\Controllers\GateCardController::class, 'enrollCheck']);
        Route::post('/gate-cards/{id}/delete-uid', [App\Http\Controllers\GateCardController::class, 'deleteUid']);

        Route::post('/gate-cards/{id}/enroll-finger', [App\Http\Controllers\GateCardController::class, 'enrollFingerRequest']);
        Route::post('/gate-cards/{id}/enroll-finger-cancel', [App\Http\Controllers\GateCardController::class, 'cancelFingerEnroll']);
        Route::get('/gate-cards/{id}/enroll-finger-check', [App\Http\Controllers\GateCardController::class, 'enrollFingerCheck']);
        Route::post('/gate-cards/{id}/delete-finger', [App\Http\Controllers\GateCardController::class, 'deleteFingerId']);

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
        Route::post('/whatsapp-logs/clear-pending', [App\Http\Controllers\WhatsappLogController::class, 'clearPending'])->name('whatsapp-logs.clear-pending');
        Route::post('/whatsapp-logs/clear-all', [App\Http\Controllers\WhatsappLogController::class, 'clearAll'])->name('whatsapp-logs.clear-all');

        // Telegram Logs
        Route::get('/telegram-logs', [App\Http\Controllers\TelegramLogController::class, 'index'])->name('telegram-logs.index');

        // API Logs
        Route::get('/api-logs', [App\Http\Controllers\ApiLogController::class, 'index'])->name('api-logs.index');

        // WhatsApp Features (Protected by wa_enabled check)
        Route::middleware([\App\Http\Middleware\CheckWaFeature::class])->group(function () {
            // Broadcast WA
            Route::get('/broadcast', [App\Http\Controllers\BroadcastController::class, 'index'])->name('broadcast.index');
            Route::post('/broadcast/send', [App\Http\Controllers\BroadcastController::class, 'send'])->name('broadcast.send');

            // WA Groups Proxy
            Route::get('/api/whatsapp/groups', [App\Http\Controllers\Api\WhatsappController::class, 'getGroups'])->name('api.whatsapp.groups');

            // WhatsApp Device Management
            Route::get('/whatsapp-device', [App\Http\Controllers\WhatsappDeviceController::class, 'index'])->name('whatsapp.device.index');
            Route::get('/whatsapp-device/status', [App\Http\Controllers\WhatsappDeviceController::class, 'status'])->name('whatsapp.device.status');
            Route::get('/whatsapp-device/check', [App\Http\Controllers\WhatsappDeviceController::class, 'check'])->name('whatsapp.device.check');
            Route::post('/whatsapp-device/login-with-code', [App\Http\Controllers\WhatsappDeviceController::class, 'loginWithCode'])->name('whatsapp.device.login-with-code');
            Route::post('/whatsapp-device/logout', [App\Http\Controllers\WhatsappDeviceController::class, 'logout'])->name('whatsapp.device.logout');
            Route::post('/whatsapp-device/reset', [App\Http\Controllers\WhatsappDeviceController::class, 'reset'])->name('whatsapp.device.reset');
            Route::post('/whatsapp-device/test', [App\Http\Controllers\WhatsappDeviceController::class, 'testMessage'])->name('whatsapp.device.test');
            Route::get('/whatsapp-device/qr-proxy', [App\Http\Controllers\WhatsappDeviceController::class, 'qrProxy'])->name('whatsapp.device.qr-proxy');
        });
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
