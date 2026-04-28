<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function index()
    {
        $licenses = License::latest()->get();
        return view('super-admin.licenses.index', compact('licenses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_name'      => 'required|string|max:255',
            'max_schools'      => 'required|integer|min:0',
            'max_students'     => 'required|integer|min:0',
            'expired_at'       => 'nullable|date|after:today',
            'allowed_hostname' => 'nullable|string|max:255',
            'notes'            => 'nullable|string',
        ]);

        $validated['license_key'] = License::generateKey();
        $validated['is_active']   = $request->has('is_active');

        License::create($validated);

        return redirect()->route('super-admin.licenses.index')
            ->with('success', 'Lisensi berhasil dibuat untuk ' . $validated['client_name']);
    }

    public function update(Request $request, License $license)
    {
        $validated = $request->validate([
            'client_name'      => 'required|string|max:255',
            'max_schools'      => 'required|integer|min:0',
            'max_students'     => 'required|integer|min:0',
            'expired_at'       => 'nullable|date',
            'allowed_hostname' => 'nullable|string|max:255',
            'notes'            => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $license->update($validated);

        return redirect()->route('super-admin.licenses.index')
            ->with('success', 'Lisensi ' . $license->client_name . ' berhasil diperbarui.');
    }

    public function destroy(License $license)
    {
        $name = $license->client_name;
        $license->delete();

        return redirect()->route('super-admin.licenses.index')
            ->with('success', 'Lisensi ' . $name . ' berhasil dihapus.');
    }

    /**
     * Regenerate a new license key for existing license
     */
    public function regenerate(License $license)
    {
        $license->update(['license_key' => License::generateKey()]);

        return redirect()->route('super-admin.licenses.index')
            ->with('success', 'License key untuk ' . $license->client_name . ' berhasil diperbarui.');
    }
}
