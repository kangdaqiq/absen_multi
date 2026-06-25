<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\Siswa;
use App\Models\Guru;
use App\Models\GateCard;
use App\Models\ApiLog;

class RfidSimulatorController extends Controller
{
    public function index(Request $request)
    {
        // Auto-login first user for testing in local environments to prevent layout user-dropdown crashes
        if (app()->environment('local', 'testing') && !auth()->check()) {
            $testUser = \App\Models\User::first();
            if ($testUser) {
                auth()->login($testUser);
            }
        }

        // Programmatically enforce auth for all environments
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // 1. Get active devices (must support RFID scans) based on user school scope
        if (!$user || $user->isSuperAdmin()) {
            $devices = Device::where('active', true)
                ->whereIn('type', ['rfid', 'rfid_fingerprint'])
                ->orderBy('name')
                ->get();
        } else {
            $devices = Device::where('active', true)
                ->where('school_id', $user->school_id)
                ->whereIn('type', ['rfid', 'rfid_fingerprint'])
                ->orderBy('name')
                ->get();
        }

        // 2. Determine selected device
        $selectedDeviceId = $request->input('device_id');
        $selectedDevice = null;

        if ($selectedDeviceId) {
            $selectedDevice = Device::where('active', true)->find($selectedDeviceId);
            // Scope protection
            if ($selectedDevice && $user && !$user->isSuperAdmin() && $selectedDevice->school_id !== $user->school_id) {
                $selectedDevice = null;
            }
        }

        // Default to first device if none selected
        if (!$selectedDevice && $devices->count() > 0) {
            $selectedDevice = $devices->first();
        }

        // 3. Load Candidates scoped to the selected device's school_id
        $siswa = collect();
        $guru = collect();
        $gateCards = collect();
        $recentLogs = collect();

        if ($selectedDevice) {
            $schoolId = $selectedDevice->school_id;

            $siswaQuery = Siswa::with('kelas')->orderBy('nama');
            $guruQuery = Guru::orderBy('nama');
            $gateQuery = GateCard::with('guru')->orderBy('name');

            if ($schoolId !== null) {
                $siswaQuery->where('school_id', $schoolId);
                $guruQuery->where('school_id', $schoolId);
                $gateQuery->where('school_id', $schoolId);
            }

            $siswa = $siswaQuery->get();
            $guru = $guruQuery->get();
            $gateCards = $gateQuery->get();

            $recentLogs = ApiLog::where('api_key', $selectedDevice->api_key)
                ->orderBy('id', 'desc')
                ->limit(15)
                ->get();
        }

        return view('simulator.rfid', compact('devices', 'selectedDevice', 'siswa', 'guru', 'gateCards', 'recentLogs'));
    }
}
