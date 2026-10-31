<?php

/**
 * ============================================================
 * Build Release Client Script
 * ============================================================
 * Membuat folder release-client/ dan release-client.zip siap kirim ke client.
 *
 * Cara pakai:
 *   php build_release.php [versi]
 *   php build_release.php v2.5.0
 *
 * Output:
 *   release-client/        ← folder siap kirim
 *   release-client.zip     ← ZIP siap kirim
 * ============================================================
 */

$version    = $argv[1] ?? 'v2.5.0';
$srcDir     = __DIR__;
$releaseDir = $srcDir . '/release-client';
$zipFile    = $srcDir . '/release-client.zip';

// ── Path relatif dari root project yang TIDAK disertakan dalam release ──────
$excludes = [
    '.git',
    '.github',
    '.vscode',
    '.idea',
    'node_modules',
    'vendor',
    'tests',
    'release-client',
    'release_client',
    'release-client.zip',
    'jagat_super_app',
    'face_engine/venv',
    'face_engine/.venv',
    'face_engine/__pycache__',
    'dist',
    '.env',
    '.env.backup',
    '.env.production',
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
    'public/storage',
    'public/hot',
    // Storage dirs & files yang tidak perlu dibawa ke client
    'storage/logs',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/app/public',
    'storage/app/private',
    'storage/app/backups',
    'storage/app/license_cache.json',
    'storage/app/license_integrity.json',
    // Command internal developer/server lisensi (tidak boleh ada di client)
    'app/Console/Commands/RehashLicenseCommand.php',
    'app/Console/Commands/GenerateLicenseKeypairCommand.php',
];

