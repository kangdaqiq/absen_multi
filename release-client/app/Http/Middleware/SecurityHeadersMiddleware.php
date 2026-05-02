<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only enforce HTTPS upgrade on production domain
        if (method_exists($response, 'header') && str_contains($request->getHost(), 'smkassuniyah.sch.id')) {
            $response->header('Content-Security-Policy', 'upgrade-insecure-requests');
        }

        return $response;
    }
}
