<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::orderBy('id', 'desc')->get();
        return view('super-admin.packages.index', compact('packages'));
    }

    public function create()
    {
        return view('super-admin.packages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'student_limit' => 'nullable|integer|min:0',
            'teacher_limit' => 'nullable|integer|min:0',
            'bot_user_limit' => 'nullable|integer|min:0',
            'history_quota_months' => 'nullable|integer|min:0',
            'wa_enabled' => 'nullable|boolean',
            'bot_enabled' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['wa_enabled'] = $request->has('wa_enabled');
        $validated['bot_enabled'] = $request->has('bot_enabled');
        $validated['is_active'] = $request->has('is_active');
        
        // Convert null strings to actual null for limits (0 means unlimited in DB logic, but null is safer for empty)
        $validated['student_limit'] = $validated['student_limit'] ?? 0;
        $validated['teacher_limit'] = $validated['teacher_limit'] ?? 0;
        $validated['bot_user_limit'] = $validated['bot_user_limit'] ?? 0;
        // Null means unlimited for history quota
        $validated['history_quota_months'] = empty($request->history_quota_months) ? null : $request->history_quota_months;

        Package::create($validated);

        return redirect()->route('super-admin.packages.index')->with('success', 'Paket berhasil ditambahkan');
    }

    public function edit(Package $package)
    {
        return view('super-admin.packages.edit', compact('package'));
    }

    public function update(Request $request, Package $package)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'student_limit' => 'nullable|integer|min:0',
            'teacher_limit' => 'nullable|integer|min:0',
            'bot_user_limit' => 'nullable|integer|min:0',
            'history_quota_months' => 'nullable|integer|min:0',
        ]);

        $validated['wa_enabled'] = $request->has('wa_enabled');
        $validated['bot_enabled'] = $request->has('bot_enabled');
        $validated['is_active'] = $request->has('is_active');

        $validated['student_limit'] = $validated['student_limit'] ?? 0;
        $validated['teacher_limit'] = $validated['teacher_limit'] ?? 0;
        $validated['bot_user_limit'] = $validated['bot_user_limit'] ?? 0;
        $validated['history_quota_months'] = empty($request->history_quota_months) ? null : $request->history_quota_months;

        $package->update($validated);

        return redirect()->route('super-admin.packages.index')->with('success', 'Paket berhasil diperbarui');
    }

    public function destroy(Package $package)
    {
        $package->delete();
        return redirect()->route('super-admin.packages.index')->with('success', 'Paket berhasil dihapus');
    }
}
