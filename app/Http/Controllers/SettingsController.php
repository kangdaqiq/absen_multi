<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('setting_value', 'setting_key');
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        // Handle logo upload
        if ($request->hasFile('logo')) {
            $request->validate([
                'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            $file = $request->file('logo');
            $filename = 'logo.' . $file->getClientOriginalExtension();

            // Move to public/img directory
            $file->move(public_path('img'), $filename);

            // Save logo filename to settings
            Setting::updateOrCreate(
                ['setting_key' => 'logo_filename'],
                ['setting_value' => $filename]
            );
        }

        $data = $request->except('_token', '_method', 'logo');

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value]
            );
        }

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
