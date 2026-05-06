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
}
