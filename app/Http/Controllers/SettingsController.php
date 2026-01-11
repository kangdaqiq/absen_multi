<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class SettingsController extends Controller
{
    public function index()
    {
        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $schoolId = auth()->user()->school_id;

            // Only get settings for this specific school
            $settings = Setting::where('school_id', $schoolId)
                ->get()
                ->pluck('setting_value', 'setting_key')
                ->toArray();

            // Merge with Global Settings for display
            $globalSettings = Setting::where('school_id', 0)
                ->get()
                ->pluck('setting_value', 'setting_key')
                ->toArray();

            // Union: School settings take precedence
            $settings = collect($settings + $globalSettings);
        } else {
            // Super admin sees global settings (school_id = 0)
            $settings = Setting::where('school_id', 0)
                ->get()
                ->pluck('setting_value', 'setting_key');
        }

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        // Handle logo upload
        // Handle logo upload
        if ($request->hasFile('logo')) {
            $request->validate([
                'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10240', // 10MB
            ]);

            // Get school_id (0 for Super Admin)
            $schoolId = auth()->user()->school_id ?? 0;

            // Upload to storage (same as SchoolController)
            $path = $request->file('logo')->store('schools/logos', 'public');

            // Save full path to settings using DB facade to handle composite key correctly
            \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
                [
                    'setting_key' => 'logo_filename',
                    'school_id' => $schoolId
                ],
                ['setting_value' => $path]
            );

            // Also update the School table for consistency (Only for actual schools)
            if ($schoolId && $schoolId > 0) {
                \App\Models\School::where('id', $schoolId)->update(['logo' => $path]);
            }
        }

        $data = $request->except('_token', '_method', 'logo');

        // Handle checkboxes (they don't send data when unchecked)
        $checkboxSettings = [
            'enable_checkout_attendance',
            'absence_notification_enabled'
        ];

        foreach ($checkboxSettings as $checkbox) {
            if (!isset($data[$checkbox])) {
                $data[$checkbox] = 'false';
            }
        }

        // Get and validate school_id
        $schoolId = auth()->user()->school_id ?? 0;

        // Ensure school_id is valid (allow 0 for Super Admin)
        if ((!$schoolId && $schoolId !== 0) && !auth()->user()->isSuperAdmin()) {
            return back()->with('error', 'User tidak memiliki school_id yang valid. Hubungi Super Admin.');
        }

        foreach ($data as $key => $value) {
            \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
                [
                    'setting_key' => $key,
                    'school_id' => $schoolId
                ],
                ['setting_value' => $value]
            );
        }

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
