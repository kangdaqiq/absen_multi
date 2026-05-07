<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Subscription;
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

        // All active packages available
        $packages = Package::where('is_active', true)->orderBy('price_monthly')->get();

        // Subscription history
        $history = Subscription::with('package')
            ->where('school_id', $school->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        // Usage stats
        $usage = [
            'students' => [
                'current' => $school->siswa()->count(),
                'limit'   => $school->student_limit,
            ],
            'teachers' => [
                'current' => $school->guru()->count(),
                'limit'   => $school->teacher_limit,
            ],
            'bot_users' => [
                'current' => $school->botAccessCount(),
                'limit'   => $school->bot_user_limit,
            ],
        ];

        return view('subscription.index', compact(
            'school',
            'activeSubscription',
            'packages',
            'history',
            'usage'
        ));
    }

    /**
     * Store a new pending subscription request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id'
        ]);

        $school = Auth::user()->school;
        $package = Package::findOrFail($request->package_id);

        // Check if there is already a unpaid subscription for this package
        $existingPending = Subscription::where('school_id', $school->id)
            ->where('status', 'unpaid')
            ->first();

        $now = now();
        $startedAt = $now;
        if ($school->expired_at && $school->expired_at > $now) {
            $startedAt = clone $school->expired_at;
        }
        $expiredAt = (clone $startedAt)->addMonth(); // Default to 1 month for this request

        if ($existingPending) {
            // Update the existing pending instead of creating a new one to avoid spam
            $existingPending->update([
                'package_id' => $package->id,
                'amount' => $package->price_monthly, // default to monthly for now, or could be dynamic
                'status' => 'unpaid',
                'billing_cycle' => 'monthly',
                'started_at' => $startedAt,
                'expired_at' => $expiredAt,
            ]);
            $subscription = $existingPending;
        } else {
            $subscription = Subscription::create([
                'school_id' => $school->id,
                'package_id' => $package->id,
                'amount' => $package->price_monthly,
                'status' => 'unpaid',
                'billing_cycle' => 'monthly', // assuming default is monthly
                'started_at' => $startedAt,
                'expired_at' => $expiredAt,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Permintaan perpanjangan berhasil dicatat.'
        ]);
    }
}
