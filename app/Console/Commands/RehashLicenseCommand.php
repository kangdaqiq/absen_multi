<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RehashLicenseCommand extends Command
{
    protected $signature   = 'license:rehash';
    protected $description = 'Generate file integrity hashes untuk release client (jalankan sebelum build ZIP release)';

    /**
     * File-file yang di-hash (harus sama dengan INTEGRITY_FILES di LicenseService)
     */
    private const FILES = [
        'app/Http/Middleware/CheckLicense.php',
        'app/Http/Middleware/SelfHostedGuard.php',
        'app/Services/LicenseService.php',
    ];

    public function handle(): int
    {
        $this->info('Generating integrity hashes...');
        $this->newLine();

        $hashes = [];
        $failed = false;

        foreach (self::FILES as $relPath) {
            $absPath = base_path($relPath);

            if (!file_exists($absPath)) {
                $this->error("  ✗ File tidak ditemukan: {$relPath}");
                $failed = true;
                continue;
            }

            $hash           = hash_file('sha256', $absPath);
            $hashes[$relPath] = $hash;
            $this->line("  ✓ <fg=green>{$relPath}</> → <fg=yellow>{$hash}</>");
        }

        if ($failed) {
            $this->newLine();
            $this->error('Beberapa file tidak ditemukan. Abort.');
            return self::FAILURE;
        }

        // Simpan ke storage/app/license_integrity.json
        $outputPath = storage_path('app/license_integrity.json');
        file_put_contents($outputPath, json_encode($hashes, JSON_PRETTY_PRINT));

        $this->newLine();
        $this->info("✅ Integrity file disimpan ke: storage/app/license_integrity.json");
        $this->newLine();
        $this->warn('⚠️  PENTING: Sertakan file ini dalam release client (storage/app/license_integrity.json).');
        $this->warn('   Jika file ini tidak ada di server client, integrity check dilewati (mode dev).');
        $this->warn('   Jangan lupa jalankan perintah ini SETIAP KALI ada perubahan pada file yang dijaga!');

        return self::SUCCESS;
    }
}
