<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\ReportGroup;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('setting_value', 'setting_key');
        $reportGroups = ReportGroup::orderBy('name')->get();
        return view('settings.index', compact('settings', 'reportGroups'));
    }

    public function update(Request $request)
    {
        $data = $request->except('_token', '_method');

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value]
            );
        }

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }

    public function storeGroup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'jid' => 'required|string|max:255'
        ]);

        ReportGroup::create([
            'name' => $request->name,
            'jid' => $request->jid,
            'is_active' => true
        ]);

        return back()->with('success', 'Grup laporan berhasil ditambahkan.');
    }

    public function toggleGroup($id)
    {
        $group = ReportGroup::findOrFail($id);
        $group->is_active = !$group->is_active;
        $group->save();

        return back()->with('success', 'Status grup berhasil diubah.');
    }

    public function deleteGroup($id)
    {
        ReportGroup::findOrFail($id)->delete();
        return back()->with('success', 'Grup laporan berhasil dihapus.');
    }
}
