<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LicenseService
{
    private const CACHE_FILE          = 'license_cache.json';
    private const GRACE_DAYS          = 3;
    private const CACHE_HOURS         = 24;
    private const LIFETIME_SYNC_HOURS = 168; // 7 hari sekali silent sync untuk lisensi Lifetime

    /**
     * In-memory cache per HTTP request agar tidak berulang kali membaca disk & verifikasi Ed25519
     * dalam satu siklus request halaman yang sama.
     */
    private ?array $memoizedResult = null;
    private ?string $memoizedKey   = null;

    /**
     * Penanda build khusus client self-hosted.
     * Bernilai false di server utama/dev, dan otomatis diubah menjadi true
     * oleh build_release.php saat membuat paket distribusi client.
     */
    private const IS_CLIENT_BUILD = false;

    /**
     * Ed25519 Public Key (Base64-encoded, 32 bytes).
     * Digunakan oleh client untuk memverifikasi signature kriptografi dari:
     * 1. Response API server lisensi (/api/license/validate)
     * 2. File cache lisensi lokal (storage/app/license_cache.json)
     * 3. File integritas sistem (storage/app/license_integrity.json)
     *
     * Di-generate melalui: php artisan license:keygen
     */
    public const PUBLIC_KEY = '0yqRWOx92OA51q2do1agtfX/w3D8vV3mQ1E4M+5Mnmg=';

    /**
     * Files yang dijaga integritasnya.
     * Jika file-file ini dimodifikasi, license dianggap invalid.
     * Update hash & Ed25519 signature setiap kali ada perubahan legitimate
     * dengan menjalankan: php artisan license:rehash
     */
    public const INTEGRITY_FILES = [
        'app/Http/Middleware/CheckLicense.php',
        'app/Http/Middleware/SelfHostedGuard.php',
        'app/Services/LicenseService.php',
    ];

    /**
     * Validate license. Returns array:
     * [valid, expired, client_name, expired_at, max_schools, max_students, max_teachers, max_bot_users,
     *  photo_enabled, face_enabled, dok_kbm_enabled, storage_quota_mb, storage_api_key,
     *  history_quota_months, message, grace_remaining_days]
     */
    public function validate(): array
    {
        if (!$this->isSelfHosted()) {
            return $this->ok('Hosted mode — no license required.');
        }

        // [SECURITY] Integrity check + Ed25519 signature verification
        if (!$this->integrityCheck()) {
            Log::error('[LicenseService] Integrity check gagal — file lisensi dimodifikasi atau signature tidak valid.');
            return $this->failResult('Integritas sistem tidak valid atau telah dimodifikasi. Hubungi provider.');
        }

        $licenseKey = trim((string) config('app.license_key'));
        if (empty($licenseKey)) {
            return $this->fail('LICENSE_KEY tidak dikonfigurasi di .env');
        }

        // Gunakan hasil in-memory jika sudah divalidasi pada request yang sama
        if ($this->memoizedResult !== null && $this->memoizedKey === $licenseKey) {
            return $this->memoizedResult;
        }

        $cache = $this->readCache($licenseKey);

        // Deteksi apakah cache yang sudah terverifikasi Ed25519 ini adalah Lisensi Lifetime (sekali beli / tanpa expired_at)
        $isLifetimeCache = $cache
            && !empty($cache['signed_payload']['valid'])
            && empty($cache['signed_payload']['expired_at'])
            && !empty($cache['result']['valid']);

        $syncIntervalHours = $isLifetimeCache ? self::LIFETIME_SYNC_HOURS : self::CACHE_HOURS;

        // Jika cache masih fresh (< syncIntervalHours), langsung gunakan hasil cache yang sudah terverifikasi Ed25519
        if ($cache && isset($cache['cached_at'], $cache['result'])) {
            try {
                $cachedAt = Carbon::parse($cache['cached_at']);
                // Pastikan cached_at tidak dimanipulasi ke masa depan
                if ($cachedAt->lte(now()->addMinutes(5)) && $cachedAt->diffInHours(now()) < $syncIntervalHours) {
                    $this->memoizedKey    = $licenseKey;
                    $this->memoizedResult = $cache['result'];
                    return $cache['result'];
                }
            } catch (\Throwable) {
                // Abaikan cache jika format tanggal rusak
            }
        }

        // Coba sinkronisasi ke server lisensi pusat (silent check jika sudah punya cache)
        $serverResponse = $this->fetchFromServer($licenseKey);

        if ($serverResponse !== null) {
            if (!empty($serverResponse['signed_payload']) && !empty($serverResponse['server_signature'])) {
                $this->writeCache(
                    signedPayload: $serverResponse['signed_payload'],
                    serverSignature: $serverResponse['server_signature'],
                    graceStarted: null
                );
            } else {
                // Jika lisensi ditolak/dinonaktifkan di server pusat, bersihkan cache lokal
                $this->clearCache();
            }

            $this->memoizedKey    = $licenseKey;
            $this->memoizedResult = $serverResponse['result'];
            return $serverResponse['result'];
        }

        // ── Server tidak dapat dihubungi (PC Client Offline / Tanpa Internet) ──
        if ($cache && !empty($cache['signed_payload']) && !empty($cache['server_signature'])) {
            // [MODE PERMANENT LIFETIME ACTIVATION]
            // Jika lisensi adalah Lifetime (expired_at kosong) dan signature Ed25519 valid:
            // Bebas kunci selamanya saat offline! Perbarui cached_at agar request berikutnya
            // tidak mencoba ping server terus-menerus saat PC lokal tidak ada internet.
            if ($isLifetimeCache) {
                $this->writeCache(
                    signedPayload: $cache['signed_payload'],
                    serverSignature: $cache['server_signature'],
                    graceStarted: null
                );

                $lifetimeResult = $cache['result'];
                $lifetimeResult['message']              = 'Lisensi Lifetime aktif (Mode Offline).';
                $lifetimeResult['grace_remaining_days'] = 0;

                $this->memoizedKey    = $licenseKey;
                $this->memoizedResult = $lifetimeResult;
                return $lifetimeResult;
            }

            // Untuk lisensi berjangka (non-lifetime / kontrak tahunan): terapkan grace period
            $graceStarted = isset($cache['grace_started_at'])
                ? Carbon::parse($cache['grace_started_at'])
                : now();

            $graceEndAt = $graceStarted->copy()->addDays(self::GRACE_DAYS);
            $remaining  = (int) now()->diffInDays($graceEndAt, false);

            // Perbarui cached_at & grace_started_at agar tidak timeout di setiap klik selama offline
            $this->writeCache(
                signedPayload: $cache['signed_payload'],
                serverSignature: $cache['server_signature'],
                graceStarted: $graceStarted
            );

            if ($remaining > 0) {
                $lastResult = $cache['result'];
                $lastResult['message']              = "Server lisensi tidak dapat dihubungi. Grace period: {$remaining} hari tersisa.";
                $lastResult['grace_remaining_days'] = $remaining;

                $this->memoizedKey    = $licenseKey;
                $this->memoizedResult = $lastResult;
                return $lastResult;
            }

            return $this->failResult('Grace period 3 hari telah habis. Hubungi provider untuk reaktivasi.');
        }

        return $this->failResult('Tidak dapat menghubungi server lisensi dan belum ada data aktivasi lokal yang valid.');
    }

    // ── Ed25519 Cryptographic Helpers ──────────────────────────────────────

    /**
     * Buat JSON kanonik (key terurut secara rekursif) agar hasil serialisasi
     * identik 100% di sisi server maupun client saat proses sign & verify.
     */
    public static function canonicalJson(array $data): string
    {
        self::recursiveKsort($data);
        return (string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private static function recursiveKsort(array &$array): void
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                self::recursiveKsort($value);
            }
        }
    }

    /**
     * Tanda tangani payload array menggunakan Ed25519 Private Key (hanya di server utama).
     */
    public static function signPayload(array $payload, ?string $privateKeyBase64 = null): ?string
    {
        $privKeyB64 = $privateKeyBase64 ?: (string) config('app.license_private_key');
        if (empty($privKeyB64)) {
            return null;
        }

        $secretKey = base64_decode($privKeyB64, true);
        if ($secretKey === false || strlen($secretKey) !== \ParagonIE_Sodium_Compat::CRYPTO_SIGN_SECRETKEYBYTES) {
            Log::error('[LicenseService] Format LICENSE_PRIVATE_KEY tidak valid (harus 64-byte Ed25519 secret key dalam Base64).');
            return null;
        }

        $message   = self::canonicalJson($payload);
        $signature = \ParagonIE_Sodium_Compat::crypto_sign_detached($message, $secretKey);

        return base64_encode($signature);
    }

    /**
     * Verifikasi signature Ed25519 dari payload menggunakan Public Key.
     */
    public static function verifyPayload(array $payload, string $signatureBase64, ?string $publicKeyBase64 = null): bool
    {
        $pubKeyB64 = $publicKeyBase64 ?: (self::PUBLIC_KEY ?: (string) config('app.license_public_key'));
        if (empty($pubKeyB64) || empty($signatureBase64)) {
            return false;
        }

        $publicKey = base64_decode($pubKeyB64, true);
        $signature = base64_decode($signatureBase64, true);

        if (
            $publicKey === false ||
            $signature === false ||
            strlen($publicKey) !== \ParagonIE_Sodium_Compat::CRYPTO_SIGN_PUBLICKEYBYTES ||
            strlen($signature) !== \ParagonIE_Sodium_Compat::CRYPTO_SIGN_BYTES
        ) {
            return false;
        }

        try {
            $message = self::canonicalJson($payload);
            return \ParagonIE_Sodium_Compat::crypto_sign_verify_detached($signature, $message, $publicKey);
        } catch (\Throwable $e) {
            Log::warning('[LicenseService] Verifikasi Ed25519 gagal: ' . $e->getMessage());
            return false;
        }
    }

    // ── Server Communication ───────────────────────────────────────────────

    private function fetchFromServer(string $licenseKey): ?array
    {
        $serverUrl = rtrim((string) config('app.license_server_url', ''), '/');
        if (empty($serverUrl)) {
            return null;
        }

        try {
            $nonce = bin2hex(random_bytes(16));

            $response = Http::connectTimeout(3)->timeout(5)->post("{$serverUrl}/api/license/validate", [
                'license_key' => $licenseKey,
                'hostname'    => gethostname() ?: 'localhost',
                'app_url'     => config('app.url', 'http://localhost'),
                'nonce'       => $nonce,
            ]);

            if ($response->status() >= 500) {
                return null;
            }

            $data = $response->json();
            if (!is_array($data)) {
                return null;
            }

            // Server mengirimkan signed payload (format Ed25519 baru)
            if (isset($data['payload']) && is_array($data['payload'])) {
                $payload   = $data['payload'];
                $signature = (string) ($data['_signature'] ?? '');

                if (empty($signature)) {
                    Log::error('[LicenseService] Server lisensi pusat merespons tanpa _signature (LICENSE_PRIVATE_KEY belum dipasang di .env server pusat).');
                    return [
                        'result'           => $this->failResult('Server lisensi pusat belum mengaktifkan LICENSE_PRIVATE_KEY (signature kosong).'),
                        'signed_payload'   => null,
                        'server_signature' => null,
                    ];
                }

                // 1. Verifikasi Ed25519 Signature dengan PUBLIC_KEY
                if (!self::verifyPayload($payload, $signature)) {
                    Log::error('[LicenseService] Signature Ed25519 dari server lisensi TIDAK VALID! Pastikan PUBLIC_KEY di client sama dengan pasangan LICENSE_PRIVATE_KEY di server pusat.');
                    return [
                        'result'           => $this->failResult('Signature kriptografi server lisensi tidak cocok dengan PUBLIC_KEY client.'),
                        'signed_payload'   => null,
                        'server_signature' => null,
                    ];
                }

                // 2. Verifikasi Nonce (cegah replay response lama)
                if (($payload['nonce'] ?? '') !== $nonce) {
                    Log::error('[LicenseService] Nonce response lisensi tidak cocok (replay attack terdeteksi).');
                    return [
                        'result'           => $this->failResult('Respon keamanan server lisensi tidak sinkron (nonce mismatch).'),
                        'signed_payload'   => null,
                        'server_signature' => null,
                    ];
                }

                // 3. Verifikasi ikatan LICENSE_KEY
                $expectedKeyHash = hash('sha256', $licenseKey);
                if (!hash_equals($expectedKeyHash, (string) ($payload['license_key_hash'] ?? ''))) {
                    return [
                        'result'           => $this->failResult('Token lisensi tidak sesuai dengan LICENSE_KEY aktif.'),
                        'signed_payload'   => null,
                        'server_signature' => null,
                    ];
                }

                $result = $this->payloadToResult($payload);

                return [
                    'result'           => $result,
                    'signed_payload'   => $result['valid'] || $result['expired'] ? $payload : null,
                    'server_signature' => $result['valid'] || $result['expired'] ? $signature : null,
                ];
            }

            // Jika server mengembalikan error tanpa payload (misal 404/400/429)
            if (isset($data['valid']) && $data['valid'] === false) {
                return [
                    'result'           => $this->failResult($data['message'] ?? 'Lisensi tidak valid.'),
                    'signed_payload'   => null,
                    'server_signature' => null,
                ];
            }

            // Jika server pusat merespons valid=true tapi belum ada field 'payload' (berarti server pusat belum git pull kode Ed25519)
            if (isset($data['valid']) && $data['valid'] === true) {
                return [
                    'result'           => $this->failResult('Server lisensi pusat (' . $serverUrl . ') belum diperbarui ke versi Ed25519.'),
                    'signed_payload'   => null,
                    'server_signature' => null,
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('[LicenseService] Tidak dapat menghubungi license server: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Konversi payload yang sudah diverifikasi Ed25519 menjadi format array status lisensi aplikasi.
     */
    private function payloadToResult(array $payload): array
    {
        if (empty($payload['valid']) && empty($payload['expired'])) {
            return $this->failResult($payload['message'] ?? 'Lisensi tidak valid.');
        }

        $expiredAt = !empty($payload['expired_at']) ? Carbon::parse($payload['expired_at'])->endOfDay() : null;
        $isExpired = !empty($payload['expired']) || ($expiredAt && $expiredAt->isPast());

        if ($isExpired) {
            $expFormatted = $expiredAt ? $expiredAt->format('d M Y') : '-';
            return [
                'valid'                => false,
                'expired'              => true,
                'client_name'          => $payload['client_name'] ?? '',
                'expired_at'           => $expFormatted,
                'max_schools'          => (int) ($payload['max_schools'] ?? 1),
                'max_students'         => (int) ($payload['max_students'] ?? 0),
                'max_teachers'         => (int) ($payload['max_teachers'] ?? 0),
                'max_bot_users'        => (int) ($payload['max_bot_users'] ?? 0),
                'photo_enabled'        => (bool) ($payload['photo_enabled'] ?? true),
                'face_enabled'         => (bool) ($payload['face_enabled'] ?? true),
                'dok_kbm_enabled'      => (bool) ($payload['dok_kbm_enabled'] ?? false),
                'storage_quota_mb'     => (int) ($payload['storage_quota_mb'] ?? 1000),
                'storage_api_key'      => $payload['storage_api_key'] ?? '',
                'history_quota_months' => $payload['history_quota_months'] ?? null,
                'message'              => 'Lisensi telah expired pada ' . $expFormatted . '. Hubungi provider untuk perpanjangan.',
                'grace_remaining_days' => 0,
            ];
        }

        return [
            'valid'                => true,
            'expired'              => false,
            'client_name'          => $payload['client_name'] ?? '',
            'expired_at'           => $expiredAt ? $expiredAt->format('d M Y') : 'Selamanya',
            'max_schools'          => (int) ($payload['max_schools'] ?? 1),
            'max_students'         => (int) ($payload['max_students'] ?? 0),
            'max_teachers'         => (int) ($payload['max_teachers'] ?? 0),
            'max_bot_users'        => (int) ($payload['max_bot_users'] ?? 0),
            'photo_enabled'        => (bool) ($payload['photo_enabled'] ?? true),
            'face_enabled'         => (bool) ($payload['face_enabled'] ?? true),
            'dok_kbm_enabled'      => (bool) ($payload['dok_kbm_enabled'] ?? false),
            'storage_quota_mb'     => (int) ($payload['storage_quota_mb'] ?? 1000),
            'storage_api_key'      => $payload['storage_api_key'] ?? '',
            'history_quota_months' => $payload['history_quota_months'] ?? null,
            'message'              => $payload['message'] ?? 'Lisensi aktif.',
            'grace_remaining_days' => 0,
        ];
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
            'storage_quota_mb'     => 0,
            'storage_api_key'      => '',
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
            'storage_quota_mb'     => 0,
            'storage_api_key'      => '',
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

    private function envelopeKey(): string
    {
        return hash('sha256', self::PUBLIC_KEY . '|' . (config('app.key') ?: 'default-app-key'));
    }

    private function readCache(string $licenseKey): ?array
    {
        $path = $this->cachePath();
        if (!file_exists($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return null;
        }

        // 1. Verifikasi HMAC envelope lokal (melindungi cached_at & grace_started_at)
        $envelopeSig = (string) ($data['_envelope_sig'] ?? '');
        unset($data['_envelope_sig']);

        $expectedEnvelope = hash_hmac('sha256', self::canonicalJson($data), $this->envelopeKey());
        if (!hash_equals($expectedEnvelope, $envelopeSig)) {
            Log::warning('[LicenseService] Cache envelope signature tidak valid — cache dihapus.');
            @unlink($path);
            return null;
        }

        // 2. Verifikasi Ed25519 Signature dari Server Pusat pada signed_payload
        $signedPayload   = $data['signed_payload'] ?? null;
        $serverSignature = (string) ($data['server_signature'] ?? '');

        if (!is_array($signedPayload) || !self::verifyPayload($signedPayload, $serverSignature)) {
            Log::warning('[LicenseService] Ed25519 signature pada cache lisensi tidak valid — cache dihapus.');
            @unlink($path);
            return null;
        }

        // 3. Pastikan cache ini memang milik LICENSE_KEY yang sedang dikonfigurasi di .env
        $expectedKeyHash = hash('sha256', $licenseKey);
        if (!hash_equals($expectedKeyHash, (string) ($signedPayload['license_key_hash'] ?? ''))) {
            Log::warning('[LicenseService] Cache lisensi bukan milik LICENSE_KEY saat ini — cache dihapus.');
            @unlink($path);
            return null;
        }

        $data['result'] = $this->payloadToResult($signedPayload);

        return $data;
    }

    private function writeCache(array $signedPayload, string $serverSignature, ?Carbon $graceStarted): void
    {
        $data = [
            'cached_at'        => now()->toIso8601String(),
            'grace_started_at' => $graceStarted?->toIso8601String(),
            'signed_payload'   => $signedPayload,
            'server_signature' => $serverSignature,
        ];

        $data['_envelope_sig'] = hash_hmac('sha256', self::canonicalJson($data), $this->envelopeKey());

        try {
            $path = $this->cachePath();
            $dir  = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            @chmod($path, 0666);
        } catch (\Throwable $e) {
            Log::error('[LicenseService] Gagal menulis license cache: ' . $e->getMessage());
        }
    }

    // ── Integrity Check (SHA-256 + Ed25519 Signature) ──────────────────────

    /**
     * Verifikasi file-file kritis tidak dimodifikasi DAN file license_integrity.json
     * memiliki signature Ed25519 yang sah dari Private Key server utama.
     */
    private function integrityCheck(): bool
    {
        $hashFile = storage_path('app/license_integrity.json');

        if (!file_exists($hashFile)) {
            if (self::IS_CLIENT_BUILD || config('app.mode') === 'self_hosted') {
                Log::warning('[LicenseService] Integrity file tidak ditemukan pada instalasi self-hosted.');
                return false;
            }
            return true;
        }

        $raw  = @file_get_contents($hashFile);
        $data = $raw ? json_decode($raw, true) : null;
        if (!is_array($data)) {
            return false;
        }

        $filesMap  = $data['files'] ?? null;
        $signature = (string) ($data['_signature'] ?? '');

        if (!is_array($filesMap) || empty($signature)) {
            Log::warning('[LicenseService] Format license_integrity.json tidak memiliki signature Ed25519.');
            return false;
        }

        // 1. Verifikasi Ed25519 signature pada daftar hash file
        if (!self::verifyPayload($filesMap, $signature)) {
            Log::error('[LicenseService] Ed25519 signature pada license_integrity.json TIDAK VALID!');
            return false;
        }

        // 2. Verifikasi SHA-256 aktual setiap file kritis di disk
        foreach (self::INTEGRITY_FILES as $relPath) {
            $absPath = base_path($relPath);
            if (!file_exists($absPath)) {
                return false;
            }

            $actualHash = hash_file('sha256', $absPath);
            if (!hash_equals((string) ($filesMap[$relPath] ?? ''), $actualHash)) {
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
        $this->memoizedResult = null;
        $this->memoizedKey    = null;

        $path = $this->cachePath();
        if (file_exists($path)) {
            @unlink($path);
        }
    }

    /**
     * Check if app is running in self-hosted client mode.
     *
     * - Jika IS_CLIENT_BUILD = true (hasil build_release.php) → SELALU self-hosted (anti-bypass .env).
     * - Jika APP_MODE = self_hosted → self-hosted.
     * - Jika ada license_integrity.json DAN server ini TIDAK memegang LICENSE_PRIVATE_KEY yang sah →
     *   berarti ini instalasi client yang mencoba mengubah APP_MODE=hosted di .env → tetap paksa self-hosted!
     */
    public function isSelfHosted(): bool
    {
        if (self::IS_CLIENT_BUILD || config('app.mode', 'hosted') === 'self_hosted') {
            return true;
        }

        if (file_exists(storage_path('app/license_integrity.json')) && !$this->isMainLicenseServer()) {
            return true;
        }

        return false;
    }

    /**
     * Periksa apakah instance ini adalah Server Lisensi Utama (memegang Ed25519 Private Key yang cocok dengan PUBLIC_KEY).
     */
    private function isMainLicenseServer(): bool
    {
        $privKeyB64 = (string) config('app.license_private_key');
        if (empty($privKeyB64) || empty(self::PUBLIC_KEY)) {
            return false;
        }

        $secretKey = base64_decode($privKeyB64, true);
        if ($secretKey === false || strlen($secretKey) !== \ParagonIE_Sodium_Compat::CRYPTO_SIGN_SECRETKEYBYTES) {
            return false;
        }

        try {
            $derivedPub = \ParagonIE_Sodium_Compat::crypto_sign_publickey_from_secretkey($secretKey);
            return hash_equals(self::PUBLIC_KEY, base64_encode($derivedPub));
        } catch (\Throwable) {
            return false;
        }
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
