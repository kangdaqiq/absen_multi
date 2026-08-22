<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Subscription;
use App\Services\QrisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    /**
     * Display the subscription/package page for the current school.
     */
    public function index()
    {
        $school = Auth::user()->school;

        // Active subscription with package eager-loaded
        $activeSubscription = Subscription::with('package')
            ->where('school_id', $school->id)
            ->where('status', 'paid')
            ->orderByDesc('expired_at')
            ->first();

        // Active pending (unpaid) subscription order if any
        $pendingSubscription = Subscription::with('package')
            ->where('school_id', $school->id)
            ->where('status', 'unpaid')
            ->latest()
            ->first();

        $pendingOrderData = null;

        // All active packages available
        $packages = Package::where('is_active', true)->orderBy('price_monthly')->get();

        // Subscription history
        $history = Subscription::with('package')
            ->where('school_id', $school->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        $isSelfHosted = config('app.mode') === 'self_hosted';
        $licenseInfo = null;
        if ($isSelfHosted) {
            $licenseInfo = app(\App\Services\LicenseService::class)->validate();
        }

        // Usage stats
        $usage = [
            'students' => [
                'current' => $school->siswa()->count(),
                'limit'   => $isSelfHosted ? ($licenseInfo['max_students'] ?? 0) : $school->student_limit,
            ],
            'teachers' => [
                'current' => $school->guru()->count(),
                'limit'   => $isSelfHosted ? ($licenseInfo['max_teachers'] ?? 0) : $school->teacher_limit,
            ],
            'bot_users' => [
                'current' => $school->botAccessCount(),
                'limit'   => $isSelfHosted ? ($licenseInfo['max_bot_users'] ?? 0) : $school->bot_user_limit,
            ],
        ];

        return view('subscription.index', compact(
            'school',
            'activeSubscription',
            'pendingSubscription',
            'pendingOrderData',
            'packages',
            'history',
            'usage',
            'isSelfHosted',
            'licenseInfo'
        ));
    }

    /**
     * Store or update a subscription payment request (Manual).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'package_id'     => 'required|exists:packages,id',
            'billing_cycle'  => 'nullable|in:monthly,yearly',
            'payment_method' => 'nullable|in:qris,manual',
        ]);

        $school = Auth::user()->school;
        $package = Package::findOrFail($request->package_id);
        $billingCycle = $request->input('billing_cycle', 'monthly');
        $paymentMethod = $request->input('payment_method', 'manual');

        $basePrice = $billingCycle === 'yearly'
            ? (float) $package->price_yearly
            : (float) $package->price_monthly;

        // Check if there is already an unpaid subscription for this school
        $existingPending = Subscription::where('school_id', $school->id)
            ->where('status', 'unpaid')
            ->first();

        $uniqueCode = 0;
        $totalAmount = $basePrice;

        $now = now();
        $startedAt = $now;
        if ($school->expired_at && $school->expired_at > $now) {
            $startedAt = clone $school->expired_at;
        }
        
        $expiredAt = $billingCycle === 'yearly'
            ? (clone $startedAt)->addYear()
            : (clone $startedAt)->addMonth();

        $subscriptionData = [
            'school_id'      => $school->id,
            'package_id'     => $package->id,
            'amount'         => $totalAmount,
            'unique_code'    => $uniqueCode,
            'status'         => 'unpaid',
            'billing_cycle'  => $billingCycle,
            'payment_method' => $paymentMethod,
            'started_at'     => $startedAt,
            'expired_at'     => $expiredAt,
        ];

        if ($existingPending) {
            $existingPending->update($subscriptionData);
            $subscription = $existingPending;
        } else {
            $subscription = Subscription::create($subscriptionData);
        }

        return response()->json([
            'success'      => true,
            'message'      => 'Permintaan perpanjangan berhasil diproses.',
            'subscription' => [
                'id'            => $subscription->id,
                'package_id'    => $package->id,
                'package_name'  => $package->name,
                'billing_cycle' => $billingCycle,
                'base_amount'   => $basePrice,
                'unique_code'   => $uniqueCode,
                'total_amount'  => $totalAmount,
                'status'        => $subscription->status,
                'created_at'    => $subscription->created_at?->format('d M Y H:i'),
            ]
        ]);
    }

    /**
     * Check real-time payment status of a subscription order.
     */
    public function checkStatus($id): JsonResponse
    {
        $school = Auth::user()->school;
        $subscription = Subscription::with('package')
            ->where('school_id', $school->id)
            ->where('id', $id)
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Data langganan tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'success'    => true,
            'is_paid'    => $subscription->status === 'paid',
            'status'     => $subscription->status,
            'amount'     => (float) $subscription->amount,
            'paid_at'    => $subscription->paid_at?->format('d M Y H:i'),
            'expired_at' => $subscription->expired_at?->format('d M Y'),
        ]);
    }

    /**
     * Cancel an unpaid subscription order.
     */
    public function cancel($id): JsonResponse
    {
        $school = Auth::user()->school;
        $subscription = Subscription::where('school_id', $school->id)
            ->where('id', $id)
            ->where('status', 'unpaid')
            ->first();

        if ($subscription) {
            $subscription->update(['status' => 'cancelled']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tagihan perpanjangan berhasil dibatalkan.'
        ]);
    }
}
