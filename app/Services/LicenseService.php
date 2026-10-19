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
     * Files yang dijaga integritasnya.
     * Jika file-file ini dimodifikasi, license dianggap invalid.
     * Update hash ini setiap kali ada perubahan legitimate pada file tersebut
     * dengan menjalankan: php artisan license:rehash
     */
    private const INTEGRITY_FILES = [
        'app/Http/Middleware/CheckLicense.php',
        'app/Http/Middleware/SelfHostedGuard.php',
        'app/Services/LicenseService.php',
    ];

    // Secret salt untuk HMAC signature cache
    // Ubah nilai ini di setiap release baru
    private const CACHE_SALT = 'absen-kangdaqiq-2026-s3cr3t';

    /**
     * Validate license. Returns array:
     * [valid, expired, client_name, expired_at, max_schools, max_students, message, grace_remaining_days]
     */
    public function validate(): array
    {
        // [SECURITY] Deteksi mode dari integrity file, bukan APP_MODE dari .env
        // Mencegah bypass dengan mengubah APP_MODE=hosted
        $isClientRelease = file_exists(storage_path('app/license_integrity.json'));

        if (!$isClientRelease) {
            // Tidak ada integrity file = dev/hosted server → skip license check
            if (config('app.mode', 'hosted') !== 'self_hosted') {
                return $this->ok('Hosted mode — no license required.');
            }
        }

        // Jika bukan client release dan APP_MODE juga bukan self_hosted = hosted server
        if (!$isClientRelease && config('app.mode', 'hosted') !== 'self_hosted') {
            return $this->ok('Hosted mode — no license required.');
        }

        // [SECURITY] Integrity check — pastikan file license tidak dimodifikasi
        if (!$this->integrityCheck()) {
            Log::error('[LicenseService] Integrity check gagal — file license telah dimodifikasi.');
            return $this->failResult('Integritas sistem tidak valid. Hubungi provider.');
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
            $lastResult = $cache['result'] ?? $this->failResult('Tidak dapat menghubungi server lisensi.');
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
                    'valid'                => false,
                    'expired'              => true,
                    'client_name'          => $data['client_name'] ?? '',
                    'expired_at'           => $expiredAt->format('d M Y'),
                    'max_schools'          => $data['max_schools'] ?? 1,
                    'max_students'         => $data['max_students'] ?? 0,
                    'max_teachers'         => $data['max_teachers'] ?? 0,
                    'max_bot_users'        => $data['max_bot_users'] ?? 0,
                    'photo_enabled'        => $data['photo_enabled'] ?? true,
                    'face_enabled'         => $data['face_enabled'] ?? true,
                    'dok_kbm_enabled'      => $data['dok_kbm_enabled'] ?? false,
                    'history_quota_months' => $data['history_quota_months'] ?? null,
                    'message'              => 'Lisensi telah expired pada ' . $expiredAt->format('d M Y') . '. Hubungi provider untuk perpanjangan.',
                    'grace_remaining_days' => 0,
                ];
            }

            return [
                'valid'                => true,
                'expired'              => false,
                'client_name'          => $data['client_name'] ?? '',
                'expired_at'           => $expiredAt?->format('d M Y') ?? 'Selamanya',
                'max_schools'          => $data['max_schools'] ?? 1,
                'max_students'         => $data['max_students'] ?? 0,
                'max_teachers'         => $data['max_teachers'] ?? 0,
                'max_bot_users'        => $data['max_bot_users'] ?? 0,
                'photo_enabled'        => $data['photo_enabled'] ?? true,
                'face_enabled'         => $data['face_enabled'] ?? true,
                'dok_kbm_enabled'      => $data['dok_kbm_enabled'] ?? false,
                'history_quota_months' => $data['history_quota_months'] ?? null,
                'message'              => 'Lisensi aktif.',
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
            'valid'                => true,
            'expired'              => false,
            'client_name'          => 'Hosted',
            'expired_at'           => 'Selamanya',
            'max_schools'          => 0,   // 0 = unlimited
            'max_students'         => 0,   // 0 = unlimited
            'max_teachers'         => 0,   // 0 = unlimited
            'max_bot_users'        => 0,   // 0 = unlimited
            'photo_enabled'        => true,
            'face_enabled'         => true,
            'dok_kbm_enabled'      => true,
            'history_quota_months' => null,
            'message'              => $message,
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
            'valid'                => false,
            'expired'              => false,
            'client_name'          => '',
            'expired_at'           => null,
            'max_schools'          => 0,
            'max_students'         => 0,
            'max_teachers'         => 0,
            'max_bot_users'        => 0,
            'photo_enabled'        => false,
            'face_enabled'         => false,
            'dok_kbm_enabled'      => false,
            'history_quota_months' => null,
            'message'              => $message,
            'grace_remaining_days' => 0,
        ];
    }

    // ── Cache (storage/app/license_cache.json) ─────────────────────────────

    private function cachePath(): string
    {
        return storage_path('app/' . self::CACHE_FILE);
    }

    private function cacheSalt(): string
    {
        // Menggunakan APP_KEY agar konsisten antara PHP CLI (Cron/Terminal) dan Web Server (PHP-FPM)
        return self::CACHE_SALT . (config('app.key') ?: gethostname());
    }

    private function readCache(): ?array
    {
        $path = $this->cachePath();
        if (!file_exists($path)) return null;

        $raw  = @file_get_contents($path);
        if ($raw === false) return null;

        $data = json_decode($raw, true);
        if (!is_array($data)) return null;

        // [SECURITY] Verifikasi HMAC signature — cegah manipulasi cache manual
        $signature = $data['_sig'] ?? null;
        unset($data['_sig']);

        $expected = hash_hmac('sha256', json_encode($data), $this->cacheSalt());
        if (!hash_equals($expected, (string) $signature)) {
            // Fallback: periksa dengan salt legacy (gethostname) jika cache lama masih ada
            $legacyExpected = hash_hmac('sha256', json_encode($data), self::CACHE_SALT . gethostname());
            if (!hash_equals($legacyExpected, (string) $signature)) {
                Log::warning('[LicenseService] Cache signature tidak valid — cache dihapus.');
                @unlink($path);
                return null;
            }
        }

        return $data;
    }

    private function writeCache(array $result, ?Carbon $graceStarted): void
    {
        $data = [
            'cached_at'        => now()->toIso8601String(),
            'grace_started_at' => $graceStarted?->toIso8601String(),
            'result'           => $result,
        ];

        // [SECURITY] Tambahkan HMAC signature untuk anti-tamper
        $data['_sig'] = hash_hmac('sha256', json_encode($data), $this->cacheSalt());

        try {
            $path = $this->cachePath();
            $dir  = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
            @chmod($path, 0666);
        } catch (\Throwable $e) {
            Log::error('[LicenseService] Gagal menulis license cache: ' . $e->getMessage());
        }
    }

    // ── Integrity Check ────────────────────────────────────────────────────

    /**
     * Verifikasi file-file kritis tidak dimodifikasi.
     * Hash disimpan di storage/app/license_integrity.json (dibuat saat build release).
     */
    private function integrityCheck(): bool
    {
        $hashFile = storage_path('app/license_integrity.json');

        if (!file_exists($hashFile)) {
            // Integrity file tidak ada:
            // - Jika APP_MODE=self_hosted → fail (client mungkin hapus file sebagai bypass)
            // - Jika APP_MODE=hosted → skip (mode developer, file memang tidak ada)
            if (config('app.mode') === 'self_hosted') {
                Log::warning('[LicenseService] Integrity file tidak ditemukan dalam mode self_hosted.');
                return false;
            }
            return true; // Dev/hosted mode, tidak perlu integrity check
        }

        $expected = json_decode(file_get_contents($hashFile), true);
        if (!is_array($expected)) return false;

        foreach (self::INTEGRITY_FILES as $relPath) {
            $absPath = base_path($relPath);
            if (!file_exists($absPath)) return false;

            $actualHash = hash_file('sha256', $absPath);
            if (($expected[$relPath] ?? '') !== $actualHash) {
                return false;
            }
        }

        return true;
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
     * Check if app is running in self-hosted client mode
     */
    public function isSelfHosted(): bool
    {
        return file_exists(storage_path('app/license_integrity.json'))
            || config('app.mode', 'hosted') === 'self_hosted';
    }

    /**
     * Check if global teacher quota is available (for self_hosted mode)
     */
    public function hasGlobalTeacherQuota(): bool
    {
        if (!$this->isSelfHosted()) {
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
        if (!$this->isSelfHosted()) {
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

    /**
     * Check if global student quota is available (for self_hosted mode)
     */
    public function hasGlobalStudentQuota(): bool
    {
        if (!$this->isSelfHosted()) {
            return true; // only enforced in self_hosted
        }

        $license = $this->validate();
        $limit = $license['max_students'] ?? 0;

        if ($limit === 0) {
            return true; // unlimited
        }

        $currentCount = \App\Models\Siswa::count();
        return $currentCount < $limit;
    }

    /**
     * Periksa apakah fitur foto siswa aktif.
     * - Pada mode self_hosted: diperiksa dari status lisensi (photo_enabled).
     * - Pada mode hosted (SaaS / Super Admin): diperiksa dari pengaturan sekolah (school->photo_enabled).
     */
    public function isPhotoFeatureEnabled(?\App\Models\School $school = null): bool
    {
        if ($this->isSelfHosted()) {
            $license = $this->validate();
            return isset($license['photo_enabled']) ? (bool) $license['photo_enabled'] : true;
        }

        if (!$school) {
            $user = auth()->user();
            $school = $user?->school;
        }

        if ($school) {
            return (bool) ($school->photo_enabled ?? true);
        }

        return true;
    }

    /**
     * Periksa apakah fitur scanner wajah (Face ID) aktif.
     * - Pada mode self_hosted: diperiksa dari status lisensi (face_enabled).
     * - Pada mode hosted (SaaS / Super Admin): diperiksa dari pengaturan sekolah (school->face_enabled).
     */
    public function isFaceFeatureEnabled(?\App\Models\School $school = null): bool
    {
        if ($this->isSelfHosted()) {
            $license = $this->validate();
            return isset($license['face_enabled']) ? (bool) $license['face_enabled'] : true;
        }

        if (!$school) {
            $user = auth()->user();
            $school = $user?->school;
        }

        if ($school) {
            return (bool) ($school->face_enabled ?? true);
        }

        return true;
    }

    /**
     * Periksa apakah fitur upload foto/video dokumentasi KBM aktif.
     * - Pada mode self_hosted: diperiksa dari status lisensi (dok_kbm_enabled).
     * - Pada mode hosted (SaaS / Super Admin): diperiksa dari pengaturan sekolah (school->dok_kbm_enabled).
     */
    public function isDokKbmFeatureEnabled(?\App\Models\School $school = null): bool
    {
        if ($this->isSelfHosted()) {
            $license = $this->validate();
            if (isset($license['is_active']) && !$license['is_active']) {
                return false;
            }
            return isset($license['dok_kbm_enabled']) ? (bool) $license['dok_kbm_enabled'] : false;
        }

        if (!$school) {
            $user = auth()->user();
            $school = $user?->school;
        }

        if ($school) {
            if (isset($school->is_active) && !$school->is_active) {
                return false;
            }
            return (bool) ($school->dok_kbm_enabled ?? false);
        }

        return false;
    }
}

