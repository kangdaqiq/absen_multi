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
        $chunks = $this->splitMessage($this->text, 4000);
        $totalChunks = count($chunks);
        $allSuccess = true;
        $lastError = null;

        foreach ($chunks as $index => $chunk) {
            $chunkText = $chunk;
            if ($totalChunks > 1) {
                $partHeader = "<i>(Bagian " . ($index + 1) . "/{$totalChunks})</i>\n\n";
                $chunkText = $partHeader . $chunkText;
            }

            try {
                $response = Http::timeout(15)->post($url, [
                    'chat_id' => $this->chatId,
                    'text' => $chunkText,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);

                // Fallback: If Telegram cannot parse broken HTML tags, send as plain text
                if (!$response->successful() && str_contains(strtolower($response->body()), 'parse')) {
                    $response = Http::timeout(15)->post($url, [
                        'chat_id' => $this->chatId,
                        'text' => strip_tags($chunkText),
                        'disable_web_page_preview' => true,
                    ]);
                }

                if (!$response->successful()) {
                    $allSuccess = false;
                    $lastError = 'Telegram API Error: HTTP ' . $response->status() . ' - ' . $response->body();
                    Log::error($lastError);
                    break;
                }

                // Add slight delay between multi-part messages to maintain order
                if ($totalChunks > 1 && $index < $totalChunks - 1) {
                    usleep(300000); // 300ms
                }
            } catch (\Exception $e) {
                $allSuccess = false;
                $lastError = 'Telegram Job Exception: ' . $e->getMessage();
                Log::error($lastError);
                break;
            }
        }

        if ($allSuccess) {
            \App\Models\TelegramLog::create([
                'school_id' => $this->schoolId,
                'chat_id' => $this->chatId,
                'message' => $this->text,
                'status' => 'sent',
                'error' => null,
            ]);
            return;
        }

        \App\Models\TelegramLog::create([
            'school_id' => $this->schoolId,
            'chat_id' => $this->chatId,
            'message' => $this->text,
            'status' => 'failed',
            'error' => $lastError,
        ]);

        // Log to ApiLog for admin visibility
        \App\Models\ApiLog::create([
            'school_id' => $this->schoolId,
            'action' => 'telegram_error',
            'success' => false,
            'message' => substr($lastError, 0, 500),
            'created_at' => now()
        ]);

        // Release back to queue for retry
        $this->release($this->backoff);
    }

    /**
     * Pecah pesan panjang menjadi beberapa bagian agar tidak melebihi batas 4.096 karakter Telegram.
     */
    private function splitMessage(string $text, int $maxLength = 3900): array
    {
        if (mb_strlen($text) <= $maxLength) {
            return [$text];
        }

        $chunks = [];
        $lines = explode("\n", $text);
        $currentChunk = '';

        foreach ($lines as $line) {
            if (mb_strlen($currentChunk . "\n" . $line) > $maxLength) {
                if (!empty($currentChunk)) {
                    $chunks[] = trim($currentChunk);
                    $currentChunk = '';
                }

                while (mb_strlen($line) > $maxLength) {
                    $chunks[] = mb_substr($line, 0, $maxLength);
                    $line = mb_substr($line, $maxLength);
                }
                $currentChunk = $line;
            } else {
                $currentChunk = empty($currentChunk) ? $line : $currentChunk . "\n" . $line;
            }
        }

        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }
}
