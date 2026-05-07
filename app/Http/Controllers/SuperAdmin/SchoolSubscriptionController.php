<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\School;
use App\Models\Package;
use Illuminate\Http\Request;

class SchoolSubscriptionController extends Controller
{
    public function index(School $school)
    {
        $subscriptions = $school->subscriptions()->with('package')
            ->orderBy('id', 'desc')
            ->paginate(20);
            
        $packages = Package::where('is_active', true)->orderBy('price_monthly')->get();
        $activePackageId = $subscriptions->first()?->package_id ?? ($packages->first()?->id ?? null);
            
        return view('super-admin.schools.subscriptions.index', compact('school', 'subscriptions', 'packages', 'activePackageId'));
    }

    public function quickRenew(Request $request, School $school)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'duration_months' => 'required|integer|min:1|max:60',
        ]);

        $package = Package::find($validated['package_id']);
        $months = (int) $validated['duration_months'];
        
        // Calculate amount
        $amount = 0;
        if ($months % 12 === 0) {
            $amount = $package->price_yearly * ($months / 12);
            $billingCycle = 'yearly';
        } else {
            $amount = $package->price_monthly * $months;
            $billingCycle = 'monthly';
        }

        // Determine start and end date
        $now = now();
        $startedAt = $now;
        // If school has existing active expiration that is in the future, start from there
        if ($school->expired_at && $school->expired_at > $now) {
            $startedAt = clone $school->expired_at;
        }
        
        $expiredAt = (clone $startedAt)->addMonths($months);

        $subscription = Subscription::create([
            'school_id' => $school->id,
            'package_id' => $package->id,
            'billing_cycle' => $billingCycle,
            'status' => 'paid',
            'started_at' => $startedAt,
            'expired_at' => $expiredAt,
            'paid_at' => $now,
            'amount' => $amount,
        ]);

        $school->update([
            'expired_at' => $expiredAt
        ]);

        return redirect()->route('super-admin.schools.subscriptions.index', $school)
            ->with('success', "Berhasil memperpanjang {$months} bulan!");
    }

    public function create(School $school)
    {
        $packages = Package::where('is_active', true)->orderBy('price_monthly')->get();
        return view('super-admin.schools.subscriptions.create', compact('school', 'packages'));
    }

    public function store(Request $request, School $school)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'status' => 'required|in:unpaid,paid,cancelled',
            'started_at' => 'nullable|date',
            'expired_at' => 'nullable|date|after_or_equal:started_at',
            'paid_at' => 'nullable|date',
            'amount' => 'nullable|numeric',
        ]);

        $package = Package::find($validated['package_id']);
        
        // Auto calculate amount if not provided
        if (empty($validated['amount']) && $validated['amount'] !== '0') {
            $validated['amount'] = $validated['billing_cycle'] === 'yearly' 
                ? $package->price_yearly 
                : $package->price_monthly;
        }

        $validated['school_id'] = $school->id;
        $subscription = Subscription::create($validated);

        if ($validated['status'] === 'paid' && !empty($validated['expired_at'])) {
            $school->update([
                'expired_at' => $validated['expired_at']
            ]);
        }

        return redirect()->route('super-admin.schools.subscriptions.index', $school)->with('success', 'Langganan berhasil ditambahkan');
    }

    public function edit(School $school, Subscription $subscription)
    {
        // Ensure the subscription belongs to the school
        if ($subscription->school_id !== $school->id) {
            abort(404);
        }

        $packages = Package::all();
        return view('super-admin.schools.subscriptions.edit', compact('school', 'subscription', 'packages'));
    }

    public function update(Request $request, School $school, Subscription $subscription)
    {
        if ($subscription->school_id !== $school->id) {
            abort(404);
        }

        $validated = $request->validate([
            'status' => 'required|in:unpaid,paid,cancelled',
            'started_at' => 'nullable|date',
            'expired_at' => 'nullable|date|after_or_equal:started_at',
            'paid_at' => 'nullable|date',
            'package_id' => 'required|exists:packages,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'amount' => 'nullable|numeric',
        ]);
        
        if (empty($validated['amount']) && $validated['amount'] !== '0') {
             $package = Package::find($validated['package_id']);
             $validated['amount'] = $validated['billing_cycle'] === 'yearly' 
                ? $package->price_yearly 
                : $package->price_monthly;
        }

        $subscription->update($validated);

        if ($validated['status'] === 'paid' && !empty($validated['expired_at'])) {
            $school->update([
                'expired_at' => $validated['expired_at']
            ]);
        }

        return redirect()->route('super-admin.schools.subscriptions.index', $school)->with('success', 'Langganan berhasil diperbarui');
    }

    public function destroy(School $school, Subscription $subscription)
    {
        if ($subscription->school_id !== $school->id) {
            abort(404);
        }
        
        $subscription->delete();
        return redirect()->route('super-admin.schools.subscriptions.index', $school)->with('success', 'Langganan berhasil dihapus');
    }

    public function confirm(School $school, Subscription $subscription)
    {
        if ($subscription->school_id !== $school->id || $subscription->status !== 'unpaid') {
            abort(404);
        }

        $now = now();
        $subscription->update([
            'status' => 'paid',
            'paid_at' => $now,
        ]);

        if ($subscription->expired_at) {
            $school->update([
                'expired_at' => $subscription->expired_at
            ]);
        }

        return redirect()->route('super-admin.schools.subscriptions.index', $school)->with('success', 'Pembayaran berhasil dikonfirmasi dan langganan diaktifkan.');
    }
}
