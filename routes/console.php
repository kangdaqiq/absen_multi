<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;


use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Helper to get time setting or default
$getTime = function ($key, $default) {
    try {
        return \App\Models\Setting::where('setting_key', $key)->value('setting_value') ?: $default;
    } catch (\Exception $e) {
        return $default;
    }
};

// Run every minute to allow per-school scheduling logic in Commands
Schedule::command('absen:process-daily')->everyMinute()->withoutOverlapping();
Schedule::command('absen:daily-report')->everyMinute()->withoutOverlapping();
Schedule::command('schedule:send-teacher-schedule')->everyMinute()->withoutOverlapping();
Schedule::command('wa:process')->everyMinute()->withoutOverlapping();

// Daily Abnormal Attendance Check
Schedule::command('absen:check-abnormal')->everyMinute()->withoutOverlapping()
    ->when(function () {
        try {
            return \App\Models\Setting::where('setting_key', 'absence_notification_enabled')
                ->value('setting_value') !== 'false';
        } catch (\Exception $e) {
            return true; // Default enabled
        }
    });
