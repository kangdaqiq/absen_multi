<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SelfHostedGuard
{
    /**
     * Blokir akses Super Admin panel di instalasi client (self-hosted).
     *
     * SECURITY: Tidak menggunakan APP_MODE dari .env karena bisa diubah client.
     * Deteksi berdasarkan keberadaan license_integrity.json yang dibuat saat build release.
     * File ini disertakan dalam release client dan tidak mudah dihapus tanpa merusak license.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // [SECURITY] Cek keberadaan integrity file sebagai penanda release client.
        // Jika file ini ada → ini adalah instalasi client → Super Admin SELALU diblokir
        // regardless of APP_MODE setting di .env
        if ($this->isClientRelease()) {
            Log::warning('[SelfHostedGuard] Akses Super Admin diblokir pada instalasi client.');
            abort(404);
        }

        // Fallback: cek APP_MODE untuk dev environment (tidak ada integrity file)
        if (config('app.mode', 'hosted') === 'self_hosted') {
            abort(404);
        }

        return $next($request);
    }

    /**
     * Deteksi apakah ini adalah instalasi client (self-hosted release).
     * Berdasarkan keberadaan license_integrity.json — dibuat saat build_release.php dijalankan.
     */
    private function isClientRelease(): bool
    {
        return file_exists(storage_path('app/license_integrity.json'));
    }
}
