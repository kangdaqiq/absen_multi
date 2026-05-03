<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class WhatsappController extends Controller
{
    private function baseUrl(): string
    {
        return rtrim(env('GOWA_API_BASE_URL', 'http://localhost:3000'), '/');
    }

    private function auth(): array
    {
        return [env('GOWA_API_USER', 'admin'), env('GOWA_API_PASS', 'jagattech')];
    }

    /**
     * Return list of WA groups for the logged-in school's device.
     */
    public function getGroups()
    {
        $base    = $this->baseUrl();
        [$user, $pass] = $this->auth();

        // Determine device ID from logged-in user's school
        $schoolId = Auth::user()?->school_id;
        $deviceId = $schoolId ? (string)$schoolId : null;

        $endpoint = $base . '/user/my/groups';

        try {
            $req = Http::timeout(15)->withBasicAuth($user, $pass);

            if ($deviceId) {
                $req = $req->withHeaders(['X-Device-Id' => $deviceId]);
            }

            $response = $req->get($endpoint);

            if ($response->successful()) {
                $responseBody = $response->json();
                Log::info("WA Get Groups (device: {$deviceId}): " . json_encode($responseBody));

                $rawGroups = [];

                if (isset($responseBody['results']['data']) && is_array($responseBody['results']['data'])) {
                    $rawGroups = $responseBody['results']['data'];
                } elseif (isset($responseBody['results']) && is_array($responseBody['results'])) {
                    $rawGroups = $responseBody['results'];
                } elseif (isset($responseBody['data']) && is_array($responseBody['data'])) {
                    $rawGroups = $responseBody['data'];
                } elseif (is_array($responseBody)) {
                    $rawGroups = $responseBody;
                }

                $groups = [];
                foreach ($rawGroups as $g) {
                    $name = $g['name'] ?? $g['Name'] ?? $g['subject'] ?? $g['Subject'] ?? 'Unknown Group';
                    $jid  = $g['id']   ?? $g['jid']  ?? $g['JID']   ?? $g['chatId']  ?? null;

                    if ($jid) {
                        $groups[] = ['name' => $name, 'jid' => $jid];
                    }
                }

                usort($groups, fn($a, $b) => strcasecmp($a['name'], $b['name']));

                return response()->json(['success' => true, 'groups' => $groups]);
            }

            Log::error("WA Get Groups Error (device: {$deviceId}): " . $response->body());
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
