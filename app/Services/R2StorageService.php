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
        return (int) StorageFile::where('license_id', $licenseId)->sum('size_bytes');
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
        ?int $uploaderId = null
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

        // 1. Validasi kuota jika sekolah memiliki batasan kuota
        if ($schoolModel && !$this->checkSchoolQuota($schoolModel, $size)) {
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
            'kbm'        => 'kbm/' . ($schoolId ? "sch_{$schoolId}" : 'general') . '/' . date('Y/m'),
            'face'       => 'faces/' . ($schoolId ? "sch_{$schoolId}" : 'general'),
            'siswa'      => 'siswa/' . ($schoolId ? "sch_{$schoolId}" : 'general'),
            'guru'       => 'guru/' . ($schoolId ? "sch_{$schoolId}" : 'general'),
            'attendance' => 'attendance/' . ($schoolId ? "sch_{$schoolId}" : 'general') . '/' . date('Y/m'),
            'logo'       => 'schools/logos',
            default      => 'uploads/' . date('Y/m'),
        };

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

        if (!$uploaded) {
            throw new \RuntimeException("Gagal mengunggah berkas ke Cloudflare R2 ({$cloudPath}).");
        }

        // 4. Bangun Public CDN URL
        $publicUrl = $this->baseUrl . '/' . ltrim($cloudPath, '/');

        // 5. Simpan catatan ke tabel storage_files
        $storageFile = StorageFile::create([
            'school_id'     => $schoolId,
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
}
