<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\School;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReconnectWhatsappDevices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wa:reconnect';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and auto-reconnect logged-in but disconnected WhatsApp devices';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $baseUrl = rtrim(env('GOWA_API_BASE_URL', 'http://localhost:3000'), '/');
        $user    = env('GOWA_API_USER', 'admin');
        $pass    = env('GOWA_API_PASS', 'jagattech');

        $this->info("Starting WhatsApp devices connection check to API: {$baseUrl}...");

        // Gather all device IDs
        // 1. 'superadmin'
        $deviceIds = ['superadmin'];

        // 2. Active schools with WA feature enabled
        try {
            $schoolIds = School::where('wa_enabled', true)->pluck('id')->map(function ($id) {
                return (string) $id;
            })->toArray();
            $deviceIds = array_merge($deviceIds, $schoolIds);
        } catch (\Exception $e) {
            $this->error("Failed to query schools: " . $e->getMessage());
            Log::error("WA Reconnect Error - School Query: " . $e->getMessage());
        }

        $this->info("Found " . count($deviceIds) . " WhatsApp device(s) to check: " . implode(', ', $deviceIds));

        foreach ($deviceIds as $deviceId) {
            $this->line("Checking device: <fg=cyan>{$deviceId}</>...");

            try {
                // Check connection status
                $statusRes = Http::timeout(10)
                    ->withBasicAuth($user, $pass)
                    ->withHeaders(['X-Device-Id' => $deviceId])
                    ->get("{$baseUrl}/app/status");

                if (!$statusRes->successful()) {
                    $this->warn("  ✗ Device status check failed (HTTP {$statusRes->status()}). GOWA might not be running or device ID is invalid.");
                    continue;
                }

                $data        = $statusRes->json();
                $isLoggedIn  = $data['results']['is_logged_in']  ?? false;
                $isConnected = $data['results']['is_connected']   ?? false;

                if ($isLoggedIn) {
                    if ($isConnected) {
                        $this->line("  ✓ <fg=green>Connected</>");
                    } else {
                        $this->warn("  ⚠ Disconnected but Logged In. Triggering reconnect...");
                        Log::info("WA Reconnect - Device {$deviceId} is disconnected. Pinging reconnect API...");

                        // Call GOWA reconnect endpoint
                        $reconnectRes = Http::timeout(15)
                            ->withBasicAuth($user, $pass)
                            ->withHeaders(['X-Device-Id' => $deviceId])
                            ->get("{$baseUrl}/app/reconnect");

                        if ($reconnectRes->successful()) {
                            // Check status again
                            $statusResAfter = Http::timeout(10)
                                ->withBasicAuth($user, $pass)
                                ->withHeaders(['X-Device-Id' => $deviceId])
                                ->get("{$baseUrl}/app/status");

                            if ($statusResAfter->successful()) {
                                $dataAfter = $statusResAfter->json();
                                if (($dataAfter['results']['is_logged_in'] ?? false) && ($dataAfter['results']['is_connected'] ?? false)) {
                                    $this->info("  ✓ <fg=green>Successfully reconnected!</>");
                                    Log::info("WA Reconnect - Device {$deviceId} successfully reconnected.");
                                    continue;
                                }
                            }
                            $this->error("  ✗ Reconnect call completed, but device is still disconnected.");
                        } else {
                            $this->error("  ✗ Reconnect call failed (HTTP {$reconnectRes->status()}): " . $reconnectRes->body());
                            Log::warning("WA Reconnect - Failed to reconnect device {$deviceId}: HTTP " . $reconnectRes->status());
                        }
                    }
                } else {
                    $this->line("  ○ Not Logged In (needs QR scan). Skipping reconnect.");
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Exception occurred: " . $e->getMessage());
                Log::error("WA Reconnect - Device {$deviceId} error: " . $e->getMessage());
            }
        }

        $this->info("WhatsApp devices connection check completed.");
        return self::SUCCESS;
    }
}
