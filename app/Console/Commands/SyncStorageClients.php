<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\School;
use App\Models\License;
use App\Services\StorageServerService;
use Illuminate\Support\Str;

class SyncStorageClients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:sync-clients {--recalc : Jalankan kalkulasi ulang kapasitas di storage server}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi seluruh sekolah dan client lisensi ke Storage Server multi-user';

    /**
     * Execute the console command.
     */
    public function handle(StorageServerService $storageService)
    {
        $this->info("=== SINKRONISASI CLIENT KE STORAGE SERVER ===");
        $this->info("Storage URL : " . $storageService->getBaseUrl());

        $health = $storageService->getHealth();
        if ($health) {
            $this->info("Status Server: ONLINE (v" . ($health['version'] ?? '1.1.0') . ")");
        } else {
            $this->warn("Status Server: OFFLINE / Unreachable. Kredensial lokal tetap akan digenerate.");
        }

        // 1. Sync Sekolah (Client SaaS)
        $schools = School::all();
        $this->info("\nMemproses {$schools->count()} sekolah...");
        $schoolSynced = 0;

        foreach ($schools as $school) {
            if (empty($school->storage_quota_mb)) {
                $school->storage_quota_mb = 1000;
            }
            if (empty($school->storage_api_key)) {
                $school->storage_api_key = 'sk_sch_' . strtolower(Str::random(24));
            }
            $school->saveQuietly();

            if ($health) {
                $res = $storageService->syncSchoolUser($school);
                if ($res['success']) {
                    $schoolSynced++;
                    $this->line("  [OK] {$school->name} -> Quota: {$school->storage_quota_human} | Key: {$school->storage_api_key}");
                } else {
                    $this->warn("  [FAIL] {$school->name}: " . ($res['message'] ?? 'Error'));
                }
            } else {
                $this->line("  [LOCAL] {$school->name} -> Quota: {$school->storage_quota_human} | Key: {$school->storage_api_key}");
            }
        }

        // 2. Sync Lisensi (Client Self-Hosted)
        $licenses = License::all();
        $this->info("\nMemproses {$licenses->count()} lisensi client...");
        $licenseSynced = 0;

        foreach ($licenses as $license) {
            if (empty($license->storage_quota_mb)) {
                $license->storage_quota_mb = 1000;
            }
            if (empty($license->storage_api_key)) {
                $license->storage_api_key = 'sk_lic_' . strtolower(Str::random(24));
            }
            $license->saveQuietly();

            if ($health) {
                $res = $storageService->syncLicenseUser($license);
                if ($res['success']) {
                    $licenseSynced++;
                    $this->line("  [OK] Lisensi {$license->client_name} -> Quota: {$license->storage_quota_mb} MB | Key: {$license->storage_api_key}");
                } else {
                    $this->warn("  [FAIL] {$license->client_name}: " . ($res['message'] ?? 'Error'));
                }
            } else {
                $this->line("  [LOCAL] Lisensi {$license->client_name} -> Quota: {$license->storage_quota_mb} MB | Key: {$license->storage_api_key}");
            }
        }

        if ($this->option('recalc') && $health) {
            $this->info("\nMenjalankan kalkulasi ulang kapasitas...");
            $recalcRes = $storageService->recalculateAll();
            $this->info("Kalkulasi selesai: " . ($recalcRes['message'] ?? 'OK'));
        }

        $this->info("\n=== SINKRONISASI SELESAI ===");
        return Command::SUCCESS;
    }
}
