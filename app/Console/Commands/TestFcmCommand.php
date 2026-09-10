<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FcmNotificationService;
use App\Models\User;
use App\Models\Siswa;

class TestFcmCommand extends Command
{
    protected $signature = 'fcm:test {token?}';
    protected $description = 'Test sending an FCM Push Notification to a device';

    public function handle()
    {
        $token = $this->argument('token');

        $this->info("=== FCM Diagnostic & Test ===");

        // 1. Check Configuration
        $cred = env('FIREBASE_CREDENTIALS');
        $serverKey = env('FCM_SERVER_KEY');
        $projectId = env('FIREBASE_PROJECT_ID');
        $clientEmail = env('FIREBASE_CLIENT_EMAIL');

        $this->line("1. Configuration in .env:");
        if ($projectId && $clientEmail) {
            $this->info("   - Direct .env Keys:     ACTIVE (Project: {$projectId}, Email: {$clientEmail})");
        } else {
            $this->line("   - FIREBASE_CREDENTIALS: " . ($cred ?: '(empty)'));
            $this->line("   - FCM_SERVER_KEY:       " . ($serverKey ? substr($serverKey, 0, 15) . '...' : '(empty)'));
        }

        // 2. Check Database Tokens
        $usersWithTokens = User::whereNotNull('fcm_token')->get();
        $siswaWithTokens = Siswa::whereNotNull('fcm_token')->get();

        $this->line("\n2. Device Tokens in Database:");
        $this->line("   - Users registered with FCM: " . $usersWithTokens->count());
        $this->line("   - Siswa registered with FCM: " . $siswaWithTokens->count());

        if (!$token) {
            if ($usersWithTokens->isNotEmpty()) {
                $token = $usersWithTokens->first()->fcm_token;
                $this->info("   -> Using token from User: " . $usersWithTokens->first()->full_name);
            } elseif ($siswaWithTokens->isNotEmpty()) {
                $token = $siswaWithTokens->first()->fcm_token;
                $this->info("   -> Using token from Siswa: " . $siswaWithTokens->first()->nama);
            }
        }

        if (!$token) {
            $this->error("\n❌ Belum ada fcm_token yang terdaftar di database.");
            $this->comment("Pastikan aplikasi Android telah login dan mengirimkan device token ke POST /api/mobile/fcm-token.");
            $this->comment("Atau jalankan: php artisan fcm:test <FCM_DEVICE_TOKEN_ANDA>");
            return 1;
        }

        $this->info("\n3. Sending Test Push Notification to token: " . substr($token, 0, 25) . "...");
        $res = FcmNotificationService::send(
            $token,
            "🔔 Uji Coba Push Notifikasi - Jagat Absen",
            "Halo! Ini adalah notifikasi uji coba dari sistem absensi Jagat Absen.",
            ['type' => 'test_alert', 'time' => now()->format('H:i:s')]
        );

        if ($res) {
            $this->info("\n✅ Push Notifikasi BERHASIL dikirimkan ke Google Firebase!");
        } else {
            $this->error("\n❌ Gagal mengirimkan push notifikasi.");
            if (FcmNotificationService::$lastError) {
                $this->warn("\nDetail Error dari Google / Server:");
                $this->line(FcmNotificationService::$lastError);
            }
        }

        return 0;
    }
}
