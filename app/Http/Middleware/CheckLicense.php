<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\LicenseService;
use Symfony\Component\HttpFoundation\Response;

class CheckLicense
{
    public function __construct(private LicenseService $licenseService) {}

    public function handle(Request $request, Closure $next): Response
    {
        // [SECURITY] Deteksi mode berdasarkan integrity file, BUKAN APP_MODE dari .env.
        // Client tidak bisa bypass dengan mengubah APP_MODE=hosted di .env.
        $isClientRelease = file_exists(storage_path('app/license_integrity.json'));

        if (!$isClientRelease) {
            // Tidak ada integrity file = environment developer (hosted server)
            // Cek APP_MODE sebagai fallback untuk dev
            if (config('app.mode', 'hosted') !== 'self_hosted') {
                return $next($request);
            }
        }

        // [SECURITY] Deteksi bypass attempt: integrity file ada tapi APP_MODE diubah ke 'hosted'
        if ($isClientRelease && config('app.mode') !== 'self_hosted') {
            Log::warning('[CheckLicense] Bypass attempt detected: APP_MODE changed to "' 
                . config('app.mode') . '" but integrity file exists. Enforcing license check.');
        }

        // Bypass: license pages, login, logout, API endpoints
        if ($request->routeIs('license.*')
            || $request->routeIs('login')
            || $request->routeIs('logout')
            || $request->is('api/*')
        ) {
            return $next($request);
        }

        $status = $this->licenseService->validate();

        if (!empty($status['expired'])) {
            return redirect()->route('license.expired');
        }

        if (empty($status['valid'])) {
            return redirect()->route('license.invalid');
        }

        // Share license info with all views
        view()->share('licenseInfo', $status);

        return $next($request);
    }
}
