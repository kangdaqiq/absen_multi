<?php
$content = file_get_contents('database/schema/mysql-schema.sql');

// Match everything from "-- Dumping data untuk tabel `tablename`" up to "-- --------------------------------------------------------"
// or up to the end of file, except for `migrations` table.
$content = preg_replace('/--\s*Dumping data untuk tabel `(?!migrations)[^`]+`.*?-- --------------------------------------------------------/is', '-- --------------------------------------------------------', $content);

// In case it's the last block:
$content = preg_replace('/--\s*Dumping data untuk tabel `(?!migrations)[^`]+`.*?(?=--\s*Indeks untuk tabel|--\s*AUTO_INCREMENT|$)/is', '', $content);

file_put_contents('database/schema/mysql-schema.sql', $content);
echo "Cleaned!";
