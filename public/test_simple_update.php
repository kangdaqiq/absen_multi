<?php
// public/test_simple_update.php
try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->handle(Illuminate\Http\Request::capture());

    echo "--- Test Simple Update ---\n";
    
    $guru = \App\Models\Guru::where('nip', 'T-FIX')->first();
    if(!$guru) {
        $guru = \App\Models\Guru::create(['nip'=>'T-FIX', 'nama'=>'Simple Update Test', 'no_wa'=>'000']);
        echo "Created Guru.\n";
    }
    
    // Test 1: Automatic Timestamp
    echo "[1] Automatic Timestamp Update... ";
    try {
        $guru->update(['enroll_status' => 'requested']);
        echo "SUCCESS.\n";
        echo "New updated_at: " . $guru->updated_at . "\n";
    } catch (\Exception $e) {
        echo "FAIL: " . $e->getMessage() . "\n";
    }
    
    // Test 2: Manual Timestamp
    echo "[2] Manual Timestamp Update... ";
    try {
        $guru->update(['updated_at' => now()->subMinutes(20)]);
        echo "SUCCESS.\n";
        echo "New updated_at: " . $guru->updated_at . "\n";
    } catch (\Exception $e) {
        echo "FAIL: " . $e->getMessage() . "\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
