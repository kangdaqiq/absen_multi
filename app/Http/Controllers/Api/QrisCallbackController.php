<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\ShopeePayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QrisCallbackController extends Controller
{
    /**
     * Handle incoming QRIS callback notification (ShopeePay Gateway & Webhook).
     * POST /api/callback/accept/{key?}
     * POST /api/endpoint/accept/{key?}
     */
    public function accept(Request $request, string $key = ''): JsonResponse
    {
        $shopeeApiKey = config('services.shopeepay.api_key', env('SHOPEEPAY_API_KEY', 'kunci-rahasia-anda-2026'));
        $qiospaySecretKey = config('services.qiospay.secret_key', env('QIOSPAY_SECRET_KEY', 'mysecret'));

        $clientKey = $key 
            ?: $request->header('X-API-Key') 
            ?: $request->input('api_key') 
            ?: $request->input('secret_key');

        // Validate Key against configured ShopeePay or Qiospay secret keys
        $validKeys = array_filter([$shopeeApiKey, $qiospaySecretKey]);
        if (empty($clientKey) || !in_array($clientKey, $validKeys, true)) {
            return response()->json([
                'status'  => 'reject',
                'message' => 'Invalid secret key',
                'data'    => null,
            ], 403);
        }

        $inputRaw = $request->getContent();
        $json = json_decode($inputRaw, true);

        if (!is_array($json)) {
            $json = $request->all();
        }

        $responseData = [
            'name'    => null,
            'nmid'    => null,
            'amount'  => null,
            'type'    => null,
            'fee'     => null,
            'refid'   => null,
            'issuer'  => null,
            'balance' => null,
            'time'    => null,
        ];

        if (is_array($json) && isset($json['data']) && is_array($json['data'])) {
            $data = $json['data'];
            $responseData = [
                'name'    => $data['name'] ?? null,
                'nmid'    => $data['nmid'] ?? null,
                'amount'  => $data['amount'] ?? null,
                'type'    => $data['type'] ?? null,
                'fee'     => $data['fee'] ?? null,
                'refid'   => $data['refid'] ?? $data['transactionId'] ?? null,
                'issuer'  => $data['issuer'] ?? null,
                'balance' => $data['balance'] ?? null,
                'time'    => $data['time'] ?? null,
            ];
        } else {
            // ShopeePay Gateway payload format
            $responseData = [
                'name'    => $json['storeName'] ?? $json['store_name'] ?? 'ShopeePay Partner',
                'nmid'    => $json['nmid'] ?? 'SHOPEEPAY',
                'amount'  => $json['amount'] ?? $json['real_amount'] ?? null,
                'type'    => 'CR',
                'fee'     => 0,
                'refid'   => $json['transactionId'] ?? $json['orderSn'] ?? $json['order_sn'] ?? ($json['trx_id'] ?? null),
                'issuer'  => $json['issuer'] ?? 'ShopeePay',
                'balance' => null,
                'time'    => $json['time'] ?? now()->toIso8601String(),
            ];
        }

        $amount = (float) ($responseData['amount'] ?? 0);
        $refid = (string) ($responseData['refid'] ?? '');

        // ── Match and activate pending subscription ──────────────────────────
        $matchedSubscription = null;
        if ($amount > 0 && \Illuminate\Support\Facades\Schema::hasTable('subscriptions')) {
            try {
                $query = Subscription::with(['school', 'package'])
                    ->where('status', 'unpaid');

                // Coba cocokkan dengan trx_id / id jika disertakan
                if (!empty($json['subscription_id'])) {
                    $query->where('id', $json['subscription_id']);
                } elseif (!empty($json['trx_id']) && preg_match('/SUB-(\d+)/', (string)$json['trx_id'], $m)) {
                    $query->where('id', $m[1]);
                } else {
                    $query->where('amount', $amount);
                }

                $matchedSubscription = $query->latest()->first();

                if ($matchedSubscription) {
                    ShopeePayService::processPaidSubscription($matchedSubscription, [
                        'transactionId' => $refid,
                        'orderSn'       => $refid,
                        'amount'        => $amount,
                        'status'        => 'success',
                        'issuer'        => $responseData['issuer'] ?? 'ShopeePay',
                        'time'          => $responseData['time'] ?? now()->toIso8601String(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Subscription query in callback failed: ' . $e->getMessage());
            }
        }

        // ── Automatic logging to storage/logs/callback_qris/ ────────────────
        $logDir = storage_path('logs/callback_qris/');
        $nameLog = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($responseData['name'] ?? 'unknown'));
        $nmidLog = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($responseData['nmid'] ?? 'nonmid'));
        
        if (empty($nameLog)) {
            $nameLog = 'unknown';
        }
        if (empty($nmidLog)) {
            $nmidLog = 'nonmid';
        }

        $logFile = $logDir . "data[{$nameLog}]-{$nmidLog}.json";

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }

        $logEntry = [
            'time'                  => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'ip'                    => $request->ip(),
            'raw'                   => $inputRaw,
            'json'                  => $json,
            'matched_subscription'  => $matchedSubscription ? [
                'id'            => $matchedSubscription->id,
                'school_id'     => $matchedSubscription->school_id,
                'school_name'   => $matchedSubscription->school?->name,
                'package'       => $matchedSubscription->package?->name,
                'amount'        => $matchedSubscription->amount,
                'billing_cycle' => $matchedSubscription->billing_cycle,
                'expired_at'    => $matchedSubscription->expired_at?->format('Y-m-d H:i:s'),
            ] : null,
        ];

        $logs = [];
        if (file_exists($logFile)) {
            $content = @file_get_contents($logFile);
            $logs = json_decode($content, true);
            if (!is_array($logs)) {
                $logs = [];
            }
        }

        $logs[] = $logEntry;
        if (count($logs) > 50) {
            $logs = array_slice($logs, -50);
        }

        @file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        Log::info('QRIS Callback processed via ShopeePay/Qris handler', [
            'refid'   => $responseData['refid'],
            'amount'  => $responseData['amount'],
            'name'    => $responseData['name'],
            'nmid'    => $responseData['nmid'],
            'matched' => $matchedSubscription ? true : false,
        ]);

        return response()->json([
            'status'  => 'accept',
            'message' => 'Data received successfully',
            'data'    => $responseData,
        ]);
    }
}
