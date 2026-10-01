<?php
$path = __DIR__ . '/../storage/app/public/siswa/DIb4uqNL2eV3IulFEzG3YVCndfOVKrpsl3ZcnhNb.png';
if (file_exists($path)) {
    $before = filesize($path);
    $src = imagecreatefrompng($path);
    $w = imagesx($src);
    $h = imagesy($src);
    $maxW = 600;
    $ratio = min($maxW / $w, 1.0);
    $tw = (int) round($w * $ratio);
    $th = (int) round($h * $ratio);

    $dst = imagecreatetruecolor($tw, $th);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);
    imagepng($dst, $path, 8);
    clearstatcache();
    $after = filesize($path);
    echo "Compressed existing photo from {$before} bytes to {$after} bytes!\n";
} else {
    echo "File not found: $path\n";
}
