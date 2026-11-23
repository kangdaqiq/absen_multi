<?php

namespace App\Services;

use App\Models\School;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ShopeePayService
 * 
 * Jembatan komunikasi antara Sistem Absensi Laravel dan ShopeePay Partner API Gateway
 * untuk verifikasi mutasi dan aktivasi QRIS secara real-time & otomatis.
 */
class ShopeePayService
{
    /**
     * Mendapatkan URL dasar ShopeePay API Gateway
     */
    public static function getGatewayUrl(): string
    {
        return rtrim(config('services.shopeepay.gateway_url') ?: 'http://127.0.0.1:3005', '/');
    }

    /**
     * Mendapatkan API Key untuk autentikasi ke Gateway
     */
    public static function getApiKey(): string
    {
        return (string) (config('services.shopeepay.api_key') ?: 'kunci-rahasia-anda-2026');
    }

    /**
     * Cek apakah mode QRIS otomatis aktif
     */
    public static function isAutomaticMode(): bool
    {
        $mode = config('services.qris.mode', env('QRIS_MODE', 'automatic'));
        return strtolower((string)$mode) === 'automatic';
    }

    /**
     * Cek status koneksi dan kesehatan ShopeePay Gateway
     */
    public static function checkHealth(): bool
    {
        try {
            $response = Http::timeout(4)->get(self::getGatewayUrl() . '/api/health');
            return $response->successful() && ($response->json('success') === true);
        } catch (\Throwable $e) {
            Log::warning('ShopeePay Gateway health check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cek validitas token ShopeePay merchant pada gateway
     */
    public static function checkTokenStatus(): array
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => self::getApiKey(),
            ])->timeout(8)->get(self::getGatewayUrl() . '/token-status');

            if ($response->successful()) {
                return $response->json('data') ?? ['token_status' => 'unknown'];
            }

            return ['token_status' => 'error', 'message' => 'HTTP ' . $response->status()];
        } catch (\Throwable $e) {
            return ['token_status' => 'unreachable', 'message' => $e->getMessage()];
        }
    }

    /**
     * Cek mutasi pembayaran masuk di ShopeePay Gateway berdasarkan nominal dan waktu awal.
     *
     * @param float|int $amount Nominal yang dicari
     * @param int|\DateTimeInterface $startTime Waktu awal pencarian mutasi
     * @param string|null $trxId Identifier invoice/subscription untuk proteksi anti double-claim
     * @return array|null Mengembalikan data transaksi jika lunas, atau null jika belum ditemukan
     */
    public static function checkPayment(float|int $amount, int|\DateTimeInterface $startTime, ?string $trxId = null): ?array
    {
        $startSeconds = ($startTime instanceof \DateTimeInterface)
            ? $startTime->getTimestamp()
            : (int) $startTime;

        $targetAmount = (int) round($amount);
        if ($targetAmount <= 0) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'X-API-Key'    => self::getApiKey(),
                'Content-Type' => 'application/json',
            ])->timeout(config('services.shopeepay.timeout', 10))
              ->post(self::getGatewayUrl() . '/check-payment', [
                  'amount'    => $targetAmount,
                  'startTime' => $startSeconds,
                  'trx_id'    => $trxId,
              ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['paid']) && !empty($data['transaction'])) {
                    return $data['transaction'];
                }
            }
        } catch (\Throwable $e) {
            Log::error('ShopeePay checkPayment error: ' . $e->getMessage(), [
                'amount'    => $targetAmount,
                'startTime' => $startSeconds,
                'trx_id'    => $trxId,
            ]);
        }

        return null;
    }

    /**
     * Cek status pembayaran untuk subscription sekolah secara spesifik.
     * Jika ditemukan mutasi lunas di ShopeePay, otomatis aktivasi paket langganan.
     */
    public static function checkSubscription(Subscription $subscription): ?array
    {
        if ($subscription->status === 'paid') {
            return [
                'already_paid' => true,
                'transactionId' => $subscription->payment_ref,
            ];
        }

        // Hitung waktu awal (15 menit sebelum tagihan dibuat sebagai batas toleransi)
        $startTime = $subscription->created_at
            ? $subscription->created_at->copy()->subMinutes(15)->getTimestamp()
            : now()->subHour()->getTimestamp();

        $scopeId = 'SUB-' . $subscription->id . ($subscription->unique_code ? '-' . $subscription->unique_code : '');

        $transaction = self::checkPayment($subscription->amount, $startTime, $scopeId);

        if ($transaction && !empty($transaction['status']) && $transaction['status'] === 'success') {
            self::processPaidSubscription($subscription, $transaction);
            return $transaction;
        }

        return null;
    }

    /**
     * Proses aktivasi langganan sekolah setelah pembayaran ShopeePay terverifikasi.
     */
    public static function processPaidSubscription(Subscription $subscription, array $transaction): bool
    {
        // Hindari proses ganda jika status sudah paid
        if ($subscription->status === 'paid') {
            return true;
        }

        return DB::transaction(function () use ($subscription, $transaction) {
            $school = School::lockForUpdate()->find($subscription->school_id);
            if (!$school) {
                return false;
            }

            $package = $subscription->package;
            $now = now();
            $currentExpired = $school->expired_at;
            $baseDate = ($currentExpired && $currentExpired->isFuture()) ? clone $currentExpired : $now;

            $newExpiredAt = $subscription->billing_cycle === 'yearly'
                ? (clone $baseDate)->addYear()
                : (clone $baseDate)->addMonth();

            $refId = (string) ($transaction['transactionId'] ?? $transaction['orderSn'] ?? ('SPAY-' . time()));

            // Update record subscription
            $subscription->update([
                'status'         => 'paid',
                'paid_at'        => $now,
                'expired_at'     => $newExpiredAt,
                'payment_method' => 'qris',
                'payment_ref'    => $refId,
            ]);

            // Update batas kuota & masa aktif sekolah
            $updateSchool = [
                'expired_at' => $newExpiredAt,
                'is_active'  => true,
            ];

            if ($package) {
                $updateSchool['student_limit']        = $package->student_limit;
                $updateSchool['teacher_limit']        = $package->teacher_limit;
                $updateSchool['bot_user_limit']       = $package->bot_user_limit;
                $updateSchool['history_quota_months'] = $package->history_quota_months;
                $updateSchool['wa_enabled']           = $package->wa_enabled;
                if ($package->bot_enabled) {
                    $updateSchool['bot_enabled'] = true;
                }
            }

            $school->update($updateSchool);

            Log::info("ShopeePay QRIS Subscription activated for School #{$school->id} ({$school->name}) - Ref: {$refId} - Expired: {$newExpiredAt->format('Y-m-d')}");

            // Kirim notifikasi bukti bayar via WhatsApp ke Admin Sekolah
            try {
                app(SubscriptionNotificationService::class)->sendPaymentSuccessWhatsApp($subscription);
            } catch (\Throwable $e) {
                Log::error("Failed to send WhatsApp payment success notice for subscription #{$subscription->id}: " . $e->getMessage());
            }

            // Simpan riwayat log callback/transaksi lokal
            self::logTransactionToFile($subscription, $transaction);

            return true;
        });
    }

    /**
     * Menyimpan log transaksi pembayaran QRIS ke direktori log untuk arsip audit
     */
    protected static function logTransactionToFile(Subscription $subscription, array $transaction): void
    {
        try {
            $logDir = storage_path('logs/callback_qris/');
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0777, true);
            }

            $dateStr = now()->format('Y-m-d');
            $logFile = $logDir . "shopeepay_{$dateStr}.json";

            $logEntry = [
                'time'            => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'subscription_id' => $subscription->id,
                'school_id'       => $subscription->school_id,
                'school_name'     => $subscription->school?->name,
                'amount'          => $subscription->amount,
                'transaction'     => $transaction,
            ];

            $logs = [];
            if (file_exists($logFile)) {
                $content = @file_get_contents($logFile);
                $logs = json_decode($content, true) ?: [];
            }

            $logs[] = $logEntry;
            if (count($logs) > 100) {
                $logs = array_slice($logs, -100);
            }

            @file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            Log::warning('Failed writing ShopeePay transaction log file: ' . $e->getMessage());
        }
    }
}
