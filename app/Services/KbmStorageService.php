<?php

namespace App\Services;

use App\Models\School;
use App\Models\StorageFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KbmStorageService
{
    /**
     * Driver penyimpanan yang aktif: 'internal' atau 'cloud_relay'
     */
    public static function getDriver(): string
    {
        $driver = env('KBM_STORAGE_DRIVER');
        if (!empty($driver)) {
            return strtolower(trim($driver));
        }

        // Auto-detect: Jika R2 keys lokal ada, atau cloud storage relay aktif
        $r2Key = config('filesystems.disks.r2.key');
        if (!empty($r2Key)) {
            return 'r2_direct';
        }

        // Jika self-hosted tanpa R2 lokal, default ke internal (aman untuk mini server offline)
        return 'internal';
    }

    /**
     * Upload dokumentasi KBM (foto/video) sesuai driver aktif
     */
    public function uploadFile(
        UploadedFile|string $file,
        School|int|null $school = null,
        string $category = 'kbm',
        ?string $customFolder = null,
        ?int $uploaderId = null
    ): array {
        $driver = self::getDriver();

        if ($driver === 'cloud_relay') {
            return $this->uploadViaCloudRelay($file, $school, $category, $uploaderId);
        }

        if ($driver === 'r2_direct') {
            return app(R2StorageService::class)->uploadFile($file, $school, $category, $customFolder, $uploaderId);
        }

        // Default: Simpan ke Internal Storage lokal
        return $this->uploadToInternalStorage($file, $school, $category, $uploaderId);
    }

    /**
     * 1. Simpan ke Storage Internal Lokal (Disk 'public' atau folder eksternal/USB)
     */
    protected function uploadToInternalStorage(
        UploadedFile|string $file,
        School|int|null $school = null,
        string $category = 'kbm',
        ?int $uploaderId = null
    ): array {
        $schoolModel = is_int($school) ? School::find($school) : $school;
        $schoolId = $schoolModel?->id;

        if ($file instanceof UploadedFile) {
            $size = $file->getSize();
            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getMimeType() ?: 'application/octet-stream';
            $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
            $realPath = $file->getRealPath();
        } else {
            if (!file_exists($file)) {
                throw new \InvalidArgumentException("File tidak ditemukan: {$file}");
            }
            $size = filesize($file);
            $originalName = basename($file);
            $mimeType = mime_content_type($file) ?: 'application/octet-stream';
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION) ?: 'bin');
            $realPath = $file;
        }

        // Cek custom local path (misal jika menggunakan flashdisk / external harddisk di STB)
        $customLocalPath = env('KBM_STORAGE_LOCAL_PATH');
        $dateFolder = date('Y/m');
        $randomName = Str::random(24) . '_' . time() . '.' . $extension;
        $subFolder = "kbm/" . ($schoolId ? "sch_{$schoolId}/" : "") . $dateFolder;

        if (!empty($customLocalPath)) {
            $targetDir = rtrim($customLocalPath, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subFolder);
            if (!file_exists($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $randomName;
            copy($realPath, $targetPath);
            $relativePath = trim($subFolder, '/') . '/' . $randomName;
            $publicUrl = asset('storage/' . $relativePath);
        } else {
            // Standar Laravel public disk: storage/app/public/kbm/...
            $relativePath = trim($subFolder, '/') . '/' . $randomName;
            $stream = fopen($realPath, 'r');
            Storage::disk('public')->put($relativePath, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            $publicUrl = asset('storage/' . $relativePath);
        }

        // Catat metadata ke database lokal storage_files
        $storageFile = StorageFile::create([
            'school_id'     => $schoolId,
            'disk'          => 'public',
            'path'          => $relativePath,
            'url'           => $publicUrl,
            'original_name' => $originalName,
            'size_bytes'    => $size,
            'mime_type'     => $mimeType,
            'category'      => $category,
            'uploader_id'   => $uploaderId,
        ]);

        $isVideo = str_starts_with($mimeType, 'video/') || in_array($extension, ['mp4', 'mov', 'avi', 'mkv', '3gp', 'webm']);

        return [
            'id'            => $storageFile->id,
            'disk'          => 'public',
            'path'          => $relativePath,
            'url'           => $publicUrl,
            'view_url'      => $publicUrl,
            'download_url'  => $publicUrl,
            'thumbnail_url' => $isVideo ? null : $publicUrl,
            'type'          => $isVideo ? 'video' : 'image',
            'name'          => $originalName,
            'size'          => $size,
            'size_human'    => R2StorageService::formatBytes($size),
            'mime_type'     => $mimeType,
        ];
    }

    /**
     * 2. Relay upload ke Cloudflare R2 lewat Server Utama (License Server)
     */
    protected function uploadViaCloudRelay(
        UploadedFile|string $file,
        School|int|null $school = null,
        string $category = 'kbm',
        ?int $uploaderId = null
    ): array {
        $licenseServerUrl = rtrim(env('LICENSE_SERVER_URL', 'https://absen.kangdaqiq.com'), '/');
        $licenseKey = trim(env('LICENSE_KEY', ''));

        if (empty($licenseKey)) {
            throw new \RuntimeException('LICENSE_KEY belum dikonfigurasi di file .env untuk upload via cloud relay.');
        }

        $endpoint = "{$licenseServerUrl}/api/license/storage/upload";

        $req = Http::timeout(60)->withHeaders([
            'X-License-Key' => $licenseKey,
        ]);

        if ($file instanceof UploadedFile) {
            $req = $req->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName());
        } else {
            $req = $req->attach('file', file_get_contents($file), basename($file));
        }

        $res = $req->post($endpoint, [
            'category' => $category,
        ]);

        if (!$res->successful()) {
            $msg = $res->json('message') ?? ('HTTP ' . $res->status() . ' - ' . $res->body());
            Log::error("[KbmStorageRelay] Gagal upload ke server utama: {$msg}");
            throw new \RuntimeException("Gagal upload ke cloud storage utama: {$msg}");
        }

        $data = $res->json('data');
        if (empty($data) || empty($data['url'])) {
            throw new \RuntimeException('Respon upload dari server cloud tidak valid.');
        }

        $schoolModel = is_int($school) ? School::find($school) : $school;

        // Catat metadata URL cloud ke database lokal storage_files
        $storageFile = StorageFile::create([
            'school_id'     => $schoolModel?->id,
            'disk'          => 'cloud_relay',
            'path'          => $data['path'] ?? ('relay/' . basename($data['url'])),
            'url'           => $data['url'],
            'original_name' => $data['name'] ?? basename($data['url']),
            'size_bytes'    => $data['size'] ?? 0,
            'mime_type'     => $data['mime_type'] ?? 'application/octet-stream',
            'category'      => $category,
            'uploader_id'   => $uploaderId,
        ]);

        $data['id'] = $storageFile->id;

        // Sinkronkan pemakaian storage ke server lisensi
        self::syncStorageMetricsToServer();

        return $data;
    }

    /**
     * Hapus berkas sesuai driver
     */
    public function deleteFileByPath(string $path): bool
    {
        $driver = self::getDriver();

        if ($driver === 'cloud_relay') {
            return $this->deleteViaCloudRelay($path);
        }

        if ($driver === 'r2_direct') {
            return app(R2StorageService::class)->deleteFileByPath($path);
        }

        // Internal Storage: Hapus dari disk 'public'
        try {
            Storage::disk('public')->delete($path);
            StorageFile::where('path', $path)->delete();
            return true;
        } catch (\Throwable $e) {
            Log::warning("[KbmStorageService] Gagal hapus file lokal {$path}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hapus file via URL
     */
    public function deleteFileByUrl(string $url): bool
    {
        $driver = self::getDriver();

        if ($driver === 'cloud_relay') {
            return $this->deleteViaCloudRelay(null, $url);
        }

        if ($driver === 'r2_direct') {
            return app(R2StorageService::class)->deleteFileByUrl($url);
        }

        // Internal Storage: parsing path dari URL
        $storageFile = StorageFile::where('url', $url)->first();
        if ($storageFile) {
            return $this->deleteFileByPath($storageFile->path);
        }

        $parsed = parse_url($url, PHP_URL_PATH);
        if ($parsed) {
            $path = ltrim(str_replace('/storage/', '', $parsed), '/');
            return $this->deleteFileByPath($path);
        }

        return false;
    }

    protected function deleteViaCloudRelay(?string $path = null, ?string $url = null): bool
    {
        $licenseServerUrl = rtrim(env('LICENSE_SERVER_URL', 'https://absen.kangdaqiq.com'), '/');
        $licenseKey = trim(env('LICENSE_KEY', ''));

        if (empty($licenseKey)) {
            return false;
        }

        try {
            $endpoint = "{$licenseServerUrl}/api/license/storage/delete";
            $res = Http::timeout(15)->withHeaders([
                'X-License-Key' => $licenseKey,
            ])->post($endpoint, [
                'path' => $path,
                'url'  => $url,
            ]);

            // Bersihkan juga dari record lokal
            if ($path) {
                StorageFile::where('path', $path)->delete();
            }
            if ($url) {
                StorageFile::where('url', $url)->delete();
            }

            self::syncStorageMetricsToServer();

            return $res->successful() && !empty($res->json('success'));
        } catch (\Throwable $e) {
            Log::warning("[KbmStorageRelay] Gagal hapus file di server utama: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Presigned Upload kompatibel dengan PWA / Mobile Apps untuk seluruh driver:
     * - internal: URL direct-put lokal pada server self-hosted
     * - cloud_relay: URL presigned Cloudflare R2 yang di-generate via server utama
     * - r2_direct: URL presigned Cloudflare R2 langsung dari server cloud
     */
    public function createPresignedUpload(
        School|int|null $school = null,
        string $originalName = 'file.jpg',
        string $mimeType = 'image/jpeg',
        int $sizeBytes = 0,
        string $category = 'kbm',
        ?int $uploaderId = null
    ): array {
        $driver = self::getDriver();

        if ($driver === 'cloud_relay') {
            return $this->createPresignedUploadViaCloudRelay($school, $originalName, $mimeType, $sizeBytes, $category, $uploaderId);
        }

        if ($driver === 'r2_direct') {
            return app(R2StorageService::class)->createPresignedUpload(
                school: $school,
                originalName: $originalName,
                mimeType: $mimeType,
                sizeBytes: $sizeBytes,
                category: $category,
                uploaderId: $uploaderId
            );
        }

        // Default: Internal Storage presigned upload (local direct-put)
        return $this->createPresignedUploadInternal($school, $originalName, $mimeType, $sizeBytes, $category, $uploaderId);
    }

    /**
     * Presigned Upload internal: Mengembalikan URL HTTP PUT lokal ke endpoint /api/mobile/storage/direct-put/{token}
     */
    protected function createPresignedUploadInternal(
        School|int|null $school = null,
        string $originalName = 'file.jpg',
        string $mimeType = 'image/jpeg',
        int $sizeBytes = 0,
        string $category = 'kbm',
        ?int $uploaderId = null
    ): array {
        $schoolModel = is_int($school) ? School::find($school) : $school;
        $schoolId = $schoolModel?->id;

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION) ?: 'bin');
        $randomName = Str::random(24) . '_' . time() . '.' . $extension;
        $dateFolder = date('Y/m');
        $subFolder = "kbm/" . ($schoolId ? "sch_{$schoolId}/" : "") . $dateFolder;
        $relativePath = trim($subFolder, '/') . '/' . $randomName;
        $publicUrl = asset('storage/' . $relativePath);

        // Catat ke tabel storage_files dengan size estimasi
        $storageFile = StorageFile::create([
            'school_id'     => $schoolId,
            'disk'          => 'public',
            'path'          => $relativePath,
            'url'           => $publicUrl,
            'original_name' => $originalName,
            'size_bytes'    => $sizeBytes,
            'mime_type'     => $mimeType,
            'category'      => $category,
            'uploader_id'   => $uploaderId,
        ]);

        $token = Str::random(40);
        Cache::put("storage_direct_put_{$token}", [
            'file_id'   => $storageFile->id,
            'path'      => $relativePath,
            'mime_type' => $mimeType,
        ], now()->addHours(2));

        $isVideo = str_starts_with($mimeType, 'video/') || in_array($extension, ['mp4', 'mov', 'avi', 'mkv', '3gp', 'webm']);

        return [
            'upload_url'   => url("/api/mobile/storage/direct-put/{$token}"),
            'method'       => 'PUT',
            'headers'      => [
                'Content-Type' => $mimeType,
            ],
            'file_id'      => $storageFile->id,
            'data'         => [
                'id'            => $storageFile->id,
                'disk'          => 'public',
                'path'          => $relativePath,
                'url'           => $publicUrl,
                'view_url'      => $publicUrl,
                'download_url'  => $publicUrl,
                'thumbnail_url' => $isVideo ? null : $publicUrl,
                'type'          => $isVideo ? 'video' : 'image',
                'name'          => $originalName,
                'size'          => $sizeBytes,
                'size_human'    => R2StorageService::formatBytes($sizeBytes),
                'mime_type'     => $mimeType,
            ],
        ];
    }

    /**
     * Presigned Upload relay: Meminta tiket upload Cloudflare R2 ke server utama
     */
    protected function createPresignedUploadViaCloudRelay(
        School|int|null $school = null,
        string $originalName = 'file.jpg',
        string $mimeType = 'image/jpeg',
        int $sizeBytes = 0,
        string $category = 'kbm',
        ?int $uploaderId = null
    ): array {
        $licenseServerUrl = rtrim(env('LICENSE_SERVER_URL', 'https://absen.kangdaqiq.com'), '/');
        $licenseKey = trim(env('LICENSE_KEY', ''));

        if (empty($licenseKey)) {
            throw new \RuntimeException('LICENSE_KEY belum dikonfigurasi di file .env.');
        }

        $endpoint = "{$licenseServerUrl}/api/license/storage/presigned-url";
        $res = Http::timeout(20)->withHeaders([
            'X-License-Key' => $licenseKey,
        ])->post($endpoint, [
            'filename'  => $originalName,
            'mime_type' => $mimeType,
            'size'      => $sizeBytes,
            'category'  => $category,
        ]);

        if (!$res->successful()) {
            $msg = $res->json('message') ?? ('HTTP ' . $res->status());
            throw new \RuntimeException("Gagal mendapatkan tiket upload dari cloud server: {$msg}");
        }

        $remoteData = $res->json();
        $schoolModel = is_int($school) ? School::find($school) : $school;
        $remoteFile = $remoteData['data'] ?? [];

        // Buat record lokal agar sinkron di database client
        $storageFile = StorageFile::create([
            'school_id'     => $schoolModel?->id,
            'disk'          => 'cloud_relay',
            'path'          => $remoteFile['path'] ?? ('relay/' . $originalName),
            'url'           => $remoteFile['url'] ?? '',
            'original_name' => $originalName,
            'size_bytes'    => $sizeBytes,
            'mime_type'     => $mimeType,
            'category'      => $category,
            'uploader_id'   => $uploaderId,
        ]);

        if (!empty($remoteData['file_id'])) {
            Cache::put("relay_remote_file_{$storageFile->id}", $remoteData['file_id'], now()->addHours(2));
        }

        $remoteData['file_id'] = $storageFile->id;
        if (isset($remoteData['data'])) {
            $remoteData['data']['id'] = $storageFile->id;
        }

        return $remoteData;
    }

    /**
     * Simpan file hasil direct-PUT lokal dari PWA / Mobile
     */
    public function saveDirectPutFile(string $token, string $rawBytes): bool
    {
        $cacheKey = "storage_direct_put_{$token}";
        $info = Cache::get($cacheKey);
        if (!$info) {
            throw new \InvalidArgumentException('Token upload tidak valid atau telah kedaluwarsa.');
        }

        $customLocalPath = env('KBM_STORAGE_LOCAL_PATH');
        $relativePath = $info['path'];

        if (!empty($customLocalPath)) {
            $targetPath = rtrim($customLocalPath, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $targetDir = dirname($targetPath);
            if (!file_exists($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }
            file_put_contents($targetPath, $rawBytes);
        } else {
            Storage::disk('public')->put($relativePath, $rawBytes);
        }

        // Perbarui ukuran aktual di tabel storage_files
        StorageFile::where('id', $info['file_id'])->update([
            'size_bytes' => strlen($rawBytes),
        ]);

        Cache::forget($cacheKey);

        return true;
    }

    /**
     * Konfirmasi upload direct selesai
     */
    public function confirmUpload(int $fileId, ?int $actualSizeBytes = null): ?StorageFile
    {
        $driver = self::getDriver();

        if ($driver === 'r2_direct') {
            return app(R2StorageService::class)->confirmUpload($fileId, $actualSizeBytes);
        }

        $file = StorageFile::find($fileId);
        if (!$file) {
            return null;
        }

        if ($actualSizeBytes && $actualSizeBytes > 0) {
            $file->update(['size_bytes' => $actualSizeBytes]);
        }

        if ($driver === 'cloud_relay') {
            $remoteFileId = Cache::get("relay_remote_file_{$fileId}");
            if ($remoteFileId) {
                try {
                    $licenseServerUrl = rtrim(env('LICENSE_SERVER_URL', 'https://absen.kangdaqiq.com'), '/');
                    $licenseKey = trim(env('LICENSE_KEY', ''));
                    Http::timeout(10)->withHeaders([
                        'X-License-Key' => $licenseKey,
                    ])->post("{$licenseServerUrl}/api/license/storage/confirm-upload", [
                        'file_id' => $remoteFileId,
                        'size'    => $actualSizeBytes,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning("[KbmStorageService] Gagal relay confirm upload ke server utama: " . $e->getMessage());
                }
            }
        }

        self::syncStorageMetricsToServer();

        return $file;
    }

    /**
     * Sinkronisasi total pemakaian penyimpanan lokal ke server lisensi pusat
     */
    public static function syncStorageMetricsToServer(): void
    {
        try {
            $licenseServerUrl = rtrim(env('LICENSE_SERVER_URL', 'https://absen.kangdaqiq.com'), '/');
            $licenseKey = trim(env('LICENSE_KEY', ''));

            if (empty($licenseKey) || empty($licenseServerUrl)) {
                return;
            }

            $usedBytes = 0;
            $filesCount = 0;
            if (\Illuminate\Support\Facades\Schema::hasTable('storage_files')) {
                $usedBytes = (int) StorageFile::sum('size_bytes');
                $filesCount = (int) StorageFile::count();
            }

            Http::timeout(5)->withHeaders([
                'X-License-Key' => $licenseKey,
            ])->post("{$licenseServerUrl}/api/license/storage/sync", [
                'used_bytes'  => $usedBytes,
                'files_count' => $filesCount,
            ]);
        } catch (\Throwable $e) {
            Log::debug("[KbmStorageService] Sinkronisasi kuota storage ke server lisensi dilewati: " . $e->getMessage());
        }
    }
}

