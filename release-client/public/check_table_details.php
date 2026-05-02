<?php
// public/check_table_details.php
try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->handle(Illuminate\Http\Request::capture());

    $guru = Illuminate\Support\Facades\DB::select("SHOW TABLE STATUS LIKE 'guru'");
    $api_keys = Illuminate\Support\Facades\DB::select("SHOW TABLE STATUS LIKE 'api_keys'");
    
    echo json_encode(['guru' => $guru, 'api_keys' => $api_keys], JSON_PRETTY_PRINT);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
