<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class WhatsappDeviceController extends Controller
{
    private function baseUrl(): string
    {
        return rtrim(env('WA_API_BASE_URL', 'http://localhost:3000'), '/');
    }

    private function auth(): array
    {
        return [env('WA_API_USER', 'admin'), env('WA_API_PASS', '')];
    }

    private function deviceId(): string
    {
        $schoolId = Auth::user()->school_id;
        return (string)$schoolId;
    }

    public function index()
    {
        return view('whatsapp.device');
    }

    /**
     * Lightweight check — ONLY checks /app/status (no QR generation).
     * Used by the JS polling loop so it doesn't reset qr_duration.
     */
    public function check()
    {
        $base     = $this->baseUrl();
        $deviceId = $this->deviceId();
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
            ]);
        } catch (\Exception $e) {
            return response()->json(['connected' => false]);
        }
    }

    /**
     * Full status: check connection + generate QR if not connected.
     */
    public function status()
    {
        $base     = $this->baseUrl();
        $deviceId = $this->deviceId();
        [$user, $pass] = $this->auth();

        try {
            // 1. Check status
            $statusRes = Http::timeout(10)
                ->withBasicAuth($user, $pass)
                ->withHeaders(['X-Device-Id' => $deviceId])
                ->get("{$base}/app/status");

            if ($statusRes->successful()) {
                $data        = $statusRes->json();
                $isLoggedIn  = $data['results']['is_logged_in']  ?? false;
                $isConnected = $data['results']['is_connected']   ?? false;

                if ($isLoggedIn && $isConnected) {
                    return response()->json([
                        'status'    => 'connected',
                        'device_id' => $deviceId,
                    ]);
                }
            }

            // 2. Not connected → request QR
            $loginRes = Http::timeout(25)
                ->withBasicAuth($user, $pass)
                ->withHeaders(['X-Device-Id' => $deviceId])
                ->get("{$base}/app/login");

            // Auto-create device if it was deleted / not found
            if ($loginRes->status() === 404 && str_contains($loginRes->body(), 'DEVICE_NOT_FOUND')) {
                // Auto-discovery endpoints for device creation (some APIs use different paths)
                $createSuccess = false;
                $debugLogs = '';
                $endpointsToTry = ["/devices", "/api/devices", "/app/devices", "/sessions/add", "/sessions"];
                
                foreach ($endpointsToTry as $ep) {
                    $createRes = Http::timeout(5)
                        ->withBasicAuth($user, $pass)
                        ->post("{$base}{$ep}", [
                            'device_id' => $deviceId,
                            'device'    => $deviceId,
                            'id'        => $deviceId,
                            'name'      => $deviceId,
                            'sessionId' => $deviceId
                        ]);
                    
                    if ($createRes->successful() && !str_contains($createRes->body(), 'Cannot POST')) {
                        $createSuccess = true;
                        break;
                    }
                    $debugLogs .= "POST {$ep}: " . $createRes->body() . "\n";
                }
                
                if (!$createSuccess) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Gagal membuat device otomatis. Endpoint tidak ditemukan.',
                        'debug'   => $debugLogs,
                    ], 500);
                }

                // Retry login
                $loginRes = Http::timeout(25)
                    ->withBasicAuth($user, $pass)
                    ->withHeaders(['X-Device-Id' => $deviceId])
                    ->get("{$base}/app/login");
            }

            if ($loginRes->successful()) {
                $loginData  = $loginRes->json();
                $qrLink     = $loginData['results']['qr_link']     ?? null;
                $qrDuration = $loginData['results']['qr_duration']  ?? 30;

                if ($qrLink) {
                    // Return a proxied URL so the browser doesn't need to reach localhost:3000 directly
                    // Extract the path from the QR link (e.g. /statics/qrcode/scan-qr-xxx.png)
                    $parsed  = parse_url($qrLink);
                    $qrPath  = $parsed['path'] ?? '';

                    $proxiedUrl = route('whatsapp.device.qr-proxy') . '?path=' . urlencode($qrPath);

                    return response()->json([
                        'status'      => 'qr_ready',
                        'qr_link'     => $proxiedUrl,
                        'qr_duration' => $qrDuration,
                        'device_id'   => $deviceId,
                    ]);
                }

                return response()->json([
                    'status'  => 'qr_pending',
                    'message' => 'QR Code sedang dibuat, mohon tunggu beberapa detik lalu coba refresh.',
                ]);
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'WA API error (HTTP ' . $loginRes->status() . '). Cek bahwa restapi-wa berjalan di ' . $base,
                'debug'   => $loginRes->body(),
            ], 500);

        } catch (\Exception $e) {
            Log::error('WhatsApp Device Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Tidak dapat terhubung ke WA API di ' . $base . '. Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Proxy QR code image from restapi-wa so browser doesn't need direct access to port 3000.
     */
    public function qrProxy(Request $request)
    {
        $path = $request->query('path', '');

        // Validate path to prevent SSRF: must start with /statics/
        if (!str_starts_with($path, '/statics/')) {
            abort(403, 'Invalid path');
        }

        $base = $this->baseUrl();
        [$user, $pass] = $this->auth();

        try {
            $res = Http::timeout(10)
                ->withBasicAuth($user, $pass)
                ->get($base . $path);

            if ($res->successful()) {
                return response($res->body(), 200)
                    ->header('Content-Type', $res->header('Content-Type') ?? 'image/png')
                    ->header('Cache-Control', 'no-store');
            }

            abort(404);
        } catch (\Exception $e) {
            abort(500, 'QR proxy error: ' . $e->getMessage());
        }
    }

    /**
     * Logout current device.
     */
    public function logout(Request $request)
    {
        $base     = $this->baseUrl();
        $deviceId = $this->deviceId();
        [$user, $pass] = $this->auth();

        try {
            $res = Http::timeout(15)
                ->withBasicAuth($user, $pass)
                ->withHeaders(['X-Device-Id' => $deviceId])
                ->get("{$base}/app/logout");

            if ($res->successful()) {
                return response()->json(['success' => true, 'message' => 'Berhasil logout dari WhatsApp.']);
            }

            return response()->json(['success' => false, 'message' => 'Gagal logout (HTTP ' . $res->status() . '): ' . $res->body()], 500);
        } catch (\Exception $e) {
            Log::error('WhatsApp Device Logout Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function testMessage(Request $request, \App\Services\WhatsAppService $wa)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        try {
            $wa->sendTestMessage($request->phone, $request->message, Auth::user()->school_id);
            return response()->json(['success' => true, 'message' => 'Pesan percobaan dimasukkan ke antrean.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
