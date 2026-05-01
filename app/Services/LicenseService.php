<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LicenseService
{
    private const CACHE_FILE       = 'license_cache.json';
    private const GRACE_DAYS       = 3;
    private const CACHE_HOURS      = 24;

    /**
     * Validate license. Returns array:
     * [valid, expired, client_name, expired_at, max_schools, max_students, message, grace_remaining_days]
     */
    public function validate(): array
    {
        // In hosted mode, license always valid
        if (config('app.mode', 'hosted') !== 'self_hosted') {
            return $this->ok('Hosted mode — no license required.');
        }

        $licenseKey = config('app.license_key');
        if (empty($licenseKey)) {
            return $this->fail('LICENSE_KEY tidak dikonfigurasi di .env');
        }

        $cache = $this->readCache();

        // If cache is fresh (< CACHE_HOURS), use it
        if ($cache && isset($cache['cached_at'])) {
            $cachedAt = Carbon::parse($cache['cached_at']);
            if ($cachedAt->diffInHours(now()) < self::CACHE_HOURS) {
                return $cache['result'];
            }
        }

        // Try to fetch from license server
        $result = $this->fetchFromServer($licenseKey);

        if ($result !== null) {
            // Server responded — save cache & reset grace period
            $this->writeCache($result, graceStarted: null);
            return $result;
        }

        // Server unreachable — apply grace period
        $graceStarted = isset($cache['grace_started_at'])
            ? Carbon::parse($cache['grace_started_at'])
            : now();

        $graceEndAt   = $graceStarted->copy()->addDays(self::GRACE_DAYS);
        $remaining    = (int) now()->diffInDays($graceEndAt, false);

        // Save grace period start if not set
        if (!isset($cache['grace_started_at'])) {
            $this->writeCache($cache['result'] ?? $this->failResult('Tidak dapat menghubungi server lisensi.'), graceStarted: $graceStarted);
        }

        if ($remaining > 0) {
            $lastResult = $cache['result'] ?? [];
            $lastResult['message'] = "Server lisensi tidak dapat dihubungi. Grace period: {$remaining} hari tersisa.";
            $lastResult['grace_remaining_days'] = $remaining;
            return $lastResult;
        }

        // Grace period expired
        return $this->failResult('Grace period 3 hari telah habis. Hubungi provider untuk reaktivasi.');
    }

    // ── Private Helpers ────────────────────────────────────────────────────

    private function fetchFromServer(string $licenseKey): ?array
    {
        // LICENSE_SERVER_URL = URL app utama kamu, misal: https://absen.kangdaqiq.com
        // Endpoint validasi: /api/license/validate
        $serverUrl = rtrim(config('app.license_server_url', ''), '/');
        if (empty($serverUrl)) {
            return null;
        }

        try {
            $response = Http::timeout(8)->post("{$serverUrl}/api/license/validate", [
                'license_key' => $licenseKey,
                'hostname'    => gethostname(),
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            if (!isset($data['valid'])) {
                return null;
            }

            if (!$data['valid']) {
                return $this->failResult($data['message'] ?? 'Lisensi tidak valid.');
            }

            // Check expiry
            $expiredAt = isset($data['expired_at']) ? Carbon::parse($data['expired_at']) : null;
            if ($expiredAt && $expiredAt->isPast()) {
                return [
                    'valid'            => false,
                    'expired'          => true,
                    'client_name'      => $data['client_name'] ?? '',
                    'expired_at'       => $expiredAt->format('d M Y'),
                    'max_schools'      => $data['max_schools'] ?? 1,
                    'max_students'     => $data['max_students'] ?? 0,
                    'max_teachers'     => $data['max_teachers'] ?? 0,
                    'max_bot_users'    => $data['max_bot_users'] ?? 0,
                    'message'          => 'Lisensi telah expired pada ' . $expiredAt->format('d M Y') . '. Hubungi provider untuk perpanjangan.',
                    'grace_remaining_days' => 0,
                ];
            }

            return [
                'valid'            => true,
                'expired'          => false,
                'client_name'      => $data['client_name'] ?? '',
                'expired_at'       => $expiredAt?->format('d M Y') ?? 'Selamanya',
                'max_schools'      => $data['max_schools'] ?? 1,
                'max_students'     => $data['max_students'] ?? 0,
                'max_teachers'     => $data['max_teachers'] ?? 0,
                'max_bot_users'    => $data['max_bot_users'] ?? 0,
                'message'          => 'Lisensi aktif.',
                'grace_remaining_days' => 0,
            ];
        } catch (\Exception $e) {
            Log::warning('[LicenseService] Tidak dapat menghubungi license server: ' . $e->getMessage());
            return null;
        }
    }

    private function ok(string $message): array
    {
        return [
            'valid'            => true,
            'expired'          => false,
            'client_name'      => 'Hosted',
            'expired_at'       => 'Selamanya',
            'max_schools'      => 0,   // 0 = unlimited
            'max_students'     => 0,   // 0 = unlimited
            'max_teachers'     => 0,   // 0 = unlimited
            'max_bot_users'    => 0,   // 0 = unlimited
            'message'          => $message,
            'grace_remaining_days' => 0,
        ];
    }

    private function fail(string $message): array
    {
        return $this->failResult($message);
    }

    private function failResult(string $message): array
    {
        return [
            'valid'            => false,
            'expired'          => false,
            'client_name'      => '',
            'expired_at'       => null,
            'max_schools'      => 0,
            'max_students'     => 0,
            'max_teachers'     => 0,
            'max_bot_users'    => 0,
            'message'          => $message,
            'grace_remaining_days' => 0,
        ];
    }

    // ── Cache (storage/app/license_cache.json) ─────────────────────────────

    private function cachePath(): string
    {
        return storage_path('app/' . self::CACHE_FILE);
    }

    private function readCache(): ?array
    {
        $path = $this->cachePath();
        if (!file_exists($path)) return null;

        $data = json_decode(file_get_contents($path), true);
        return is_array($data) ? $data : null;
    }

    private function writeCache(array $result, ?Carbon $graceStarted): void
    {
        $data = [
            'cached_at'        => now()->toIso8601String(),
            'grace_started_at' => $graceStarted?->toIso8601String(),
            'result'           => $result,
        ];
        file_put_contents($this->cachePath(), json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Force clear cache (used by ValidateLicenseCommand)
     */
    public function clearCache(): void
    {
        $path = $this->cachePath();
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Check if global teacher quota is available (for self_hosted mode)
     */
    public function hasGlobalTeacherQuota(): bool
    {
        if (config('app.mode', 'hosted') !== 'self_hosted') {
            return true; // only enforced in self_hosted
        }

        $license = $this->validate();
        $limit = $license['max_teachers'] ?? 0;

        if ($limit === 0) {
            return true; // unlimited
        }

        $currentCount = \App\Models\Guru::count();
        return $currentCount < $limit;
    }

    /**
     * Check if global bot user quota is available (for self_hosted mode)
     */
    public function hasGlobalBotQuota(): bool
    {
        if (config('app.mode', 'hosted') !== 'self_hosted') {
            return true; // only enforced in self_hosted
        }

        $license = $this->validate();
        $limit = $license['max_bot_users'] ?? 0;

        if ($limit === 0) {
            return true; // unlimited
        }

        $currentCount = \App\Models\Guru::where('bot_access', true)->count();
        return $currentCount < $limit;
    }
}
