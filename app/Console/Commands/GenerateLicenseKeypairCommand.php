<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateLicenseKeypairCommand extends Command
{
    protected $signature   = 'license:keygen {--force : Timpa keypair Ed25519 yang sudah ada}';
    protected $description = 'Generate pasangan kunci kriptografi Ed25519 (Private & Public Key) untuk sistem lisensi';

    public function handle(): int
    {
        $existingPrivate = config('app.license_private_key');
        if (!empty($existingPrivate) && !$this->option('force')) {
            $this->warn('⚠️  LICENSE_PRIVATE_KEY sudah ada di konfigurasi (.env).');
            $this->line('   Gunakan opsi <fg=yellow>--force</> jika ingin membuat ulang keypair baru.');
            $this->line('   Catatan: Mengganti keypair mengharuskan build ulang release client.');
            return self::FAILURE;
        }

        $this->info('Generating Ed25519 keypair...');

        if (function_exists('sodium_crypto_sign_keypair')) {
            $keypair   = sodium_crypto_sign_keypair();
            $secretKey = sodium_crypto_sign_secretkey($keypair);
            $publicKey = sodium_crypto_sign_publickey($keypair);
        } else {
            $keypair   = \ParagonIE_Sodium_Compat::crypto_sign_keypair();
            $secretKey = \ParagonIE_Sodium_Compat::crypto_sign_secretkey($keypair);
            $publicKey = \ParagonIE_Sodium_Compat::crypto_sign_publickey($keypair);
        }

        $privBase64 = base64_encode($secretKey);
        $pubBase64  = base64_encode($publicKey);

        // 1. Simpan ke .env
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $env = file_get_contents($envPath);
            $env = $this->setEnvValue($env, 'LICENSE_PRIVATE_KEY', $privBase64);
            $env = $this->setEnvValue($env, 'LICENSE_PUBLIC_KEY', $pubBase64);
            file_put_contents($envPath, $env);
            $this->line("  ✓ <fg=green>.env</> diperbarui dengan LICENSE_PRIVATE_KEY & LICENSE_PUBLIC_KEY");
        } else {
            $this->warn('  ! File .env tidak ditemukan. Pastikan Anda menambahkan key berikut ke .env server pusat:');
            $this->line("    LICENSE_PRIVATE_KEY={$privBase64}");
            $this->line("    LICENSE_PUBLIC_KEY={$pubBase64}");
        }

        // Bersihkan config cache jika ada agar .env baru terbaca di command berikutnya
        \Illuminate\Support\Facades\Artisan::call('config:clear');

        // Set runtime config agar command lain di proses yang sama langsung membaca key baru
        config([
            'app.license_private_key' => $privBase64,
            'app.license_public_key'  => $pubBase64,
        ]);

        // 2. Tanamkan PUBLIC_KEY ke dalam LicenseService.php
        $servicePath = base_path('app/Services/LicenseService.php');
        if (file_exists($servicePath)) {
            $serviceCode = file_get_contents($servicePath);
            $updatedCode = preg_replace(
                "/public const PUBLIC_KEY\s*=\s*'[^']*';/",
                "public const PUBLIC_KEY = '{$pubBase64}';",
                $serviceCode,
                1,
                $count
            );

            if ($count > 0 && $updatedCode !== null) {
                file_put_contents($servicePath, $updatedCode);
                $this->line("  ✓ <fg=green>app/Services/LicenseService.php</> PUBLIC_KEY diperbarui");
            }
        }

        $this->newLine();
        $this->info('✅ Ed25519 Keypair berhasil dibuat!');
        $this->line("   Public Key : <fg=cyan>{$pubBase64}</>");
        $this->newLine();

        // 3. Otomatis jalankan license:rehash agar integrity file ditandatangani dengan key baru
        $this->call('license:rehash');

        return self::SUCCESS;
    }

    private function setEnvValue(string $envContent, string $key, string $value): string
    {
        if (preg_match("/^{$key}=.*$/m", $envContent)) {
            return (string) preg_replace("/^{$key}=.*$/m", "{$key}={$value}", $envContent);
        }

        return rtrim($envContent) . "\n{$key}={$value}\n";
    }
}
