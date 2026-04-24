<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Setting;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\Device;
use App\Models\Attendance;
use App\Models\AbsensiGuru;
use App\Models\TeacherCheckoutSession;
use App\Models\MessageQueue;
use App\Models\JadwalPelajaran;
use App\Models\Mapel;

use App\Models\SiswaFingerprint;
use App\Models\GuruFingerprint;

class SchoolBackupController extends Controller
{
    public function index()
    {
        // Logic: 
        // Super Admin -> Sees School Selector
        // School Admin -> Sees their own school backup UI directly

        if (auth()->user()->isSuperAdmin()) {
            $schools = \App\Models\School::all();
            return view('backup.index', compact('schools'));
        }

        return view('backup.index');
    }

    public function download(Request $request)
    {
        $user = auth()->user();

        // Determine School ID
        if ($user->isSuperAdmin()) {
            $schoolId = $request->input('school_id');
            if (!$schoolId)
                return back()->with('error', 'Silakan pilih sekolah.');
        } else {
            $schoolId = $user->school_id;
            if (!$schoolId)
                return back()->with('error', 'Anda tidak terhubung dengan sekolah manapun.');
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $schoolName = Setting::where('school_id', $schoolId)->where('setting_key', 'nama_sekolah')->value('setting_value') ?? 'School';
        $filename = 'backup_' . \Str::slug($schoolName) . '_' . $timestamp . '.json';

        return response()->streamDownload(function () use ($schoolId) {
            echo "{";
            echo '"version": "1.0",';
            echo '"exported_at": "' . now()->toIso8601String() . '",';
            echo '"school_id_source": ' . $schoolId . ',';

            // 1. Settings
            echo '"settings": ' . Setting::where('school_id', $schoolId)->get()->toJson() . ',';

            // 2. Users (Role: Admin, Student, Teacher associated with this school)
            echo '"users": ' . User::where('school_id', $schoolId)->get()->toJson() . ',';

            // 3. Classes
            echo '"kelas": ' . Kelas::where('school_id', $schoolId)->get()->toJson() . ',';

            // 4. Students & Fingerprints
            // We need to fetch students, then likely fetch their fingerprints separately or with eager load.
            // To keep JSON flat-ish, let's do separate keys.
            $siswaIds = Siswa::where('school_id', $schoolId)->pluck('id');
            echo '"siswa": ' . Siswa::where('school_id', $schoolId)->get()->makeVisible(['uuid_rfid', 'id_finger'])->toJson() . ',';
            echo '"siswa_fingerprints": ' . SiswaFingerprint::whereIn('student_id', $siswaIds)->get()->toJson() . ',';

            // 5. Teachers & Fingerprints
            $guruIds = Guru::where('school_id', $schoolId)->pluck('id');
            echo '"guru": ' . Guru::where('school_id', $schoolId)->get()->makeVisible(['uid_rfid', 'id_finger'])->toJson() . ',';
            echo '"guru_fingerprints": ' . GuruFingerprint::whereIn('guru_id', $guruIds)->get()->toJson() . ',';

            // 6. Schedules (Jadwal & JadwalPelajaran & Mapel)
            echo '"jadwal": ' . Jadwal::where('school_id', $schoolId)->get()->toJson() . ',';
            echo '"mapel": ' . Mapel::where('school_id', $schoolId)->get()->toJson() . ',';
            echo '"jadwal_pelajaran": ' . JadwalPelajaran::where('school_id', $schoolId)->get()->toJson() . ',';

            // 7. Holidays (Removed)

            // 8. Devices
            echo '"devices": ' . Device::where('school_id', $schoolId)->get()->toJson() . ',';

            // 9. Attendance Records (Large Data)
            // Use chunking logic? For JSON stream, we can iterate.
            echo '"attendance": [';
            $first = true;
            Attendance::whereIn('student_id', $siswaIds)->chunk(500, function ($rows) use (&$first) {
                foreach ($rows as $row) {
                    if (!$first)
                        echo ",";
                    echo $row->toJson();
                    $first = false;
                }
            });
            echo '],';

            // 10. Teacher Attendance
            echo '"absensi_guru": [';
            $first = true;
            AbsensiGuru::where('school_id', $schoolId)->chunk(500, function ($rows) use (&$first) {
                foreach ($rows as $row) {
                    if (!$first)
                        echo ",";
                    echo $row->toJson();
                    $first = false;
                }
            });
            echo '],';

            // 11. Teacher Checkout Sessions
            echo '"teacher_checkout_sessions": [';
            $first = true;
            // Sessions store teacher_id. We must filter by teachers in this school.
            TeacherCheckoutSession::whereIn('teacher_id', $guruIds)->chunk(500, function ($rows) use (&$first) {
                foreach ($rows as $row) {
                    if (!$first)
                        echo ",";
                    echo $row->toJson();
                    $first = false;
                }
            });
            echo ']'; // End of JSON object

            echo "}";
        }, $filename, ['Content-Type' => 'application/json']);
    }

    public function restore(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'backup_file' => 'required|file|mimes:json,txt',
            'confirmation' => 'required|accepted',
            // School ID required ONLY if Super Admin
            'school_id' => $user->isSuperAdmin() ? 'required|exists:schools,id' : 'nullable'
        ]);

