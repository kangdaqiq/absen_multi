<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\LicenseService;
use Symfony\Component\HttpFoundation\Response;

class SelfHostedGuard
{
    public function __construct(private LicenseService $licenseService) {}

    /**
     * Blokir akses Super Admin panel di instalasi client (self-hosted).
     *
     * SECURITY: Menggunakan LicenseService::isSelfHosted() yang memeriksa
     * konstanta IS_CLIENT_BUILD, keberadaan license_integrity.json, serta APP_MODE.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->licenseService->isSelfHosted()) {
            Log::warning('[SelfHostedGuard] Akses Super Admin diblokir pada instalasi client.');
            abort(404);
        }

        return $next($request);
    }
}
