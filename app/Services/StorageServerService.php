<?php

namespace App\Services;

use App\Models\School;
use App\Models\License;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StorageServerService
{
    protected string $baseUrl;
    protected string $adminKey;
    protected string $defaultKey;
    protected string $folder;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl    = rtrim(config('services.storage_server.url') ?: 'https://storage.jagattech.my.id', '/');
        $this->adminKey   = config('services.storage_server.admin_key') ?: config('services.storage_server.key') ?: 'android-storage-secret-key';
        $this->defaultKey = config('services.storage_server.key') ?: 'android-storage-secret-key';
        $this->folder     = config('services.storage_server.folder') ?: 'kbm_docs';
        $this->timeout    = 6; // detik
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getAdminKey(): string
    {
        return $this->adminKey;
    }

    public function getFolder(): string
    {
        return $this->folder;
    }

    /**
     * Memeriksa kesehatan & koneksi storage server
     */
    public function getHealth(): ?array
    {
        try {
            $response = Http::timeout($this->timeout)->get("{$this->baseUrl}/api/v1/health");
            if ($response->successful()) {
                return $response->json('data') ?? [];
            }
        } catch (\Throwable $e) {
            Log::warning("[StorageServerService] Health check failed: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Mengambil daftar seluruh pengguna dari storage server (Admin API)
     */
    public function listUsers(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['X-API-Key' => $this->adminKey])
                ->get("{$this->baseUrl}/api/v1/admin/users");

            if ($response->successful()) {
                return $response->json('data') ?? [];
            }

            Log::warning("[StorageServerService] List users failed: HTTP " . $response->status() . " - " . $response->body());
        } catch (\Throwable $e) {
            Log::warning("[StorageServerService] List users exception: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Mengambil informasi kuota untuk API key tertentu
     */
    public function getUserStorage(string $apiKey): ?array
    {
        if (empty($apiKey)) {
            return null;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['X-API-Key' => $apiKey])
                ->get("{$this->baseUrl}/api/v1/user/storage");

            if ($response->successful()) {
                return $response->json('data') ?? null;
            }
        } catch (\Throwable $e) {
            Log::warning("[StorageServerService] getUserStorage exception: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Membuat akun user baru di storage server
     */
    public function createUser(string $username, int $quotaMb, ?string $apiKey = null, string $role = 'user'): array
    {
        try {
            $payload = [
                'username' => $username,
                'quota_mb' => max(0, $quotaMb),
                'role'     => $role,
            ];
            if (!empty($apiKey)) {
                $payload['api_key'] = $apiKey;
            }

            $response = Http::timeout($this->timeout)
                ->withHeaders(['X-API-Key' => $this->adminKey])
                ->post("{$this->baseUrl}/api/v1/admin/users", $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data'    => $response->json('data') ?? [],
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Gagal membuat pengguna di storage server.',
                'status'  => $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error("[StorageServerService] createUser error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Mengubah batas kuota pengguna di storage server (dalam Megabytes)
     */
    public function updateUserQuota(string $storageUserId, int $quotaMb): array
    {
        if (empty($storageUserId)) {
            return ['success' => false, 'message' => 'storage_user_id kosong.'];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['X-API-Key' => $this->adminKey])
                ->put("{$this->baseUrl}/api/v1/admin/users/{$storageUserId}/quota", [
                    'quota_mb' => max(0, $quotaMb),
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data'    => $response->json('data') ?? [],
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Gagal mengubah kuota.',
                'status'  => $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error("[StorageServerService] updateUserQuota error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Menghapus user di storage server
     */
    public function deleteUser(string $storageUserId): array
    {
        if (empty($storageUserId)) {
            return ['success' => false, 'message' => 'storage_user_id kosong.'];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['X-API-Key' => $this->adminKey])
                ->delete("{$this->baseUrl}/api/v1/admin/users/{$storageUserId}");

            return [
                'success' => $response->successful(),
                'message' => $response->json('message') ?? ($response->successful() ? 'OK' : 'Gagal'),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Memicu sinkronisasi kalkulasi ulang pemakaian disk aktual di storage server
     */
    public function recalculateAll(): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['X-API-Key' => $this->adminKey])
                ->post("{$this->baseUrl}/api/v1/admin/users/recalculate");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => $response->json('message') ?? 'Sinkronisasi berhasil.',
                    'data'    => $response->json('data') ?? [],
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Gagal sinkronisasi kuota.',
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Sinkronisasi akun storage untuk satu sekolah / client SaaS
     */
    public function syncSchoolUser(School $school): array
    {
        // Pastikan API key lokal ada
        if (empty($school->storage_api_key)) {
            $school->storage_api_key = 'sk_sch_' . strtolower(Str::random(24));
            $school->saveQuietly();
        }

        $quotaMb = (int) ($school->storage_quota_mb ?? 1000);
        $cleanCode = Str::slug($school->code ?: ('sch_' . $school->id), '_');
        $username = 'sch_' . $cleanCode;

        // Jika sudah punya storage_user_id, coba update kuotanya dulu
        if (!empty($school->storage_user_id)) {
            $updateRes = $this->updateUserQuota($school->storage_user_id, $quotaMb);
            if ($updateRes['success']) {
                return [
                    'success' => true,
                    'action'  => 'updated',
                    'data'    => $updateRes['data'] ?? [],
                ];
            }
            // Jika 404 (user hilang di storage server), kita lanjut create ulang di bawah
        }

        // Buat akun baru di storage server
        $createRes = $this->createUser($username, $quotaMb, $school->storage_api_key, 'user');

        if ($createRes['success']) {
            $userData = $createRes['data']['user'] ?? [];
            if (!empty($userData['id'])) {
                $school->storage_user_id = $userData['id'];
                if (!empty($userData['api_key'])) {
                    $school->storage_api_key = $userData['api_key'];
                }
                $school->saveQuietly();
            }

            return [
                'success' => true,
                'action'  => 'created',
                'data'    => $createRes['data'] ?? [],
            ];
        }

        // Jika username / key sudah terdaftar, coba cari user_id dari daftar users
        $allUsers = $this->listUsers();
        foreach ($allUsers as $u) {
            if (($u['username'] ?? '') === $username || ($u['user_id'] ?? '') === $school->storage_user_id) {
                $school->storage_user_id = $u['user_id'];
                $school->saveQuietly();
                $this->updateUserQuota($u['user_id'], $quotaMb);
                return [
                    'success' => true,
                    'action'  => 'matched_and_updated',
                    'data'    => $u,
                ];
            }
        }

        return [
            'success' => false,
            'message' => $createRes['message'] ?? 'Gagal membuat/menghubungkan akun ke storage server.',
        ];
    }

    /**
     * Sinkronisasi akun storage untuk Client License (Self-hosted)
     */
    public function syncLicenseUser(License $license): array
    {
        if (empty($license->storage_api_key)) {
            $license->storage_api_key = 'sk_lic_' . strtolower(Str::random(24));
            $license->saveQuietly();
        }

        $quotaMb = (int) ($license->storage_quota_mb ?? 1000);
        $cleanName = Str::slug($license->client_name ?: ('client_' . $license->id), '_');
        $username = 'lic_' . $cleanName;

        if (!empty($license->storage_user_id)) {
            $updateRes = $this->updateUserQuota($license->storage_user_id, $quotaMb);
            if ($updateRes['success']) {
                return [
                    'success' => true,
                    'action'  => 'updated',
                    'data'    => $updateRes['data'] ?? [],
                ];
            }
        }

        $createRes = $this->createUser($username, $quotaMb, $license->storage_api_key, 'user');

        if ($createRes['success']) {
            $userData = $createRes['data']['user'] ?? [];
            if (!empty($userData['id'])) {
                $license->storage_user_id = $userData['id'];
                if (!empty($userData['api_key'])) {
                    $license->storage_api_key = $userData['api_key'];
                }
                $license->saveQuietly();
            }

            return [
                'success' => true,
                'action'  => 'created',
                'data'    => $createRes['data'] ?? [],
            ];
        }

        return [
            'success' => false,
            'message' => $createRes['message'] ?? 'Gagal sinkronisasi akun storage lisensi.',
        ];
    }
}