// ── Storage items yang WAJIB ada tapi dikosongkan ───────────────────────────
$emptyDirs = [
    'storage/logs',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/app/public',
    'storage/app/private',
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

// ── Step 2: Bersihkan dan buat folder release-client ─────────────────────────
echo "[2/5] Menyiapkan folder release-client...\n";
if (is_dir($releaseDir)) {
    echo "      Menghapus folder lama...\n";
    deleteDir($releaseDir);
}
mkdir($releaseDir, 0755, true);

// Copy semua file kecuali yang di-exclude
copyDir($srcDir, $releaseDir, $srcDir, $excludes);
echo "      ✓ File disalin (termasuk resources/views, app, config, routes, public/build).\n\n";

// Set IS_CLIENT_BUILD = true pada release-client/app/Services/LicenseService.php
$clientLicenseService = $releaseDir . '/app/Services/LicenseService.php';
if (file_exists($clientLicenseService)) {
    $code = file_get_contents($clientLicenseService);
    $code = str_replace(
        'private const IS_CLIENT_BUILD = false;',
        'private const IS_CLIENT_BUILD = true;',
        $code
    );
    file_put_contents($clientLicenseService, $code);
    echo "      ✓ Flag IS_CLIENT_BUILD=true diaktifkan pada paket client.\n\n";
}

// ── Step 3: Generate integrity hash + Ed25519 signature untuk release-client ─
echo "[3/5] Generating integrity hash + Ed25519 signature (license:rehash)...\n";
$output = shell_exec('php "' . $srcDir . '/artisan" license:rehash --path="' . $releaseDir . '" 2>&1');
echo $output . "\n";

if (!file_exists($releaseDir . '/storage/app/license_integrity.json')) {
    die("❌ ERROR: license_integrity.json tidak terbuat di release-client. Pastikan LICENSE_PRIVATE_KEY sudah ada (php artisan license:keygen).\n\n");
}
echo "      ✓ Integrity hash + Ed25519 signature siap.\n\n";

// Buat empty dirs yang dibutuhkan Laravel
echo "[3b] Membuat direktori kosong yang dibutuhkan...\n";
foreach ($emptyDirs as $dir) {
    $fullPath = $releaseDir . '/' . $dir;
    if (!is_dir($fullPath)) {
        mkdir($fullPath, 0755, true);
    }
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
CLIENT_NAME=                           # ← nama client/sekolah (opsional, otomatis dari lisensi jika kosong)

# ── License ───────────────────────────────────────────────────────────────────
LICENSE_KEY=XXXX-XXXX-XXXX-XXXX        # ← isi dengan license key dari provider
LICENSE_SERVER_URL=https://absen.jagattech.my.id

# ── Cloudflare R2 (Auto Backup Database) ──────────────────────────────────────
CLOUDFLARE_R2_ACCESS_KEY_ID=
CLOUDFLARE_R2_SECRET_ACCESS_KEY=
CLOUDFLARE_R2_BUCKET=
CLOUDFLARE_R2_ENDPOINT=https://<ACCOUNT_ID>.r2.cloudflarestorage.com
CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT=true

# ── Database ──────────────────────────────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absen_jagat
DB_USERNAME=absen_user
DB_PASSWORD=                            # ← isi password database

# ── Session & Cache ───────────────────────────────────────────────────────────
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database

# ── WhatsApp API (GOWA) ───────────────────────────────────────────────────────
GOWA_API_BASE_URL=http://localhost:3000
GOWA_API_USER=admin
GOWA_API_PASS=changeme

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

if (class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
        die("❌ ERROR: Tidak bisa membuat ZIP file.\n");
    }

    addDirToZip($zip, $releaseDir, 'release-client');
    $zip->close();
} else {
    echo "      ℹ ZipArchive tidak aktif. Menggunakan zip / python3 / tar...\n";
    if (PHP_OS_FAMILY !== 'Windows') {
        $cmd = 'cd "' . $srcDir . '" && zip -rq "' . $zipFile . '" release-client 2>&1';
        shell_exec($cmd);
        if (!file_exists($zipFile)) {
            $pyCmd = 'python3 -m zipfile -c "' . $zipFile . '" "' . $releaseDir . '" 2>&1';
            shell_exec($pyCmd);
        }
    } else {
        // Gunakan tar.exe bawaan Windows 10/11 (jauh lebih cepat dari Compress-Archive)
        $cmd = 'tar -a -c -f "' . $zipFile . '" -C "' . $srcDir . '" release-client 2>&1';
        shell_exec($cmd);
        if (!file_exists($zipFile)) {
            $psCmd = 'powershell -Command "Compress-Archive -Path release-client -DestinationPath release-client.zip -Force"';
            shell_exec($psCmd);
        }
    }
    if (!file_exists($zipFile)) {
        die("❌ ERROR: Gagal membuat ZIP file. Pastikan ekstensi php-zip atau paket zip terinstall.\n");
    }
}

$sizeMb = round(filesize($zipFile) / 1024 / 1024, 2);
echo "      ✓ ZIP dibuat: release-client.zip ({$sizeMb} MB)\n\n";

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║  ✅ Build selesai!                                   ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

// ── Helper Functions ──────────────────────────────────────────────────────────

function isExcluded(string $relPath, array $excludes): bool
{
    $relPath = str_replace('\\', '/', $relPath);
    foreach ($excludes as $ex) {
        $ex = str_replace('\\', '/', trim($ex, '/'));
        if ($relPath === $ex || str_starts_with($relPath, $ex . '/')) {
            return true;
        }
    }
    return false;
}

function copyDir(string $src, string $dst, string $rootSrc, array $excludes): void
{
    if (!is_dir($src)) return;

    $items = scandir($src);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $srcPath = $src . '/' . $item;
        $dstPath = $dst . '/' . $item;
        $relPath = ltrim(str_replace('\\', '/', substr($srcPath, strlen($rootSrc))), '/');

        if (isExcluded($relPath, $excludes)) {
            continue;
        }

        if (is_link($srcPath)) {
            continue;
        }

        if (is_dir($srcPath)) {
            if (!is_dir($dstPath)) {
                mkdir($dstPath, 0755, true);
            }
            copyDir($srcPath, $dstPath, $rootSrc, $excludes);
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
        if (is_link($path)) {
            @unlink($path);
            @rmdir($path);
        } elseif (is_dir($path)) {
            deleteDir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
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
