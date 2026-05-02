#!/usr/bin/env php
<?php

/**
 * build_release.php
 * Script untuk membuat paket release yang siap dikirim ke client.
 *
 * Cara pakai:
 *   php build_release.php [versi]
 *   php build_release.php v2.4.0
 *
 * Output: dist/absensi-v2.4.0.zip
 */

$version = $argv[1] ?? 'latest';
$distDir = __DIR__ . '/dist';
$buildDir = __DIR__ . '/dist/build';
$zipFile = "{$distDir}/absensi-{$version}.zip";

// ── File/folder yang TIDAK disertakan dalam release ──────────────────────────
$excludes = [
    '.git',
    '.github',
    'node_modules',
    'tests',
    'dist',
    '.env',
    '.env.example',
    '.env.selfhosted.example',
    'storage/logs',
    'storage/app/license_cache.json',
    'build_release.php',
    'phpunit.xml',
    'vite.config.js',
    'package.json',
    'package-lock.json',
    'composer.lock',
    'clean.php',
    'clean3.php',
    'replace-secure-url.php',
    'diagnostic.php',
    'restapi-wa',
    'legacy_src',
    'esp8266_code',
    'docs',
];

echo "======================================================\n";
echo " Absensi Release Builder\n";
echo " Version: {$version}\n";
echo "======================================================\n\n";

// ── 1. Cek npm build sudah dijalankan ────────────────────────────────────────
if (!is_dir(__DIR__ . '/public/build')) {
    echo "❌ ERROR: public/build tidak ada. Jalankan: npm run build\n";
    exit(1);
}

// ── 2. Jalankan license:rehash ────────────────────────────────────────────────
echo "[1/5] Generating integrity hashes...\n";
$output = shell_exec('php ' . __DIR__ . '/artisan license:rehash 2>&1');
echo $output . "\n";

if (!file_exists(__DIR__ . '/storage/app/license_integrity.json')) {
    echo "❌ ERROR: license_integrity.json tidak terbuat.\n";
    exit(1);
}

// ── 3. Buat dir dist ──────────────────────────────────────────────────────────
echo "[2/5] Membersihkan folder dist...\n";
if (is_dir($distDir)) {
    shell_exec("rm -rf {$buildDir}");
}
mkdir($buildDir, 0755, true);

// ── 4. Copy file ──────────────────────────────────────────────────────────────
echo "[3/5] Menyalin file...\n";
copyDir(__DIR__, $buildDir, $excludes);

// ── 5. Buat .env.example untuk client ────────────────────────────────────────
echo "[4/5] Membuat .env.client.example...\n";
$envClient = <<<ENV
APP_NAME="Sistem Absensi"
APP_ENV=production
APP_KEY=                         # ← jalankan: php artisan key:generate
APP_DEBUG=false
APP_URL=http://localhost         # ← ganti dengan IP/domain server
APP_MODE=self_hosted

# ── License ───────────────────────────────────────────────────────────────────
LICENSE_KEY=XXXX-XXXX-XXXX-XXXX         # ← isi dengan key dari provider
LICENSE_SERVER_URL=https://absen.kangdaqiq.com

# ── Database ──────────────────────────────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absen_sell
DB_USERNAME=absen_user
DB_PASSWORD=                     # ← isi password database

# ── Session & Cache ───────────────────────────────────────────────────────────
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database

# ── WhatsApp API (GOWA) ───────────────────────────────────────────────────────
WA_API_BASE_URL=http://localhost:3000
WA_API_USER=admin
WA_API_PASS=changeme

# ── Mail (opsional) ───────────────────────────────────────────────────────────
MAIL_MAILER=log
ENV;

file_put_contents("{$buildDir}/.env.example", $envClient);

// ── 6. ZIP ────────────────────────────────────────────────────────────────────
echo "[5/5] Membuat ZIP...\n";
$currentDir = getcwd();
chdir($distDir);
shell_exec("zip -r absensi-{$version}.zip build/ 2>&1");
chdir($currentDir);

// Cleanup build dir
shell_exec("rm -rf {$buildDir}");

echo "\n";
echo "======================================================\n";
echo " ✅ Release siap: dist/absensi-{$version}.zip\n";
echo "======================================================\n\n";

echo "Checklist sebelum kirim ke client:\n";
echo "  [ ] Pastikan APP_KEY sudah kosong di .env.example\n";
echo "  [ ] Pastikan LICENSE_KEY sudah dibuatkan di panel Super Admin\n";
echo "  [ ] Sertakan INSTALL.md bersama ZIP\n\n";

// ── Helpers ───────────────────────────────────────────────────────────────────
function copyDir(string $src, string $dst, array $excludes): void
{
    $items = scandir($src);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $relPath = ltrim(str_replace(dirname($src) . '/' . basename($src), '', $src . '/' . $item), '/');

        // Cek exclude
        foreach ($excludes as $ex) {
            if (str_starts_with($item, $ex) || str_starts_with(basename($item), $ex)) {
                continue 2;
            }
        }

        $srcPath = $src . '/' . $item;
        $dstPath = $dst . '/' . $item;

        if (is_dir($srcPath)) {
            mkdir($dstPath, 0755, true);
            copyDir($srcPath, $dstPath, $excludes);
        } else {
            copy($srcPath, $dstPath);
        }
    }
}
