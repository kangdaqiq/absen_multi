<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\Siswa;
use App\Models\Guru;

class FcmNotificationService
{
    /**
     * Send push notification to one or multiple device tokens
     * Supports both FCM HTTP v1 (Service Account JSON) and Legacy Server Key.
     *
     * @param string|array $fcmTokens
     * @param string $title
     * @param string $message
     * @param array $data
     * @return bool
     */
    public static function send($fcmTokens, string $title, string $message, array $data = []): bool
    {
        if (empty($fcmTokens)) {
            Log::info("FCM Notification skipped: No FCM device token provided.");
            return false;
        }

        $tokens = is_array($fcmTokens) ? array_filter($fcmTokens) : [$fcmTokens];
        if (empty($tokens)) {
            return false;
        }

        // 1. Try FCM HTTP v1 using Service Account JSON if available
        $serviceAccount = self::getServiceAccount();
        if ($serviceAccount) {
            return self::sendViaHttpV1($tokens, $title, $message, $data, $serviceAccount);
        }

        // 2. Fallback to Legacy FCM Server Key
        $serverKey = env('FCM_SERVER_KEY');
        if (!empty($serverKey)) {
            return self::sendViaLegacy($tokens, $title, $message, $data, $serverKey);
        }

        Log::warning("FCM Notification skipped: Neither FIREBASE_CREDENTIALS JSON nor FCM_SERVER_KEY is configured in .env");
        return false;
    }

    /**
     * Send push notification via modern FCM HTTP v1 API
     */
    private static function sendViaHttpV1(array $tokens, string $title, string $message, array $data, array $serviceAccount): bool
    {
        $accessToken = self::getGoogleAccessToken($serviceAccount);
        if (!$accessToken) {
            Log::error("FCM HTTP v1: Failed to obtain Google OAuth2 access token.");
            return false;
        }

        $projectId = $serviceAccount['project_id'] ?? null;
        if (!$projectId) {
            Log::error("FCM HTTP v1: project_id not found in service account JSON.");
            return false;
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        $successCount = 0;

        // Convert all data values to strings (FCM HTTP v1 requirement)
        $stringData = [];
        foreach (array_merge(['click_action' => 'FLUTTER_NOTIFICATION_CLICK', 'type' => 'attendance_alert'], $data) as $k => $v) {
            $stringData[(string) $k] = (string) $v;
        }

        foreach ($tokens as $token) {
            try {
                $payload = [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $message,
                        ],
                        'data' => $stringData,
                        'android' => [
                            'priority' => 'high',
                            'notification' => [
                                'sound' => 'default',
                                'channel_id' => 'high_importance_channel',
                            ]
                        ]
                    ]
                ];

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])->timeout(10)->post($url, $payload);

                if ($response->successful()) {
                    $successCount++;
                    Log::info("FCM HTTP v1 sent successfully to token: " . substr($token, 0, 15) . "...");
                } else {
                    Log::warning("FCM HTTP v1 error: HTTP " . $response->status() . " - " . $response->body());
                }
            } catch (\Exception $e) {
                Log::error("FCM HTTP v1 Exception: " . $e->getMessage());
            }
        }

        return $successCount > 0;
    }

    /**
     * Send push notification via Legacy FCM API
     */
    private static function sendViaLegacy(array $tokens, string $title, string $message, array $data, string $serverKey): bool
    {
        try {
            $payload = [
                'notification' => [
                    'title' => $title,
                    'body' => $message,
                    'sound' => 'default',
                ],
                'data' => array_merge([
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'type' => 'attendance_alert',
                ], $data),
                'priority' => 'high'
            ];

            if (count($tokens) === 1) {
                $payload['to'] = $tokens[0];
            } else {
                $payload['registration_ids'] = array_values($tokens);
            }

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post('https://fcm.googleapis.com/fcm/send', $payload);

            if ($response->successful()) {
                Log::info("FCM Legacy sent successfully to " . count($tokens) . " device(s): " . $title);
                return true;
            } else {
                Log::warning("FCM Legacy failed: HTTP " . $response->status() . " - " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("FCM Legacy Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Load Google Service Account data from direct env keys or file
     */
    private static function getServiceAccount(): ?array
    {
        // 1. Direct environment variables in .env (FIREBASE_PROJECT_ID, FIREBASE_CLIENT_EMAIL, FIREBASE_PRIVATE_KEY)
        $privateKey = env('FIREBASE_PRIVATE_KEY');
        $clientEmail = env('FIREBASE_CLIENT_EMAIL');
        $projectId = env('FIREBASE_PROJECT_ID');

        if (!empty($privateKey) && !empty($clientEmail) && !empty($projectId)) {
            // Normalize newline formatting if stored with \n in .env
            $normalizedKey = str_replace('\n', "\n", $privateKey);
            return [
                'type' => 'service_account',
                'project_id' => trim($projectId),
                'client_email' => trim($clientEmail),
                'private_key' => $normalizedKey,
            ];
        }

        // 2. Direct JSON string in .env (FIREBASE_SERVICE_ACCOUNT_JSON)
        $jsonString = env('FIREBASE_SERVICE_ACCOUNT_JSON');
        if (!empty($jsonString)) {
            $data = json_decode($jsonString, true);
            if ($data && isset($data['private_key'], $data['client_email'], $data['project_id'])) {
                return $data;
            }
        }

        // 3. File path from .env (FIREBASE_CREDENTIALS)
        $credPath = env('FIREBASE_CREDENTIALS');
        if ($credPath) {
            $fullPath = base_path($credPath);
            if (file_exists($fullPath)) {
                $json = file_get_contents($fullPath);
                return json_decode($json, true);
            }
            if (file_exists($credPath)) {
                $json = file_get_contents($credPath);
                return json_decode($json, true);
            }
        }

        // 4. Default storage path: storage/app/firebase-service-account.json
        $defaultPath = storage_path('app/firebase-service-account.json');
        if (file_exists($defaultPath)) {
            return json_decode(file_get_contents($defaultPath), true);
        }

        return null;
    }

    /**
     * Generate Google OAuth2 Access Token from Service Account private key
     */
    private static function getGoogleAccessToken(array $serviceAccount): ?string
    {
        $cacheKey = 'fcm_google_access_token_' . md5($serviceAccount['client_email'] ?? 'fcm');
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $privateKey = $serviceAccount['private_key'] ?? null;
        $clientEmail = $serviceAccount['client_email'] ?? null;

        if (!$privateKey || !$clientEmail) {
            return null;
        }

        $now = time();
        $jwtHeader = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $jwtClaim = base64_encode(json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ]));

        // Fix base64url encoding
        $jwtHeader = str_replace(['+', '/', '='], ['-', '_', ''], $jwtHeader);
        $jwtClaim = str_replace(['+', '/', '='], ['-', '_', ''], $jwtClaim);

        $toSign = $jwtHeader . '.' . $jwtClaim;
        $signature = '';

        if (!openssl_sign($toSign, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            Log::error("Failed to sign Google JWT with private key.");
            return null;
        }

        $jwtSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        $jwt = $toSign . '.' . $jwtSignature;

        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $accessToken = $data['access_token'] ?? null;
                $expiresIn = (int) ($data['expires_in'] ?? 3600);

                if ($accessToken) {
                    Cache::put($cacheKey, $accessToken, now()->addSeconds($expiresIn - 300));
                    return $accessToken;
                }
            } else {
                Log::error("Google OAuth token exchange failed: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Google OAuth Exception: " . $e->getMessage());
        }

        return null;
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
