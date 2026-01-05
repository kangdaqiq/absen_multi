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

Schedule::command('db:backup')->dailyAt($getTime('schedule_backup_db', '23:59'));
Schedule::command('absen:process-daily')
    ->dailyAt($getTime('schedule_process_daily', '13:30'))
    ->days([1, 2, 3, 4, 5, 6]); // Mon-Sat

Schedule::command('wa:process')->everyMinute();
Schedule::command('schedule:send-teacher-schedule')->dailyAt($getTime('schedule_send_teacher_schedule', '07:30'));
Schedule::command('absen:daily-report')
    ->dailyAt($getTime('schedule_daily_report', '08:15'))
    ->days([1, 2, 3, 4, 5, 6]); // Mon-Sat

// Weekly Absence Summary - Every Monday at 08:00
Schedule::command('absen:weekly-absence-summary')
    ->weeklyOn(1, '08:00')
    ->when(function () {
        try {
            return \App\Models\Setting::where('setting_key', 'absence_notification_enabled')
                ->value('setting_value') !== 'false';
        } catch (\Exception $e) {
            return true; // Default enabled
        }
    });
