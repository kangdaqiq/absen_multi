<?php

namespace App\Services;

use App\Exceptions\StorageQuotaExceededException;
use App\Models\License;
use App\Models\School;
use App\Models\StorageFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class R2StorageService
{
    protected string $disk = 'r2';
    protected string $baseUrl;
    protected string $bucket;

    public function __construct()
    {
        $this->bucket = config('filesystems.disks.r2.bucket') ?: env('CLOUDFLARE_R2_BUCKET', 'jagatabsen');
        $this->baseUrl = rtrim(config('filesystems.disks.r2.url') ?: env('CLOUDFLARE_R2_URL', 'https://r2-cloud.jagattech.my.id'), '/');
    }

    /**
     * Cek apakah koneksi ke Cloudflare R2 aktif dan sehat
     */
    public function getHealth(): ?array
    {
        try {
            $testKey = '.health-check.txt';
            $disk = Storage::disk($this->disk);
            $disk->put($testKey, 'ok');
            $disk->delete($testKey);

            return [
                'status'          => 'healthy',
                'service'         => 'Cloudflare R2 Cloud Storage',
                'bucket'          => $this->bucket,
                'public_url'      => $this->baseUrl,
                'endpoint'        => config('filesystems.disks.r2.endpoint'),
                'max_upload_size' => '100.00 MB',
                'version'         => 'R2-Cloud 1.0',
                'uptime'          => 'Cloud Managed (99.99%)',
            ];
        } catch (\Throwable $e) {
            Log::warning("[R2StorageService] Health check failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Hitung pemakaian storage sekolah saat ini (dalam bytes)
     */
    public function getSchoolUsedBytes(int $schoolId): int
    {
        return (int) StorageFile::where('school_id', $schoolId)->sum('size_bytes');
    }

    /**
     * Hitung pemakaian storage lisensi saat ini (dalam bytes)
     */
    public function getLicenseUsedBytes(int $licenseId): int
    {
        $r2Bytes = (int) StorageFile::where('license_id', $licenseId)->sum('size_bytes');
        $license = License::find($licenseId);
        if ($license && !empty($license->storage_used_bytes) && $license->storage_used_bytes > $r2Bytes) {
            return (int) $license->storage_used_bytes;
        }
        return $r2Bytes;
    }

    /**
     * Informasi lengkap kuota & pemakaian per lisensi (client self-hosted)
     */
    public function getLicenseStorageStats(License|int $license): array
    {
        if (is_int($license)) {
            $license = License::find($license);
        }

        if (!$license) {
            return [
                'used_bytes'   => 0,
                'quota_bytes'  => 0,
                'quota_mb'     => 0,
                'used_human'   => '0 B',
                'quota_human'  => '0 MB',
                'free_human'   => '0 B',
                'percent'      => 0,
                'total_files'  => 0,
                'is_full'      => false,
                'is_warning'   => false,
                'is_critical'  => false,
            ];
        }

        $r2Bytes = (int) StorageFile::where('license_id', $license->id)->sum('size_bytes');
        $r2Files = StorageFile::where('license_id', $license->id)->count();

        $usedBytes = max($r2Bytes, (int) ($license->storage_used_bytes ?? 0));
        $totalFiles = max($r2Files, (int) ($license->storage_files_count ?? 0));

        $quotaMb = (int) ($license->storage_quota_mb ?? 1000);
        $quotaBytes = ($quotaMb > 0) ? ($quotaMb * 1024 * 1024) : 0;

        $percent = ($quotaBytes > 0) ? min(100, round(($usedBytes / $quotaBytes) * 100, 1)) : 0;
        $freeBytes = ($quotaBytes > 0) ? max(0, $quotaBytes - $usedBytes) : PHP_INT_MAX;

        return [
            'used_bytes'   => $usedBytes,
            'quota_bytes'  => $quotaBytes,
            'quota_mb'     => $quotaMb,
            'used_human'   => self::formatBytes($usedBytes),
            'quota_human'  => ($quotaBytes > 0) ? self::formatBytes($quotaBytes) : 'Unlimited',
            'free_human'   => ($quotaBytes > 0) ? self::formatBytes($freeBytes) : 'Unlimited',
            'percent'      => $percent,
            'total_files'  => $totalFiles,
            'is_full'      => ($quotaBytes > 0 && $usedBytes >= $quotaBytes),
            'is_critical'  => $percent >= 90,
            'is_warning'   => $percent >= 70 && $percent < 90,
        ];
    }

    /**
     * Cek apakah lisensi memiliki sisa kuota yang cukup untuk file baru
     */
    public function checkLicenseQuota(License|int $license, int $incomingBytes): bool
    {
        if (is_int($license)) {
            $license = License::find($license);
        }

        if (!$license) {
            return false;
        }

        $quotaMb = (int) ($license->storage_quota_mb ?? 1000);
        if ($quotaMb <= 0) {
            return true;
        }

        $usedBytes = $this->getLicenseUsedBytes($license->id);
        return ($usedBytes + $incomingBytes) <= ($quotaMb * 1024 * 1024);
    }

    /**
     * Informasi lengkap kuota & pemakaian per sekolah
     */
    public function getSchoolStorageStats(School|int $school): array
    {
        if (is_int($school)) {
            $school = School::find($school);
        }

        if (!$school) {
            return [
                'used_bytes'   => 0,
                'quota_bytes'  => 0,
                'used_human'   => '0 B',
                'quota_human'  => '0 MB',
                'free_human'   => '0 B',
                'percent'      => 0,
                'total_files'  => 0,
                'is_full'      => false,
                'is_warning'   => false,
                'is_critical'  => false,
            ];
        }

        $usedBytes = $this->getSchoolUsedBytes($school->id);
        $totalFiles = StorageFile::where('school_id', $school->id)->count();

        $quotaMb = (int) ($school->storage_quota_mb ?? 1000);
        $quotaBytes = ($quotaMb > 0) ? ($quotaMb * 1024 * 1024) : 0;

        $percent = ($quotaBytes > 0) ? min(100, round(($usedBytes / $quotaBytes) * 100, 1)) : 0;
        $freeBytes = ($quotaBytes > 0) ? max(0, $quotaBytes - $usedBytes) : PHP_INT_MAX;

        return [
            'used_bytes'   => $usedBytes,
            'quota_bytes'  => $quotaBytes,
            'quota_mb'     => $quotaMb,
            'used_human'   => self::formatBytes($usedBytes),
            'quota_human'  => ($quotaBytes > 0) ? self::formatBytes($quotaBytes) : 'Unlimited',
            'free_human'   => ($quotaBytes > 0) ? self::formatBytes($freeBytes) : 'Unlimited',
            'percent'      => $percent,
            'total_files'  => $totalFiles,
            'is_full'      => ($quotaBytes > 0 && $usedBytes >= $quotaBytes),
            'is_critical'  => $percent >= 90,
            'is_warning'   => $percent >= 70 && $percent < 90,
        ];
    }

    /**
     * Cek apakah sekolah memiliki sisa kuota yang cukup untuk file baru
     */
    public function checkSchoolQuota(School|int $school, int $incomingBytes): bool
    {
        if (is_int($school)) {
            $school = School::find($school);
        }

        if (!$school) {
            return true;
        }

        $quotaMb = (int) ($school->storage_quota_mb ?? 1000);
        if ($quotaMb <= 0) {
            return true; // Unlimited
        }

        $quotaBytes = $quotaMb * 1024 * 1024;
        $currentUsed = $this->getSchoolUsedBytes($school->id);

        return ($currentUsed + $incomingBytes) <= $quotaBytes;
    }

    /**
     * Upload berkas langsung ke Cloudflare R2 dengan validasi kuota
     *
     * @param UploadedFile|string $file Objek UploadedFile atau path file lokal
     * @param School|int|null $school Sekolah pemilik berkas
     * @param string $category Kategori ('kbm', 'face', 'siswa', 'guru', 'attendance', 'logo')
     * @param string|null $customFolder Subfolder opsional
     * @param int|null $uploaderId User ID pengunggah
     * @return array Metadata berkas terformat rapi
     * @throws StorageQuotaExceededException Jika kuota sekolah tidak mencukupi
     */
    public function uploadFile(
        UploadedFile|string $file,
        School|int|null $school = null,
        string $category = 'kbm',
        ?string $customFolder = null,
        ?int $uploaderId = null,
        ?int $licenseId = null
    ): array {
        $schoolModel = is_int($school) ? School::find($school) : $school;
        $schoolId = $schoolModel?->id;

        // Ambil info ukuran dan nama
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

        // 1. Validasi kuota jika sekolah atau lisensi memiliki batasan kuota
        if ($licenseId && !$this->checkLicenseQuota($licenseId, $size)) {
            $license = License::find($licenseId);
            $stats = $this->getLicenseStorageStats($license);
            throw new StorageQuotaExceededException(
                "Kapasitas cloud storage lisensi '{$license->client_name}' telah penuh ({$stats['used_human']} dari {$stats['quota_human']}). Silakan hubungi Super Admin untuk menambah kuota.",
                $stats['used_bytes'],
                $stats['quota_bytes'],
                $size
            );
        } elseif ($schoolModel && !$this->checkSchoolQuota($schoolModel, $size)) {
            $stats = $this->getSchoolStorageStats($schoolModel);
            throw new StorageQuotaExceededException(
                "Kapasitas penyimpanan sekolah '{$schoolModel->name}' telah penuh ({$stats['used_human']} dari {$stats['quota_human']}). Silakan hubungi Super Admin untuk menambah kuota.",
                $stats['used_bytes'],
                $stats['quota_bytes'],
                $size
            );
        }

        // 2. Tentukan struktur path di bucket R2
        $folderPrefix = $customFolder ?: match ($category) {
            'kbm'           => 'kbm/' . ($schoolId ? "sch_{$schoolId}" : ($licenseId ? "lic_{$licenseId}" : 'general')) . '/' . date('Y/m'),
            'face'          => 'faces/' . ($schoolId ? "sch_{$schoolId}" : ($licenseId ? "lic_{$licenseId}" : 'general')),
            'siswa'         => 'siswa/' . ($schoolId ? "sch_{$schoolId}" : ($licenseId ? "lic_{$licenseId}" : 'general')),
            'guru'          => 'guru/' . ($schoolId ? "sch_{$schoolId}" : ($licenseId ? "lic_{$licenseId}" : 'general')),
            'attendance'    => 'attendance/' . ($schoolId ? "sch_{$schoolId}" : ($licenseId ? "lic_{$licenseId}" : 'general')) . '/' . date('Y/m'),
            'pelanggaran'   => 'pelanggaran/' . ($schoolId ? "sch_{$schoolId}" : 'general') . '/' . date('Y/m'),
            'penanganan_bk' => 'penanganan_bk/' . ($schoolId ? "sch_{$schoolId}" : 'general') . '/' . date('Y/m'),
            'surat_izin'    => 'surat_izin/' . ($schoolId ? "sch_{$schoolId}" : 'general') . '/' . date('Y/m'),
            'leaves'        => 'surat_izin/' . ($schoolId ? "sch_{$schoolId}" : 'general') . '/' . date('Y/m'),
            'logo'          => 'schools/logos',
            default         => 'uploads/' . date('Y/m'),
        };

        // Otomatis kompresi foto kamera HP sebelum upload ke Cloudflare R2
        $isTempFile = false;
        if (str_starts_with($mimeType, 'image/')) {
            [$realPath, $size, $mimeType, $isTempFile] = $this->compressImageIfNeeded($realPath, $mimeType);
            if ($isTempFile) {
                $extension = 'jpg';
            }
        }

        $uniqueName = Str::random(24) . '_' . time() . '.' . $extension;
        $cloudPath = trim($folderPrefix, '/') . '/' . $uniqueName;

        // 3. Upload stream ke Cloudflare R2
        $stream = fopen($realPath, 'r');
        $uploaded = Storage::disk($this->disk)->put($cloudPath, $stream, [
            'visibility'   => 'public',
            'mimetype'     => $mimeType,
            'ContentType'  => $mimeType,
        ]);
        if (is_resource($stream)) {
            fclose($stream);
        }

        // Hapus file temporary kompresi jika ada
        if ($isTempFile && file_exists($realPath)) {
            @unlink($realPath);
        }

        if (!$uploaded) {
            throw new \RuntimeException("Gagal mengunggah berkas ke Cloudflare R2 ({$cloudPath}).");
        }

        // 4. Bangun Public CDN URL
        $publicUrl = $this->baseUrl . '/' . ltrim($cloudPath, '/');

        // 5. Simpan catatan ke tabel storage_files
        $storageFile = StorageFile::create([
            'school_id'     => $schoolId,
            'license_id'    => $licenseId,
            'disk'          => $this->disk,
            'path'          => $cloudPath,
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
            'path'          => $cloudPath,
            'url'           => $publicUrl,
            'view_url'      => $publicUrl,
            'download_url'  => $publicUrl,
            'thumbnail_url' => $isVideo ? null : $publicUrl,
            'type'          => $isVideo ? 'video' : 'image',
            'name'          => $originalName,
            'size'          => $size,
            'size_human'    => self::formatBytes($size),
            'mime_type'     => $mimeType,
        ];
    }

    /**
     * Generate S3 Presigned URL untuk Direct Upload dari Client (PWA/Android) langsung ke Cloudflare R2
     * Memotong transit ke server Laravel, 0% beban bandwidth dan RAM di VPS
     *
     * @param School|int|null $school
     * @param string $originalName
     * @param string $mimeType
     * @param int $sizeBytes
     * @param string $category
     * @param string|null $customFolder
     * @param int|null $uploaderId
     * @return array
     * @throws StorageQuotaExceededException
     */
    public function createPresignedUpload(
        School|int|null $school = null,
        string $originalName = 'file.jpg',
        string $mimeType = 'image/jpeg',
        int $sizeBytes = 0,
        string $category = 'kbm',
        ?string $customFolder = null,
        ?int $uploaderId = null,
        ?int $licenseId = null
    ): array {
        $schoolModel = is_int($school) ? School::find($school) : $school;
        $schoolId = $schoolModel?->id;

        // 1. Validasi kuota jika ada estimasi ukuran berkas
        if ($licenseId && $sizeBytes > 0 && !$this->checkLicenseQuota($licenseId, $sizeBytes)) {
            $license = License::find($licenseId);
            $stats = $this->getLicenseStorageStats($license);
            throw new StorageQuotaExceededException(
                "Kapasitas cloud storage lisensi '{$license->client_name}' telah penuh ({$stats['used_human']} dari {$stats['quota_human']}). Silakan hubungi Super Admin untuk menambah kuota.",
                $stats['used_bytes'],
                $stats['quota_bytes'],
                $sizeBytes
            );
        } elseif ($schoolModel && $sizeBytes > 0 && !$this->checkSchoolQuota($schoolModel, $sizeBytes)) {
            $stats = $this->getSchoolStorageStats($schoolModel);
            throw new StorageQuotaExceededException(
                "Kapasitas penyimpanan sekolah '{$schoolModel->name}' telah penuh ({$stats['used_human']} dari {$stats['quota_human']}). Silakan hubungi Super Admin untuk menambah kuota.",
                $stats['used_bytes'],
                $stats['quota_bytes'],
                $sizeBytes
            );
        }

        // 2. Tentukan path unik di Cloudflare R2
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION) ?: 'bin');
        $folderPrefix = $customFolder ?: match ($category) {
            'kbm'        => 'kbm/' . ($schoolId ? "sch_{$schoolId}" : ($licenseId ? "lic_{$licenseId}" : 'general')) . '/' . date('Y/m'),
            'face'       => 'faces/' . ($schoolId ? "sch_{$schoolId}" : ($licenseId ? "lic_{$licenseId}" : 'general')),
            'siswa'      => 'siswa/' . ($schoolId ? "sch_{$schoolId}" : ($licenseId ? "lic_{$licenseId}" : 'general')),
            'guru'       => 'guru/' . ($schoolId ? "sch_{$schoolId}" : ($licenseId ? "lic_{$licenseId}" : 'general')),
            'attendance' => 'attendance/' . ($schoolId ? "sch_{$schoolId}" : ($licenseId ? "lic_{$licenseId}" : 'general')) . '/' . date('Y/m'),
            'logo'       => 'schools/logos',
            default      => 'uploads/' . date('Y/m'),
        };

        $uniqueName = Str::random(24) . '_' . time() . '.' . $extension;
        $cloudPath = trim($folderPrefix, '/') . '/' . $uniqueName;

        // 3. Generate Presigned PUT Request via AWS S3 SDK (+30 menit)
        $s3Client = Storage::disk($this->disk)->getClient();
        $cmd = $s3Client->getCommand('PutObject', [
            'Bucket'      => $this->bucket,
            'Key'         => $cloudPath,
            'ContentType' => $mimeType,
        ]);
        $request = $s3Client->createPresignedRequest($cmd, '+30 minutes');
        $presignedUrl = (string) $request->getUri();

        // 4. Catat ke tabel storage_files
        $publicUrl = $this->baseUrl . '/' . ltrim($cloudPath, '/');
        $storageFile = StorageFile::create([
            'school_id'     => $schoolId,
            'license_id'    => $licenseId,
            'disk'          => $this->disk,
            'path'          => $cloudPath,
            'url'           => $publicUrl,
            'original_name' => $originalName,
            'size_bytes'    => $sizeBytes,
            'mime_type'     => $mimeType,
            'category'      => $category,
            'uploader_id'   => $uploaderId,
        ]);

        $isVideo = str_starts_with($mimeType, 'video/') || in_array($extension, ['mp4', 'mov', 'avi', 'mkv', '3gp', 'webm']);

        return [
            'upload_url'   => $presignedUrl,
            'method'       => 'PUT',
            'headers'      => [
                'Content-Type' => $mimeType,
            ],
            'file_id'      => $storageFile->id,
            'data'         => [
                'id'            => $storageFile->id,
                'path'          => $cloudPath,
                'url'           => $publicUrl,
                'view_url'      => $publicUrl,
                'download_url'  => $publicUrl,
                'thumbnail_url' => $isVideo ? null : $publicUrl,
                'type'          => $isVideo ? 'video' : 'image',
                'name'          => $originalName,
                'size'          => $sizeBytes,
                'mime_type'     => $mimeType,
            ],
        ];
    }

    /**
     * Konfirmasi upload direct selesai untuk mengupdate ukuran berkas sebenarnya
     */
    public function confirmUpload(int $fileId, ?int $actualSizeBytes = null): ?StorageFile
    {
        $file = StorageFile::find($fileId);
        if ($file && $actualSizeBytes !== null && $actualSizeBytes > 0) {
            $file->size_bytes = $actualSizeBytes;
            $file->save();
        }
        return $file;
    }

    /**
     * Hapus berkas dari Cloudflare R2 dan tabel tracking
     */
    public function deleteFileByPath(string $path): bool
    {
        try {
            Storage::disk($this->disk)->delete($path);
            StorageFile::where('path', $path)->delete();
            return true;
        } catch (\Throwable $e) {
            Log::warning("[R2StorageService] Failed to delete file {$path}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hapus berkas berdasarkan URL publiknya
     */
    public function deleteFileByUrl(string $url): bool
    {
        $parsed = parse_url($url, PHP_URL_PATH);
        if (!$parsed) {
            return false;
        }
        $path = ltrim($parsed, '/');
        return $this->deleteFileByPath($path);
    }

    /**
     * Upload file ke Cloudflare R2 dengan fallback otomatis ke storage lokal jika R2 offline/gagal
     */
    public function uploadWithFallback(
        UploadedFile|string $file,
        School|int|null $school = null,
        string $category = 'pelanggaran',
        ?string $customFolder = null,
        ?int $uploaderId = null
    ): string {
        try {
            $uploaded = $this->uploadFile($file, $school, $category, $customFolder, $uploaderId);
            return $uploaded['url'];
        } catch (\Throwable $e) {
            Log::warning("[R2StorageService] R2 upload failed, falling back to local public storage: " . $e->getMessage());
            if ($file instanceof UploadedFile) {
                return $file->store($category, 'public');
            }
            return Storage::disk('public')->putFile($category, $file);
        }
    }

    /**
     * Hapus berkas dengan aman (baik yang tersimpan di Cloudflare R2 maupun lokal disk)
     */
    public function safeDelete(?string $pathOrUrl, string $defaultDisk = 'public'): bool
    {
        if (empty($pathOrUrl)) {
            return false;
        }

        if (str_starts_with($pathOrUrl, 'http')) {
            return $this->deleteFileByUrl($pathOrUrl);
        }

        try {
            if (Storage::disk($defaultDisk)->exists($pathOrUrl)) {
                return Storage::disk($defaultDisk)->delete($pathOrUrl);
            }
        } catch (\Throwable $e) {
            Log::warning("[R2StorageService] Failed to delete local file: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Ambil statistik agregat untuk Super Admin Dashboard
     */
    public function getGlobalStats(): array
    {
        $health = $this->getHealth();
        $isOnline = !empty($health);

        $schools = School::withCount(['siswa', 'guru'])->orderBy('name')->get();
        $licenses = License::orderBy('client_name')->get();

        $totalUsedBytes = (int) StorageFile::sum('size_bytes');
        $totalFiles = StorageFile::count();

        $totalQuotaBytes = 0;
        foreach ($schools as $s) {
            $mb = (int) ($s->storage_quota_mb ?? 1000);
            if ($mb > 0) {
                $totalQuotaBytes += ($mb * 1024 * 1024);
            }
        }
        foreach ($licenses as $l) {
            $mb = (int) ($l->storage_quota_mb ?? 1000);
            if ($mb > 0) {
                $totalQuotaBytes += ($mb * 1024 * 1024);
            }
        }

        $globalPercent = ($totalQuotaBytes > 0) ? min(100, round(($totalUsedBytes / $totalQuotaBytes) * 100, 1)) : 0;

        return [
            'is_online'            => $isOnline,
            'server_url'           => $this->baseUrl,
            'bucket'               => $this->bucket,
            'server_version'       => 'Cloudflare R2 (Global CDN)',
            'server_uptime'        => '99.99% Cloud SLA',
            'max_upload_size'      => '100.00 MB',
            'total_schools'        => $schools->count(),
            'total_dok_kbm_active' => $schools->where('dok_kbm_enabled', true)->count(),
            'total_licenses'       => $licenses->count(),
            'total_used_human'     => self::formatBytes($totalUsedBytes),
            'total_quota_human'    => ($totalQuotaBytes > 0) ? self::formatBytes($totalQuotaBytes) : 'Unlimited',
            'total_used_bytes'     => $totalUsedBytes,
            'total_quota_bytes'    => $totalQuotaBytes,
            'global_usage_percent' => $globalPercent,
            'total_files'          => $totalFiles,
            'health'               => $health,
        ];
    }

    /**
     * Format angka bytes menjadi string yang nyaman dibaca
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / pow(1024, $power);

        return round($value, $precision) . ' ' . $units[$power];
    }

    /**
     * Kompresi gambar kamera HP (Full HD max 1600px, 82% quality, auto-orient EXIF).
     *
     * @return array [realPath, size, mimeType, isTempFile]
     */
    protected function compressImageIfNeeded(string $sourcePath, string $mimeType, int $maxDimension = 1600, int $quality = 82): array
    {
        $fileSize = @filesize($sourcePath) ?: 0;

        // Hanya kompresi jika GD tersedia dan file bertipe JPEG/PNG/WEBP
        if (!extension_loaded('gd') || !in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'])) {
            return [$sourcePath, $fileSize, $mimeType, false];
        }

        // Jika ukuran file sudah sangat kecil (< 150KB), tidak perlu kompresi
        if ($fileSize > 0 && $fileSize < 153600) {
            return [$sourcePath, $fileSize, $mimeType, false];
        }

        try {
            $image = match ($mimeType) {
                'image/jpeg' => @imagecreatefromjpeg($sourcePath),
                'image/png'  => @imagecreatefrompng($sourcePath),
                'image/webp' => @imagecreatefromwebp($sourcePath),
                default      => null,
            };

            if (!$image) {
                return [$sourcePath, $fileSize, $mimeType, false];
            }

            // Auto-rotate berdasarkan EXIF (bila JPEG)
            if ($mimeType === 'image/jpeg' && function_exists('exif_read_data')) {
                $exif = @exif_read_data($sourcePath);
                $orientation = $exif['Orientation'] ?? 1;
                $image = match ($orientation) {
                    3 => imagerotate($image, 180, 0),
                    6 => imagerotate($image, -90, 0),
                    8 => imagerotate($image, 90, 0),
                    default => $image,
                };
            }

            $origWidth = imagesx($image);
            $origHeight = imagesy($image);

            // Hitung dimensi baru jika melebihi batas maksimal (e.g. 1600px)
            if ($origWidth > $maxDimension || $origHeight > $maxDimension) {
                if ($origWidth > $origHeight) {
                    $newWidth = $maxDimension;
                    $newHeight = (int) round(($origHeight / $origWidth) * $maxDimension);
                } else {
                    $newHeight = $maxDimension;
                    $newWidth = (int) round(($origWidth / $origHeight) * $maxDimension);
                }

                $resized = imagecreatetruecolor($newWidth, $newHeight);
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
                imagedestroy($image);
                $image = $resized;
            }

            $tempPath = tempnam(sys_get_temp_dir(), 'r2_cmp_') . '.jpg';
            // Simpan sebagai JPEG dengan quality 82%
            imagejpeg($image, $tempPath, $quality);
            imagedestroy($image);

            $newSize = @filesize($tempPath) ?: 0;
            // Jika kompresi menghasilkan file yang lebih kecil atau sama, gunakan file hasil kompresi
            if ($newSize > 0 && ($fileSize === 0 || $newSize < $fileSize)) {
                return [$tempPath, $newSize, 'image/jpeg', true];
            }

            // Jika tidak lebih kecil, hapus temp dan pakai aslinya
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        } catch (\Throwable $e) {
            \Log::warning('R2 image compression failed: ' . $e->getMessage());
        }

        return [$sourcePath, $fileSize, $mimeType, false];
    }
}
