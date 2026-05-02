<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LicenseService;

class ValidateLicenseCommand extends Command
{
    protected $signature   = 'license:validate {--force : Clear cache before validating}';
    protected $description = 'Validate and refresh the license from the license server';

    public function __construct(private LicenseService $licenseService) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (config('app.mode', 'hosted') !== 'self_hosted') {
            $this->info('Mode: hosted — license validation tidak diperlukan.');
            return 0;
        }

        if ($this->option('force')) {
            $this->licenseService->clearCache();
            $this->info('Cache lisensi dihapus. Memvalidasi ulang...');
        }

        $this->info('Memvalidasi lisensi ke server...');
        $result = $this->licenseService->validate();

        if ($result['valid']) {
            $this->info('✅ Lisensi VALID');
            $this->line("   Klien     : {$result['client_name']}");
            $this->line("   Berlaku s/d: {$result['expired_at']}");
            $this->line("   Max Sekolah: " . ($result['max_schools'] === 0 ? 'Unlimited' : $result['max_schools']));
            $this->line("   Max Siswa  : " . ($result['max_students'] === 0 ? 'Unlimited' : $result['max_students']));
            $this->line("   Max Guru   : " . (($result['max_teachers'] ?? 0) === 0 ? 'Unlimited' : $result['max_teachers']));
            $this->line("   Max Bot    : " . (($result['max_bot_users'] ?? 0) === 0 ? 'Unlimited' : $result['max_bot_users']));
        } elseif ($result['expired']) {
            $this->error('⛔ Lisensi EXPIRED: ' . $result['message']);
            return 1;
        } else {
            $graceRemaining = $result['grace_remaining_days'] ?? 0;
            if ($graceRemaining > 0) {
                $this->warn("⚠️  {$result['message']}");
            } else {
                $this->error('❌ Lisensi TIDAK VALID: ' . $result['message']);
                return 1;
            }
        }

        return 0;
    }
}
