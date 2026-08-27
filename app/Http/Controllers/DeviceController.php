<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeviceController extends Controller
{
    public function index()
    {
        $query = Device::orderBy('created_at', 'desc');

        // Filter by school_id for non-super admin users
        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $query->where('school_id', auth()->user()->school_id);
        }

        $devices = $query->get();
        return view('devices.index', compact('devices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'api_key' => 'required|string|max:64|unique:api_keys,api_key',
            'type' => 'required|in:rfid,fingerprint,rfid_fingerprint',
            'finger_id_min' => 'nullable|integer|min:1',
            'finger_id_max' => 'nullable|integer|gte:finger_id_min',
            'active' => 'required|boolean',
        ]);

        $data = $request->all();
        $data['finger_id_min'] = $request->filled('finger_id_min') ? (int)$request->finger_id_min : 1;
        $data['finger_id_max'] = $request->filled('finger_id_max') ? (int)$request->finger_id_max : 200;

        // Add school_id for non-super admin users
        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $data['school_id'] = auth()->user()->school_id;
        }

        Device::create($data);

        return redirect()->route('devices.index')->with('success', 'Device berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $device = Device::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100',
            'api_key' => 'required|string|max:64|unique:api_keys,api_key,' . $device->id,
            'type' => 'required|in:rfid,fingerprint,rfid_fingerprint',
            'finger_id_min' => 'nullable|integer|min:1',
            'finger_id_max' => 'nullable|integer|gte:finger_id_min',
            'active' => 'required|boolean',
        ]);

        $data = $request->all();
        $data['finger_id_min'] = $request->filled('finger_id_min') ? (int)$request->finger_id_min : 1;
        $data['finger_id_max'] = $request->filled('finger_id_max') ? (int)$request->finger_id_max : 200;

        $device->update($data);

        return redirect()->route('devices.index')->with('success', 'Device berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $device = Device::findOrFail($id);
        $device->delete();

        return redirect()->route('devices.index')->with('success', 'Device berhasil dihapus.');
    }
}
