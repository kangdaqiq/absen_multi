<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect('login');
        }

        $user = auth()->user();

        // Expand role aliases: guru <-> teacher
        $effectiveRoles = $roles;
        if (in_array('teacher', $roles) && !in_array('guru', $effectiveRoles)) {
            $effectiveRoles[] = 'guru';
        }
        if (in_array('guru', $roles) && !in_array('teacher', $effectiveRoles)) {
            $effectiveRoles[] = 'teacher';
        }

        // Check if user has one of the allowed roles
        if (in_array($user->role, $effectiveRoles)) {
            return $next($request);
        }

        // Redirect if unauthorized
        return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
    }
}
