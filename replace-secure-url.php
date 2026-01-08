#!/usr/bin/env php
<?php

$files = [
    'resources/views/guru/index.blade.php',
    'resources/views/kelas/index.blade.php',
    'resources/views/mapel/index.blade.php',
    'resources/views/devices/index.blade.php',
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (!file_exists($path)) {
        echo "File not found: $file\n";
        continue;
    }

    $content = file_get_contents($path);
    $newContent = str_replace("secure_url(", "url(", $content);

    if ($content !== $newContent) {
        file_put_contents($path, $newContent);
        echo "✓ Updated: $file\n";
    } else {
        echo "- No changes: $file\n";
    }
}

echo "\n✅ Done!\n";
