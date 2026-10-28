<?php

namespace App\Services;

use App\Models\JurnalPelajaran;
use App\Models\School;
use App\Models\StorageFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StorageCleanerService
{
    protected R2StorageService $r2Service;

    public function __construct(R2StorageService $r2Service)
    {
        $this->r2Service = $r2Service;
    }

    /**
     * Pindai dan temukan seluruh berkas sampah/orphan di storage tanpa menghapusnya (Dry Run).
     *
     * @param array $options
     *   - school_id: int|null
     *   - hours: int (default 24, berkas yang dibuat lebih dari X jam lalu)
     *   - category: string (default 'kbm')
     * @return array
     */
    public function scanOrphanFiles(array $options = []): array
    {
        $schoolId = !empty($options['school_id']) ? (int) $options['school_id'] : null;
        $hours    = isset($options['hours']) ? max(0, (int) $options['hours']) : 24;
        $category = $options['category'] ?? 'kbm';

        $cutoffTime = Carbon::now()->subHours($hours);

        // 1. Ekstrak seluruh berkas dokumentasi yang benar-benar tersimpan di jurnal pelajaran
        $activeDocReferences = $this->collectActiveJurnalReferences($schoolId);

        // 2. Query storage_files yang berpotensi menjadi sampah (berumur > cutoffTime)
        $query = StorageFile::query()
            ->with('school:id,name,code')
            ->where('created_at', '<=', $cutoffTime);

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        if ($category !== 'all') {
            $query->where('category', $category);
        }

        $candidates = $query->orderBy('created_at', 'desc')->get();

        $orphanFiles = [];
        $totalBytes = 0;
        $schoolBreakdown = [];
        $categoryBreakdown = [];

        foreach ($candidates as $file) {
            $isOrphan = false;
            $reason = '';

            if ($file->category === 'kbm') {
                if (!$this->isKbmFileUsed($file, $activeDocReferences)) {
                    $isOrphan = true;
                    $reason = 'Dokumentasi KBM di-upload tapi tidak pernah disimpan atau telah dihapus dari jurnal.';
                }
            } else {
                // Untuk kategori selain kbm jika category = 'all'
                $isOrphan = $this->checkOtherCategoryOrphan($file, $reason);
            }

            if ($isOrphan) {
                $fileBytes = (int) $file->size_bytes;
                $totalBytes += $fileBytes;

                $schId = $file->school_id ?? 0;
                $schName = $file->school?->name ?? 'Tanpa Sekolah / Global';

                if (!isset($schoolBreakdown[$schId])) {
                    $schoolBreakdown[$schId] = [
                        'school_id'   => $schId,
                        'school_name' => $schName,
                        'total_files' => 0,
                        'total_bytes' => 0,
                        'human_bytes' => '0 B',
                    ];
                }
                $schoolBreakdown[$schId]['total_files']++;
                $schoolBreakdown[$schId]['total_bytes'] += $fileBytes;
                $schoolBreakdown[$schId]['human_bytes'] = R2StorageService::formatBytes($schoolBreakdown[$schId]['total_bytes']);

                $cat = $file->category ?: 'general';
                if (!isset($categoryBreakdown[$cat])) {
                    $categoryBreakdown[$cat] = [
                        'category'    => $cat,
                        'label'       => $this->getCategoryLabel($cat),
                        'total_files' => 0,
                        'total_bytes' => 0,
                        'human_bytes' => '0 B',
                    ];
                }
                $categoryBreakdown[$cat]['total_files']++;
                $categoryBreakdown[$cat]['total_bytes'] += $fileBytes;
                $categoryBreakdown[$cat]['human_bytes'] = R2StorageService::formatBytes($categoryBreakdown[$cat]['total_bytes']);

                $orphanFiles[] = [
                    'id'            => $file->id,
                    'school_id'     => $file->school_id,
                    'school_name'   => $schName,
                    'disk'          => $file->disk,
                    'path'          => $file->path,
                    'url'           => $file->url,
                    'original_name' => $file->original_name ?: basename($file->path),
                    'size_bytes'    => $fileBytes,
                    'size_human'    => R2StorageService::formatBytes($fileBytes),
                    'mime_type'     => $file->mime_type,
                    'category'      => $file->category,
                    'category_label'=> $this->getCategoryLabel($file->category),
                    'created_at'    => $file->created_at ? $file->created_at->format('Y-m-d H:i') : null,
                    'age_human'     => $file->created_at ? $file->created_at->diffForHumans() : '-',
                    'reason'        => $reason,
                ];
            }
        }

        return [
            'total_files'        => count($orphanFiles),
            'total_bytes'        => $totalBytes,
            'total_human'        => R2StorageService::formatBytes($totalBytes),
            'cutoff_hours'       => $hours,
            'cutoff_time'        => $cutoffTime->toDateTimeString(),
            'school_breakdown'   => array_values($schoolBreakdown),
            'category_breakdown' => array_values($categoryBreakdown),
            'files'              => $orphanFiles,
        ];
    }

    /**
     * Eksekusi pembersihan/penghapusan berkas sampah dari Cloudflare R2 dan tabel storage_files.
     *
     * @param array $options
     *   - school_id: int|null
     *   - file_ids: array|null (opsional jika hanya menghapus berkas tertentu yang dicentang)
     *   - hours: int (default 24)
     *   - category: string (default 'kbm')
     * @return array
     */
    public function purgeOrphanFiles(array $options = []): array
    {
        // 1. Jalankan scan terlebih dahulu untuk memverifikasi berkas yang benar-benar orphan
        $scanResult = $this->scanOrphanFiles($options);
        $verifiedOrphans = collect($scanResult['files'])->keyBy('id');

        if ($verifiedOrphans->isEmpty()) {
            return [
                'success'       => true,
                'deleted_files' => 0,
                'freed_bytes'   => 0,
                'freed_human'   => '0 B',
                'message'       => 'Tidak ada berkas sampah yang perlu dibersihkan.',
            ];
        }

        // 2. Filter berdasarkan file_ids jika ada pilihan selektif dari user
        if (!empty($options['file_ids']) && is_array($options['file_ids'])) {
            $selectedIds = array_map('intval', $options['file_ids']);
            $targets = $verifiedOrphans->filter(fn($item) => in_array($item['id'], $selectedIds));
        } else {
            $targets = $verifiedOrphans;
        }

        if ($targets->isEmpty()) {
            return [
                'success'       => true,
                'deleted_files' => 0,
                'freed_bytes'   => 0,
                'freed_human'   => '0 B',
                'message'       => 'Tidak ada berkas yang cocok dengan filter yang dipilih.',
            ];
        }

        $deletedCount = 0;
        $freedBytes = 0;
        $failedFiles = [];

        foreach ($targets as $item) {
            $path = ltrim($item['path'], '/');
            $disk = $item['disk'] ?: 'r2';

            $physicalDeleted = true;
            try {
                if (Storage::disk($disk)->exists($path)) {
                    $physicalDeleted = Storage::disk($disk)->delete($path);
                }
            } catch (\Throwable $e) {
                Log::warning("[StorageCleanerService] Gagal menghapus file fisik {$path}: " . $e->getMessage());
                $physicalDeleted = false;
            }

            // Hapus record dari database storage_files (bahkan jika di R2 sudah tidak ada agar database sinkron)
            try {
                StorageFile::where('id', $item['id'])->delete();
                $deletedCount++;
                $freedBytes += (int) $item['size_bytes'];
            } catch (\Throwable $e) {
                Log::error("[StorageCleanerService] Gagal menghapus record DB ID {$item['id']}: " . $e->getMessage());
                $failedFiles[] = [
                    'id'   => $item['id'],
                    'name' => $item['original_name'],
                    'error'=> $e->getMessage(),
                ];
            }
        }

        return [
            'success'       => true,
            'deleted_files' => $deletedCount,
            'freed_bytes'   => $freedBytes,
            'freed_human'   => R2StorageService::formatBytes($freedBytes),
            'failed_files'  => $failedFiles,
            'message'       => "Berhasil membersihkan {$deletedCount} berkas sampah (" . R2StorageService::formatBytes($freedBytes) . ") dari storage.",
        ];
    }

    /**
     * Kumpulkan seluruh referensi URL, path, dan ID dari tabel jurnal_pelajaran yang masih aktif.
     */
    protected function collectActiveJurnalReferences(?int $schoolId = null): array
    {
        $query = JurnalPelajaran::query()
            ->whereNotNull('dokumentasi')
            ->where('dokumentasi', '!=', '[]')
            ->where('dokumentasi', '!=', '')
            ->where('dokumentasi', '!=', '""');

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $rawDokList = $query->pluck('dokumentasi');

        $usedUrls      = [];
        $usedPaths     = [];
        $usedBasenames = [];
        $usedIds       = [];

        foreach ($rawDokList as $raw) {
            $items = is_string($raw) ? json_decode($raw, true) : $raw;
            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $item) {
                if (is_string($item)) {
                    $this->registerReference($item, null, $usedUrls, $usedPaths, $usedBasenames, $usedIds);
                } elseif (is_array($item)) {
                    $url = $item['url'] ?? ($item['view_url'] ?? ($item['download_url'] ?? null));
                    $id  = $item['id'] ?? null;
                    $this->registerReference($url, $id, $usedUrls, $usedPaths, $usedBasenames, $usedIds);
                }
            }
        }

        return [
            'urls'      => $usedUrls,
            'paths'     => $usedPaths,
            'basenames' => $usedBasenames,
            'ids'       => $usedIds,
        ];
    }

    /**
     * Daftarkan item referensi ke lookup map
     */
    protected function registerReference(?string $url, ?int $id, array &$usedUrls, array &$usedPaths, array &$usedBasenames, array &$usedIds): void
    {
        if ($id) {
            $usedIds[(int) $id] = true;
        }

        if (empty($url)) {
            return;
        }

        // URL murni tanpa query string
        $pureUrl = strtok($url, '?');
        $usedUrls[strtolower(trim($pureUrl))] = true;

        $path = parse_url($pureUrl, PHP_URL_PATH);
        if ($path) {
            $cleanPath = ltrim($path, '/');
            $usedPaths[strtolower($cleanPath)] = true;

            $base = basename($cleanPath);
            if (!empty($base)) {
                $usedBasenames[strtolower($base)] = true;
            }
        }
    }

    /**
     * Cek apakah berkas KBM dipakai di jurnal
     */
    protected function isKbmFileUsed(StorageFile $file, array $activeRefs): bool
    {
        // 1. Cek ID berkas
        if (!empty($activeRefs['ids'][$file->id])) {
            return true;
        }

        // 2. Cek URL berkas
        if ($file->url) {
            $pureUrl = strtolower(trim(strtok($file->url, '?')));
            if (!empty($activeRefs['urls'][$pureUrl])) {
                return true;
            }
        }

        // 3. Cek Path berkas
        if ($file->path) {
            $cleanPath = strtolower(ltrim($file->path, '/'));
            if (!empty($activeRefs['paths'][$cleanPath])) {
                return true;
            }

            $base = strtolower(basename($cleanPath));
            if (!empty($activeRefs['basenames'][$base])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cek orphan untuk kategori lain (Siswa, Guru, Face, Attendance, Logo)
     */
    protected function checkOtherCategoryOrphan(StorageFile $file, string &$reason): bool
    {
        $cleanPath = ltrim($file->path, '/');
        $pureUrl = strtok($file->url, '?');

        switch ($file->category) {
            case 'siswa':
                $inUse = \App\Models\Siswa::where('foto', 'like', "%{$cleanPath}%")
                    ->orWhere('foto', 'like', "%{$pureUrl}%")
                    ->exists();
                if (!$inUse) {
                    $reason = 'Foto siswa lama yang diganti atau siswa telah dihapus.';
                    return true;
                }
                break;

            case 'guru':
                $inUse = \App\Models\Guru::where('foto', 'like', "%{$cleanPath}%")
                    ->orWhere('foto', 'like', "%{$pureUrl}%")
                    ->exists();
                if (!$inUse) {
                    $reason = 'Foto guru lama yang diganti atau guru telah dihapus.';
                    return true;
                }
                break;

            case 'logo':
                $inUse = School::where('logo', 'like', "%{$cleanPath}%")
                    ->orWhere('logo', 'like', "%{$pureUrl}%")
                    ->exists();
                if (!$inUse) {
                    $reason = 'Logo sekolah lama yang telah diganti.';
                    return true;
                }
                break;

            case 'attendance':
                $inUse = \App\Models\Attendance::where('photo_path', 'like', "%{$cleanPath}%")
                    ->orWhere('selfie_path', 'like', "%{$cleanPath}%")
                    ->exists();
                if (!$inUse) {
                    $reason = 'Foto presensi yang record-nya telah dihapus.';
                    return true;
                }
                break;
        }

        return false;
    }

    /**
     * Label manusiawi untuk kategori berkas
     */
    protected function getCategoryLabel(?string $cat): string
    {
        return match ($cat) {
            'kbm'        => 'Dokumentasi KBM',
            'siswa'      => 'Foto Siswa',
            'guru'       => 'Foto Guru',
            'face'       => 'Biometrik Wajah',
            'attendance' => 'Foto Presensi',
            'logo'       => 'Logo Sekolah',
            default      => 'Berkas Umum',
        };
    }
}
