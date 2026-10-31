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
            $schoolSlug = Str::slug($license->client_name, '_');
            if (empty($schoolSlug)) {
                $schoolSlug = 'client_' . $license->id;
            }

            // Ekstensi file (hanya izinkan sql, gz, atau zip untuk keamanan)
            $rawExt = strtolower((string) $uploadedFile->getClientOriginalExtension());
            $extension = in_array($rawExt, ['sql', 'gz', 'zip'], true) ? $rawExt : 'sql';

            // Format nama backup: nama_sekolah_tgl (contoh: smpn_1_jakarta_15-10-2026_163000.sql)
            $tgl = now()->format('d-m-Y_His');
            $fileName = "{$schoolSlug}_{$tgl}.{$extension}";

            // Simpan di 1 folder bersama backup server utama: storage/app/backups
            $backupDir = storage_path('app/backups');
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Kebijakan: HANYA 1 backup per client.
            // Hapus file backup lama milik client ini di folder backups (pola nama_sekolah_*)
            $existingFiles = glob("{$backupDir}/{$schoolSlug}_*.*");
            if (!empty($existingFiles)) {
                foreach ($existingFiles as $existingFile) {
                    if (is_file($existingFile)) {
                        @unlink($existingFile);
                    }
                }
            }

            // Hapus juga file backup sebelumnya jika path tercatat berbeda (misal legacy di storage/app/private/client_backups)
            if (!empty($license->backup_file)) {
                $prevFullPath = storage_path('app/' . $license->backup_file);
                if (file_exists($prevFullPath) && is_file($prevFullPath)) {
                    @unlink($prevFullPath);
                }
                if (Storage::disk('local')->exists($license->backup_file)) {
                    Storage::disk('local')->delete($license->backup_file);
                }
            }

            // Pindahkan file backup ke storage/app/backups
            $uploadedFile->move($backupDir, $fileName);
            $storedPath = "backups/{$fileName}";
            $fullFilePath = "{$backupDir}/{$fileName}";
            $fileSize = file_exists($fullFilePath) ? filesize($fullFilePath) : $uploadedFile->getSize();

            // Simpan informasi backup ke database
            $license->update([
                'last_backup_at' => now(),
                'backup_file'    => $storedPath,
                'backup_size'    => $fileSize,
            ]);

            Log::info("[ClientBackup] Backup client '{$license->client_name}' berhasil disimpan di 1 folder backups: {$fileName} ({$fileSize} bytes).");

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
