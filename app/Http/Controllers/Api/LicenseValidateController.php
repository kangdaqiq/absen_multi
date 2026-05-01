<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LicenseValidateController extends Controller
{
    /**
     * Public endpoint for self-hosted clients to validate their license.
     * POST /api/license/validate
     */
    public function validate(Request $request): JsonResponse
    {
        $key      = trim($request->input('license_key', ''));
        $hostname = trim($request->input('hostname', ''));

        if (empty($key)) {
            return response()->json([
                'valid'   => false,
                'message' => 'license_key diperlukan.',
            ], 400);
        }

        $license = License::where('license_key', $key)->first();

        if (!$license) {
            return response()->json([
                'valid'   => false,
                'message' => 'Lisensi tidak ditemukan.',
            ], 404);
        }

        if (!$license->is_active) {
            return response()->json([
                'valid'   => false,
                'message' => 'Lisensi telah dinonaktifkan oleh provider. Hubungi KangDaQiQ.',
            ]);
        }

        // Check hostname lock
        if (!empty($license->allowed_hostname) && !empty($hostname)) {
            if (strtolower($hostname) !== strtolower($license->allowed_hostname)) {
                return response()->json([
                    'valid'   => false,
                    'message' => 'Hostname tidak sesuai dengan lisensi terdaftar.',
                ]);
            }
        }

        // Update last ping
        $license->update(['last_ping_at' => now()]);

        // Check expiry
        if ($license->isExpired()) {
            return response()->json([
                'valid'        => false,
                'expired'      => true,
                'client_name'  => $license->client_name,
                'expired_at'   => $license->expired_at->format('Y-m-d'),
                'max_schools'  => $license->max_schools,
                'max_students' => $license->max_students,
                'max_teachers' => $license->max_teachers,
                'max_bot_users'=> $license->max_bot_users,
                'message'      => 'Lisensi telah expired pada ' . $license->expired_at->format('d M Y') . '. Hubungi KangDaQiQ untuk perpanjangan.',
            ]);
        }

        return response()->json([
            'valid'        => true,
            'client_name'  => $license->client_name,
            'max_schools'  => $license->max_schools,
            'max_students' => $license->max_students,
            'max_teachers' => $license->max_teachers,
            'max_bot_users'=> $license->max_bot_users,
            'expired_at'   => $license->expired_at?->format('Y-m-d'),
            'message'      => 'Lisensi aktif.',
        ]);
    }
}
