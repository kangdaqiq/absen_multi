<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use Carbon\Carbon;

class AutoDeleteHistoryCommand extends Command
{
    protected $signature = 'absen:auto-delete-history {--dry-run : Preview what will be deleted without actually deleting}';
    protected $description = 'Auto-delete old attendance history based on per-school history_quota_months setting';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('=== DRY RUN MODE — Tidak ada data yang dihapus ===');
        }

        $this->info('=== Auto Delete History Absen ===');
        $this->info('Waktu: ' . now()->format('Y-m-d H:i:s'));
        $this->info('');

        $schools = School::where('is_active', true)
            ->whereNotNull('history_quota_months')
            ->where('history_quota_months', '>', 0)
            ->get();

        if ($schools->isEmpty()) {
            $this->info('Tidak ada sekolah dengan kuota history yang dikonfigurasi. Selesai.');
            return 0;
        }

        $totalDeletedAttendance = 0;
        $totalDeletedGuruAbsensi = 0;

        foreach ($schools as $school) {
            $this->processSchool($school, $isDryRun, $totalDeletedAttendance, $totalDeletedGuruAbsensi);
        }

        $this->info('');
        $this->info('=== Ringkasan ===');
        $this->info("Total absensi siswa yang dihapus : {$totalDeletedAttendance} record");
        $this->info("Total absensi guru yang dihapus  : {$totalDeletedGuruAbsensi} record");

        if ($isDryRun) {
            $this->warn('=== DRY RUN selesai — tidak ada data yang benar-benar dihapus ===');
        } else {
            $this->info('=== Auto Delete selesai ===');
        }

        return 0;
    }

    private function processSchool(School $school, bool $isDryRun, int &$totalAttendance, int &$totalGuru): void
    {
        $months    = $school->history_quota_months;
        $cutoff    = Carbon::now()->subMonths($months)->format('Y-m-d');
        $schoolId  = $school->id;

        $this->info("Sekolah: [{$school->name}] (ID: {$schoolId}) | Kuota: {$months} bulan | Hapus sebelum: {$cutoff}");

        // ── Absensi Siswa ────────────────────────────────────────────────────
        $studentIds = DB::table('siswa')
            ->where('school_id', $schoolId)
            ->pluck('id');

        $attendanceQuery = DB::table('attendance')
            ->whereIn('student_id', $studentIds)
            ->where('tanggal', '<', $cutoff);

        $countAttendance = $attendanceQuery->count();

        if ($isDryRun) {
            $this->line("  [DRY-RUN] Absensi siswa yang akan dihapus : {$countAttendance} record");
        } else {
            $deleted = $attendanceQuery->delete();
            $this->line("  ✓ Absensi siswa dihapus : {$deleted} record");
            $totalAttendance += $deleted;
        }

        // ── Absensi Guru ─────────────────────────────────────────────────────
        $guruIds = DB::table('guru')
            ->where('school_id', $schoolId)
            ->pluck('id');

        $guruQuery = DB::table('absensi_guru')
            ->whereIn('guru_id', $guruIds)
            ->where('tanggal', '<', $cutoff);

        $countGuru = $guruQuery->count();

        if ($isDryRun) {
            $this->line("  [DRY-RUN] Absensi guru yang akan dihapus   : {$countGuru} record");
        } else {
            $deleted = $guruQuery->delete();
            $this->line("  ✓ Absensi guru dihapus   : {$deleted} record");
            $totalGuru += $deleted;
        }

        $this->line("  ─────────────────────────────────────────");
    }
}
