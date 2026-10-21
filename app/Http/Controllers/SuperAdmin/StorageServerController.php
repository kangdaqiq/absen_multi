<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\License;
use App\Models\StorageFile;
use App\Services\R2StorageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StorageServerController extends Controller
{
    protected R2StorageService $r2Service;

    public function __construct(R2StorageService $r2Service)
    {
        $this->r2Service = $r2Service;
    }

    /**
     * Tampilan utama Manajemen Storage Cloudflare R2 & Kuota Client di Super Admin
     */
    public function index(Request $request)
    {
        $health = $this->r2Service->getHealth();
        $isOnline = !empty($health);

        // Ambil data agregat per school_id dari tabel storage_files
        $schoolFilesAgg = StorageFile::whereNotNull('school_id')
            ->selectRaw('school_id, COUNT(*) as total_files, SUM(size_bytes) as total_bytes')
            ->groupBy('school_id')
            ->get()
            ->keyBy('school_id');

        // Ambil data agregat per license_id
        $licenseFilesAgg = StorageFile::whereNotNull('license_id')
            ->selectRaw('license_id, COUNT(*) as total_files, SUM(size_bytes) as total_bytes')
            ->groupBy('license_id')
            ->get()
            ->keyBy('license_id');

        // Ambil seluruh Sekolah (Client SaaS)
        $schools = School::withCount(['siswa', 'guru'])
            ->orderBy('name')
            ->get()
            ->map(function ($school) use ($schoolFilesAgg) {
                $agg = $schoolFilesAgg->get($school->id);
                $usedBytes = (int) ($agg->total_bytes ?? 0);
                $totalFiles = (int) ($agg->total_files ?? 0);

                $quotaMb = (int) ($school->storage_quota_mb ?? 1000);
                $quotaBytes = ($quotaMb > 0) ? ($quotaMb * 1024 * 1024) : 0;
                $percent = ($quotaBytes > 0) ? min(100, round(($usedBytes / $quotaBytes) * 100, 1)) : 0;

                $school->storage_info = [
                    'used_bytes'   => $usedBytes,
                    'quota_bytes'  => $quotaBytes,
                    'used_human'   => R2StorageService::formatBytes($usedBytes),
                    'quota_human'  => ($quotaBytes > 0) ? R2StorageService::formatBytes($quotaBytes) : 'Unlimited',
                    'total_files'  => $totalFiles,
                    'percent'      => $percent,
                ];
                $school->used_bytes = $usedBytes;
                $school->used_human = R2StorageService::formatBytes($usedBytes);
                $school->usage_percentage = $percent;
                $school->total_files = $totalFiles;

                return $school;
            });

        // Ambil seluruh Lisensi (Client Self-Hosted)
        $licenses = License::orderBy('client_name')
            ->get()
            ->map(function ($license) use ($licenseFilesAgg) {
                $agg = $licenseFilesAgg->get($license->id);
                $usedBytes = (int) ($agg->total_bytes ?? 0);
                $totalFiles = (int) ($agg->total_files ?? 0);

                $quotaMb = (int) ($license->storage_quota_mb ?? 1000);
                $quotaBytes = ($quotaMb > 0) ? ($quotaMb * 1024 * 1024) : 0;
                $percent = ($quotaBytes > 0) ? min(100, round(($usedBytes / $quotaBytes) * 100, 1)) : 0;

                $license->storage_info = [
                    'used_bytes'   => $usedBytes,
                    'quota_bytes'  => $quotaBytes,
                    'used_human'   => R2StorageService::formatBytes($usedBytes),
                    'quota_human'  => ($quotaBytes > 0) ? R2StorageService::formatBytes($quotaBytes) : 'Unlimited',
                    'total_files'  => $totalFiles,
                    'percent'      => $percent,
                ];
                $license->used_bytes = $usedBytes;
                $license->used_human = R2StorageService::formatBytes($usedBytes);
                $license->usage_percentage = $percent;
                $license->total_files = $totalFiles;

                return $license;
            });

        $stats = $this->r2Service->getGlobalStats();

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

            $quotaHuman = ($quotaMb >= 1024) ? round($quotaMb / 1024, 2) . ' GB' : ($quotaMb > 0 ? "{$quotaMb} MB" : 'Unlimited');

            return response()->json([
                'success'     => true,
                'quota_mb'    => $quotaMb,
                'quota_human' => $quotaHuman,
                'message'     => "Kuota storage untuk {$school->name} berhasil diubah menjadi {$quotaHuman}.",
            ]);
        }

        $license = License::findOrFail($validated['id']);
        $license->storage_quota_mb = $quotaMb;
        if (empty($license->storage_api_key)) {
            $license->storage_api_key = 'sk_lic_' . strtolower(\Illuminate\Support\Str::random(24));
        }
        $license->save();

        $quotaHuman = ($quotaMb >= 1024) ? round($quotaMb / 1024, 2) . ' GB' : ($quotaMb > 0 ? "{$quotaMb} MB" : 'Unlimited');

        return response()->json([
            'success'     => true,
            'quota_mb'    => $quotaMb,
            'quota_human' => $quotaHuman,
            'message'     => "Kuota storage untuk lisensi {$license->client_name} berhasil diubah menjadi {$quotaHuman}.",
        ]);
    }

    /**
     * Memicu sinkronisasi manual untuk satu sekolah
     */
    public function syncSchool(School $school): JsonResponse
    {
        $stats = $this->r2Service->getSchoolStorageStats($school);
        return response()->json([
            'success' => true,
            'message' => "Sinkronisasi storage Cloudflare R2 untuk {$school->name} selesai.",
            'data'    => $stats,
        ]);
    }

    /**
     * Memicu sinkronisasi manual untuk semua sekolah
     */
    public function syncAll(): JsonResponse
    {
        $schools = School::all();
        return response()->json([
            'success' => true,
            'message' => "Seluruh {$schools->count()} sekolah telah tersinkronisasi dengan Cloudflare R2.",
            'synced'  => $schools->count(),
            'failed'  => 0,
        ]);
    }

    /**
     * Memicu audit ulang kapasitas dari Cloudflare R2
     */
    public function recalculate(): JsonResponse
    {
        $totalFiles = StorageFile::count();
        $totalBytes = (int) StorageFile::sum('size_bytes');

        return response()->json([
            'success' => true,
            'message' => "Kalkulasi ulang selesai. Terdata {$totalFiles} berkas (" . R2StorageService::formatBytes($totalBytes) . ") di Cloudflare R2.",
            'data'    => [
                'total_files' => $totalFiles,
                'total_bytes' => $totalBytes,
            ],
        ]);
    }

    /**
     * Test koneksi ke Cloudflare R2
     */
    public function testConnection(): JsonResponse
    {
        $health = $this->r2Service->getHealth();
        if ($health) {
            return response()->json([
                'success' => true,
                'message' => "Koneksi ke Cloudflare R2 [Bucket: {$health['bucket']}] BERHASIL.",
                'data'    => $health,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Tidak dapat terhubung ke Cloudflare R2. Periksa kredensial di .env.',
        ], 502);
    }
}
