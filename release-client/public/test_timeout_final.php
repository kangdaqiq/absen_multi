<?php
// public/test_timeout_final.php
try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->handle(Illuminate\Http\Request::capture());

    echo "--- Test Timeout Final ---\n";

    $guru = \App\Models\Guru::firstOrCreate(['nip'=>'T-FINAL'], ['nama'=>'Final Test', 'no_wa'=>'0812345']);
    
    // 1. Fresh (Active)
    $guru->timestamps = false; // We set manually
    $guru->enroll_status = 'requested';
    $guru->updated_at = now();
    $guru->save();
    
    $controller = app(\App\Http\Controllers\Api\FingerprintController::class);
    $req = Illuminate\Http\Request::create('/api/fingerprint/check-enroll', 'POST', ['api_key' => 'TEST-KEY-FIX']); // using key from previous test
    
    $res1 = $controller->checkEnrollRequest($req);
    $json1 = json_decode($res1->getContent(), true);
    echo "[1] Fresh: " . $json1['status'] . " (Expect: enroll_mode)\n";
    
    // 2. Expired (Standby)
    $guru->updated_at = now()->subMinutes(20);
    $guru->save();
    
    $res2 = $controller->checkEnrollRequest($req);
    $json2 = json_decode($res2->getContent(), true);
    echo "[2] Expired: " . $json2['status'] . " (Expect: standby)\n";
    
    if ($json1['status'] == 'enroll_mode' && $json2['status'] == 'standby') {
        echo "RESULT: PASS\n";
    } else {
        echo "RESULT: FAIL\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
