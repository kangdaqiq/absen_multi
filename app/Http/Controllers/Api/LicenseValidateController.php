<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;

class LicenseValidateController extends Controller
{
    /**
     * Public endpoint for self-hosted clients to validate their license.
     * POST /api/license/validate
     */
    public function validate(Request $request): JsonResponse
    {
        // [SECURITY] Proteksi brute-force license key (maks 30 request / menit per IP)
        $rateLimitKey = 'license-validate:' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 30)) {
            return response()->json([
                'valid'   => false,
                'message' => 'Terlalu banyak permintaan validasi. Silakan coba beberapa saat lagi.',
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        $key      = trim((string) $request->input('license_key', ''));
        $hostname = trim((string) $request->input('hostname', ''));
        $nonce    = trim((string) $request->input('nonce', ''));

        if (empty($key)) {
            return response()->json([
                'valid'   => false,
                'message' => 'license_key diperlukan.',
            ], 400);
        }

        $license = License::where('license_key', $key)->first();

        // [SECURITY PATCH] Jangan pernah membocorkan daftar license key atau debug input pada response!
        if (!$license) {
            return response()->json([
                'valid'   => false,
                'message' => 'Lisensi tidak ditemukan.',
            ], 404);
        }

        if (!$license->is_active) {
            return $this->signedResponse([
                'valid'            => false,
                'expired'          => false,
                'license_key_hash' => hash('sha256', $license->license_key),
                'client_name'      => $license->client_name,
                'issued_at'        => now()->toIso8601String(),
                'nonce'            => $nonce,
                'message'          => 'Lisensi telah dinonaktifkan oleh provider. Hubungi KangDaQiQ.',
            ]);
        }

        // Check hostname lock (hanya jika diisi spesifik oleh Super Admin dan bukan wildcard / localhost)
        $allowedHost = strtolower(trim((string) $license->allowed_hostname));
        if (!empty($allowedHost) && !in_array($allowedHost, ['*', 'localhost', '127.0.0.1'], true)) {
            if (empty($hostname) || strtolower($hostname) !== $allowedHost) {
                return $this->signedResponse([
                    'valid'            => false,
                    'expired'          => false,
                    'license_key_hash' => hash('sha256', $license->license_key),
                    'client_name'      => $license->client_name,
                    'issued_at'        => now()->toIso8601String(),
                    'nonce'            => $nonce,
                    'message'          => 'Hostname server tidak sesuai dengan lisensi terdaftar.',
                ]);
            }
        }

        // Update last ping
        $license->update(['last_ping_at' => now()]);

        // Check expiry
        if ($license->isExpired()) {
            return $this->signedResponse([
                'valid'                => false,
                'expired'              => true,
                'license_key_hash'     => hash('sha256', $license->license_key),
                'client_name'          => $license->client_name,
                'expired_at'           => $license->expired_at->format('Y-m-d'),
                'max_schools'          => (int) $license->max_schools,
                'max_students'         => (int) $license->max_students,
                'max_teachers'         => (int) $license->max_teachers,
                'max_bot_users'        => (int) $license->max_bot_users,
                'photo_enabled'        => (bool) ($license->photo_enabled ?? true),
                'face_enabled'         => (bool) ($license->face_enabled ?? true),
                'dok_kbm_enabled'      => (bool) ($license->dok_kbm_enabled ?? false),
                'storage_quota_mb'     => (int) ($license->storage_quota_mb ?? 1000),
                'storage_api_key'      => '',
                'history_quota_months' => $license->history_quota_months,
                'issued_at'            => now()->toIso8601String(),
                'nonce'                => $nonce,
                'message'              => 'Lisensi telah expired pada ' . $license->expired_at->format('d M Y') . '. Hubungi KangDaQiQ untuk perpanjangan.',
            ]);
        }

        return $this->signedResponse([
            'valid'                => true,
            'expired'              => false,
            'license_key_hash'     => hash('sha256', $license->license_key),
            'client_name'          => $license->client_name,
            'max_schools'          => (int) $license->max_schools,
            'max_students'         => (int) $license->max_students,
            'max_teachers'         => (int) $license->max_teachers,
            'max_bot_users'        => (int) $license->max_bot_users,
            'photo_enabled'        => (bool) ($license->photo_enabled ?? true),
            'face_enabled'         => (bool) ($license->face_enabled ?? true),
            'dok_kbm_enabled'      => (bool) ($license->dok_kbm_enabled ?? false),
            'storage_quota_mb'     => (int) ($license->storage_quota_mb ?? 1000),
            'storage_api_key'      => $license->storage_api_key ?: (string) config('services.storage_server.key', ''),
            'history_quota_months' => $license->history_quota_months,
            'expired_at'           => $license->expired_at?->format('Y-m-d'),
            'issued_at'            => now()->toIso8601String(),
            'nonce'                => $nonce,
            'message'              => 'Lisensi aktif.',
        ]);
    }

    /**
     * Bungkus response dengan Ed25519 detached signature + sertakan field top-level
     * untuk kompatibilitas mundur.
     */
    private function signedResponse(array $payload, int $status = 200): JsonResponse
    {
        $signature = LicenseService::signPayload($payload);

        return response()->json(array_merge($payload, [
            'payload'    => $payload,
            '_signature' => $signature,
        ]), $status);
    }
}
