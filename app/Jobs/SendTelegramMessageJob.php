<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendTelegramMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;

    protected $token;
    protected $chatId;
    protected $text;
    protected $schoolId;

    public function __construct(string $token, string $chatId, string $text, ?int $schoolId = null)
    {
        $this->token = $token;
        $this->chatId = $chatId;
        $this->text = $text;
        $this->schoolId = $schoolId;
    }

    public function handle(): void
    {
        $url = "https://api.telegram.org/bot{$this->token}/sendMessage";

        try {
            $response = Http::timeout(15)->post($url, [
                'chat_id' => $this->chatId,
                'text' => $this->text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            if ($response->successful()) {
                return;
            }

            $errorMsg = 'Telegram API Error: HTTP ' . $response->status() . ' - ' . $response->body();
            Log::error($errorMsg);
            
            // Log to ApiLog for admin visibility
            \App\Models\ApiLog::create([
                'school_id' => $this->schoolId,
                'action' => 'telegram_error',
                'success' => false,
                'message' => substr($errorMsg, 0, 500),
                'created_at' => now()
            ]);

            // Release back to queue for retry
            $this->release($this->backoff);
        } catch (\Exception $e) {
            $errorMsg = 'Telegram Job Exception: ' . $e->getMessage();
            Log::error($errorMsg);

            \App\Models\ApiLog::create([
                'school_id' => $this->schoolId,
                'action' => 'telegram_error',
                'success' => false,
                'message' => substr($errorMsg, 0, 500),
                'created_at' => now()
            ]);

            $this->release($this->backoff);
        }
    }
}
