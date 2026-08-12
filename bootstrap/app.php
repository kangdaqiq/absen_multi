<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'role'             => \App\Http\Middleware\RoleMiddleware::class,
            'self_hosted_guard' => \App\Http\Middleware\SelfHostedGuard::class,
        ]);
        $middleware->appendToGroup('web', \App\Http\Middleware\CheckLicense::class);
        $middleware->append(\App\Http\Middleware\SecurityHeadersMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $e, $request) {
            if ($request->expectsJson() || $request->isXmlHttpRequest()) {
                return response()->json([
                    'message' => 'Sesi halaman Anda telah berakhir.',
                    'redirect' => route('login')
                ], 419);
            }

            return redirect()->route('login')->with('warning', 'Sesi halaman Anda telah berakhir. Silakan login kembali.');
        });
    })->create();
