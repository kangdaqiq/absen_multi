<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Illuminate\Console\Command;

class RehashLicenseCommand extends Command
{
    protected $signature   = 'license:rehash {--path= : Direktori target release (default: base_path)}';
    protected $description = 'Generate file integrity hashes + Ed25519 signature untuk release client';

    public function handle(): int
    {
        $targetBase = $this->option('path')
            ? rtrim((string) $this->option('path'), '/\\')
            : base_path();

        if (!is_dir($targetBase)) {
            $this->error("Direktori target tidak ditemukan: {$targetBase}");
            return self::FAILURE;
        }

        $this->info('Generating integrity hashes & Ed25519 cryptographic signature...');
        $this->newLine();

        $hashes = [];
        $failed = false;

        foreach (LicenseService::INTEGRITY_FILES as $relPath) {
            $absPath = $targetBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);

            if (!file_exists($absPath)) {
                $this->error("  ✗ File tidak ditemukan: {$relPath}");
                $failed = true;
                continue;
            }

            $hash             = hash_file('sha256', $absPath);
            $hashes[$relPath] = $hash;
            $this->line("  ✓ <fg=green>{$relPath}</> → <fg=yellow>{$hash}</>");
        }

        if ($failed) {
            $this->newLine();
            $this->error('Beberapa file tidak ditemukan. Abort.');
            return self::FAILURE;
        }

        // Tanda tangani daftar hash menggunakan Ed25519 Private Key
        $signature = LicenseService::signPayload($hashes);
        if (empty($signature)) {
            $this->newLine();
            $this->error('❌ Gagal menandatangani integrity file: LICENSE_PRIVATE_KEY belum dikonfigurasi.');
            $this->line('   Jalankan perintah berikut terlebih dahulu: <fg=cyan>php artisan license:keygen</>');
            return self::FAILURE;
        }

        // Ambil PUBLIC_KEY langsung dari file LicenseService.php di target (agar akurat meski baru diubah oleh license:keygen)
        $targetServiceFile = $targetBase . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'LicenseService.php';
        $targetPubKey = null;
        if (file_exists($targetServiceFile)) {
            $serviceContent = (string) file_get_contents($targetServiceFile);
            if (preg_match("/public const PUBLIC_KEY\s*=\s*'([^']*)';/", $serviceContent, $m)) {
                $targetPubKey = $m[1];
            }
        }

        // Pastikan signature cocok dengan PUBLIC_KEY yang tertanam di LicenseService target
        if (!LicenseService::verifyPayload($hashes, $signature, $targetPubKey)) {
            $this->newLine();
            $this->error('❌ Signature Ed25519 tidak cocok dengan LicenseService::PUBLIC_KEY!');
            $this->line('   Jalankan: <fg=cyan>php artisan license:keygen --force</> untuk menyinkronkan keypair.');
            return self::FAILURE;
        }

        $payload = [
            'generated_at' => now()->toIso8601String(),
            'files'        => $hashes,
            '_signature'   => $signature,
        ];

        $outputDir = $targetBase . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app';
        if (!is_dir($outputDir)) {
            @mkdir($outputDir, 0775, true);
        }

        $outputPath = $outputDir . DIRECTORY_SEPARATOR . 'license_integrity.json';
        file_put_contents($outputPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->newLine();
        $this->info("✅ Integrity file + Ed25519 signature disimpan ke: {$outputPath}");
        $this->line("   Signature : <fg=cyan>" . substr($signature, 0, 40) . "...</>");
        $this->newLine();

        return self::SUCCESS;
    }
}
