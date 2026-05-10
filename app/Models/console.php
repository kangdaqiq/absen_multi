<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;


use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('db:backup')->dailyAt('23:59');
// Dijalankan setiap menit; waktu eksekusi dikontrol oleh setting 'schedule_process_daily' di DB per sekolah
Schedule::command('absen:process-daily')->everyMinute();

Schedule::command('wa:process')->everyMinute();
Schedule::command('schedule:send-teacher-schedule')->dailyAt('07:30');
Schedule::command('absen:daily-report')->dailyAt('08:15');
