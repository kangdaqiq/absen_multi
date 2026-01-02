<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeviceController extends Controller
{
    public function index()
    {
        $devices = Device::orderBy('created_at', 'desc')->get();
        return view('devices.index', compact('devices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'api_key' => 'required|string|max:64|unique:api_keys,api_key',
            'type' => 'required|in:rfid,fingerprint,rfid_fingerprint',
            'active' => 'required|boolean',
        ]);

        Device::create($request->all());

        return redirect()->route('devices.index')->with('success', 'Device berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $device = Device::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100',
            'api_key' => 'required|string|max:64|unique:api_keys,api_key,' . $device->id,
            'type' => 'required|in:rfid,fingerprint,rfid_fingerprint',
            'active' => 'required|boolean',
        ]);

        $device->update($request->all());

        return redirect()->route('devices.index')->with('success', 'Device berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $device = Device::findOrFail($id);
        $device->delete();

        return redirect()->route('devices.index')->with('success', 'Device berhasil dihapus.');
    }
}
