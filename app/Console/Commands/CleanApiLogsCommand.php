<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApiLog;
use App\Models\School;
use Carbon\Carbon;

class CleanApiLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'absen:clean-api-logs {--days=7 : Jumlah hari retensi log yang dipertahankan} {--dry-run : Preview tanpa menghapus data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus riwayat log API otomatis, sisakan N hari terakhir di setiap sekolah (default 7 hari)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        if ($days <= 0) {
            $days = 7;
        }

        $isDryRun = (bool) $this->option('dry-run');
        $cutoff = Carbon::now()->subDays($days);

        $this->info("=== Bersihkan Log API Otomatis ===");
        $this->info("Retensi : {$days} hari terakhir");
        $this->info("Batas   : Sebelum " . $cutoff->format('Y-m-d H:i:s'));
        if ($isDryRun) {
            $this->warn("[MODE DRY-RUN] Data tidak akan dihapus.");
        }
        $this->line("");

        $totalDeleted = 0;
        $schools = School::orderBy('name')->get();

        // 1. Bersihkan per sekolah
        foreach ($schools as $school) {
            $query = ApiLog::where('school_id', $school->id)
                ->where('created_at', '<', $cutoff);

            $count = $query->count();
            if ($count > 0) {
                if ($isDryRun) {
                    $this->line("  [DRY-RUN] Sekolah [{$school->name}] (ID: {$school->id}) : {$count} log akan dihapus");
                } else {
                    $deleted = $query->delete();
                    $totalDeleted += $deleted;
                    $this->line("  ✓ Sekolah [{$school->name}] (ID: {$school->id}) : {$deleted} log dihapus");
                }
            }
        }

        // 2. Bersihkan log global/sistem tanpa school_id
        $globalQuery = ApiLog::whereNull('school_id')
            ->where('created_at', '<', $cutoff);

        $globalCount = $globalQuery->count();
        if ($globalCount > 0) {
            if ($isDryRun) {
                $this->line("  [DRY-RUN] Log Sistem / Global (Tanpa Sekolah) : {$globalCount} log akan dihapus");
            } else {
                $deleted = $globalQuery->delete();
                $totalDeleted += $deleted;
                $this->line("  ✓ Log Sistem / Global (Tanpa Sekolah) : {$deleted} log dihapus");
            }
        }

        $this->line("");
        if ($isDryRun) {
            $totalPreview = $globalCount + ApiLog::whereNotNull('school_id')->where('created_at', '<', $cutoff)->count();
            $this->info("Total log yang memenuhi kriteria penghapusan: {$totalPreview} record.");
        } else {
            $this->info("Pembersihan selesai! Total {$totalDeleted} log berhasil dihapus.");
        }

        return 0;
    }
}
