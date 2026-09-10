<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Siswa;
use App\Models\Guru;

class FcmNotificationService
{
    /**
     * Send push notification using legacy FCM HTTP v1 / Server Key API
     *
     * @param string|array $fcmTokens
     * @param string $title
     * @param string $message
     * @param array $data
     * @return bool
     */
    public static function send($fcmTokens, string $title, string $message, array $data = []): bool
    {
        $serverKey = config('services.fcm.server_key') ?: env('FCM_SERVER_KEY');

        if (empty($serverKey)) {
            Log::info("FCM Notification skipped: FCM_SERVER_KEY not configured in .env");
            return false;
        }

        if (empty($fcmTokens)) {
            return false;
        }

        $tokens = is_array($fcmTokens) ? array_filter($fcmTokens) : [$fcmTokens];
        if (empty($tokens)) {
            return false;
        }

        try {
            $payload = [
                'registration_ids' => array_values($tokens),
                'notification' => [
                    'title' => $title,
                    'body' => $message,
                    'sound' => 'default',
                ],
                'data' => array_merge([
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'type' => 'attendance_alert',
                ], $data)
            ];

            // If single token, 'to' can also be used
            if (count($tokens) === 1) {
                $payload['to'] = $tokens[0];
                unset($payload['registration_ids']);
            }

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post('https://fcm.googleapis.com/fcm/send', $payload);

            if ($response->successful()) {
                Log::info("FCM sent successfully to " . count($tokens) . " device(s): " . $title);
                return true;
            } else {
                Log::warning("FCM failed: HTTP " . $response->status() . " - " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("FCM Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send push notification to a specific User
     */
    public static function sendToUser(User $user, string $title, string $message, array $data = []): bool
    {
        if (!empty($user->fcm_token)) {
            return self::send($user->fcm_token, $title, $message, $data);
        }
        return false;
    }

    /**
     * Send push notification to a specific Student (Siswa)
     */
    public static function sendToSiswa(Siswa $siswa, string $title, string $message, array $data = []): bool
    {
        $tokens = [];
        if (!empty($siswa->fcm_token)) {
            $tokens[] = $siswa->fcm_token;
        }
        if ($siswa->user && !empty($siswa->user->fcm_token)) {
            $tokens[] = $siswa->user->fcm_token;
        }

        $tokens = array_unique($tokens);
        if (!empty($tokens)) {
            return self::send($tokens, $title, $message, $data);
        }
        return false;
    }

    /**
     * Send push notification to a specific Teacher (Guru)
     */
    public static function sendToGuru(Guru $guru, string $title, string $message, array $data = []): bool
    {
        if ($guru->user && !empty($guru->user->fcm_token)) {
            return self::send($guru->user->fcm_token, $title, $message, $data);
        }
        return false;
    }
}
