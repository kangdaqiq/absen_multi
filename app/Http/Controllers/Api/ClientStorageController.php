<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Services\R2StorageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ClientStorageController extends Controller
{
    /**
     * Endpoint relay upload file dari client self-hosted ke Cloudflare R2 via Server Utama.
     * POST /api/license/storage/upload
     */
    public function upload(Request $request): JsonResponse
    {
        $licenseKey = trim($request->header('X-License-Key') ?: $request->input('license_key', ''));

        if (empty($licenseKey)) {
            return response()->json([
                'success' => false,
                'message' => 'license_key wajib disertakan pada parameter atau header X-License-Key.',
            ], 400);
        }

        $license = License::where('license_key', $licenseKey)->first();

        if (!$license) {
            return response()->json([
                'success' => false,
                'message' => 'Lisensi tidak ditemukan.',
            ], 404);
        }

        if (!$license->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Lisensi nonaktif. Hubungi provider.',
            ], 403);
        }

        if ($license->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Lisensi telah expired. Silakan perpanjang lisensi Anda.',
            ], 403);
        }

        if (!$request->hasFile('file')) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan dalam permintaan.',
            ], 422);
        }

        $uploadedFile = $request->file('file');
        if (!$uploadedFile->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Upload file gagal atau file corrupt: ' . $uploadedFile->getErrorMessage(),
            ], 422);
        }

        $category = $request->input('category', 'kbm');

        try {
            $r2Service = app(R2StorageService::class);
            $school = $license->school;

            $docItem = $r2Service->uploadFile(
                file: $uploadedFile,
                school: $school,
                category: $category,
                customFolder: null,
                uploaderId: null
            );

            Log::info("[ClientStorageRelay] Berhasil relay upload file untuk lisensi '{$license->client_name}': {$docItem['path']}");

            return response()->json([
                'success' => true,
                'message' => 'Upload ke cloud storage server utama berhasil.',
                'data'    => $docItem,
            ]);
        } catch (\App\Exceptions\StorageQuotaExceededException $e) {
            Log::warning("[ClientStorageRelay] Kuota R2 storage penuh untuk lisensi '{$license->client_name}'");
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error'   => [
                    'used_bytes'  => $e->usedBytes,
                    'quota_bytes' => $e->quotaBytes,
                ],
            ], 413);
        } catch (\Throwable $e) {
            Log::error("[ClientStorageRelay] Gagal upload file untuk lisensi '{$license->client_name}': " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat upload berkas ke cloud: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Endpoint relay hapus file dari Cloudflare R2 via Server Utama.
     * POST /api/license/storage/delete
     */
    public function delete(Request $request): JsonResponse
    {
        $licenseKey = trim($request->header('X-License-Key') ?: $request->input('license_key', ''));

        if (empty($licenseKey)) {
            return response()->json([
                'success' => false,
                'message' => 'license_key wajib disertakan.',
            ], 400);
        }

        $license = License::where('license_key', $licenseKey)->first();
        if (!$license || !$license->is_active || $license->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Lisensi tidak valid atau telah expired.',
            ], 403);
        }

        $path = $request->input('path');
        $url = $request->input('url');

        if (!$path && !$url) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter path atau url wajib disertakan.',
            ], 422);
        }

        try {
            $r2Service = app(R2StorageService::class);
            $deleted = $path ? $r2Service->deleteFileByPath($path) : $r2Service->deleteFileByUrl($url);

            return response()->json([
                'success' => $deleted,
                'message' => $deleted ? 'File berhasil dihapus dari cloud storage.' : 'Gagal menghapus file dari cloud storage.',
            ]);
        } catch (\Throwable $e) {
            Log::error("[ClientStorageRelay] Gagal menghapus file untuk lisensi '{$license->client_name}': " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus file: ' . $e->getMessage(),
            ], 500);
        }
    }
}
