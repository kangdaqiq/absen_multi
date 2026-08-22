<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QrisCallbackController extends Controller
{
    /**
     * Handle incoming QRIS callback notification from Qiospay.
     * POST /api/callback/accept/{key}
     */
    public function accept(Request $request, string $key = ''): JsonResponse
    {
        $secretKey = config('services.qiospay.secret_key', env('QIOSPAY_SECRET_KEY', 'mysecret'));

        // Validate Secret Key
        if ($key !== $secretKey) {
            return response()->json([
                'status'  => 'reject',
                'message' => 'Invalid secret key',
                'data'    => null,
            ], 403);
        }

        $inputRaw = $request->getContent();
        $json = json_decode($inputRaw, true);

        // Fallback if raw JSON decoding fails but request payload is present
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
                'refid'   => $data['refid'] ?? null,
                'issuer'  => $data['issuer'] ?? null,
                'balance' => $data['balance'] ?? null,
                'time'    => $data['time'] ?? null,
            ];
        }

        $amount = (float) ($responseData['amount'] ?? 0);
        $refid = (string) ($responseData['refid'] ?? '');

        // ── Match and activate pending subscription ──────────────────────────
        $matchedSubscription = null;
        if ($amount > 0) {
            // Find unpaid subscription with exact amount (using unique code)
            $matchedSubscription = Subscription::with(['school', 'package'])
                ->where('status', 'unpaid')
                ->where('amount', $amount)
                ->latest()
                ->first();

            if ($matchedSubscription) {
                $school = $matchedSubscription->school;
                $package = $matchedSubscription->package;

                $now = now();
                $currentExpired = $school->expired_at;
                $baseDate = ($currentExpired && $currentExpired > $now) ? $currentExpired : $now;

                $newExpiredAt = $matchedSubscription->billing_cycle === 'yearly'
                    ? (clone $baseDate)->addYear()
                    : (clone $baseDate)->addMonth();

                // Update subscription record
                $matchedSubscription->update([
                    'status'         => 'paid',
                    'paid_at'        => $now,
                    'expired_at'     => $newExpiredAt,
                    'payment_method' => 'qris',
                    'payment_ref'    => $refid,
                ]);

                // Update school quotas & expiration
                $updateSchool = [
                    'expired_at' => $newExpiredAt,
                ];

                if ($package) {
                    $updateSchool['student_limit'] = $package->student_limit;
                    $updateSchool['teacher_limit'] = $package->teacher_limit;
                    $updateSchool['bot_user_limit'] = $package->bot_user_limit;
                    $updateSchool['history_quota_months'] = $package->history_quota_months;
                    $updateSchool['wa_enabled'] = $package->wa_enabled;
                    if ($package->bot_enabled) {
                        $updateSchool['bot_enabled'] = true;
                    }
                }

                $school->update($updateSchool);

                Log::info("QRIS Subscription activated for School #{$school->id} ({$school->name}) - Package: " . ($package?->name ?? 'Custom') . " until {$newExpiredAt->format('Y-m-d')}");
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
            mkdir($logDir, 0777, true);
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
            $content = file_get_contents($logFile);
            $logs = json_decode($content, true);
            if (!is_array($logs)) {
                $logs = [];
            }
        }

        $logs[] = $logEntry;
        if (count($logs) > 50) {
            $logs = array_slice($logs, -50);
        }

        file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        Log::info('QRIS Callback processed', [
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
