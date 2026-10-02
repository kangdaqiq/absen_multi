<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MessageQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;

class ProcessWhatsappQueue extends Command
{
    protected $signature = 'wa:process 
                            {--school= : ID Sekolah spesifik yang akan diproses (atau "none" untuk pesan tanpa sekolah)}
                            {--limit=10 : Jumlah pesan per batch per sekolah}
                            {--sync : Jalankan secara sinkron tanpa sub-proses paralel}';

    protected $description = 'Process pending WhatsApp messages per school in parallel (with anti-ban protection)';

    /**
     * Batas maksimal pesan yang dikirim per sekolah per jam.
     * Melebihi batas ini rawan memicu ban dari WhatsApp.
     */
    private const RATE_LIMIT_PER_HOUR = 500;

    public function handle()
    {
        $limit = (int) $this->option('limit') ?: 10;
        $schoolOption = $this->option('school');

        // Jika opsi --school diberikan, jalankan worker terisolasi untuk sekolah tersebut
        if ($schoolOption !== null) {
            $schoolId = in_array(strtolower((string)$schoolOption), ['none', 'null', '0'], true) ? null : (int) $schoolOption;
            return $this->handleSchoolWorker($schoolId, $limit);
        }

        // Jika tanpa opsi --school, bertindak sebagai Master Dispatcher
        return $this->handleMasterDispatcher($limit);
    }

    /**
     * Master Dispatcher:
     * Melakukan pembersihan global, mencari semua sekolah yang memiliki antrean pending,
     * lalu menjalankan worker independen per sekolah secara paralel.
     */
    private function handleMasterDispatcher(int $limit): int
    {
        // 1. Self-healing: Reset stuck 'processing' messages (older than 10 minutes) back to 'pending'
        MessageQueue::where('status', 'processing')
            ->where('updated_at', '<', now()->subMinutes(10))
            ->update(['status' => 'pending', 'updated_at' => now()]);

        // 2. Expire: tandai semua pesan pending dari hari sebelumnya sebagai failed
        MessageQueue::where('status', 'pending')
            ->where('created_at', '<', today())
            ->update([
                'status'      => 'failed',
                'retry_count' => 3,
                'last_error'  => 'Expired - Message from previous day',
                'updated_at'  => now(),
            ]);

        // 3. Ambil seluruh target sekolah yang saat ini memiliki pesan pending
        $targetQuery = MessageQueue::query()
            ->leftJoin('schools', 'message_queues.school_id', '=', 'schools.id')
            ->where('message_queues.status', 'pending')
            ->where(function ($q) {
                $q->whereNull('message_queues.scheduled_at')
                  ->orWhere('message_queues.scheduled_at', '<=', now());
            })
            ->when(config('app.mode', 'hosted') !== 'self_hosted', function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('message_queues.school_id')
                      ->orWhere('schools.wa_enabled', true);
                });
            });

        $targets = $targetQuery->distinct()->pluck('message_queues.school_id')->toArray();

        if (empty($targets)) {
            $this->line("Tidak ada antrean WhatsApp pending.");
            return 0;
        }

        $this->info("Menemukan antrean WhatsApp untuk " . count($targets) . " target sekolah/global.");

        $isSync = (bool) $this->option('sync');

        foreach ($targets as $targetId) {
            $targetArg = $targetId === null ? 'none' : (string) $targetId;
            $schoolLabel = $targetId === null ? 'Global / SuperAdmin' : "Sekolah ID: {$targetId}";

            if ($isSync) {
                $this->info("Memproses {$schoolLabel} secara sinkron...");
                $this->handleSchoolWorker($targetId, $limit);
            } else {
                // Jalankan worker paralel di latar belakang menggunakan PHP Process
                $this->info("Mendispatch worker paralel untuk {$schoolLabel}...");
                try {
                    Process::path(base_path())->start([
                        PHP_BINARY,
                        base_path('artisan'),
                        'wa:process',
                        "--school={$targetArg}",
                        "--limit={$limit}"
                    ]);
                } catch (\Throwable $e) {
                    Log::error("Gagal mendispatch background worker WA untuk {$schoolLabel}: " . $e->getMessage());
                    // Fallback sinkron jika background process gagal di-spawn
                    $this->handleSchoolWorker($targetId, $limit);
                }
            }
        }

        return 0;
    }

    /**
     * Worker Terisolasi per Sekolah:
     * Memproses antrean khusus untuk 1 sekolah tertentu dengan lock independen,
     * sehingga tidak pernah mengganggu atau terblokir oleh antrean sekolah lain.
     */
    private function handleSchoolWorker(?int $schoolId, int $limit): int
    {
        $lockKey = "wa_worker_lock_school_" . ($schoolId ?? 'global');
        $lock = Cache::lock($lockKey, 300); // 5 menit lock

        if (!$lock->get()) {
            $this->warn("Worker untuk " . ($schoolId ? "Sekolah ID: {$schoolId}" : "Global") . " sedang berjalan. Dilewati.");
            return 0;
        }

        try {
            $messages = [];

            DB::transaction(function () use ($schoolId, $limit, &$messages) {
                $candidates = MessageQueue::query()
                    ->select('message_queues.*')
                    ->leftJoin('schools', 'message_queues.school_id', '=', 'schools.id')
                    ->where('message_queues.status', 'pending')
                    ->where(function ($q) {
                        $q->whereNull('message_queues.scheduled_at')
                          ->orWhere('message_queues.scheduled_at', '<=', now());
                    })
                    ->when($schoolId === null, function ($q) {
                        $q->whereNull('message_queues.school_id');
                    }, function ($q) use ($schoolId) {
                        $q->where('message_queues.school_id', $schoolId);
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
                    MessageQueue::whereIn('id', $ids)->update(['status' => 'processing', 'updated_at' => now()]);
                    $messages = $candidates;
                }
            });

            if (empty($messages)) {
                return 0;
            }

            $schoolLabel = $schoolId ? "Sekolah ID: {$schoolId}" : "Global";
            $this->info("[{$schoolLabel}] Memproses " . count($messages) . " pesan...");

            foreach ($messages as $msg) {
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

                $this->info("[{$schoolLabel}] Msg ID {$msg->id} -> " . ($success ? 'SENT' : 'FAILED'));

                // Random Jitter Delay (Anti-ban) khusus per device sekolah ini
                $minDelay = (int) env('WA_DELAY_MIN_SECONDS', 6) * 1_000_000;
                $maxDelay = (int) env('WA_DELAY_MAX_SECONDS', 12) * 1_000_000;
                $jitter   = rand(min($minDelay, $maxDelay), max($minDelay, $maxDelay));
                usleep($jitter);
            }
        } finally {
            $lock->release();
        }

        return 0;
    }

    /**
     * Cek apakah sekolah ini sudah mencapai batas rate limit per jam.
     */
    private function isRateLimited(int $schoolId): bool
    {
        $limitPerHour = (int) env('WA_RATE_LIMIT_PER_HOUR', self::RATE_LIMIT_PER_HOUR);
        if ($limitPerHour <= 0) {
            return false;
        }

        $sentThisHour = MessageQueue::where('school_id', $schoolId)
            ->where('status', 'sent')
            ->where('updated_at', '>=', now()->startOfHour())
            ->count();

        return $sentThisHour >= $limitPerHour;
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
                        $response = $retryResponse;
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
