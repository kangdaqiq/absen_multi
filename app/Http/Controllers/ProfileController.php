<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            // Password optional
            'current_password' => 'nullable|required_with:new_password',
            'new_password' => 'nullable|min:8|confirmed',
        ]);

        if ($request->filled('new_password')) {
            if (!Hash::check($request->current_password, $user->password_hash)) {
                return back()->withErrors(['current_password' => 'Password saat ini salah.']);
            }
            $user->password_hash = Hash::make($request->new_password);
        }

        $user->full_name = $request->name;
        $user->email = $request->email;
        $user->save();

        if ($request->hasFile('global_logo') && $user->isSuperAdmin()) {
            $request->validate([
                'global_logo' => 'image|mimes:jpeg,png,jpg,gif,svg|max:10240',
            ]);

            $path = $request->file('global_logo')->store('schools/logos', 'public');

            // Save as global setting (school_id = 0)
            \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
                ['setting_key' => 'logo_filename', 'school_id' => 0],
                ['setting_value' => $path]
            );
        }

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
