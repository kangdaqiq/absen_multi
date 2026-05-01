<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\LicenseService;
use Symfony\Component\HttpFoundation\Response;

class CheckLicense
{
    public function __construct(private LicenseService $licenseService) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Only applies in self_hosted mode
        if (config('app.mode', 'hosted') !== 'self_hosted') {
            return $next($request);
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
