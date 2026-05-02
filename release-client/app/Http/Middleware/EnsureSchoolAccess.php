<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super admin can access everything
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        // Regular admin must have a school assigned
        if ($user && $user->isAdmin() && !$user->school_id) {
            abort(403, 'Anda belum di-assign ke sekolah manapun. Hubungi Super Admin.');
        }

        // Share school_id to all views for filtering
        if ($user && $user->school_id) {
            view()->share('currentSchoolId', $user->school_id);
            view()->share('currentSchool', $user->school);
        }

        return $next($request);
    }
}
