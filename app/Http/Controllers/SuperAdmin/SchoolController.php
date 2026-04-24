<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SchoolController extends Controller
{
    /**
     * Display a listing of schools
     */
    public function index()
    {
        $schools = School::withCount(['siswa', 'guru', 'admins'])
            ->latest()
            ->paginate(15);

        return view('super-admin.schools.index', compact('schools'));
    }

    /**
     * Show the form for creating a new school
     */
    public function create()
    {
        return view('super-admin.schools.create');
    }

    /**
     * Store a newly created school
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'operator_phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|max:10240',
            'student_limit' => 'nullable|integer|min:0',
        ]);

        // Auto-generate code
        do {
            $code = 'SCH-' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (\App\Models\School::where('code', $code)->exists());
        $validated['code'] = $code;

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('schools/logos', 'public');
            $validated['logo'] = $logoPath;
        }

        // Handle checkboxes
        $validated['is_active'] = $request->has('is_active');
        $validated['wa_enabled'] = $request->has('wa_enabled');
        $validated['student_limit'] = $request->student_limit;

        $school = School::create($validated);

        // Create default settings for the new school
        $defaultSettings = [
            'nama_sekolah' => $validated['name'],
            'alamat_sekolah' => $validated['address'] ?? '',
            'logo_filename' => isset($validated['logo']) ? $validated['logo'] : null, // Use full path logic store returns
            'enable_checkout_attendance' => 'false',
            'absence_notification_enabled' => 'false',
            'schedule_process_daily' => '14:00',
            'absence_threshold_days' => '3',
            'absence_check_period_days' => '7',
            'toleransi_buka_absen_pulang' => '15',
            'kota_lokasi_ttd' => 'Jakarta' // Default city
        ];

        foreach ($defaultSettings as $key => $value) {
            if ($value !== null) {
                \App\Models\Setting::create([
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'school_id' => $school->id
                ]);
            }
        }

        // Sync WA Device
        $this->syncWaDevice($school);

        return redirect()
            ->route('super-admin.schools.index')
            ->with('success', 'Sekolah berhasil ditambahkan dan pengaturan default telah dibuat.');
    }

    /**
     * Display the specified school
     */
    public function show(School $school)
    {
        $school->load(['siswa', 'guru', 'users', 'kelas']);

        return view('super-admin.schools.show', compact('school'));
    }

    /**
     * Show the form for editing the specified school
     */
    public function edit(School $school)
    {
        return view('super-admin.schools.edit', compact('school'));
    }

    /**
     * Update the specified school
     */
    public function update(Request $request, School $school)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'operator_phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|max:10240',
            'student_limit' => 'nullable|integer|min:0',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($school->logo && \Storage::disk('public')->exists($school->logo)) {
                \Storage::disk('public')->delete($school->logo);
            }

            $logoPath = $request->file('logo')->store('schools/logos', 'public');
            $validated['logo'] = $logoPath;

            // Sync with Settings
            \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
                ['setting_key' => 'logo_filename', 'school_id' => $school->id],
                ['setting_value' => $logoPath]
            );
        }

        // Handle checkboxes
        // Handle checkboxes
        $validated['is_active'] = $request->has('is_active');
        $validated['wa_enabled'] = $request->has('wa_enabled');
        $validated['student_limit'] = $request->student_limit;

        $school->update($validated);

        // Sync Name and Address with Settings
        \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
            ['school_id' => $school->id, 'setting_key' => 'nama_sekolah'],
            ['setting_value' => $validated['name']]
        );

        \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
            ['school_id' => $school->id, 'setting_key' => 'alamat_sekolah'],
            ['setting_value' => $validated['address'] ?? '']
        );

        // Sync WA Device
        $this->syncWaDevice($school);

        return redirect()
            ->route('super-admin.schools.index')
            ->with('success', 'Sekolah berhasil diperbarui.');
    }

    /**
     * Remove the specified school
     */
    public function destroy(School $school)
    {
        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // 1. Delete Related Data
            // Users (Admins)
            \App\Models\User::where('school_id', $school->id)->delete();

            // Settings
            \App\Models\Setting::where('school_id', $school->id)->delete();

            // Hari Libur (Removed)

            // Jadwal
            \App\Models\Jadwal::where('school_id', $school->id)->delete();

            // Mapel (if linked to school? Mapel table usually has no school_id in simple implementations unless upgraded)
            // Checking Mapel model/schema might be needed, but assuming simple deletion for now. 
            // Better check if Mapel has school_id. Assuming yes from context.
            // If not, we skip. Let's be safe and check schema first? 
            // Actually, let's look at what we KNOW has school_id.

            // Classes
            \App\Models\Kelas::where('school_id', $school->id)->delete();

            // Students (and their attendance/fingerprints via database constraints or manual?)
            // If DB doesn't have ON DELETE CASCADE, we must delete manually.
            $studentIds = \App\Models\Siswa::where('school_id', $school->id)->pluck('id');
            \App\Models\Attendance::whereIn('student_id', $studentIds)->delete();
            \App\Models\SiswaFingerprint::whereIn('student_id', $studentIds)->delete();
            \App\Models\Siswa::whereIn('id', $studentIds)->delete();

            // Teachers (and their attendance/fingerprints)
            $teacherIds = \App\Models\Guru::where('school_id', $school->id)->pluck('id');
            \App\Models\AbsensiGuru::whereIn('guru_id', $teacherIds)->delete();
            \App\Models\GuruFingerprint::whereIn('guru_id', $teacherIds)->delete();
            \App\Models\TeacherCheckoutSession::whereIn('teacher_id', $teacherIds)->delete();
            \App\Models\Guru::whereIn('id', $teacherIds)->delete();

            // Devices (API Keys)
            \App\Models\Device::where('school_id', $school->id)->delete();

            // 2. Delete Logo File
            if ($school->logo && \Storage::disk('public')->exists($school->logo)) {
                \Storage::disk('public')->delete($school->logo);
            }

            // 3. Delete School
            $school->delete();

            \Illuminate\Support\Facades\DB::commit();

            // Delete WA Device
            $this->deleteWaDevice($school);

            return redirect()
                ->route('super-admin.schools.index')
                ->with('success', 'Sekolah dan seluruh data terkait berhasil dihapus.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()
                ->route('super-admin.schools.index')
                ->with('error', 'Gagal menghapus sekolah: ' . $e->getMessage());
        }
    }

    private function syncWaDevice(School $school)
    {
        $base = rtrim(env('WA_API_BASE_URL', 'http://localhost:3000'), '/');
        $user = env('WA_API_USER', 'admin');
        $pass = env('WA_API_PASS', '04112000');
        $deviceId = (string)$school->id;

        try {
            if ($school->wa_enabled) {
                \Illuminate\Support\Facades\Http::timeout(5)
                    ->withBasicAuth($user, $pass)
                    ->post("{$base}/devices", [
                        'device_id' => $deviceId,
                        'description' => 'WA Device for ' . $school->name
                    ]);
            } else {
                \Illuminate\Support\Facades\Http::timeout(5)
                    ->withBasicAuth($user, $pass)
                    ->delete("{$base}/devices/{$deviceId}");
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to sync WA device for school {$school->id}: " . $e->getMessage());
        }
    }

    private function deleteWaDevice(School $school)
    {
        $base = rtrim(env('WA_API_BASE_URL', 'http://localhost:3000'), '/');
        $user = env('WA_API_USER', 'admin');
        $pass = env('WA_API_PASS', '04112000');
        $deviceId = (string)$school->id;

        try {
            \Illuminate\Support\Facades\Http::timeout(5)
                ->withBasicAuth($user, $pass)
                ->delete("{$base}/devices/{$deviceId}");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to delete WA device for school {$school->id}: " . $e->getMessage());
        }
    }
}
