<?php

/**
 * ============================================================
 * Build Release Client Script
 * ============================================================
 * Membuat folder release-client/ siap kirim ke client.
 *
 * Cara pakai:
 *   php build_release.php [versi]
 *   php build_release.php v2.4.0
 *
 * Output:
 *   release-client/        ← folder siap kirim
 *   release-client.zip     ← ZIP siap kirim
 * ============================================================
 */

$version    = $argv[1] ?? 'latest';
$srcDir     = __DIR__;
$releaseDir = $srcDir . '/release-client';
$zipFile    = $srcDir . '/release-client.zip';

// ── File/folder yang TIDAK disertakan dalam release ──────────────────────────
$excludes = [
    '.git',
    '.github',
    'node_modules',
    'tests',
    'release-client',
    'release_client',
    'dist',
    '.env',
    'build_release.php',
    'phpunit.xml',
    'vite.config.js',
    'package.json',
    'package-lock.json',
    'clean.php',
    'clean3.php',
    'replace-secure-url.php',
    'diagnostic.php',
    'restapi-wa',
    'legacy_src',
    'esp8266_code',
    'docs',
    // Storage dirs yang tidak perlu
    'storage/logs',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/app/license_cache.json',
];

// ── Storage items yang WAJIB ada tapi bisa dikosongkan ───────────────────────
$emptyDirs = [
    'storage/logs',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/app/public',
    'bootstrap/cache',
];

echo "\n";
echo "╔══════════════════════════════════════════════════════╗\n";
echo "║         Absensi — Build Release Client               ║\n";
echo "║         Versi: {$version}\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

// ── Step 1: Pastikan npm build sudah dijalankan ───────────────────────────────
echo "[1/5] Cek public/build...\n";
if (!is_dir($srcDir . '/public/build')) {
    die("❌ ERROR: public/build tidak ada. Jalankan: npm run build\n\n");
}
echo "      ✓ public/build ada.\n\n";

// ── Step 2: Generate integrity hash ──────────────────────────────────────────
echo "[2/5] Generating integrity hash (license:rehash)...\n";
$output = shell_exec('php ' . $srcDir . '/artisan license:rehash 2>&1');
echo $output . "\n";

if (!file_exists($srcDir . '/storage/app/license_integrity.json')) {
    die("❌ ERROR: license_integrity.json tidak terbuat. Pastikan artisan bisa dijalankan.\n\n");
}
echo "      ✓ Integrity hash siap.\n\n";

// ── Step 3: Bersihkan dan buat folder release-client ─────────────────────────
echo "[3/5] Menyiapkan folder release-client...\n";
if (is_dir($releaseDir)) {
    echo "      Menghapus folder lama...\n";
    deleteDir($releaseDir);
}
mkdir($releaseDir, 0755, true);

// Copy semua file kecuali yang di-exclude
copyDir($srcDir, $releaseDir, $excludes);
echo "      ✓ File disalin.\n\n";

// Buat empty dirs yang dibutuhkan Laravel
echo "[3b] Membuat direktori kosong yang dibutuhkan...\n";
foreach ($emptyDirs as $dir) {
    $fullPath = $releaseDir . '/' . $dir;
    if (!is_dir($fullPath)) {
        mkdir($fullPath, 0755, true);
    }
    // Tambahkan .gitkeep
    file_put_contents($fullPath . '/.gitkeep', '');
}
echo "      ✓ Direktori kosong dibuat.\n\n";

// ── Step 4: Buat .env.example untuk client ────────────────────────────────────
echo "[4/5] Membuat .env.example...\n";
$envContent = <<<ENV
APP_NAME="Sistem Absensi"
APP_ENV=production
APP_KEY=                                # ← WAJIB: jalankan: php artisan key:generate
APP_DEBUG=false
APP_URL=http://localhost                 # ← ganti dengan IP/domain server kamu
APP_MODE=self_hosted

# ── License ───────────────────────────────────────────────────────────────────
LICENSE_KEY=XXXX-XXXX-XXXX-XXXX        # ← isi dengan license key dari provider
LICENSE_SERVER_URL=https://absen.kangdaqiq.com

# ── Database ──────────────────────────────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absen_sell
DB_USERNAME=absen_user
DB_PASSWORD=                            # ← isi password database

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

file_put_contents($releaseDir . '/.env.example', $envContent);
echo "      ✓ .env.example dibuat.\n\n";

// ── Step 5: Buat ZIP ──────────────────────────────────────────────────────────
echo "[5/5] Membuat ZIP...\n";
if (file_exists($zipFile)) {
    unlink($zipFile);
}

$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
    die("❌ ERROR: Tidak bisa membuat ZIP file.\n");
}

addDirToZip($zip, $releaseDir, 'release-client');
$zip->close();

$sizeMb = round(filesize($zipFile) / 1024 / 1024, 1);
echo "      ✓ ZIP dibuat: release-client.zip ({$sizeMb} MB)\n\n";

// ── Selesai ───────────────────────────────────────────────────────────────────
echo "╔══════════════════════════════════════════════════════╗\n";
echo "║  ✅ Build selesai!                                   ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";
echo "Output:\n";
echo "  📁 release-client/          ← folder siap kirim\n";
echo "  📦 release-client.zip       ← ZIP siap kirim\n\n";
echo "Checklist sebelum kirim ke client:\n";
echo "  [ ] .env.example sudah ada dan APP_KEY kosong\n";
echo "  [ ] LICENSE_KEY sudah dibuat di panel Super Admin\n";
echo "  [ ] INSTALL.md sudah ada di dalam release-client/\n";
echo "  [ ] storage/app/license_integrity.json ada di dalam release-client/\n\n";
echo "Verifikasi integrity:\n";
echo "  php " . $srcDir . "/artisan license:rehash\n\n";

// ── Helper Functions ──────────────────────────────────────────────────────────

function copyDir(string $src, string $dst, array $excludes): void
{
    if (!is_dir($src)) return;

    $items = scandir($src);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        // Cek exclude (nama file/folder)
        foreach ($excludes as $ex) {
            $exBase = basename($ex);
            if ($item === $exBase || $item === $ex) {
                continue 2;
            }
        }

        $srcPath = $src . '/' . $item;
        $dstPath = $dst . '/' . $item;

        if (is_dir($srcPath)) {
            // Cek apakah path relatif dari src dir di-exclude
            $relPath = ltrim(str_replace(__DIR__, '', $srcPath), '/\\');
            foreach ($excludes as $ex) {
                if ($relPath === $ex || str_starts_with($relPath, $ex . '/') || str_starts_with($relPath, $ex . '\\')) {
                    continue 2;
                }
            }
            mkdir($dstPath, 0755, true);
            copyDir($srcPath, $dstPath, $excludes);
        } else {
            copy($srcPath, $dstPath);
        }
    }
}

function deleteDir(string $dir): void
{
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            deleteDir($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

function addDirToZip(ZipArchive $zip, string $dir, string $zipPath): void
{
    $zip->addEmptyDir($zipPath);
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $fullPath = $dir . '/' . $item;
        $zipItemPath = $zipPath . '/' . $item;
        if (is_dir($fullPath)) {
            addDirToZip($zip, $fullPath, $zipItemPath);
        } else {
            $zip->addFile($fullPath, $zipItemPath);
        }
    }
}
