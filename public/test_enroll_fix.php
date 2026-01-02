<?php
// public/test_enroll_fix.php
try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->handle(Illuminate\Http\Request::capture());

    echo "--- Test Enroll Fix ---\n";

    // 1. Create Data
    // Device
    $device = \App\Models\Device::firstOrCreate(
        ['api_key' => 'TEST-KEY-FIX'],
        ['name' => 'Enroll Fix Device', 'active' => true]
    );
    
    // Guru
    $guru = \App\Models\Guru::firstOrCreate(
        ['nip' => 'T-FIX'],
        ['nama' => 'Guru Enroll Fix', 'no_wa' => '08999999999']
    );
    
    // Reset Status
    $guru->update(['enroll_status' => 'none']);
    
    // 2. Simulate Check - Expect No Enrollment
    $controller = app(\App\Http\Controllers\Api\FingerprintController::class);
    
    echo "\n[1] Check Standby:\n";
    $req1 = Illuminate\Http\Request::create('/api/fingerprint/check-enroll', 'POST', ['api_key' => 'TEST-KEY-FIX']);
    $res1 = $controller->checkEnrollRequest($req1);
    echo $res1->getContent() . "\n";
    
    // 3. Set Request
    $guru->update(['enroll_status' => 'requested']);
    
    echo "\n[2] Check Requested:\n";
    $req2 = Illuminate\Http\Request::create('/api/fingerprint/check-enroll', 'POST', ['api_key' => 'TEST-KEY-FIX']);
    $res2 = $controller->checkEnrollRequest($req2);
    echo $res2->getContent() . "\n";
    
    // 4. Verify no crash on updated_at
    // If output JSON is valid, we passed.

    // 5. Cleanup
    // $guru->delete();
    // $device->delete();

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
