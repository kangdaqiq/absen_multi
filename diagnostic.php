#!/usr/bin/env php
<?php

/**
 * Diagnostic Script for Parent Notification System
 * Run: php diagnostic.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     PARENT NOTIFICATION DIAGNOSTIC SCRIPT                  ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// 1. Check Database Connection
echo "📊 [1/8] Checking Database Connection...\n";
try {
    DB::connection()->getPdo();
    echo "   ✅ Database connected successfully\n\n";
} catch (Exception $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// 2. Check Message Queue Table
echo "📋 [2/8] Checking Message Queue...\n";
try {
    $total = \App\Models\MessageQueue::count();
    $pending = \App\Models\MessageQueue::where('status', 'pending')->count();
    $sent = \App\Models\MessageQueue::where('status', 'sent')->count();
    $failed = \App\Models\MessageQueue::where('status', 'failed')->count();

    echo "   Total messages: $total\n";
    echo "   - Pending: $pending\n";
    echo "   - Sent: $sent\n";
    echo "   - Failed: $failed\n";

    if ($total > 0) {
        echo "\n   Latest 3 messages:\n";
        $messages = \App\Models\MessageQueue::latest()->take(3)->get();
        foreach ($messages as $m) {
            $phone = substr($m->phone_number, 0, 15) . '...';
            echo "   - ID:{$m->id} | Phone:{$phone} | Status:{$m->status} | {$m->created_at}\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

// 3. Check Siswa with Parent Phone
echo "👨‍👩‍👧 [3/8] Checking Students with Parent Phone Numbers...\n";
try {
    $totalWithOrtu = \App\Models\Siswa::whereNotNull('wa_ortu')->where('wa_ortu', '!=', '')->count();
    $totalSiswa = \App\Models\Siswa::count();

    echo "   Total students: $totalSiswa\n";
    echo "   Students with wa_ortu: $totalWithOrtu\n";

    if ($totalWithOrtu > 0) {
        echo "\n   Sample students with parent phone:\n";
        $siswa = \App\Models\Siswa::whereNotNull('wa_ortu')->where('wa_ortu', '!=', '')->take(3)->get();
        foreach ($siswa as $s) {
            echo "   - ID:{$s->id} | {$s->nama} | Siswa:{$s->no_wa} | Ortu:{$s->wa_ortu}\n";
        }
    } else {
        echo "   ⚠️  WARNING: No students have parent phone numbers!\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

// 4. Check Recent Attendance
echo "📅 [4/8] Checking Recent Attendance Records...\n";
try {
    $today = now()->format('Y-m-d');
    $todayAttendance = \App\Models\Attendance::where('tanggal', $today)->count();

    echo "   Today's attendance records: $todayAttendance\n";

    if ($todayAttendance > 0) {
        echo "\n   Latest 3 attendance records:\n";
        $attendance = \App\Models\Attendance::latest()->take(3)->get();
        foreach ($attendance as $a) {
            $student = \App\Models\Siswa::find($a->student_id);
            $nama = $student ? $student->nama : 'Unknown';
            echo "   - {$nama} | Date:{$a->tanggal} | Status:{$a->status} | In:{$a->jam_masuk} | Out:{$a->jam_pulang}\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

// 5. Check API Logs
echo "📝 [5/8] Checking Recent API Activity...\n";
try {
    $recentLogs = \App\Models\ApiLog::latest()->take(5)->get();

    if ($recentLogs->count() > 0) {
        echo "   Latest 5 API logs:\n";
        foreach ($recentLogs as $log) {
            $success = $log->success ? '✅' : '❌';
            $msg = substr($log->message, 0, 40);
            echo "   $success {$log->action} | {$msg} | {$log->created_at}\n";
        }
    } else {
        echo "   ⚠️  No recent API activity\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

// 6. Check WhatsApp API Configuration
echo "🔧 [6/8] Checking WhatsApp API Configuration...\n";
$waUrl = env('WA_API_URL', 'NOT SET');
$waUser = env('WA_API_USER', 'NOT SET');
$waPass = env('WA_API_PASS') ? '***SET***' : 'NOT SET';

echo "   WA_API_URL: $waUrl\n";
echo "   WA_API_USER: $waUser\n";
echo "   WA_API_PASS: $waPass\n";

// Test API connectivity
if ($waUrl !== 'NOT SET') {
    try {
        $ch = curl_init($waUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode > 0) {
            echo "   ✅ WhatsApp API is reachable (HTTP $httpCode)\n";
        } else {
            echo "   ❌ WhatsApp API is not reachable\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Cannot reach WhatsApp API: " . $e->getMessage() . "\n";
    }
}
echo "\n";

// 7. Check Scheduler Status
echo "⏰ [7/8] Checking Scheduler Configuration...\n";
try {
    $lastDailyProcess = \App\Models\Setting::where('setting_key', 'last_daily_process_date')->value('setting_value');
    $lastDailyReport = \App\Models\Setting::where('setting_key', 'last_daily_report_date')->value('setting_value');

    echo "   Last daily process: " . ($lastDailyProcess ?? 'Never') . "\n";
    echo "   Last daily report: " . ($lastDailyReport ?? 'Never') . "\n";
    echo "   Current date: " . now()->format('Y-m-d') . "\n";
    echo "\n   ⚠️  Remember to run: php artisan schedule:work (or setup cron)\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 8. Test Notification Function
echo "🧪 [8/8] Testing Notification Function...\n";
try {
    // Find a student with parent phone
    $testStudent = \App\Models\Siswa::whereNotNull('wa_ortu')
        ->where('wa_ortu', '!=', '')
        ->whereNotNull('no_wa')
        ->where('no_wa', '!=', '')
        ->first();

    if ($testStudent) {
        echo "   Found test student: {$testStudent->nama}\n";
        echo "   Student phone: {$testStudent->no_wa}\n";
        echo "   Parent phone: {$testStudent->wa_ortu}\n";

        // Create test service
        $wa = new \App\Services\WhatsAppService();

        // Count messages before
        $beforeCount = \App\Models\MessageQueue::count();

        // Send test notification
        $wa->sendCheckIn(
            $testStudent->nama,
            $testStudent->no_wa,
            '09:00',
            'H',
            null,
            $testStudent->wa_ortu
        );

        // Count messages after
        $afterCount = \App\Models\MessageQueue::count();
        $newMessages = $afterCount - $beforeCount;

        echo "\n   Messages before: $beforeCount\n";
        echo "   Messages after: $afterCount\n";
        echo "   New messages created: $newMessages\n";

        if ($newMessages == 2) {
            echo "   ✅ SUCCESS! Both student and parent notifications queued!\n";

            // Show the messages
            $latest = \App\Models\MessageQueue::latest()->take(2)->get();
            echo "\n   Created messages:\n";
            foreach ($latest as $msg) {
                $phone = substr($msg->phone_number, 0, 20);
                echo "   - Phone: $phone | Status: {$msg->status}\n";
            }
        } elseif ($newMessages == 1) {
            echo "   ⚠️  WARNING: Only 1 message created (should be 2)\n";
        } else {
            echo "   ❌ FAILED: No messages created!\n";
        }
    } else {
        echo "   ⚠️  No student found with both no_wa and wa_ortu\n";
        echo "   Cannot perform test\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error during test: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n";
    echo "   " . $e->getTraceAsString() . "\n";
}

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║                    DIAGNOSTIC COMPLETE                     ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
