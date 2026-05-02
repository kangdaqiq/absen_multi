<?php
// public/check_schema_v2.php
try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->handle(Illuminate\Http\Request::capture());

    $guru = Illuminate\Support\Facades\DB::select('DESCRIBE guru');
    $kelas = Illuminate\Support\Facades\DB::select('DESCRIBE kelas');
    
    echo json_encode(['guru' => $guru, 'kelas' => $kelas], JSON_PRETTY_PRINT);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
