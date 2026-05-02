<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappDevicesController extends Controller
{
    private function baseUrl(): string
    {
        return rtrim(env('WA_API_BASE_URL', 'http://localhost:3000'), '/');
    }

    private function auth(): array
    {
        return [env('WA_API_USER', 'admin'), env('WA_API_PASS', '')];
    }

    /**
     * Show all schools and their WA device status.
     */
    public function index()
    {
        $schools = School::orderBy('name')->get();

        return view('super-admin.whatsapp-devices', compact('schools'));
    }
    public function status($schoolId)
    {
        $deviceId = (string)$schoolId;
        $base     = $this->baseUrl();
        [$user, $pass] = $this->auth();

        try {
            $res  = Http::timeout(8)
                ->withBasicAuth($user, $pass)
                ->withHeaders(['X-Device-Id' => $deviceId])
                ->get("{$base}/app/status");

            $data        = $res->json();
            $isLoggedIn  = $data['results']['is_logged_in']  ?? false;
            $isConnected = $data['results']['is_connected']   ?? false;

            return response()->json([
                'connected' => ($isLoggedIn && $isConnected),
                'device_id' => $deviceId,
            ]);
        } catch (\Exception $e) {
            return response()->json(['connected' => false, 'device_id' => $deviceId]);
        }
    }
}
