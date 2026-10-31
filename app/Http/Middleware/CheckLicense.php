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
        // [SECURITY] Deteksi mode menggunakan isSelfHosted() yang mencakup IS_CLIENT_BUILD,
        // keberadaan license_integrity.json, dan APP_MODE.
        if (!$this->licenseService->isSelfHosted()) {
            return $next($request);
        }

        // [SECURITY] Deteksi bypass attempt: instalasi client tapi APP_MODE diubah dari 'self_hosted'
        if (config('app.mode') !== 'self_hosted') {
            Log::warning('[CheckLicense] Bypass attempt detected: APP_MODE changed to "'
                . config('app.mode') . '" on self-hosted installation. Enforcing license check.');
        }

        // Bypass: license pages, login, logout, API endpoints, and developer simulator
        if ($request->routeIs('license.*')
            || $request->routeIs('login*')
            || $request->is('login')
            || $request->routeIs('logout')
            || $request->routeIs('simulator.*')
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
