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
                $responseBody = $response->json();
                Log::info("WA Get Groups Response: " . json_encode($responseBody)); // Debug log

                // Flexible parser: seek array check keys 'results' or 'data' or use body if array
                $rawGroups = [];

                // Case: results.data (As per User Provided JSON)
                if (isset($responseBody['results']['data']) && is_array($responseBody['results']['data'])) {
                    $rawGroups = $responseBody['results']['data'];
                }
                // Case: results (Direct array)
                elseif (isset($responseBody['results']) && is_array($responseBody['results'])) {
                    $rawGroups = $responseBody['results'];
                }
                // Case: data (Direct array)
                elseif (isset($responseBody['data']) && is_array($responseBody['data'])) {
                    $rawGroups = $responseBody['data'];
                }
                // Case: Root array
                elseif (is_array($responseBody)) {
                    $rawGroups = $responseBody;
                }

                // Standardize Output
                $groups = [];
                foreach ($rawGroups as $g) {
                    // Handle variations in key names (case insensitive search or check common keys)
                    $name = $g['name'] ?? $g['Name'] ?? $g['subject'] ?? $g['Subject'] ?? 'Unknown Group'; // Handle Name/Subject
                    $jid = $g['id'] ?? $g['jid'] ?? $g['JID'] ?? $g['chatId'] ?? null; // Handle ID/JID

                    if ($jid) {
                        $groups[] = [
                            'name' => $name,
                            'jid' => $jid
                        ];
                    }
                }

                // Sort by name
                usort($groups, function ($a, $b) {
                    return strcasecmp($a['name'], $b['name']);
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
