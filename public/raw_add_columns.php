<?php
// public/raw_add_columns.php
try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->handle(Illuminate\Http\Request::capture());
    
    echo "--- Raw SQL Add Columns ---\n";
    
    // MariaDB/MySQL IF NOT EXISTS syntax for ADD COLUMN requires specific version,
    // or we catch exception.
    
    try {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE guru ADD COLUMN created_at TIMESTAMP NULL DEFAULT NULL");
        echo "Added created_at.\n";
    } catch (\Exception $e) {
        echo "created_at likely exists: " . $e->getMessage() . "\n";
    }
    
    try {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE guru ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL");
        echo "Added updated_at.\n";
    } catch (\Exception $e) {
        echo "updated_at likely exists: " . $e->getMessage() . "\n";
    }
    
    // Verify
    $cols = \Illuminate\Support\Facades\Schema::getColumnListing('guru');
    print_r($cols);

} catch (\Exception $e) {
    echo "Fatal: " . $e->getMessage();
}
