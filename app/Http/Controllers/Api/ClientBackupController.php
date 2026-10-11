<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ClientBackupController extends Controller
{
    /**
     * Endpoint for self-hosted clients to upload database backup to main server.
     * POST /api/license/backup/upload
     * 
     * Enforces policy: Only 1 latest backup file kept per client.
     */
    public function upload(Request $request): JsonResponse
    {
        $licenseKey = trim($request->input('license_key', ''));

        if (empty($licenseKey)) {
            return response()->json([
                'success' => false,
                'message' => 'license_key wajib disertakan.',
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

        if (!$request->hasFile('backup_file')) {
            return response()->json([
                'success' => false,
                'message' => 'File backup tidak ditemukan dalam permintaan.',
            ], 422);
        }

        $uploadedFile = $request->file('backup_file');
        if (!$uploadedFile->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Upload file backup gagal atau file corrupt: ' . $uploadedFile->getErrorMessage(),
            ], 422);
        }

        try {
            $clientSlug = Str::slug($license->client_name, '_');
            if (empty($clientSlug)) {
                $clientSlug = 'client_' . $license->id;
            }

            $disk = Storage::disk('local');
            $clientDir = "client_backups/{$clientSlug}";

            // Kebijakan: HANYA 1 backup per client.
            // Hapus semua file backup lama milik client ini jika ada.
            if ($disk->exists($clientDir)) {
                $existingFiles = $disk->files($clientDir);
                foreach ($existingFiles as $existingFile) {
                    $disk->delete($existingFile);
                }
            } else {
                $disk->makeDirectory($clientDir);
            }

            // Hapus juga file backup sebelumnya jika path tercatat berbeda
            if (!empty($license->backup_file) && $disk->exists($license->backup_file)) {
                $disk->delete($license->backup_file);
            }

            $extension = $uploadedFile->getClientOriginalExtension() ?: 'sql';
            $fileName = "{$clientSlug}_latest.{$extension}";
            $storedPath = $uploadedFile->storeAs($clientDir, $fileName, 'local');

            $fileSize = $uploadedFile->getSize();

            // Simpan informasi backup ke database
            $license->update([
                'last_backup_at' => now(),
                'backup_file'    => $storedPath,
                'backup_size'    => $fileSize,
            ]);

            Log::info("[ClientBackup] Backup client '{$license->client_name}' berhasil disimpan. Path: {$storedPath}, Size: {$fileSize} bytes.");

            return response()->json([
                'success' => true,
                'message' => 'Backup berhasil diterima dan disimpan di server utama.',
                'data'    => [
                    'client_name'    => $license->client_name,
                    'file_name'      => $fileName,
                    'backup_size'    => $license->formatted_backup_size,
                    'last_backup_at' => $license->last_backup_at->toDateTimeString(),
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error("[ClientBackup] Gagal memproses backup client '{$license->client_name}': " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan backup di server: ' . $e->getMessage(),
            ], 500);
        }
    }
}
