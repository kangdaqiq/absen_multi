<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SelfHostedGuard
{
    /**
     * In self_hosted mode, the Super Admin panel is hidden and inaccessible.
     * Super Admin panel is only for the developer (hosted mode).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.mode', 'hosted') === 'self_hosted') {
            abort(404);
        }

        return $next($request);
    }
}
