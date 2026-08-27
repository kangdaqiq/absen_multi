<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuruController extends Controller
{
    public function index(Request $request)
    {
        $query = Guru::with('defaultShift')->orderBy('nama');

        // Filter by school_id for non-super admin users
        $schoolId = null;
        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $schoolId = auth()->user()->school_id;
            $query->where('school_id', $schoolId);
        }

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('no_wa', 'like', "%{$search}%");
            });
        }

        $guru = $query->paginate(20)->withQueryString();
        // Fetch active devices for enrollment dropdowns
        $devicesQuery = \App\Models\Device::where('active', 1)
            ->whereIn('type', ['fingerprint', 'rfid_fingerprint'])
            ->orderBy('name');
        if ($schoolId) {
            $devicesQuery->where('school_id', $schoolId);
        }
        $devices = $devicesQuery->get();
        // Fetch active shifts for school
        $shiftsQuery = \App\Models\Shift::where('is_active', true);
        if ($schoolId) {
            $shiftsQuery->where('school_id', $schoolId);
        }
        $shifts = $shiftsQuery->orderBy('jam_masuk')->get();

        return view('guru.index', compact('guru', 'devices', 'shifts'));
    }

    public function store(Request $request)
    {
        $schoolId = auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id;

        // Check teacher quota
        if ($schoolId) {
            $school = \App\Models\School::find($schoolId);
            if ($school && !$school->hasTeacherQuota()) {
                return back()->with('error', 'Gagal: Kuota guru/staff untuk sekolah ini sudah penuh (' . $school->teacher_limit . ' guru). Hubungi Super Admin untuk upgrade kuota.')->withInput();
            }
        }

        // Check global license quota for self_hosted
        $licenseService = app(\App\Services\LicenseService::class);
        if (!$licenseService->hasGlobalTeacherQuota()) {
            return back()->with('error', 'Gagal: Kuota global Guru/Karyawan dari Lisensi Anda telah penuh. Silakan upgrade lisensi Anda.')->withInput();
        }

        $request->validate([
            'nama' => 'required|string|max:100',
            'nip' => 'nullable|string|max:50',
            'default_shift_id' => 'nullable|exists:shifts,id',
            'tgl_lahir' => 'nullable|date',
            'no_wa' => [
                'required',
                'string',
                'max:20',
                'regex:/^(08|628)[0-9]{8,13}$/',
                Rule::unique('guru')->where(fn($q) => $q->where('school_id', $schoolId))
            ],
            'uid_rfid' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('guru')->where(fn($q) => $q->where('school_id', $schoolId))
            ],
            'telegram_chat_id' => 'nullable|string|max:50',
        ]);

        $data = $request->all();
        $data['is_global_report'] = $request->has('is_global_report');
        if (isset($data['no_wa'])) {
            $data['no_wa'] = $this->normalizeWa($data['no_wa']);
        }
        if (empty($data['telegram_chat_id'])) {
            $data['telegram_chat_id'] = null;
        }

        // Add school_id from authenticated user
        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $data['school_id'] = auth()->user()->school_id;
        }

        Guru::create($data);

        return redirect()->route('guru.index')->with('success', 'Guru berhasil ditambahkan.');
    }

    public function update(Request $request, $guru)
    {
        $guruModel = Guru::findOrFail($guru);
        $schoolId = auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id;

        $request->validate([
            'nama' => 'required|string|max:100',
            'nip' => 'nullable|string|max:50',
            'default_shift_id' => 'nullable|exists:shifts,id',
            'tgl_lahir' => 'nullable|date',
            'no_wa' => [
                'required',
                'string',
                'max:20',
                'regex:/^(08|628)[0-9]{8,13}$/',
                Rule::unique('guru')->ignore($guruModel->id)->where(fn($q) => $q->where('school_id', $schoolId))
            ],
            'uid_rfid' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('guru')->ignore($guruModel->id)->where(fn($q) => $q->where('school_id', $schoolId))
            ],
            'telegram_chat_id' => 'nullable|string|max:50',
        ]);

        $data = $request->all();
        $data['is_global_report'] = $request->has('is_global_report');
        if (isset($data['no_wa'])) {
            $data['no_wa'] = $this->normalizeWa($data['no_wa']);
        }
        if (empty($data['telegram_chat_id'])) {
            $data['telegram_chat_id'] = null;
        }
        $guruModel->update($data);

        return redirect()->route('guru.index')->with('success', 'Data guru berhasil diperbarui.');
    }

    public function destroy($guru)
    {
        $guruModel = Guru::findOrFail($guru);
        $guruModel->delete();

        return redirect()->route('guru.index')->with('success', 'Guru berhasil dihapus.');
    }

    /**
     * Toggle bot_access for a teacher (AJAX).
     * Admin sekolah bisa toggle, dibatasi oleh bot_user_limit dari superadmin.
     */
    public function toggleBotAccess(Request $request, $id)
    {
        $guru = Guru::findOrFail($id);

        // Pastikan admin hanya bisa atur guru sekolahnya sendiri
        $user = auth()->user();
        if (!$user->isSuperAdmin() && $guru->school_id !== $user->school_id) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $school = \App\Models\School::find($guru->school_id);

        // Jika akan diaktifkan, cek kuota bot
        // Jika akan diaktifkan, cek kuota bot
        if (!$guru->bot_access) {
            if ($school && !$school->hasBotQuota()) {
                $limit = $school->bot_user_limit;
                return response()->json(['success' => false, 'message' => "Kuota Bot WhatsApp sekolah penuh (Maks: $limit guru)."], 422);
            }

            // Check global license bot quota for self_hosted
            $licenseService = app(\App\Services\LicenseService::class);
            if (!$licenseService->hasGlobalBotQuota()) {
                return response()->json(['success' => false, 'message' => "Kuota global Bot WhatsApp dari Lisensi Anda penuh. Hubungi provider."], 422);
            }
        }

        $guru->bot_access = !$guru->bot_access;
        $guru->save();

        if (config('app.mode', 'hosted') === 'self_hosted') {
            $botCount = \App\Models\Guru::where('bot_access', true)->count();
            $licenseLimit = app(\App\Services\LicenseService::class)->validate()['max_bot_users'] ?? 0;
            $botLimit = $licenseLimit > 0 ? $licenseLimit : '∞';
        } else {
            $botCount = $school ? $school->botAccessCount() : '-';
            $botLimit = ($school && $school->bot_user_limit > 0) ? $school->bot_user_limit : '∞';
        }

        return response()->json([
            'success'    => true,
            'bot_access' => $guru->bot_access,
            'bot_count'  => $botCount,
            'bot_limit'  => $botLimit,
            'message'    => $guru->bot_access
                ? "{$guru->nama} sekarang bisa menggunakan bot WhatsApp. ({$botCount}/{$botLimit})"
                : "{$guru->nama} tidak lagi bisa menggunakan bot WhatsApp. ({$botCount}/{$botLimit})",
        ]);
    }

    // Enrollment Methods

    public function enrollRequest($id)
    {
        // Cancel others (Siswa & Guru) to ensure Single Active Request - SCOPED
        $guru = Guru::findOrFail($id);
        $schoolId = $guru->school_id;

        \App\Models\Siswa::where('enroll_status', 'requested')
            ->where('school_id', $schoolId)
            ->update(['enroll_status' => 'none']);

        Guru::where('enroll_status', 'requested')
            ->where('school_id', $schoolId)
            ->where('id', '!=', $id) // exclude self just in case, though usually update is next
            ->update(['enroll_status' => 'none']);

        $guru->update(['enroll_status' => 'requested']);
        // Push notification removed for RFID as per user request
        return response()->json(['ok' => true]);
    }

    public function cancelEnroll($id)
    {
        $guru = Guru::findOrFail($id);
        if ($guru->enroll_status === 'requested') {
            $guru->update(['enroll_status' => 'none']);
        }
        return response()->json(['ok' => true]);
    }

    public function enrollCheck($id)
    {
        $guru = Guru::findOrFail($id);
        if ($guru->enroll_status === 'done' && $guru->uid_rfid) {
            return response()->json(['ok' => true, 'uid' => $guru->uid_rfid]);
        } elseif (str_starts_with($guru->enroll_status, 'error:')) {
            $errorMsg = substr($guru->enroll_status, 6);
            if ($errorMsg === 'UID Dipakai') {
                $errorMsg = 'Kartu sudah terdaftar di sistem.';
            }
            $guru->update(['enroll_status' => 'none']);
            return response()->json(['ok' => true, 'uid' => null, 'error' => $errorMsg]);
        }
        return response()->json(['ok' => true, 'uid' => null]);
    }

    public function deleteUid($id)
    {
        $guru = Guru::findOrFail($id);
        $guru->update(['uid_rfid' => null, 'enroll_status' => 'none']);
        return response()->json(['ok' => true]);
    }

    // Fingerprint Enrollment Methods
    public function enrollFingerRequest(Request $request, $id)
    {
        $request->validate([
            'device_id' => 'required|exists:api_keys,id',
        ]);

        // Cancel others to ensure Single Active Request - SCOPED
        $guru = Guru::findOrFail($id);
        $schoolId = $guru->school_id;

        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            if ($guru->school_id !== auth()->user()->school_id) {
                return response()->json(['ok' => false, 'message' => 'Akses ditolak.'], 403);
            }
        }

        $device = \App\Models\Device::where('id', $request->device_id)
            ->where('school_id', $schoolId)
            ->first();

        if (!$device) {
            return response()->json(['ok' => false, 'message' => 'Device tidak valid untuk sekolah guru ini.'], 422);
        }

        \App\Models\Siswa::where('enroll_finger_status', 'requested')
            ->where('school_id', $schoolId)
            ->update(['enroll_finger_status' => 'none']);

        Guru::where('enroll_finger_status', 'requested')
            ->where('school_id', $schoolId)
            ->update(['enroll_finger_status' => 'none']);

        \App\Models\GateCard::where('enroll_finger_status', 'requested')
            ->where('school_id', $schoolId)
            ->update(['enroll_finger_status' => 'none']);

        $guru->update(['enroll_finger_status' => 'requested']);
        \Illuminate\Support\Facades\Cache::put('enroll_target_device_' . $schoolId, $device->id, now()->addMinutes(2));

        // --- Push Notification to Selected Device ---
        $lastLog = \App\Models\ApiLog::where('api_key', $device->api_key)
            ->whereNotNull('ip_address')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastLog && $lastLog->ip_address) {
            \Illuminate\Support\Facades\Log::info("Pushing Enroll to ESP: {$lastLog->ip_address} for Guru ID: {$guru->id}");
            try {
                // Send Push to /enroll-finger or similar
                // Using query params to pass ID if needed, or just trigger mode
                \Illuminate\Support\Facades\Http::timeout(2)
                    ->get("http://{$lastLog->ip_address}/enroll-finger?id=" . $guru->id);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to push to ESP Finger at {$lastLog->ip_address}: " . $e->getMessage());
            }
        } else {
            \Illuminate\Support\Facades\Log::warning("No IP found for device: {$device->name}");
        }
        // --------------------------------------------

        return response()->json(['ok' => true]);
    }

    public function cancelFingerEnroll($id)
    {
        $guru = Guru::findOrFail($id);
        \Illuminate\Support\Facades\Cache::forget('enroll_target_device_' . $guru->school_id);
        if ($guru->enroll_finger_status === 'requested') {
            $guru->update(['enroll_finger_status' => 'none']);
        }
        return response()->json(['ok' => true]);
    }

    public function enrollFingerCheck($id)
    {
        $guru = Guru::findOrFail($id);

        if ($guru->enroll_finger_status === 'done' && $guru->id_finger) {
            \Illuminate\Support\Facades\Cache::forget('enroll_stage_' . $guru->school_id);
            \Illuminate\Support\Facades\Cache::forget('enroll_target_device_' . $guru->school_id);
            return response()->json(['ok' => true, 'id_finger' => $guru->id_finger, 'status' => 'done']);
        }

        if ($guru->enroll_finger_status === null) {
            \Illuminate\Support\Facades\Cache::forget('enroll_stage_' . $guru->school_id);
            \Illuminate\Support\Facades\Cache::forget('enroll_target_device_' . $guru->school_id);
            $lastLog = \App\Models\ApiLog::where('school_id', $guru->school_id)
                ->where('action', 'enroll_failed')
                ->where('created_at', '>=', now()->subSeconds(45))
                ->latest()
                ->first();
            $msg = $lastLog ? $lastLog->message : 'Pendaftaran sidik jari gagal / jari tidak cocok.';
            return response()->json(['ok' => false, 'status' => 'failed', 'message' => $msg]);
        }

        $stage = \Illuminate\Support\Facades\Cache::get('enroll_stage_' . $guru->school_id, 'touch_1');
        return response()->json(['ok' => true, 'id_finger' => null, 'status' => 'requested', 'stage' => $stage]);
    }

    public function deleteFingerId($id)
    {
        $guru = Guru::findOrFail($id);

        // 1. Get all fingerprints
        $fingerprints = $guru->fingerprints()->with('device')->get();

        foreach ($fingerprints as $fp) {
            if ($fp->device) {
                // Find last IP
                $lastLog = \App\Models\ApiLog::where('api_key', $fp->device->api_key)
                    ->whereNotNull('ip_address')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($lastLog && $lastLog->ip_address) {
                    \Illuminate\Support\Facades\Log::info("Pushing Delete to ESP: {$lastLog->ip_address} for Finger ID: {$fp->finger_id}");
                    try {
                        // Send Push to /delete-finger
                        \Illuminate\Support\Facades\Http::timeout(2)
                            ->get("http://{$lastLog->ip_address}/delete-finger?id=" . $fp->finger_id);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to push delete to ESP {$lastLog->ip_address}: " . $e->getMessage());
                    }
                }
            }
        }

        // 2. Delete all fingerprints for this guru from DB
        $guru->fingerprints()->delete();

        // 3. Clear legacy columns
        $guru->update(['id_finger' => null, 'enroll_finger_status' => 'none']);

        return response()->json(['ok' => true]);
    }


    // Import Excel
    public function import(Request $request)
    {
        $request->validate([
            'fileExcel' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            $file = $request->file('fileExcel');
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $countSuccess = 0;
            $countSkip = 0;
            $firstRow = true;

            foreach ($rows as $row) {
                if ($firstRow) {
                    $firstRow = false;
                    continue;
                }

                $nama = trim($row[0] ?? '');
                $nip = trim($row[1] ?? '');

                if ($nip === '') {
                    $nip = null;
                }

                $wa = isset($row[2]) ? trim($row[2]) : null;

                if ($nama === '') {
                    $countSkip++;
                    continue;
                }

                $normalizedWa = $this->normalizeWa($wa);

                $schoolId = auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id;

                // Check duplicate by WA or NIP if present - SCOPED
                $exists = false;
                if ($normalizedWa) {
                    $queryWa = Guru::where('no_wa', $normalizedWa);
                    if ($schoolId)
                        $queryWa->where('school_id', $schoolId);
                    $exists = $queryWa->exists();
                }

                if (!$exists && $nip) {
                    $queryNip = Guru::where('nip', $nip);
                    if ($schoolId)
                        $queryNip->where('school_id', $schoolId);
                    $exists = $queryNip->exists();
                }

                if ($exists) {
                    $countSkip++;
                    continue;
                }

                // Check quota before each insert
                if ($schoolId) {
                    $school = $school ?? \App\Models\School::find($schoolId);
                    if ($school && !$school->hasTeacherQuota()) {
                        if ($request->wantsJson()) {
                            return response()->json(['success' => false, 'message' => "Import dihentikan: Kuota guru/staff penuh ({$school->teacher_limit} guru). Berhasil diimpor: {$countSuccess} guru."]);
                        }
                        return redirect()->route('guru.index')->with('error', "Import dihentikan: Kuota guru/staff penuh ({$school->teacher_limit} guru). Berhasil diimpor: {$countSuccess} guru.");
                    }
                }

                Guru::create([
                    'nama'      => $nama,
                    'nip'       => $nip,
                    'no_wa'     => $normalizedWa,
                    'school_id' => $schoolId,
                ]);

                $countSuccess++;
            }

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => "Import selesai. Berhasil: $countSuccess. Dilewati (Duplikat/Kosong): $countSkip."]);
            }
            return redirect()->route('guru.index')->with('success', "Import selesai. Berhasil: $countSuccess. Dilewati (Duplikat/Kosong): $countSkip.");

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal import file: ' . $e->getMessage()]);
            }
            return redirect()->route('guru.index')->with('error', 'Gagal import file: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'Nama Lengkap');
        $sheet->setCellValue('B1', 'NIP (Opsional)');
        $sheet->setCellValue('C1', 'No WhatsApp (08xxx)');

        // Example
        $sheet->setCellValue('A2', 'Budi Santoso, S.Pd');
        $sheet->setCellValue('B2', '198001012005011001');
        $sheet->setCellValue('C2', '081234567890');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="Template_Import_Guru.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    private function normalizeWa($wa)
    {
        if (!$wa)
            return null;
        $wa = preg_replace('/[^0-9]/', '', $wa);
        if (strpos($wa, '08') === 0) {
            $wa = '62' . substr($wa, 1);
        }
        return $wa;
    }
}
