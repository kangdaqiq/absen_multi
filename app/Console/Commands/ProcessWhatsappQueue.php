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

        $messages = MessageQueue::where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        if ($messages->isEmpty()) {
            $this->info('No pending messages.');
            return;
        }

        $this->info("Found {$messages->count()} messages. Processing...");

        foreach ($messages as $msg) {
            $msg->update(['status' => 'processing']);

            $success = $this->sendMessage($msg->phone_number, $msg->message);

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

    private function sendMessage($phone, $message)
    {
        // Config from env
        $url = env('WA_API_URL', 'http://localhost:3000/send/message');
        $user = env('WA_API_USER', 'admin');
        $pass = env('WA_API_PASS', '04112000');

        try {
            $response = Http::timeout(20)
                ->withBasicAuth($user, $pass)
                ->post($url, [
                    'phone' => $phone,
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
