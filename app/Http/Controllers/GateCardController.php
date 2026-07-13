<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GateCard;
use App\Models\Guru;
use App\Models\Siswa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GateCardController extends Controller
{
    public function index()
    {
        $schoolId = auth()->user()->school_id;
        $gateCards = GateCard::where('school_id', $schoolId)->get();
        $gurus = Guru::where('school_id', $schoolId)->orderBy('nama')->get();
        $devices = \App\Models\Device::where('school_id', $schoolId)
            ->where('active', true)
            ->whereIn('type', ['fingerprint', 'rfid_fingerprint'])
            ->orderBy('name')
            ->get();
        return view('gate-cards.index', compact('gateCards', 'gurus', 'devices'));
    }

    public function create()
    {
        $schoolId = auth()->user()->school_id;
        $gurus = Guru::where('school_id', $schoolId)->orderBy('nama')->get();
        return view('gate-cards.create', compact('gurus'));
    }

    public function store(Request $request)
    {
        if ($request->guru_id === 'lainnya') {
            $request->merge(['guru_id' => null]);
        }

        $request->validate([
            'guru_id' => 'nullable|exists:guru,id',
            'name' => 'required_without:guru_id|string|max:100|nullable',
            'uid_rfid' => [
                'nullable',
                'string',
                'max:50',
                function ($attribute, $value, $fail) {
                    $schoolId = auth()->user()->school_id;
                    if (GateCard::where('uid_rfid', $value)->where('school_id', $schoolId)->exists()) {
                        $fail('UID RFID sudah digunakan oleh kartu gerbang lain.');
                    }
                    if (Guru::where('uid_rfid', $value)->where('school_id', $schoolId)->exists()) {
                        $fail('UID RFID sudah digunakan oleh seorang guru.');
                    }
                    if (Siswa::where('uid_rfid', $value)->where('school_id', $schoolId)->exists()) {
                        $fail('UID RFID sudah digunakan oleh seorang siswa.');
                    }
                },
            ],
        ]);

        $name = $request->name;
        if ($request->filled('guru_id')) {
            $guru = Guru::find($request->guru_id);
            if ($guru && $guru->school_id === auth()->user()->school_id) {
                $name = $guru->nama;
            } else {
                abort(403, 'Invalid Guru ID');
            }
        }

        GateCard::create([
            'school_id' => auth()->user()->school_id,
            'guru_id' => $request->guru_id,
            'name' => $name,
            'uid_rfid' => $request->uid_rfid,
            'enroll_status' => $request->uid_rfid ? 'done' : 'requested'
        ]);

        return redirect()->route('gate-cards.index')->with('success', 'Kartu Gerbang berhasil ditambahkan.');
    }

    public function edit(GateCard $gateCard)
    {
        // Scope to school
        if ($gateCard->school_id !== auth()->user()->school_id) abort(403);
        
        $schoolId = auth()->user()->school_id;
        $gurus = Guru::where('school_id', $schoolId)->orderBy('nama')->get();
        return view('gate-cards.edit', compact('gateCard', 'gurus'));
    }

    public function update(Request $request, GateCard $gateCard)
    {
        if ($gateCard->school_id !== auth()->user()->school_id) abort(403);

        if ($request->guru_id === 'lainnya') {
            $request->merge(['guru_id' => null]);
        }

        $request->validate([
            'guru_id' => 'nullable|exists:guru,id',
            'name' => 'required_without:guru_id|string|max:100|nullable',
            'uid_rfid' => [
                'nullable',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($gateCard) {
                    $schoolId = auth()->user()->school_id;
                    if (GateCard::where('uid_rfid', $value)->where('school_id', $schoolId)->where('id', '!=', $gateCard->id)->exists()) {
                        $fail('UID RFID sudah digunakan oleh kartu gerbang lain.');
                    }
                    if (Guru::where('uid_rfid', $value)->where('school_id', $schoolId)->exists()) {
                        $fail('UID RFID sudah digunakan oleh seorang guru.');
                    }
                    if (Siswa::where('uid_rfid', $value)->where('school_id', $schoolId)->exists()) {
                        $fail('UID RFID sudah digunakan oleh seorang siswa.');
                    }
                },
            ],
        ]);

        $name = $request->name;
        if ($request->filled('guru_id')) {
            $guru = Guru::find($request->guru_id);
            if ($guru && $guru->school_id === auth()->user()->school_id) {
                $name = $guru->nama;
            } else {
                abort(403, 'Invalid Guru ID');
            }
        }

        $gateCard->update([
            'guru_id' => $request->guru_id,
            'name' => $name,
            'uid_rfid' => $request->uid_rfid,
        ]);

        return redirect()->route('gate-cards.index')->with('success', 'Kartu Gerbang berhasil diperbarui.');
    }

    public function destroy(GateCard $gateCard)
    {
        if ($gateCard->school_id !== auth()->user()->school_id) abort(403);
        
        $gateCard->delete();
        return redirect()->route('gate-cards.index')->with('success', 'Kartu Gerbang berhasil dihapus.');
    }

    public function requestEnroll(GateCard $gateCard)
    {
        if ($gateCard->school_id !== auth()->user()->school_id) abort(403);

        // Reset others to done
        GateCard::where('school_id', auth()->user()->school_id)
            ->where('enroll_status', 'requested')
            ->update(['enroll_status' => 'done']);

        $gateCard->update([
            'enroll_status' => 'requested'
        ]);

        return back()->with('success', 'Silakan tempelkan kartu pada alat absensi (RFID).');
    }

    public function enrollRequest($id)
    {
        $gateCard = GateCard::where('school_id', auth()->user()->school_id)->findOrFail($id);
        
        // Reset others
        GateCard::where('school_id', auth()->user()->school_id)
            ->where('enroll_status', 'requested')
            ->update(['enroll_status' => 'done']);

        $gateCard->update(['enroll_status' => 'requested']);
        return response()->json(['ok' => true]);
    }

    public function cancelEnroll($id)
    {
        $gateCard = GateCard::where('school_id', auth()->user()->school_id)->findOrFail($id);
        $gateCard->update(['enroll_status' => 'done']);
        return response()->json(['ok' => true]);
    }

    public function enrollCheck($id)
    {
        $gateCard = GateCard::where('school_id', auth()->user()->school_id)->findOrFail($id);
        if ($gateCard->enroll_status == 'done') {
            return response()->json(['ok' => true, 'uid' => $gateCard->uid_rfid]);
        } elseif (str_starts_with($gateCard->enroll_status, 'error:')) {
            $errorMsg = substr($gateCard->enroll_status, 6);
            if ($errorMsg === 'UID Dipakai') {
                $errorMsg = 'Kartu sudah terdaftar di sistem.';
            }
            $gateCard->update(['enroll_status' => 'none']);
            return response()->json(['ok' => false, 'error' => $errorMsg]);
        }
        return response()->json(['ok' => false]);
    }

    public function deleteUid($id)
    {
        $gateCard = GateCard::where('school_id', auth()->user()->school_id)->findOrFail($id);
        $gateCard->update([
            'uid_rfid' => null,
            'enroll_status' => 'done'
        ]);
        return response()->json(['ok' => true]);
    }

    public function enrollFingerRequest($id, Request $request)
    {
        $gateCard = GateCard::where('school_id', auth()->user()->school_id)->findOrFail($id);
        $schoolId = $gateCard->school_id;

        // Reset others
        Siswa::where('enroll_finger_status', 'requested')
            ->where('school_id', $schoolId)
            ->update(['enroll_finger_status' => 'none']);

        Guru::where('enroll_finger_status', 'requested')
            ->where('school_id', $schoolId)
            ->update(['enroll_finger_status' => 'none']);

        GateCard::where('enroll_finger_status', 'requested')
            ->where('school_id', $schoolId)
            ->update(['enroll_finger_status' => 'none']);

        $gateCard->update(['enroll_finger_status' => 'requested']);

        // Get device IP and send push notification
        $device = \App\Models\Device::find($request->device_id);
        $latestLog = \App\Models\ApiLog::where('api_key', $device->api_key)
            ->whereNotNull('ip_address')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($latestLog && $latestLog->ip_address) {
            try {
                $url = "http://{$latestLog->ip_address}/enroll-finger?id={$gateCard->id}";
                Http::timeout(3)->get($url);
                Log::info("Fingerprint enrollment push sent to {$latestLog->ip_address} for gate card {$gateCard->id}");
            } catch (\Exception $e) {
                Log::error("Failed to send enrollment push for gate card: " . $e->getMessage());
            }
        }

        return response()->json(['ok' => true]);
    }

    public function cancelFingerEnroll($id)
    {
        $gateCard = GateCard::where('school_id', auth()->user()->school_id)->findOrFail($id);
        if ($gateCard->enroll_finger_status === 'requested') {
            $gateCard->update(['enroll_finger_status' => 'none']);
        }
        return response()->json(['ok' => true]);
    }

    public function enrollFingerCheck($id)
    {
        $gateCard = GateCard::where('school_id', auth()->user()->school_id)->findOrFail($id);

        if ($gateCard->enroll_finger_status === 'done' && $gateCard->id_finger) {
            return response()->json(['ok' => true, 'id_finger' => $gateCard->id_finger, 'status' => 'done']);
        }

        return response()->json(['ok' => true, 'id_finger' => null, 'status' => 'requested']);
    }

    public function deleteFingerId($id)
    {
        $gateCard = GateCard::where('school_id', auth()->user()->school_id)->findOrFail($id);

        // Get all fingerprints for this gate card
        $fingerprints = \App\Models\GateCardFingerprint::where('gate_card_id', $gateCard->id)->get();

        foreach ($fingerprints as $fingerprint) {
            $device = \App\Models\Device::find($fingerprint->device_id);
            if ($device) {
                // Get device IP
                $latestLog = \App\Models\ApiLog::where('api_key', $device->api_key)
                    ->whereNotNull('ip_address')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($latestLog && $latestLog->ip_address) {
                    try {
                        $url = "http://{$latestLog->ip_address}/delete-finger?id={$fingerprint->finger_id}";
                        Http::timeout(3)->get($url);
                    } catch (\Exception $e) {}
                }
                
                // Backup delete via cache polling
                Cache::put('delete_finger_' . $device->id, $fingerprint->finger_id, now()->addMinutes(5));
            }
        }

        // Delete from database
        \App\Models\GateCardFingerprint::where('gate_card_id', $gateCard->id)->delete();
        $gateCard->update(['id_finger' => null, 'enroll_finger_status' => 'none']);

        return response()->json(['ok' => true]);
    }
}

