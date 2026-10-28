<?php

namespace App\Console\Commands;

use App\Services\StorageCleanerService;
use Illuminate\Console\Command;

class CleanOrphanStorageFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:clean-orphans
                            {--school= : ID Sekolah tertentu (opsional, kosongkan untuk semua)}
                            {--hours=24 : Berkas dibuat lebih dari X jam lalu (default 24 jam)}
                            {--category=kbm : Kategori berkas (kbm, all, siswa, guru, logo, attendance)}
                            {--dry-run : Pindai & tampilkan daftar sampah tanpa menghapus}
                            {--force : Lewati konfirmasi interaktif saat penghapusan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pindai dan bersihkan berkas sampah / orphan di Cloudflare R2 storage yang tidak terhubung ke jurnal atau data aktif';

    /**
     * Execute the console command.
     */
    public function handle(StorageCleanerService $cleaner): int
    {
        $schoolId = $this->option('school') ? (int) $this->option('school') : null;
        $hours    = (int) ($this->option('hours') ?? 24);
        $category = $this->option('category') ?? 'kbm';
        $dryRun   = $this->option('dry-run');
        $force    = $this->option('force');

        $this->info("=== Pindai Berkas Sampah Storage ===");
        $this->line("Batas Umur  : > {$hours} jam yang lalu");
        $this->line("Kategori    : {$category}");
        if ($schoolId) {
            $this->line("Filter School ID : {$schoolId}");
        }
        $this->newLine();

        $scan = $cleaner->scanOrphanFiles([
            'school_id' => $schoolId,
            'hours'     => $hours,
            'category'  => $category,
        ]);

        if ($scan['total_files'] === 0) {
            $this->info("✔ Bersih! Tidak ditemukan berkas sampah di storage.");
            return Command::SUCCESS;
        }

        $this->warn("Ditemukan {$scan['total_files']} berkas sampah dengan total ukuran {$scan['total_human']}.");
        $this->newLine();

        // Tampilkan ringkasan per sekolah
        if (!empty($scan['school_breakdown'])) {
            $this->table(
                ['School ID', 'Nama Sekolah', 'Total Berkas', 'Ukuran'],
                array_map(fn($s) => [
                    $s['school_id'],
                    $s['school_name'],
                    $s['total_files'],
                    $s['human_bytes']
                ], $scan['school_breakdown'])
            );
            $this->newLine();
        }

        // Tampilkan 10 berkas sampel
        $sampleFiles = array_slice($scan['files'], 0, 10);
        $this->table(
            ['ID', 'Sekolah', 'Nama Berkas', 'Ukuran', 'Waktu Upload', 'Alasan'],
            array_map(fn($f) => [
                $f['id'],
                $f['school_name'],
                \Illuminate\Support\Str::limit($f['original_name'], 30),
                $f['size_human'],
                $f['created_at'],
                \Illuminate\Support\Str::limit($f['reason'], 40),
            ], $sampleFiles)
        );

        if ($scan['total_files'] > 10) {
            $this->comment("...dan " . ($scan['total_files'] - 10) . " berkas lainnya.");
        }
        $this->newLine();

        if ($dryRun) {
            $this->info("Mode --dry-run aktif. Tidak ada berkas yang dihapus.");
            return Command::SUCCESS;
        }

        if (!$force) {
            if (!$this->confirm("Apakah Anda yakin ingin MENGHAPUS secara permanen {$scan['total_files']} berkas ({$scan['total_human']}) dari Cloudflare R2?", false)) {
                $this->comment("Pembersihan dibatalkan.");
                return Command::SUCCESS;
            }
        }

        $this->info("Sedang membersihkan berkas sampah dari Cloudflare R2...");
        $res = $cleaner->purgeOrphanFiles([
            'school_id' => $schoolId,
            'hours'     => $hours,
            'category'  => $category,
        ]);

        $this->info("✔ {$res['message']}");
        return Command::SUCCESS;
    }
}
