<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\QrisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicInvoiceController extends Controller
{
    /**
     * Display the public invoice payment page (no login required).
     */
    public function show(string $token)
    {
        $subscription = Subscription::with(['school', 'package'])
            ->where('invoice_token', $token)
            ->firstOrFail();

        $subscription->ensureInvoiceDetails();
        $school = $subscription->school;
        $package = $subscription->package;

        $baseAmount = (float) ($subscription->amount - ($subscription->unique_code ?? 0));
        $uniqueCode = (int) ($subscription->unique_code ?? 0);
        $totalAmount = (float) $subscription->amount;

        $qrisPayload = null;
        $qrisImage = null;

        if ($subscription->status === 'unpaid') {
            $qrisPayload = QrisService::generateDynamicQris($totalAmount);
            $qrisImage = QrisService::getQrCodeUrl($qrisPayload, 320);
        }

        return view('invoice.show', compact(
            'subscription',
            'school',
            'package',
            'baseAmount',
            'uniqueCode',
            'totalAmount',
            'qrisPayload',
            'qrisImage'
        ));
    }

    /**
     * Polling endpoint to check real-time payment status of the public invoice.
     */
    public function checkStatus(string $token): JsonResponse
    {
        $subscription = Subscription::with(['school', 'package'])
            ->where('invoice_token', $token)
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'success'      => true,
            'is_paid'      => $subscription->status === 'paid',
            'status'       => $subscription->status,
            'amount'       => (float) $subscription->amount,
            'paid_at'      => $subscription->paid_at ? $subscription->paid_at->translatedFormat('d F Y H:i') : null,
            'expired_at'   => $subscription->expired_at ? $subscription->expired_at->translatedFormat('d F Y') : null,
            'school_name'  => $subscription->school?->name,
            'package_name' => $subscription->package?->name,
        ]);
    }
}
