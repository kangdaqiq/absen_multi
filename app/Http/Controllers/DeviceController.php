<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\SiswaFingerprint;
use App\Models\GuruFingerprint;
use App\Models\GateCardFingerprint;
use App\Models\ApiLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DeviceController extends Controller
{
    public function index()
    {
        $query = Device::orderBy('created_at', 'desc');

        $isSuperAdmin = auth()->user() && auth()->user()->isSuperAdmin();
        $schoolId = auth()->user() ? auth()->user()->school_id : null;

        // Filter by school_id for non-super admin users
        if (!$isSuperAdmin && $schoolId) {
            $query->where('school_id', $schoolId);
        }

        $devices = $query->get();

        // 1. Hitung Template Sidik Jari yang Tersimpan di Server
        $schoolDeviceIds = Device::when(!$isSuperAdmin && $schoolId, fn($q) => $q->where('school_id', $schoolId))->pluck('id')->toArray();

        $siswaTemplatesCount = SiswaFingerprint::whereIn('device_id', $schoolDeviceIds)
            ->whereNotNull('template_data')
            ->where('template_data', '!=', '')
            ->whereHas('student')
            ->distinct('finger_id')
            ->count('finger_id');

        $guruTemplatesCount = GuruFingerprint::whereIn('device_id', $schoolDeviceIds)
            ->whereNotNull('template_data')
            ->where('template_data', '!=', '')
            ->whereHas('guru')
            ->distinct('finger_id')
            ->count('finger_id');

        $gateTemplatesCount = GateCardFingerprint::whereIn('device_id', $schoolDeviceIds)
            ->whereNotNull('template_data')
            ->where('template_data', '!=', '')
            ->whereHas('gateCard')
            ->distinct('finger_id')
            ->count('finger_id');

        $totalTemplates = $siswaTemplatesCount + $guruTemplatesCount + $gateTemplatesCount;

        // Ambil seluruh daftar finger_id unik yang bertemplate untuk menghitung mapping per device
        $siswaIds = SiswaFingerprint::whereIn('device_id', $schoolDeviceIds)
            ->whereNotNull('template_data')
            ->where('template_data', '!=', '')
            ->whereHas('student')
            ->pluck('finger_id')
            ->toArray();

        $guruIds = GuruFingerprint::whereIn('device_id', $schoolDeviceIds)
            ->whereNotNull('template_data')
            ->where('template_data', '!=', '')
            ->whereHas('guru')
            ->pluck('finger_id')
            ->toArray();

        $gateIds = GateCardFingerprint::whereIn('device_id', $schoolDeviceIds)
            ->whereNotNull('template_data')
            ->where('template_data', '!=', '')
            ->whereHas('gateCard')
            ->pluck('finger_id')
            ->toArray();

        $allTemplateIds = array_values(array_unique(array_merge($siswaIds, $guruIds, $gateIds)));

        foreach ($devices as $d) {
            if ($d->type !== 'rfid') {
                $min = $d->finger_id_min ? (int)$d->finger_id_min : 1;
                $max = $d->finger_id_max ? (int)$d->finger_id_max : 200;
                $d->matched_templates_count = count(array_filter($allTemplateIds, fn($id) => $id >= $min && $id <= $max));
            } else {
                $d->matched_templates_count = 0;
            }
        }

        // 2. Ambil Log Sinkronisasi Template Terbaru (15 log terakhir)
        $syncActions = [
            'sync_template',
            'sync_template_list',
            'sync_template_download',
            'sync_template_result',
            'sync_requested',
            'enroll_success',
            'enroll_failed'
        ];

        $syncLogs = ApiLog::with('school')
            ->when(!$isSuperAdmin && $schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->where(function ($q) use ($syncActions) {
                $q->whereIn('action', $syncActions)
                    ->orWhere('message', 'like', '%template%')
                    ->orWhere('message', 'like', '%sinkronisasi%')
                    ->orWhere('message', 'like', '%Enroll%');
            })
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        return view('devices.index', compact(
            'devices',
            'totalTemplates',
            'siswaTemplatesCount',
            'guruTemplatesCount',
            'gateTemplatesCount',
            'syncLogs'
        ));
    }

    public function triggerSync($id)
    {
        $device = Device::findOrFail($id);
        if (!auth()->user()->isSuperAdmin() && $device->school_id !== auth()->user()->school_id) {
            abort(403);
        }

        Cache::put('sync_finger_' . $device->id, true, 600);

        ApiLog::create([
            'school_id' => $device->school_id,
            'api_key' => $device->api_key,
            'action' => 'sync_requested',
            'uid' => null,
            'success' => true,
            'message' => 'Permintaan Sinkronisasi Manual (On-Demand) Dikirim dari Web ke Alat: ' . ($device->name ?? 'Scanner'),
            'created_at' => now()
        ]);

        return redirect()->route('devices.index')->with('success', 'Perintah sinkronisasi manual berhasil dikirim ke ' . $device->name . '. Alat akan melakukan sinkronisasi otomatis.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'api_key' => 'required|string|max:64|unique:api_keys,api_key',
            'type' => 'required|in:rfid,fingerprint,rfid_fingerprint',
            'device_category' => 'nullable|in:attendance,sholat',
            'finger_id_min' => 'nullable|integer|min:1',
            'finger_id_max' => 'nullable|integer|gte:finger_id_min',
            'active' => 'required|boolean',
        ]);

        $data = $request->all();
        $data['device_category'] = $request->input('device_category', 'attendance') ?: 'attendance';
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
            'device_category' => 'nullable|in:attendance,sholat',
            'finger_id_min' => 'nullable|integer|min:1',
            'finger_id_max' => 'nullable|integer|gte:finger_id_min',
            'active' => 'required|boolean',
        ]);

        $data = $request->all();
        $data['device_category'] = $request->input('device_category', 'attendance') ?: 'attendance';
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
