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
                uploaderId: null,
                licenseId: $license->id
            );

            if (!empty($docItem['id'])) {
                \App\Models\StorageFile::where('id', $docItem['id'])->update(['license_id' => $license->id]);
            }

            // Update metrik pemakaian kuota pada record lisensi
            $newSize = (int) ($docItem['size'] ?? 0);
            $license->increment('storage_used_bytes', $newSize);
            $license->increment('storage_files_count', 1);
            $license->update(['last_ping_at' => now()]);

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

            // Cari berkas untuk mencatat pengurangan ukuran
            $existing = null;
            if ($path) {
                $existing = \App\Models\StorageFile::where('path', $path)
                    ->orWhere('path', ltrim($path, '/'))
                    ->first();
            }
            if (!$existing && $url) {
                $existing = \App\Models\StorageFile::where('url', $url)->first();
            }

            $deleted = $path ? $r2Service->deleteFileByPath($path) : $r2Service->deleteFileByUrl($url);

            if ($deleted && $existing) {
                $fileBytes = (int) ($existing->size_bytes ?? 0);
                $license->update([
                    'storage_used_bytes'  => max(0, (int) $license->storage_used_bytes - $fileBytes),
                    'storage_files_count' => max(0, (int) $license->storage_files_count - 1),
                    'last_ping_at'        => now(),
                ]);
            }

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

    /**
     * Endpoint relay request presigned upload URL ke Cloudflare R2 via Server Utama
     * POST /api/license/storage/presigned-url
     */
    public function presignedUrl(Request $request): JsonResponse
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

        $request->validate([
            'filename'  => 'required|string|max:255',
            'mime_type' => 'required|string|max:100',
            'size'      => 'nullable|integer|min:0',
            'category'  => 'nullable|string|in:kbm,face,siswa,guru,attendance,logo,general',
        ]);

        try {
            $r2Service = app(R2StorageService::class);
            $school = $license->school;

            $res = $r2Service->createPresignedUpload(
                school: $school,
                originalName: $request->filename,
                mimeType: $request->mime_type,
                sizeBytes: (int) ($request->size ?? 0),
                category: $request->category ?? 'kbm',
                uploaderId: null,
                licenseId: $license->id
            );

            if (!empty($res['file_id'])) {
                \App\Models\StorageFile::where('id', $res['file_id'])->update(['license_id' => $license->id]);
            }

            return response()->json([
                'success'    => true,
                'upload_url' => $res['upload_url'],
                'method'     => $res['method'],
                'headers'    => $res['headers'],
                'file_id'    => $res['file_id'],
                'data'       => $res['data'],
            ]);
        } catch (\App\Exceptions\StorageQuotaExceededException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error'   => [
                    'used_bytes'  => $e->usedBytes,
                    'quota_bytes' => $e->quotaBytes,
                ],
            ], 413);
        } catch (\Throwable $e) {
            Log::error("[ClientStorageRelay] Gagal generate presigned URL untuk lisensi '{$license->client_name}': " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat URL upload: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Endpoint relay konfirmasi upload file ke Cloudflare R2 via Server Utama
     * POST /api/license/storage/confirm-upload
     */
    public function confirmUpload(Request $request): JsonResponse
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

        $fileId = (int) $request->input('file_id');
        $size = $request->input('size') ? (int) $request->input('size') : null;

        try {
            $r2Service = app(R2StorageService::class);
            $file = $r2Service->confirmUpload($fileId, $size);

            if ($file) {
                $file->update(['license_id' => $license->id]);
                $fileSize = (int) ($file->size_bytes ?? 0);
                $license->increment('storage_used_bytes', $fileSize);
                $license->increment('storage_files_count', 1);
                $license->update(['last_ping_at' => now()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Upload berhasil dikonfirmasi.',
                'data'    => $file,
            ]);
        } catch (\Throwable $e) {
            Log::error("[ClientStorageRelay] Gagal konfirmasi upload untuk file #{$fileId}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal konfirmasi upload: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Endpoint sinkronisasi metrik penyimpanan dari client self-hosted
     * POST /api/license/storage/sync
     */
    public function sync(Request $request): JsonResponse
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

        $usedBytes  = max(0, (int) $request->input('used_bytes', 0));
        $filesCount = max(0, (int) $request->input('files_count', 0));

        $license->update([
            'storage_used_bytes'  => $usedBytes,
            'storage_files_count' => $filesCount,
            'last_ping_at'        => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Metrik kuota penyimpanan lisensi berhasil disinkronkan.',
            'data'    => [
                'license_key'         => $license->license_key,
                'client_name'         => $license->client_name,
                'storage_quota_mb'    => (int) ($license->storage_quota_mb ?? 1000),
                'storage_used_bytes'  => $license->storage_used_bytes,
                'storage_files_count' => $license->storage_files_count,
            ],
        ]);
    }
}

