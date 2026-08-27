<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\School;
use Illuminate\Http\Request;

class SchoolDeviceController extends Controller
{
    /**
     * Display a listing of devices for a specific school
     */
    public function index(School $school)
    {
        $devices = Device::where('school_id', $school->id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('super-admin.devices.index', compact('school', 'devices'));
    }

    /**
     * Store a newly created device for the school
     */
    public function store(Request $request, School $school)
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
        $data['school_id'] = $school->id;
        $data['finger_id_min'] = $request->filled('finger_id_min') ? (int)$request->finger_id_min : 1;
        $data['finger_id_max'] = $request->filled('finger_id_max') ? (int)$request->finger_id_max : 200;

        Device::create($data);

        return redirect()->route('super-admin.schools.devices.index', $school)
            ->with('success', 'Device berhasil ditambahkan untuk sekolah ' . $school->name);
    }

    /**
     * Update the specified device
     */
    public function update(Request $request, School $school, Device $device)
    {
        // Ensure device belongs to this school
        if ($device->school_id !== $school->id) {
            abort(404);
        }

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

        return redirect()->route('super-admin.schools.devices.index', $school)
            ->with('success', 'Device berhasil diperbarui.');
    }

    /**
     * Remove the specified device
     */
    public function destroy(School $school, Device $device)
    {
        // Ensure device belongs to this school
        if ($device->school_id !== $school->id) {
            abort(404);
        }

        $device->delete();

        return redirect()->route('super-admin.schools.devices.index', $school)
            ->with('success', 'Device berhasil dihapus.');
    }
}
