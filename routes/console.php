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
Schedule::command('absen:process-daily')->dailyAt($getTime('schedule_process_daily', '13:30'));

Schedule::command('wa:process')->everyMinute();
Schedule::command('schedule:send-teacher-schedule')->dailyAt($getTime('schedule_send_teacher_schedule', '07:30'));
Schedule::command('absen:daily-report')->dailyAt($getTime('schedule_daily_report', '08:15'));
