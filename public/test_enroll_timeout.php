<?php
// public/test_enroll_timeout.php
try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->handle(Illuminate\Http\Request::capture());

    echo "--- Test Enroll Timeout ---\n";

    // 1. Setup
    $guru = \App\Models\Guru::where('nip', 'T-FIX')->first();
    if(!$guru) {
        $guru = \App\Models\Guru::create(['nip'=>'T-FIX', 'nama'=>'Timeout Test', 'no_wa'=>'000']);
    }
    
    $controller = app(\App\Http\Controllers\Api\FingerprintController::class);
    $req = Illuminate\Http\Request::create('/api/fingerprint/check-enroll', 'POST', ['api_key' => 'TEST-KEY-FIX']);

    // 2. Fresh Request (Should be Active)
    $guru->update(['enroll_status' => 'requested', 'updated_at' => now()]);
    echo "[1] Fresh Request: ";
    $res1 = $controller->checkEnrollRequest($req);
    echo $res1->getContent() . "\n";
    
    // 3. Old Request (Should be Standby)
    $guru->update(['enroll_status' => 'requested', 'updated_at' => now()->subMinutes(20)]);
    echo "[2] Expired Request: ";
    $res2 = $controller->checkEnrollRequest($req);
    echo $res2->getContent() . "\n";
    
    // Cleanup
    // $guru->delete();

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
