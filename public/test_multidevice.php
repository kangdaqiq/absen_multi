<?php
// public/test_multidevice.php
try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->handle(Illuminate\Http\Request::capture());

    echo "--- Test Multi-Device Fingerprint ---\n";
    
    // 1. Setup: Get Guru and Set Status
    $guru = \App\Models\Guru::first();
    if (!$guru) die("No Guru found");
    
    $guru->enroll_finger_status = 'requested';
    $guru->save();
    echo "Set Guru {$guru->nama} status to 'requested'.\n";
    
    // 2. Simulate Check Enroll (Device A)
    // Key: a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a
    $apiKey = 'a7cde739dc87c55c642d6e105571bac53850f7026199babf79f992483e37458a';
    
    $req = \Illuminate\Http\Request::create('/api/fingerprint/check-enroll', 'POST', ['api_key' => $apiKey]);
    $res = app()->handle($req);
    echo "Check Enroll Response: " . $res->getContent() . "\n";
    
    $json = json_decode($res->getContent(), true);
    if (!isset($json['enroll']) || !$json['enroll']) {
        die("Enroll check failed");
    }
    
    $targetId = $json['target_id'];
    echo "Target ID: $targetId\n";
    
    // 3. Simulate Finalize Enroll
    $req2 = \Illuminate\Http\Request::create('/api/fingerprint', 'POST', [
        'api_key' => $apiKey,
        'finger_id' => $targetId,
        'enroll_success' => true
    ]);
    $res2 = app()->handle($req2);
    echo "Finalize Response: " . $res2->getContent() . "\n";
    
    // 4. Verify Database
    $fp = \App\Models\GuruFingerprint::where('guru_id', $guru->id)->where('finger_id', $targetId)->first();
    if ($fp) {
        echo "DB Check: OK. Found fingerprint ID $targetId for Device {$fp->device_id}.\n";
    } else {
        echo "DB Check: FAILED. Record not found.\n";
    }
    
    // 5. Simulate Scan
    $req3 = \Illuminate\Http\Request::create('/api/fingerprint', 'POST', [
        'api_key' => $apiKey,
        'finger_id' => $targetId
    ]);
    $res3 = app()->handle($req3);
    echo "Scan Response: " . $res3->getContent() . "\n";
    
    // Cleanup
    $guru->enroll_finger_status = null;
    $guru->save();

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
    echo $e->getTraceAsString();
}