        if ($user->isSuperAdmin()) {
            $schoolId = $request->input('school_id');
        } else {
            $schoolId = $user->school_id;
            if (!$schoolId)
                return back()->with('error', 'Sekolah tidak valid.');
        }

        $file = $request->file('backup_file');
        $content = file_get_contents($file->getRealPath());
        $data = json_decode($content, true);

        if (!$data || !isset($data['version'])) {
            return back()->with('error', 'Format file backup tidak valid.');
        }

        DB::beginTransaction();
        try {
            // STEP 1: WIPE CURRENT DATA
            // Trigger cascading delete logic via SchoolController? 
            // Better to re-implement specific deletion here to avoid deleting the School record itself.
            // We just want to EMPTY the tables for this school.

            // Delete dependent tables first
            Attendance::whereHas('student', fn($q) => $q->where('school_id', $schoolId))->delete();
            SiswaFingerprint::whereHas('student', fn($q) => $q->where('school_id', $schoolId))->delete();

            AbsensiGuru::where('school_id', $schoolId)->delete();
            TeacherCheckoutSession::whereHas('teacher', fn($q) => $q->where('school_id', $schoolId))->delete(); // teacher relation check
            GuruFingerprint::whereHas('guru', fn($q) => $q->where('school_id', $schoolId))->delete();

            JadwalPelajaran::where('school_id', $schoolId)->delete();
            Mapel::where('school_id', $schoolId)->delete();
            Jadwal::where('school_id', $schoolId)->delete();


            Siswa::where('school_id', $schoolId)->delete();
            Guru::where('school_id', $schoolId)->delete();
            Kelas::where('school_id', $schoolId)->delete();
            Device::where('school_id', $schoolId)->delete();
            Setting::where('school_id', $schoolId)->delete();
            User::where('school_id', $schoolId)->where('id', '!=', $user->id)->delete(); // Keep current admin?
            // If we delete current admin, we get logged out or error.
            // Ideally, we should Restore Users EXCEPT current one? Or update current one?
            // Let's decide: RESTORE Users but SKIP/UPDATE current user if collision.
            // Safe bet: Don't delete SELF.

            // STEP 2: RESTORE & MAP IDs
            $mapUsers = [];
            $mapKelas = [];
            $mapGuru = [];
            $mapSiswa = [];
            $mapJadwal = [];
            $mapMapel = [];

            // 1. Settings
            foreach ($data['settings'] as $item) {
                // Force current school_id
                $item['school_id'] = $schoolId;
                unset($item['id']);
                Setting::create($item);
            }

            // 2. Users (Admin/Staff) - Skip Students/Teachers users for now, handle them with link
            foreach ($data['users'] as $item) {
                if ($item['role'] == 'student')
                    continue; // Will be re-created/linked by Siswa logic? Or manually restored?
                // Let's restore Admins.
                if ($item['id'] == $user->id)
                    continue; // Skip self

                $oldId = $item['id'];
                unset($item['id']);
                $item['school_id'] = $schoolId;

                // Check username/email collision
                $exists = User::where('username', $item['username'])->orWhere('email', $item['email'])->exists();
                if ($exists) {
                    $item['username'] = $item['username'] . '_restored_' . rand(100, 999);
                    $item['email'] = $item['username'] . '@restored.com';
                }

                $newUser = User::create($item);
                $mapUsers[$oldId] = $newUser->id;
            }

            // 3. Kelas
            foreach ($data['kelas'] as $item) {
                $oldId = $item['id'];
                unset($item['id']);
                $item['school_id'] = $schoolId;
                $newKelas = Kelas::create($item);
                $mapKelas[$oldId] = $newKelas->id;
            }

            // 4. Guru
            foreach ($data['guru'] as $item) {
                $oldId = $item['id'];
                unset($item['id']);
                $item['school_id'] = $schoolId;

                // Map User ID if exists
                if (isset($item['user_id']) && isset($mapUsers[$item['user_id']])) {
                    $item['user_id'] = $mapUsers[$item['user_id']];
                } else {
                    $item['user_id'] = null; // Unlink if user not found (e.g. self-admin)
                }

                $newGuru = Guru::create($item);
                $mapGuru[$oldId] = $newGuru->id;
            }

            // 5. Guru Fingerprints
            if (isset($data['guru_fingerprints'])) {
                foreach ($data['guru_fingerprints'] as $item) {
                    unset($item['id']);
                    if (isset($mapGuru[$item['guru_id']])) {
                        $item['guru_id'] = $mapGuru[$item['guru_id']];
                        // Device ID? We haven't restored devices yet.
                        // Order matters! Devices should be before fingerprints if linked.
                        // But fingerprints table usually link to device_id.
                        // Let's restore devices first? No, list order on top had devices late.
                        // Let's move Devices higher or handle map later.
                        // Wait, logic: Restore Devices -> Map Device IDs.
                    }
                }
            }

            // Re-order: Handle Devices now
            $mapDevices = [];
            foreach ($data['devices'] ?? [] as $item) {
                $oldId = $item['id'];
                unset($item['id']);
                $item['school_id'] = $schoolId;
                // Unique API Key?
                $exists = Device::where('api_key', $item['api_key'])->exists();
                if ($exists) {
                    $item['api_key'] = $item['api_key'] . '_restored';
                }
                $newDevice = Device::create($item);
                $mapDevices[$oldId] = $newDevice->id;
            }

            // Now Guru Fingerprints again
            if (isset($data['guru_fingerprints'])) {
                foreach ($data['guru_fingerprints'] as $item) {
                    unset($item['id']);
                    if (isset($mapGuru[$item['guru_id']]) && isset($mapDevices[$item['device_id']])) {
                        $item['guru_id'] = $mapGuru[$item['guru_id']];
                        $item['device_id'] = $mapDevices[$item['device_id']];
                        GuruFingerprint::create($item);
                    }
                }
            }

            // 6. Siswa
            foreach ($data['siswa'] as $item) {
                $oldId = $item['id'];
                unset($item['id']);
                $item['school_id'] = $schoolId;

                // Map Kelas
                if (isset($item['kelas_id']) && isset($mapKelas[$item['kelas_id']])) {
                    $item['kelas_id'] = $mapKelas[$item['kelas_id']];
                }

                // Map User (Student User) - We skipped filtering student users early.
                // Re-create user for student?
                // The export contained ALL users.
                // If restore created a User with role 'student', we mapped it in $mapUsers.
                // But earlier I put `if ($item['role'] == 'student') continue;`
                // So Student Users were NOT created in Users loop.
                // We should probably recreate them here or handled above.
                // Better: Create new user for student to ensure credential sync.
                $username = $schoolId . $item['nis']; // New logic
                
                // Ensure unique email
                $email = $username . '@siswa.smkassuniyah.sch.id';
                $emailExists = User::where('email', $email)->exists();
                if ($emailExists) {
                    $email = $username . rand(10, 99) . '@siswa.smkassuniyah.sch.id';
                }

                $userSitswa = User::updateOrCreate(
                    ['username' => $username],
                    [
                        'full_name' => $item['nama'],
                        'email' => $email,
                        'password_hash' => \Illuminate\Support\Facades\Hash::make($item['nis']),
                        'role' => 'student',
                        'school_id' => $schoolId
                    ]
                );
                $item['user_id'] = $userSitswa->id;

                $newSiswa = Siswa::create($item);
                $mapSiswa[$oldId] = $newSiswa->id;
            }

            // 7. Siswa Fingerprints
            if (isset($data['siswa_fingerprints'])) {
                foreach ($data['siswa_fingerprints'] as $item) {
                    unset($item['id']);
                    if (isset($mapSiswa[$item['student_id']]) && isset($mapDevices[$item['device_id']])) {
                        $item['student_id'] = $mapSiswa[$item['student_id']];
                        $item['device_id'] = $mapDevices[$item['device_id']];
                        SiswaFingerprint::create($item);
                    }
                }
            }

            // 8. Jadwal
            foreach ($data['jadwal'] as $item) {
                $oldId = $item['id'];
                unset($item['id']);
                $item['school_id'] = $schoolId;
                $newJadwal = Jadwal::create($item);
                $mapJadwal[$oldId] = $newJadwal->id;
            }

            // 8.5 Mapel
            foreach ($data['mapel'] ?? [] as $item) {
                $oldId = $item['id'];
                unset($item['id']);
                $item['school_id'] = $schoolId;
                $newMapel = Mapel::create($item);
                $mapMapel[$oldId] = $newMapel->id;
            }

            // 9. Jadwal Pelajaran
            foreach ($data['jadwal_pelajaran'] as $item) {
                unset($item['id']);
                $item['school_id'] = $schoolId;
                if (isset($mapKelas[$item['kelas_id']]))
                    $item['kelas_id'] = $mapKelas[$item['kelas_id']];
                if (isset($mapGuru[$item['guru_id']]))
                    $item['guru_id'] = $mapGuru[$item['guru_id']];
                if (isset($mapMapel[$item['mapel_id']]))
                    $item['mapel_id'] = $mapMapel[$item['mapel_id']];

                JadwalPelajaran::create($item);
            }

            // 10. Hari Libur (Removed)

            // 11. Attendance
            foreach ($data['attendance'] as $item) {
                unset($item['id']);
                if (isset($mapSiswa[$item['student_id']])) {
                    $item['student_id'] = $mapSiswa[$item['student_id']];
                    Attendance::create($item);
                }
            }

            // 12. Absensi Guru
            foreach ($data['absensi_guru'] as $item) {
                unset($item['id']);
                $item['school_id'] = $schoolId;
                if (isset($mapGuru[$item['guru_id']])) {
                    $item['guru_id'] = $mapGuru[$item['guru_id']];
                    // jadwal_pelajaran_id ??
                    AbsensiGuru::create($item);
                }
            }

            DB::commit();
            return back()->with('success', 'Data sekolah berhasil direstore/diimport ulang.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Restore Failed: " . $e->getMessage());
            return back()->with('error', 'Restore Gagal: ' . $e->getMessage());
        }
    }
}
