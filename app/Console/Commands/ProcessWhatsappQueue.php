<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MessageQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessWhatsappQueue extends Command
{
    protected $signature = 'wa:process {--limit=10}';
    protected $description = 'Process pending WhatsApp messages from the queue (with anti-ban protection)';

    /**
     * Batas maksimal pesan yang dikirim per sekolah per jam.
     * Melebihi batas ini rawan memicu ban dari WhatsApp.
     */
    private const RATE_LIMIT_PER_HOUR = 50;

    public function handle()
    {
        $limit = $this->option('limit');

        // Expire: tandai semua pesan pending dari hari sebelumnya sebagai failed
        MessageQueue::where('status', 'pending')
            ->where('created_at', '<', today())
            ->update([
                'status'      => 'failed',
                'retry_count' => 3,
                'last_error'  => 'Expired - Message from previous day',
                'updated_at'  => now(),
            ]);

        // Atomic lock & update — cegah worker ganda ambil pesan yang sama.
        // Hanya ambil pesan yang scheduled_at sudah lewat atau NULL (kirim segera).
        $messages = [];

        DB::transaction(function () use ($limit, &$messages) {
            $candidates = MessageQueue::query()
                ->select('message_queues.*')
                ->leftJoin('schools', 'message_queues.school_id', '=', 'schools.id')
                ->where('message_queues.status', 'pending')
                // Hanya pesan yang sudah waktunya dikirim
                ->where(function ($q) {
                    $q->whereNull('message_queues.scheduled_at')
                      ->orWhere('message_queues.scheduled_at', '<=', now());
                })
                ->when(config('app.mode', 'hosted') !== 'self_hosted', function ($query) {
                    $query->where(function ($q) {
                        $q->whereNull('message_queues.school_id')
                          ->orWhere('schools.wa_enabled', true);
                    });
                })
                ->orderBy('message_queues.priority', 'desc')
                ->orderBy('message_queues.created_at', 'asc')
                ->limit($limit)
                ->lockForUpdate()
                ->get();

            if ($candidates->isNotEmpty()) {
                $ids = $candidates->pluck('id');
                // Tandai sebagai 'processing' agar worker lain melewatinya
                MessageQueue::whereIn('id', $ids)->update(['status' => 'processing', 'updated_at' => now()]);
                $messages = $candidates;
            }
        });

        // Auto-retry: kembalikan pesan 'failed' hari ini (retry_count < 3) ke 'pending'
        MessageQueue::where('status', 'failed')
            ->whereDate('created_at', today())
            ->where(function ($q) {
                $q->whereNull('retry_count')->orWhere('retry_count', '<', 3);
            })
            ->update(['status' => 'pending', 'updated_at' => now()]);

        if (empty($messages)) {
            return;
        }

        $this->info("Found " . count($messages) . " messages. Processing...");

        foreach ($messages as $msg) {
            // Guard: lewati pesan dari hari sebelumnya
            if ($msg->created_at->lt(today())) {
                $msg->update([
                    'status'      => 'failed',
                    'updated_at'  => now(),
                    'retry_count' => 3,
                    'last_error'  => 'Expired - Message from previous day',
                ]);
                $this->info("Message ID {$msg->id} -> EXPIRED (MARKED FAILED)");
                continue;
            }

            // === RATE LIMITING PER SEKOLAH ===
            // Cek berapa pesan sudah terkirim jam ini untuk sekolah ini.
            // Jika sudah mencapai batas, kembalikan ke 'pending' dan skip.
            if ($msg->school_id !== null && $this->isRateLimited($msg->school_id)) {
                $msg->update(['status' => 'pending', 'updated_at' => now()]);
                $this->warn("Message ID {$msg->id} -> RATE LIMITED (school_id: {$msg->school_id}), will retry next run.");
                continue;
            }

            $result  = $this->sendMessage($msg->phone_number, $msg->message, $msg->school_id);
            $success = $result['success'];

            $msg->update([
                'status'      => $success ? 'sent' : 'failed',
                'updated_at'  => now(),
                'retry_count' => $success ? $msg->retry_count : (($msg->retry_count ?? 0) + 1),
                'last_error'  => $success ? null : $result['error'],
            ]);

            $this->info("Message ID {$msg->id} -> " . ($success ? 'SENT' : 'FAILED'));

            // === RANDOM JITTER DELAY (3–8 detik) ===
            // Delay tidak konsisten meniru pola manusia dan menghindari
            // deteksi bot oleh WhatsApp yang mengenali pola interval tetap.
            $jitter = rand(3_000_000, 8_000_000); // microseconds
            usleep($jitter);
        }
    }

    /**
     * Cek apakah sekolah ini sudah mencapai batas rate limit per jam.
     */
    private function isRateLimited(int $schoolId): bool
    {
        $sentThisHour = MessageQueue::where('school_id', $schoolId)
            ->where('status', 'sent')
            ->where('updated_at', '>=', now()->startOfHour())
            ->count();

        return $sentThisHour >= self::RATE_LIMIT_PER_HOUR;
    }

    /**
     * Kirim pesan via GOWA API.
     */
    private function sendMessage($phone, $message, $schoolId = null)
    {
        $baseUrl  = rtrim(env('GOWA_API_BASE_URL', 'http://localhost:3000'), '/');
        $url      = $baseUrl . '/send/message';
        $user     = env('GOWA_API_USER', 'admin');
        $pass     = env('GOWA_API_PASS', 'jagattech');
        $deviceId = $schoolId ? (string)$schoolId : 'superadmin';

        try {
            $response = Http::timeout(20)
                ->withBasicAuth($user, $pass)
                ->withHeaders(['X-Device-Id' => $deviceId])
                ->post($url, [
                    'phone'   => $phone,
                    'message' => $message,
                ]);

            if ($response->successful()) {
                $body = $response->json();
                if (isset($body['code']) && $body['code'] === 'SUCCESS') {
                    return ['success' => true, 'error' => null];
                }
                return ['success' => false, 'error' => $body['message'] ?? 'API Code is not SUCCESS'];
            }

            // Check if failure is due to disconnection
            $isDisconnected = ($response->status() === 401) ||
                              (str_contains(strtolower($response->body()), 'please reconnect')) ||
                              (str_contains(strtolower($response->body()), 'not connect'));

            if ($isDisconnected) {
                Log::warning("WA API returned disconnect error for device {$deviceId}. Attempting auto-reconnect before retry...");
                
                try {
                    $reconnectUrl = $baseUrl . '/app/reconnect';
                    $reconnectRes = Http::timeout(15)
                        ->withBasicAuth($user, $pass)
                        ->withHeaders(['X-Device-Id' => $deviceId])
                        ->get($reconnectUrl);

                    if ($reconnectRes->successful()) {
                        // Wait 2 seconds for connection to stabilize
                        sleep(2);

                        // Retry sending the message
                        Log::info("WA API: Retrying message send for device {$deviceId} after successful reconnect...");
                        $retryResponse = Http::timeout(20)
                            ->withBasicAuth($user, $pass)
                            ->withHeaders(['X-Device-Id' => $deviceId])
                            ->post($url, [
                                'phone'   => $phone,
                                'message' => $message,
                            ]);

                        if ($retryResponse->successful()) {
                            $retryBody = $retryResponse->json();
                            if (isset($retryBody['code']) && $retryBody['code'] === 'SUCCESS') {
                                return ['success' => true, 'error' => null];
                            }
                            return ['success' => false, 'error' => $retryBody['message'] ?? 'API Code is not SUCCESS after retry'];
                        }
                        $response = $retryResponse; // Use retry response for error logging
                    }
                } catch (\Exception $retryEx) {
                    Log::error("WA API Retry Exception for device {$deviceId}: " . $retryEx->getMessage());
                }
            }

            $errorMsg = 'HTTP ' . $response->status() . ': ' . ($response->json()['message'] ?? $response->body());
            Log::error("WA API Error: " . $errorMsg);
            return ['success' => false, 'error' => $errorMsg];
        } catch (\Exception $e) {
            Log::error("WA Exception: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
