<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Pastikan response adalah instance dari Response (bukan StreamedResponse dll yang mungkin repot, tapi umumnya aman)
        if (method_exists($response, 'header')) {
            $response->header('Content-Security-Policy', 'upgrade-insecure-requests');
        }

        return $response;
    }
}
