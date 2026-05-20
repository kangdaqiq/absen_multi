#!/usr/bin/env php
<?php

/**
 * Dynamic Custom Domain & Logo Verification Script
 * Run: php verify_domain.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

echo "\033[1;36m╔════════════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[1;36m║     DYNAMIC CUSTOM DOMAIN & LOGO VERIFICATION SCRIPT       ║\033[0m\n";
echo "\033[1;36m╚════════════════════════════════════════════════════════════╝\033[0m\n\n";

// 1. Check Database Connection
echo "📊 [1/4] Checking Database Connection...\n";
try {
    DB::connection()->getPdo();
    echo "   \033[1;32m✅ Database connected successfully\033[0m\n\n";
} catch (Exception $e) {
    echo "   \033[1;31m❌ Database connection failed:\033[0m " . $e->getMessage() . "\n\n";
    exit(1);
}

// 2. Setup Test School
echo "🏫 [2/4] Setting Up Test School with Custom Domain...\n";
$domain = 'smpn1.sch.id';
$schoolName = 'SMP Negeri 1 Jakarta';

try {
    // Delete if already exists to ensure fresh start
    School::where('domain', $domain)->delete();

    $school = School::create([
        'name' => $schoolName,
        'type' => 'school',
        'code' => 'TEST-SCH-01',
        'domain' => $domain,
        'is_active' => true,
    ]);

    echo "   \033[1;32m✅ Test school created successfully:\033[0m\n";
    echo "      - Name:   {$school->name}\n";
    echo "      - Code:   {$school->code}\n";
    echo "      - Domain: {$school->domain}\n";
    
    // Check if we can find it in database
    $find = School::where('domain', $domain)->first();
    echo "      - Verification query: " . ($find ? "Found '{$find->name}' (ID: {$find->id})" : "NOT FOUND IN DB!") . "\n\n";

} catch (Exception $e) {
    echo "   \033[1;31m❌ Failed to setup test school:\033[0m " . $e->getMessage() . "\n\n";
    exit(1);
}

// 3. Verify Routing & Request Handling
echo "🌐 [3/4] Testing Dynamic Request Handling...\n";

function simulateRequest($host) {
    global $app;
    
    // Setup server variables for host resolution
    $_SERVER['HTTP_HOST'] = $host;
    $_SERVER['SERVER_NAME'] = $host;
    
    // Create Laravel request with full URL
    $request = Request::create('http://' . $host . '/login', 'GET');
    $request->headers->set('Host', $host);
    
    // Bind request to container so controller dependency injection resolves it
    $app->instance('request', $request);
    
    // Dispatch request through router
    $response = Route::dispatch($request);
    
    return [
        'status' => $response->getStatusCode(),
        'content' => $response->getContent(),
    ];
}

$success = true;

// Test Case A: Registered custom domain
echo "   \033[1;33mTest Case A:\033[0m Accessing custom domain '\033[1;35m{$domain}\033[0m'...\n";
$resA = simulateRequest($domain);

if ($resA['status'] === 200) {
    $hasSchoolName = str_contains($resA['content'], $schoolName);
    $hasWelcomeText = str_contains($resA['content'], 'Silakan masuk menggunakan akun Anda');
    
    if ($hasSchoolName && $hasWelcomeText) {
        echo "      \033[1;32m✅ SUCCESS!\033[0m Custom branding correctly loaded.\n";
        echo "         - Renders School Name: '{$schoolName}'\n";
    } else {
        echo "      \033[1;31m❌ FAILED!\033[0m Response did not contain school branding.\n";
        echo "         HTML Snippet: " . substr(strip_tags($resA['content']), 0, 1000) . "\n";
        $success = false;
    }
} else {
    echo "      \033[1;31m❌ FAILED!\033[0m HTTP Status Code received: " . $resA['status'] . " (Expected 200)\n";
    $success = false;
}

// Test Case B: Registered custom domain with 'www.' prefix
$wwwDomain = 'www.' . $domain;
echo "\n   \033[1;33mTest Case B:\033[0m Accessing custom domain with www prefix '\033[1;35m{$wwwDomain}\033[0m'...\n";
$resB = simulateRequest($wwwDomain);

if ($resB['status'] === 200) {
    $hasSchoolName = str_contains($resB['content'], $schoolName);
    
    if ($hasSchoolName) {
        echo "      \033[1;32m✅ SUCCESS!\033[0m Custom branding correctly loaded for domain with www prefix.\n";
    } else {
        echo "      \033[1;31m❌ FAILED!\033[0m Response did not contain school branding.\n";
        $success = false;
    }
} else {
    echo "      \033[1;31m❌ FAILED!\033[0m HTTP Status Code received: " . $resB['status'] . " (Expected 200)\n";
    $success = false;
}

// Test Case C: Default primary domain
$defaultHost = 'localhost';
echo "\n   \033[1;33mTest Case C:\033[0m Accessing default/generic host '\033[1;35m{$defaultHost}\033[0m'...\n";
$resC = simulateRequest($defaultHost);

if ($resC['status'] === 200) {
    $hasDefaultGreeting = str_contains($resC['content'], 'Selamat Datang 👋');
    $hasDefaultSub = str_contains($resC['content'], 'Masukkan kredensial Anda untuk mengakses dashboard');
    $hasSchoolName = str_contains($resC['content'], $schoolName);
    
    if ($hasDefaultGreeting && $hasDefaultSub && !$hasSchoolName) {
        echo "      \033[1;32m✅ SUCCESS!\033[0m Default generic Jagat Tech branding correctly loaded.\n";
    } else {
        echo "      \033[1;31m❌ FAILED!\033[0m Page displayed incorrect branding for default/generic host.\n";
        $success = false;
    }
} else {
    echo "      \033[1;31m❌ FAILED!\033[0m HTTP Status Code received: " . $resC['status'] . " (Expected 200)\n";
    $success = false;
}

echo "\n";

// 4. Cleanup
echo "🧹 [4/4] Cleaning Up Test Data...\n";
try {
    School::where('domain', $domain)->delete();
    echo "   \033[1;32m✅ Test school deleted successfully.\033[0m\n\n";
} catch (Exception $e) {
    echo "   \033[1;31m❌ Failed to delete test school:\033[0m " . $e->getMessage() . "\n\n";
}

echo "\033[1;36m╔════════════════════════════════════════════════════════════╗\033[0m\n";
if ($success) {
    echo "║        \033[1;32m🎉 ALL VERIFICATION TEST CASES PASSED SUCCESSFULLY! 🎉\033[0m      ║\n";
} else {
    echo "║        \033[1;31m⚠️  SOME VERIFICATION TEST CASES FAILED! CHECK LOGS. ⚠️\033[0m     ║\n";
}
echo "\033[1;36m╚════════════════════════════════════════════════════════════╝\033[0m\n";

exit($success ? 0 : 1);
