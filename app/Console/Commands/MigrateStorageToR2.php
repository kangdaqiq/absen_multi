<?php

namespace App\Console\Commands;

use App\Models\Guru;
use App\Models\School;
use App\Models\Siswa;
use App\Models\StorageFile;
use App\Models\UserFaceData;
use App\Services\R2StorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateStorageToR2 extends Command
{
    protected $signature = 'storage:migrate-to-r2 
                            {--dry-run : Simulasi tanpa mengunggah berkas}
                            {--force : Paksa upload ulang berkas yang sudah ada}';

    protected $description = 'Migrasikan seluruh berkas lokal (foto siswa, guru, master wajah, logo sekolah) ke Cloudflare R2';

    public function handle(R2StorageService $r2Service): int
    {
        $this->info("=== MEMULAI MIGRASI BERKAS KE CLOUDFLARE R2 ===");
        
        $health = $r2Service->getHealth();
        if (!$health) {
            $this->error("Koneksi ke Cloudflare R2 GAGAL. Periksa kredensial di .env");
            return self::FAILURE;
        }

        $this->info("Koneksi Cloudflare R2 Aktif [Bucket: {$health['bucket']}]");
        $this->info("Public CDN URL: {$health['public_url']}");
        $this->newLine();

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn("[DRY RUN] Mode simulasi diaktifkan. Berkas tidak akan benar-benar diunggah.");
        }

        $baseUrl = rtrim(config('filesystems.disks.r2.url') ?: 'https://r2-cloud.jagattech.my.id', '/');

        // 1. Migrasi Foto Siswa
        $this->migrateSiswaPhotos($baseUrl, $dryRun);

        // 2. Migrasi Foto Guru
        $this->migrateGuruPhotos($baseUrl, $dryRun);

        // 3. Migrasi Foto Master Wajah
        $this->migrateFacePhotos($baseUrl, $dryRun);

        // 4. Migrasi Logo Sekolah
        $this->migrateSchoolLogos($baseUrl, $dryRun);

        $this->newLine();
        $this->info("=== MIGRASI SELESAI ===");
        $stats = $r2Service->getGlobalStats();
        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Total Berkas di R2', $stats['total_files']],
                ['Total Kapasitas Terpakai', $stats['total_used_human']],
                ['Total Kuota Dialokasikan', $stats['total_quota_human']],
                ['Persentase Global', $stats['global_usage_percent'] . '%'],
            ]
        );

        return self::SUCCESS;
    }

    protected function migrateSiswaPhotos(string $baseUrl, bool $dryRun): void
    {
        $siswas = Siswa::whereNotNull('foto')
            ->where('foto', '!=', '')
            ->where('foto', 'not like', 'http%')
            ->get();

        $this->info("Memproses Foto Siswa (" . $siswas->count() . " siswa)...");

        $bar = $this->output->createProgressBar($siswas->count());
        $bar->start();

        foreach ($siswas as $siswa) {
            $localRel = ltrim(str_replace(['\\', '/'], '/', $siswa->foto), '/');
            $localPath = storage_path('app/public/' . $localRel);

            if (!file_exists($localPath)) {
                $bar->advance();
                continue;
            }

            $schoolId = $siswa->school_id;
            $cloudPath = "siswa/sch_{$schoolId}/" . basename($localPath);
            $publicUrl = "{$baseUrl}/{$cloudPath}";
            $size = filesize($localPath);
            $mime = mime_content_type($localPath) ?: 'image/jpeg';

            if (!$dryRun) {
                $stream = fopen($localPath, 'r');
                Storage::disk('r2')->put($cloudPath, $stream, [
                    'visibility'  => 'public',
                    'mimetype'    => $mime,
                    'ContentType' => $mime,
                ]);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                StorageFile::updateOrCreate(
                    ['path' => $cloudPath],
                    [
                        'school_id'     => $schoolId,
                        'disk'          => 'r2',
                        'url'           => $publicUrl,
                        'original_name' => basename($localPath),
                        'size_bytes'    => $size,
                        'mime_type'     => $mime,
                        'category'      => 'siswa',
                    ]
                );

                $siswa->foto = $publicUrl;
                $siswa->saveQuietly();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function migrateGuruPhotos(string $baseUrl, bool $dryRun): void
    {
        $gurus = Guru::whereNotNull('foto')
            ->where('foto', '!=', '')
            ->where('foto', 'not like', 'http%')
            ->get();

        $this->info("Memproses Foto Guru (" . $gurus->count() . " guru)...");

        $bar = $this->output->createProgressBar($gurus->count());
        $bar->start();

        foreach ($gurus as $guru) {
            $localRel = ltrim(str_replace(['\\', '/'], '/', $guru->foto), '/');
            $localPath = storage_path('app/public/' . $localRel);

            if (!file_exists($localPath)) {
                $bar->advance();
                continue;
            }

            $schoolId = $guru->school_id;
            $cloudPath = "guru/sch_{$schoolId}/" . basename($localPath);
            $publicUrl = "{$baseUrl}/{$cloudPath}";
            $size = filesize($localPath);
            $mime = mime_content_type($localPath) ?: 'image/jpeg';

            if (!$dryRun) {
                $stream = fopen($localPath, 'r');
                Storage::disk('r2')->put($cloudPath, $stream, [
                    'visibility'  => 'public',
                    'mimetype'    => $mime,
                    'ContentType' => $mime,
                ]);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                StorageFile::updateOrCreate(
                    ['path' => $cloudPath],
                    [
                        'school_id'     => $schoolId,
                        'disk'          => 'r2',
                        'url'           => $publicUrl,
                        'original_name' => basename($localPath),
                        'size_bytes'    => $size,
                        'mime_type'     => $mime,
                        'category'      => 'guru',
                    ]
                );

                $guru->foto = $publicUrl;
                $guru->saveQuietly();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function migrateFacePhotos(string $baseUrl, bool $dryRun): void
    {
        $faces = UserFaceData::whereNotNull('sample_photo_path')
            ->where('sample_photo_path', '!=', '')
            ->where('sample_photo_path', 'not like', 'http%')
            ->get();

        $this->info("Memproses Foto Master Wajah (" . $faces->count() . " data)...");

        $bar = $this->output->createProgressBar($faces->count());
        $bar->start();

        foreach ($faces as $face) {
            $localRel = ltrim(str_replace(['\\', '/'], '/', $face->sample_photo_path), '/');
            $localPath = storage_path('app/public/' . $localRel);

            if (!file_exists($localPath)) {
                $bar->advance();
                continue;
            }

            $schoolId = $face->siswa?->school_id ?? $face->guru?->school_id ?? $face->user?->school_id;
            $cloudPath = "faces/sch_{$schoolId}/" . basename($localPath);
            $publicUrl = "{$baseUrl}/{$cloudPath}";
            $size = filesize($localPath);
            $mime = mime_content_type($localPath) ?: 'image/jpeg';

            if (!$dryRun) {
                $stream = fopen($localPath, 'r');
                Storage::disk('r2')->put($cloudPath, $stream, [
                    'visibility'  => 'public',
                    'mimetype'    => $mime,
                    'ContentType' => $mime,
                ]);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                StorageFile::updateOrCreate(
                    ['path' => $cloudPath],
                    [
                        'school_id'     => $schoolId,
                        'disk'          => 'r2',
                        'url'           => $publicUrl,
                        'original_name' => basename($localPath),
                        'size_bytes'    => $size,
                        'mime_type'     => $mime,
                        'category'      => 'face',
                    ]
                );

                $face->sample_photo_path = $publicUrl;
                $face->saveQuietly();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function migrateSchoolLogos(string $baseUrl, bool $dryRun): void
    {
        $schools = School::whereNotNull('logo')
            ->where('logo', '!=', '')
            ->where('logo', 'not like', 'http%')
            ->get();

        $this->info("Memproses Logo Sekolah (" . $schools->count() . " sekolah)...");

        $bar = $this->output->createProgressBar($schools->count());
        $bar->start();

        foreach ($schools as $school) {
            $localRel = ltrim(str_replace(['\\', '/'], '/', $school->logo), '/');
            $localPath = storage_path('app/public/' . $localRel);

            if (!file_exists($localPath)) {
                $bar->advance();
                continue;
            }

            $cloudPath = "schools/logos/sch_{$school->id}_" . basename($localPath);
            $publicUrl = "{$baseUrl}/{$cloudPath}";
            $size = filesize($localPath);
            $mime = mime_content_type($localPath) ?: 'image/png';

            if (!$dryRun) {
                $stream = fopen($localPath, 'r');
                Storage::disk('r2')->put($cloudPath, $stream, [
                    'visibility'  => 'public',
                    'mimetype'    => $mime,
                    'ContentType' => $mime,
                ]);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                StorageFile::updateOrCreate(
                    ['path' => $cloudPath],
                    [
                        'school_id'     => $school->id,
                        'disk'          => 'r2',
                        'url'           => $publicUrl,
                        'original_name' => basename($localPath),
                        'size_bytes'    => $size,
                        'mime_type'     => $mime,
                        'category'      => 'logo',
                    ]
                );

                $school->logo = $publicUrl;
                $school->saveQuietly();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
}
