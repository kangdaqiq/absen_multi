<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckWaFeature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Allow super admin to access anything if needed, though they don't have a school
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if school has wa_enabled
        if ($user && $user->school && !$user->school->wa_enabled) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Fitur WhatsApp tidak diaktifkan untuk sekolah ini.'], 403);
            }
            abort(403, 'Fitur WhatsApp tidak diaktifkan untuk sekolah ini. Silakan hubungi Super Admin.');
        }

        return $next($request);
    }
}
