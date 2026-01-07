<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappController extends Controller
{
    public function getGroups()
    {
        // Guess Base URL from Send URL
        $sendUrl = env('WA_API_URL', 'http://localhost:3000/send/message');

        // Remove '/send/message' from end if exists
        $baseUrl = preg_replace('/\/send\/message\/?$/', '', $sendUrl);
        // Ensure no trailing slash
        $baseUrl = rtrim($baseUrl, '/');

        $endpoint = $baseUrl . '/user/my/groups';

        $user = env('WA_API_USER', 'admin');
        $pass = env('WA_API_PASS', '04112000');

        try {
            $response = Http::timeout(10)
                ->withBasicAuth($user, $pass)
                ->get($endpoint);

            if ($response->successful()) {
                $data = $response->json();

                // Assuming data structure matches documentation: 
                // { "code": 200, "message": "Success", "results": [ { "JID": "...", "Name": "..." } ] }

                $groups = $data['results'] ?? [];
                // Sort by name
                usort($groups, function ($a, $b) {
                    return strcasecmp($a['Name'], $b['Name']);
                });

                return response()->json([
                    'success' => true,
                    'groups' => $groups
                ]);
            }

            Log::error("WA Get Groups Error: " . $response->body());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data dari WA Gateway. Status: ' . $response->status()
            ], 500);

        } catch (\Exception $e) {
            Log::error("WA Get Groups Exception: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan koneksi ke WA Gateway.'
            ], 500);
        }
    }
}
