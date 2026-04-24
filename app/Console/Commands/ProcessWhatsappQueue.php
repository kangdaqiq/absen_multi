<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MessageQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessWhatsappQueue extends Command
{
    protected $signature = 'wa:process {--limit=10}';
    protected $description = 'Process pending WhatsApp messages from the queue';

    public function handle()
    {
        $limit = $this->option('limit');

        // 1. ATOMIC LOCK & UPDATE
        // Prevent multiple workers from picking the same messages
        $messages = [];

        \Illuminate\Support\Facades\DB::transaction(function () use ($limit, &$messages) {
            $candidates = MessageQueue::query()
                ->select('message_queue.*')
                ->leftJoin('schools', 'message_queue.school_id', '=', 'schools.id')
                ->where('message_queue.status', 'pending')
                ->where(function ($q) {
                    $q->whereNull('message_queue.school_id')
                        ->orWhere('schools.wa_enabled', true);
                })
                ->orderBy('message_queue.created_at', 'asc')
                ->limit($limit)
                ->lockForUpdate()
                ->get();

            if ($candidates->isNotEmpty()) {
                $ids = $candidates->pluck('id');
                // Mark as processing immediately so other workers skip them
                MessageQueue::whereIn('id', $ids)->update(['status' => 'processing', 'updated_at' => now()]);
                $messages = $candidates;
            }
        });

        if (empty($messages)) {
            // $this->info('No pending messages.'); // Reduce noise
            return;
        }

        $this->info("Found " . count($messages) . " messages. Processing...");

        foreach ($messages as $msg) {
            // Use fresh instance or just use data (status is already 'processing')
            // $msg->status = 'processing'; 

            $success = $this->sendMessage($msg->phone_number, $msg->message, $msg->school_id);

            // Final Update
            $msg->update([
                'status' => $success ? 'sent' : 'failed',
                'updated_at' => now(),
                'last_error' => $success ? null : 'API Request Failed'
            ]);

            $this->info("Message ID {$msg->id} -> " . ($success ? 'SENT' : 'FAILED'));

            // Delay to prevent WA Ban
            sleep(2);
        }
    }

    private function sendMessage($phone, $message, $schoolId = null)
    {
        $baseUrl = rtrim(env('WA_API_BASE_URL', 'http://localhost:3000'), '/');
        $url     = $baseUrl . '/send/message';
        $user    = env('WA_API_USER', 'admin');
        $pass    = env('WA_API_PASS', '04112000');

        $headers = $schoolId ? ['X-Device-Id' => (string)$schoolId] : [];

        try {
            $response = Http::timeout(20)
                ->withBasicAuth($user, $pass)
                ->withHeaders($headers)
                ->post($url, [
                    'phone'   => $phone,
                    'message' => $message,
                ]);

            if ($response->successful()) {
                $body = $response->json();
                return isset($body['code']) && $body['code'] === 'SUCCESS';
            }

            Log::error("WA API Error: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("WA Exception: " . $e->getMessage());
            return false;
        }
    }
}
