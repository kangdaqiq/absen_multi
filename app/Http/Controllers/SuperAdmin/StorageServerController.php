<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\License;
use App\Services\StorageServerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StorageServerController extends Controller
{
    protected StorageServerService $storageService;

    public function __construct(StorageServerService $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * Tampilan utama Manajemen Storage Server & Kuota Client di Super Admin
     */
    public function index(Request $request)
    {
        $health = $this->storageService->getHealth();
        $isOnline = !empty($health);

        // Ambil data users langsung dari Storage Server API jika online
        $storageUsers = $isOnline ? $this->storageService->listUsers() : [];

        // Buat map user storage berdasarkan user_id dan username untuk lookup cepat
        $userMapById = [];
        $userMapByUsername = [];
        foreach ($storageUsers as $u) {
            if (!empty($u['user_id'])) {
                $userMapById[$u['user_id']] = $u;
            }
            if (!empty($u['username'])) {
                $userMapByUsername[$u['username']] = $u;
            }
        }

        // Ambil seluruh Sekolah (Client SaaS)
        $schools = School::withCount(['siswa', 'guru'])
            ->orderBy('name')
            ->get()
            ->map(function ($school) use ($userMapById, $userMapByUsername) {
                $storageInfo = null;
                if (!empty($school->storage_user_id) && isset($userMapById[$school->storage_user_id])) {
                    $storageInfo = $userMapById[$school->storage_user_id];
                } else {
                    $cleanCode = \Illuminate\Support\Str::slug($school->code ?: ('sch_' . $school->id), '_');
                    $expectedUsername = 'sch_' . $cleanCode;
                    if (isset($userMapByUsername[$expectedUsername])) {
                        $storageInfo = $userMapByUsername[$expectedUsername];
                    }
                }

                $usedBytes = $storageInfo['used_bytes'] ?? 0;
                $quotaBytes = ($school->storage_quota_mb > 0) ? ($school->storage_quota_mb * 1024 * 1024) : 0;
                $percent = ($quotaBytes > 0) ? min(100, round(($usedBytes / $quotaBytes) * 100, 1)) : 0;

                $school->storage_info = $storageInfo;
                $school->used_bytes = $usedBytes;
                $school->used_human = $storageInfo['used_human'] ?? $this->formatBytes($usedBytes);
                $school->usage_percentage = $percent;
                $school->total_files = $storageInfo['total_files'] ?? 0;

                return $school;
            });

        // Ambil seluruh Lisensi (Client Self-Hosted)
        $licenses = License::orderBy('client_name')
            ->get()
            ->map(function ($license) use ($userMapById, $userMapByUsername) {
                $storageInfo = null;
                if (!empty($license->storage_user_id) && isset($userMapById[$license->storage_user_id])) {
                    $storageInfo = $userMapById[$license->storage_user_id];
                } else {
                    $cleanName = \Illuminate\Support\Str::slug($license->client_name ?: ('client_' . $license->id), '_');
                    $expectedUsername = 'lic_' . $cleanName;
                    if (isset($userMapByUsername[$expectedUsername])) {
                        $storageInfo = $userMapByUsername[$expectedUsername];
                    }
                }

                $usedBytes = $storageInfo['used_bytes'] ?? 0;
                $quotaBytes = ($license->storage_quota_mb > 0) ? ($license->storage_quota_mb * 1024 * 1024) : 0;
                $percent = ($quotaBytes > 0) ? min(100, round(($usedBytes / $quotaBytes) * 100, 1)) : 0;

                $license->storage_info = $storageInfo;
                $license->used_bytes = $usedBytes;
                $license->used_human = $storageInfo['used_human'] ?? $this->formatBytes($usedBytes);
                $license->usage_percentage = $percent;
                $license->total_files = $storageInfo['total_files'] ?? 0;

                return $license;
            });

        // Hitung metrik agregat
        $totalUsedBytes = 0;
        $totalQuotaBytes = 0;
        $totalFiles = 0;

        foreach ($schools as $s) {
            $totalUsedBytes += $s->used_bytes;
            $totalFiles += $s->total_files;
            if ($s->storage_quota_mb > 0) {
                $totalQuotaBytes += ($s->storage_quota_mb * 1024 * 1024);
            }
        }

        foreach ($licenses as $l) {
            $totalUsedBytes += $l->used_bytes;
            $totalFiles += $l->total_files;
            if ($l->storage_quota_mb > 0) {
                $totalQuotaBytes += ($l->storage_quota_mb * 1024 * 1024);
            }
        }

        $globalUsagePercent = ($totalQuotaBytes > 0) ? min(100, round(($totalUsedBytes / $totalQuotaBytes) * 100, 1)) : 0;

        $stats = [
            'is_online'            => $isOnline,
            'server_url'           => $this->storageService->getBaseUrl(),
            'server_version'       => $health['version'] ?? '1.1.0',
            'server_uptime'        => $health['uptime'] ?? '-',
            'max_upload_size'      => $health['max_upload_size'] ?? '100.00 MB',
            'total_schools'        => $schools->count(),
            'total_dok_kbm_active' => $schools->where('dok_kbm_enabled', true)->count(),
            'total_licenses'       => $licenses->count(),
            'total_used_human'     => $this->formatBytes($totalUsedBytes),
            'total_quota_human'    => $this->formatBytes($totalQuotaBytes),
            'global_usage_percent' => $globalUsagePercent,
            'total_files'          => $totalFiles,
        ];

        return view('super-admin.storage.index', compact('stats', 'schools', 'licenses', 'health'));
    }

    /**
     * Ubah kuota client (Sekolah atau Lisensi) via AJAX
     */
    public function updateQuota(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'     => 'required|in:school,license',
            'id'       => 'required|integer',
            'quota_mb' => 'required|integer|min:0',
        ]);

        $quotaMb = (int) $validated['quota_mb'];

        if ($validated['type'] === 'school') {
            $school = School::findOrFail($validated['id']);
            $school->storage_quota_mb = $quotaMb;
            if (empty($school->storage_api_key)) {
                $school->storage_api_key = 'sk_sch_' . strtolower(\Illuminate\Support\Str::random(24));
            }
            $school->save();

            $sync = $this->storageService->syncSchoolUser($school);

            return response()->json([
                'success'             => true,
                'quota_mb'            => $quotaMb,
                'quota_human'         => $school->storage_quota_human,
                'message'             => "Kuota storage untuk {$school->name} berhasil diubah menjadi {$school->storage_quota_human}.",
                'sync'                => $sync,
            ]);
        }

        $license = License::findOrFail($validated['id']);
        $license->storage_quota_mb = $quotaMb;
        if (empty($license->storage_api_key)) {
            $license->storage_api_key = 'sk_lic_' . strtolower(\Illuminate\Support\Str::random(24));
        }
        $license->save();

        $sync = $this->storageService->syncLicenseUser($license);

        $quotaHuman = ($quotaMb >= 1024) ? round($quotaMb / 1024, 2) . ' GB' : ($quotaMb > 0 ? "{$quotaMb} MB" : 'Unlimited');

        return response()->json([
            'success'     => true,
            'quota_mb'    => $quotaMb,
            'quota_human' => $quotaHuman,
            'message'     => "Kuota storage untuk lisensi {$license->client_name} berhasil diubah menjadi {$quotaHuman}.",
            'sync'        => $sync,
        ]);
    }

    /**
     * Memicu sinkronisasi manual untuk satu sekolah
     */
    public function syncSchool(School $school): JsonResponse
    {
        $result = $this->storageService->syncSchoolUser($school);
        return response()->json($result);
    }

    /**
     * Memicu sinkronisasi manual untuk semua sekolah
     */
    public function syncAll(): JsonResponse
    {
        $schools = School::all();
        $synced = 0;
        $failed = 0;

        foreach ($schools as $school) {
            $res = $this->storageService->syncSchoolUser($school);
            if ($res['success']) {
                $synced++;
            } else {
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Sinkronisasi selesai. Berhasil: {$synced}, Gagal: {$failed}.",
            'synced'  => $synced,
            'failed'  => $failed,
        ]);
    }

    /**
     * Memicu kalkulasi ulang kapasitas seluruh user di storage server
     */
    public function recalculate(): JsonResponse
    {
        $res = $this->storageService->recalculateAll();
        return response()->json($res);
    }

    /**
     * Test koneksi ke storage server
     */
    public function testConnection(): JsonResponse
    {
        $health = $this->storageService->getHealth();
        if ($health) {
            return response()->json([
                'success' => true,
                'message' => 'Koneksi ke Storage Server berhasil.',
                'data'    => $health,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Tidak dapat terhubung ke Storage Server di ' . $this->storageService->getBaseUrl(),
        ], 502);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
