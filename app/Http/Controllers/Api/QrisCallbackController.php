<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrisCallbackController extends Controller
{
    /**
     * Endpoint Callback QRIS (Nonaktif - Mode QRIS Manual).
     * Integrasi API Qiospay telah dinonaktifkan karena provider bangkrut & tutup operasional.
     * POST /api/callback/accept/{key}
     */
    public function accept(Request $request, string $key = ''): JsonResponse
    {
        return response()->json([
            'status'  => 'disabled',
            'message' => 'Integrasi API/Webhook Qiospay telah dinonaktifkan (mode QRIS manual). Pembayaran diverifikasi secara manual oleh Super Admin.',
            'data'    => null,
        ], 410);
    }
}
