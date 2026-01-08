<?php
// Clear all cache
echo "Clearing Laravel cache...\n";

// Clear config cache
if (file_exists(__DIR__ . '/bootstrap/cache/config.php')) {
    unlink(__DIR__ . '/bootstrap/cache/config.php');
    echo "✓ Config cache cleared\n";
}

// Clear route cache
if (file_exists(__DIR__ . '/bootstrap/cache/routes-v7.php')) {
    unlink(__DIR__ . '/bootstrap/cache/routes-v7.php');
    echo "✓ Route cache cleared\n";
}

// Clear compiled views
$viewPath = __DIR__ . '/storage/framework/views';
if (is_dir($viewPath)) {
    $files = glob($viewPath . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    echo "✓ View cache cleared\n";
}

// Clear application cache
$cachePath = __DIR__ . '/storage/framework/cache/data';
if (is_dir($cachePath)) {
    $files = glob($cachePath . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    echo "✓ Application cache cleared\n";
}

// Clear opcache if available
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✓ OPcache cleared\n";
}

echo "\n✅ All cache cleared! Please refresh your browser.\n";
