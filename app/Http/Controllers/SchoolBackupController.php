<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Setting;
use App\Models\NotificationTemplate;
use App\Models\Shift;
use App\Models\GuruShiftAssignment;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Guru;
use App\Models\UserFaceData;
use App\Models\GateCard;
use App\Models\GateCardFingerprint;
use App\Models\Jadwal;
use App\Models\JamPelajaran;
use App\Models\Device;
use App\Models\Attendance;
use App\Models\AbsensiGuru;
use App\Models\TeacherCheckoutSession;
use App\Models\JadwalPelajaran;
use App\Models\JurnalPelajaran;
use App\Models\AbsensiPelajaran;
use App\Models\Kegiatan;
use App\Models\KegiatanSession;
use App\Models\KegiatanAttendance;
use App\Models\StudentLeave;
use App\Models\Mapel;
use App\Models\Jurusan;
use App\Models\SiswaFingerprint;
use App\Models\GuruFingerprint;

class SchoolBackupController extends Controller
{
    public function index()
    {
        if (auth()->user()->isSuperAdmin()) {
            $schools = \App\Models\School::all();
            return view('backup.index', compact('schools'));
        }

        return view('backup.index');
    }

    /**
     * Helper untuk menuliskan array/query Eloquent secara streaming (chunked) ke output JSON.
     */
    private function streamJsonArray(string $key, $query, bool $withTrailingComma = true, int $chunkSize = 500): void
    {
        echo '"' . $key . '": [';
        $first = true;
        $query->chunk($chunkSize, function ($rows) use (&$first) {
            foreach ($rows as $row) {
                if (!$first) {
                    echo ',';
                }
                echo is_object($row) && method_exists($row, 'toJson') ? $row->toJson() : json_encode($row);
                $first = false;
            }
        });
        echo $withTrailingComma ? '],' : ']';
    }

    public function download(Request $request)
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $schoolId = $request->input('school_id');
            if (!$schoolId) {
                return back()->with('error', 'Silakan pilih sekolah.');
            }
        } else {
            $schoolId = $user->school_id;
            if (!$schoolId) {
                return back()->with('error', 'Anda tidak terhubung dengan sekolah manapun.');
            }
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $schoolName = Setting::where('school_id', $schoolId)->where('setting_key', 'nama_sekolah')->value('setting_value') ?? 'School';
        $filename = 'backup_' . \Str::slug($schoolName) . '_' . $timestamp . '.json';

        return response()->streamDownload(function () use ($schoolId) {
            $userIds = User::where('school_id', $schoolId)->pluck('id');
            $siswaIds = Siswa::where('school_id', $schoolId)->pluck('id');
            $guruIds = Guru::where('school_id', $schoolId)->pluck('id');
            $gateCardIds = Schema::hasTable('gate_cards')
                ? GateCard::where('school_id', $schoolId)->pluck('id')
                : collect();
            $kegiatanIds = Schema::hasTable('kegiatans')
                ? Kegiatan::where('school_id', $schoolId)->pluck('id')
                : collect();

            echo '{';
            echo '"version": "2.0",';
            echo '"exported_at": "' . now()->toIso8601String() . '",';
            echo '"school_id_source": ' . (int) $schoolId . ',';

            // 1. Settings & Notification Templates
            echo '"settings": ' . Setting::where('school_id', $schoolId)->get()->toJson() . ',';
            echo '"notification_templates": ' . (Schema::hasTable('notification_templates')
                ? NotificationTemplate::where('school_id', $schoolId)->get()->toJson()
                : '[]') . ',';

            // 2. Users (Admin, Teacher, Student)
            echo '"users": ' . User::where('school_id', $schoolId)->get()->makeVisible(['password_hash'])->toJson() . ',';

            // 3. Shifts (Master Shift Kerja Guru/Pegawai)
            echo '"shifts": ' . (Schema::hasTable('shifts')
                ? Shift::where('school_id', $schoolId)->get()->toJson()
                : '[]') . ',';

            // 4. Jurusan & Kelas
            echo '"jurusan": ' . Jurusan::where('school_id', $schoolId)->get()->toJson() . ',';
            echo '"kelas": ' . Kelas::where('school_id', $schoolId)->get()->toJson() . ',';

            // 5. Devices
            echo '"devices": ' . Device::where('school_id', $schoolId)->get()->toJson() . ',';

            // 6. Guru, Fingerprints, & Shift Assignments
            echo '"guru": ' . Guru::where('school_id', $schoolId)->get()->makeVisible(['uid_rfid', 'id_finger'])->toJson() . ',';
            echo '"guru_fingerprints": ' . GuruFingerprint::whereIn('guru_id', $guruIds)->get()->toJson() . ',';
            echo '"guru_shift_assignments": ' . (Schema::hasTable('guru_shift_assignments')
                ? GuruShiftAssignment::where('school_id', $schoolId)->orWhereIn('guru_id', $guruIds)->get()->toJson()
                : '[]') . ',';

            // 7. Siswa & Fingerprints
            echo '"siswa": ' . Siswa::where('school_id', $schoolId)->get()->makeVisible(['uid_rfid', 'id_finger'])->toJson() . ',';
            echo '"siswa_fingerprints": ' . SiswaFingerprint::whereIn('student_id', $siswaIds)->get()->toJson() . ',';

            // 8. User Face Data (512-d & 128-d Face Recognition Embeddings)
            echo '"user_face_data": ' . (Schema::hasTable('user_face_data')
                ? UserFaceData::where(function ($q) use ($siswaIds, $guruIds, $userIds) {
                    $q->whereIn('siswa_id', $siswaIds)
                      ->orWhereIn('guru_id', $guruIds)
                      ->orWhereIn('user_id', $userIds);
                })->get()->toJson()
                : '[]') . ',';

            // 9. Gate Cards & Gate Card Fingerprints
            echo '"gate_cards": ' . (Schema::hasTable('gate_cards')
                ? GateCard::where('school_id', $schoolId)->get()->toJson()
                : '[]') . ',';
            echo '"gate_card_fingerprints": ' . (Schema::hasTable('gate_card_fingerprints') && $gateCardIds->isNotEmpty()
                ? GateCardFingerprint::whereIn('gate_card_id', $gateCardIds)->get()->toJson()
                : '[]') . ',';

            // 10. Jadwal Harian, Jam Pelajaran, Mapel, & Jadwal Pelajaran
            echo '"jadwal": ' . Jadwal::where('school_id', $schoolId)->get()->toJson() . ',';
            echo '"jam_pelajaran": ' . (Schema::hasTable('jam_pelajaran')
                ? JamPelajaran::where('school_id', $schoolId)->get()->toJson()
                : '[]') . ',';
            echo '"mapel": ' . Mapel::where('school_id', $schoolId)->get()->toJson() . ',';
            echo '"jadwal_pelajaran": ' . JadwalPelajaran::where('school_id', $schoolId)->get()->toJson() . ',';

            // 11. Kegiatan (Ekskul / Sholat / Event), Pivot Kelas & Siswa, dan Sesi Kegiatan
            echo '"kegiatans": ' . (Schema::hasTable('kegiatans')
                ? Kegiatan::where('school_id', $schoolId)->get()->toJson()
                : '[]') . ',';
            echo '"kegiatan_kelas": ' . (Schema::hasTable('kegiatan_kelas') && $kegiatanIds->isNotEmpty()
                ? DB::table('kegiatan_kelas')->whereIn('kegiatan_id', $kegiatanIds)->get()->toJson()
                : '[]') . ',';
            echo '"kegiatan_siswa": ' . (Schema::hasTable('kegiatan_siswa') && $kegiatanIds->isNotEmpty()
                ? DB::table('kegiatan_siswa')->whereIn('kegiatan_id', $kegiatanIds)->get()->toJson()
                : '[]') . ',';
            echo '"kegiatan_sessions": ' . (Schema::hasTable('kegiatan_sessions')
                ? KegiatanSession::where('school_id', $schoolId)->orWhereIn('kegiatan_id', $kegiatanIds)->get()->toJson()
                : '[]') . ',';

            // 12. Student Leaves (Pengajuan Izin / Sakit / Dispensasi Siswa)
            if (Schema::hasTable('student_leaves')) {
                $this->streamJsonArray(
                    'student_leaves',
                    StudentLeave::where('school_id', $schoolId)->orWhereIn('student_id', $siswaIds)->orderBy('id'),
                    true
                );
            } else {
                echo '"student_leaves": [],';
            }

            // 13. Jurnal Pelajaran & Absensi Pelajaran (KBM Kelas)
            if (Schema::hasTable('jurnal_pelajaran')) {
                $this->streamJsonArray(
                    'jurnal_pelajaran',
                    JurnalPelajaran::where('school_id', $schoolId)->orderBy('id'),
                    true
                );
            } else {
                echo '"jurnal_pelajaran": [],';
            }

            if (Schema::hasTable('absensi_pelajaran')) {
                $this->streamJsonArray(
                    'absensi_pelajaran',
                    AbsensiPelajaran::where('school_id', $schoolId)->orderBy('id'),
                    true
                );
            } else {
                echo '"absensi_pelajaran": [],';
            }

            // 14. Kegiatan Attendances
            if (Schema::hasTable('kegiatan_attendances')) {
                $this->streamJsonArray(
                    'kegiatan_attendances',
                    KegiatanAttendance::where('school_id', $schoolId)->orWhereIn('kegiatan_id', $kegiatanIds)->orderBy('id'),
                    true
                );
            } else {
                echo '"kegiatan_attendances": [],';
            }

            // 15. Attendance Records Siswa
            $this->streamJsonArray(
                'attendance',
                Attendance::whereIn('student_id', $siswaIds)->orderBy('id'),
                true
            );

            // 16. Absensi Guru
            $this->streamJsonArray(
                'absensi_guru',
                AbsensiGuru::where('school_id', $schoolId)->orderBy('id'),
                true
            );

            // 17. Teacher Checkout Sessions
            $this->streamJsonArray(
                'teacher_checkout_sessions',
                TeacherCheckoutSession::whereIn('teacher_id', $guruIds)->orderBy('id'),
                false
            );

            echo '}';
        }, $filename, ['Content-Type' => 'application/json']);
    }

    /**
     * Filter hanya atribut yang bukan accessor virtual (menghindari error SQL column not found saat insert).
     */
    private function cleanAttributes(array $item, array $excludeKeys = []): array
    {
        $defaultExclude = array_merge([
            'id',
            'foto_url',
            'username',
            'public_identifier',
            'sample_photo_url',
            'dokumentasi_list',
            'has_dokumentasi',
            'formatted_jam_mulai',
            'formatted_jam_selesai',
            'rentang_waktu',
            'durasi_menit',
            'label',
            'hari_array',
            'status_label',
            'status_badge',
            'formatted_jam_masuk',
            'formatted_jam_pulang',
            'formatted_jam_terlambat',
            'formatted_hari_kerja',
            'target_scope_label',
            'formatted_hari',
            'jam_format',
            'jeda_istirahat_info',
            'durasi_hari',
            'jenis_label',
        ], $excludeKeys);

        foreach ($defaultExclude as $k) {
            unset($item[$k]);
        }
        return $item;
    }

    public function restore(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'backup_file' => 'required|file|mimes:json,txt',
            'confirmation' => 'required|accepted',
            'school_id' => $user->isSuperAdmin() ? 'required|exists:schools,id' : 'nullable'
        ]);

        if ($user->isSuperAdmin()) {
            $schoolId = (int) $request->input('school_id');
        } else {
            $schoolId = (int) $user->school_id;
            if (!$schoolId) {
                return back()->with('error', 'Sekolah tidak valid.');
            }
        }

        $file = $request->file('backup_file');
        $content = file_get_contents($file->getRealPath());
        $data = json_decode($content, true);

        if (!$data || !isset($data['version'])) {
            return back()->with('error', 'Format file backup tidak valid.');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::beginTransaction();
        try {
            // Simpan password user yang sudah ada di sekolah ini sebagai fallback
            $existingPasswords = User::where('school_id', $schoolId)
                ->whereNotNull('password_hash')
                ->pluck('password_hash', 'username')
                ->toArray();

            // Index data user siswa dari file backup agar password & username siswa tetap terjaga
            $backupStudentUsersById = [];
            foreach ($data['users'] ?? [] as $uItem) {
                if (($uItem['role'] ?? '') === 'student' && !empty($uItem['id'])) {
                    $backupStudentUsersById[$uItem['id']] = $uItem;
                }
            }

            // =========================================================================
            // STEP 1: WIPE CURRENT SCHOOL DATA (URUTAN AMAN DEPENDENSI)
            // =========================================================================
            $oldSiswaIds = Siswa::where('school_id', $schoolId)->pluck('id');
            $oldGuruIds = Guru::where('school_id', $schoolId)->pluck('id');
            $oldUserIds = User::where('school_id', $schoolId)->pluck('id');

            if (Schema::hasTable('user_face_data')) {
                UserFaceData::whereIn('siswa_id', $oldSiswaIds)
                    ->orWhereIn('guru_id', $oldGuruIds)
                    ->orWhereIn('user_id', $oldUserIds)
                    ->delete();
            }

            if (Schema::hasTable('kegiatans')) {
                $oldKegiatanIds = Kegiatan::where('school_id', $schoolId)->pluck('id');
                if (Schema::hasTable('kegiatan_attendances')) {
                    KegiatanAttendance::where('school_id', $schoolId)->orWhereIn('kegiatan_id', $oldKegiatanIds)->delete();
                }
                if (Schema::hasTable('kegiatan_sessions')) {
                    KegiatanSession::where('school_id', $schoolId)->orWhereIn('kegiatan_id', $oldKegiatanIds)->delete();
                }
                if (Schema::hasTable('kegiatan_siswa') && $oldKegiatanIds->isNotEmpty()) {
                    DB::table('kegiatan_siswa')->whereIn('kegiatan_id', $oldKegiatanIds)->delete();
                }
                if (Schema::hasTable('kegiatan_kelas') && $oldKegiatanIds->isNotEmpty()) {
                    DB::table('kegiatan_kelas')->whereIn('kegiatan_id', $oldKegiatanIds)->delete();
                }
                Kegiatan::where('school_id', $schoolId)->delete();
            }

            if (Schema::hasTable('absensi_pelajaran')) {
                AbsensiPelajaran::where('school_id', $schoolId)->delete();
            }
            if (Schema::hasTable('jurnal_pelajaran')) {
                JurnalPelajaran::where('school_id', $schoolId)->delete();
            }
            if (Schema::hasTable('student_leaves')) {
                StudentLeave::where('school_id', $schoolId)->orWhereIn('student_id', $oldSiswaIds)->delete();
            }

            if (Schema::hasTable('gate_cards')) {
                $oldGateCardIds = GateCard::where('school_id', $schoolId)->pluck('id');
                if (Schema::hasTable('gate_card_fingerprints') && $oldGateCardIds->isNotEmpty()) {
                    GateCardFingerprint::whereIn('gate_card_id', $oldGateCardIds)->delete();
                }
                GateCard::where('school_id', $schoolId)->delete();
            }

            if (Schema::hasTable('guru_shift_assignments')) {
                GuruShiftAssignment::where('school_id', $schoolId)->orWhereIn('guru_id', $oldGuruIds)->delete();
            }
            if (Schema::hasTable('shifts')) {
                Shift::where('school_id', $schoolId)->delete();
            }
            if (Schema::hasTable('notification_templates')) {
                NotificationTemplate::where('school_id', $schoolId)->delete();
            }

            Attendance::whereIn('student_id', $oldSiswaIds)->delete();
            SiswaFingerprint::whereIn('student_id', $oldSiswaIds)->delete();

            AbsensiGuru::where('school_id', $schoolId)->delete();
            TeacherCheckoutSession::whereIn('teacher_id', $oldGuruIds)->delete();
            GuruFingerprint::whereIn('guru_id', $oldGuruIds)->delete();

            JadwalPelajaran::where('school_id', $schoolId)->delete();
            if (Schema::hasTable('jam_pelajaran')) {
                JamPelajaran::where('school_id', $schoolId)->delete();
            }
            Mapel::where('school_id', $schoolId)->delete();
            Jadwal::where('school_id', $schoolId)->delete();

            Siswa::where('school_id', $schoolId)->delete();
            Guru::where('school_id', $schoolId)->delete();
            Kelas::where('school_id', $schoolId)->delete();
            Jurusan::where('school_id', $schoolId)->delete();
            Device::where('school_id', $schoolId)->delete();
            Setting::where('school_id', $schoolId)->delete();
            User::where('school_id', $schoolId)->where('id', '!=', $user->id)->delete();

            // =========================================================================
            // STEP 2: RESTORE & REMAP SELURUH FOREIGN KEY ID
            // =========================================================================
            $mapUsers           = [];
            $mapShifts          = [];
            $mapJurusan         = [];
            $mapKelas           = [];
            $mapDevices         = [];
            $mapGuru            = [];
            $mapSiswa           = [];
            $mapGateCards       = [];
            $mapJamPelajaran    = [];
            $mapMapel           = [];
            $mapJadwalPelajaran = [];
            $mapJurnalPelajaran = [];
            $mapKegiatan        = [];

            // 1. Settings & Notification Templates
            foreach ($data['settings'] ?? [] as $item) {
                $item = $this->cleanAttributes($item);
                $item['school_id'] = $schoolId;
                Setting::create($item);
            }

            if (Schema::hasTable('notification_templates') && !empty($data['notification_templates'])) {
                foreach ($data['notification_templates'] as $item) {
                    $item = $this->cleanAttributes($item);
                    $item['school_id'] = $schoolId;
                    NotificationTemplate::create($item);
                }
            }

            // 2. Users (Admin/Teacher/Staff) — akun student dipulihkan bersama tabel Siswa dengan menjaga password aslinya
            foreach ($data['users'] ?? [] as $item) {
                if (($item['role'] ?? '') === 'student') {
                    continue;
                }

                $oldId = $item['id'];

                if ($item['id'] == $user->id || $item['username'] === $user->username || (!empty($item['email']) && $item['email'] === $user->email)) {
                    $mapUsers[$oldId] = $user->id;
                    continue;
                }

                $item = $this->cleanAttributes($item);
                $item['school_id'] = $schoolId;

                if (empty($item['password_hash'])) {
                    if (!empty($existingPasswords[$item['username']])) {
                        $item['password_hash'] = $existingPasswords[$item['username']];
                    } else {
                        $defaultPass = !empty($item['username']) ? $item['username'] : 'admin123';
                        $item['password_hash'] = Hash::make($defaultPass);
                    }
                }

                $existingUser = User::where('username', $item['username'])->first();
                if ($existingUser) {
                    if (!empty($item['email']) && $existingUser->email === $item['email']) {
                        $mapUsers[$oldId] = $existingUser->id;
                        continue;
                    }
                    $suffix = substr(uniqid(), -5);
                    $item['username'] = $item['username'] . '_r' . $suffix;
                    $item['email']    = $item['username'] . '@restored.local';
                } elseif (!empty($item['email']) && User::where('email', $item['email'])->exists()) {
                    $suffix = substr(uniqid(), -5);
                    $item['email'] = 'user_' . $suffix . '@restored.local';
                }

                $newUser = User::create($item);
                $mapUsers[$oldId] = $newUser->id;
            }

            // 3. Shifts (Master Shift Kerja — dipulihkan sebelum Guru & AbsensiGuru)
            if (Schema::hasTable('shifts') && !empty($data['shifts'])) {
                foreach ($data['shifts'] as $item) {
                    $oldId = $item['id'];
                    $item = $this->cleanAttributes($item);
                    $item['school_id'] = $schoolId;
                    $newShift = Shift::create($item);
                    $mapShifts[$oldId] = $newShift->id;
                }
            }

            // 4. Jurusan
            foreach ($data['jurusan'] ?? [] as $item) {
                $oldId = $item['id'];
                $item = $this->cleanAttributes($item);
                $item['school_id'] = $schoolId;
                $newJurusan = Jurusan::create($item);
                $mapJurusan[$oldId] = $newJurusan->id;
            }

            // 5. Kelas
            $rawWaliKelas = [];
            foreach ($data['kelas'] ?? [] as $item) {
                $oldId = $item['id'];
                $rawWaliKelas[$oldId] = [
                    'wali_kelas_id'   => $item['wali_kelas_id'] ?? null,
                    'wali_kelas_2_id' => $item['wali_kelas_2_id'] ?? null,
                ];

                $item = $this->cleanAttributes($item);
                $item['school_id'] = $schoolId;
                $item['wali_kelas_id']   = null;
                $item['wali_kelas_2_id'] = null;

                if (!empty($item['jurusan_id'])) {
                    $item['jurusan_id'] = $mapJurusan[$item['jurusan_id']] ?? null;
                }

                $newKelas = Kelas::create($item);
                $mapKelas[$oldId] = $newKelas->id;
            }

            // 6. Guru (dengan remap user_id & default_shift_id)
            foreach ($data['guru'] ?? [] as $item) {
                $oldId = $item['id'];
                $oldUserId = $item['user_id'] ?? null;
                $oldShiftId = $item['default_shift_id'] ?? null;

                $item = $this->cleanAttributes($item);
                $item['school_id'] = $schoolId;
                $item['user_id'] = $mapUsers[$oldUserId] ?? null;
                if (array_key_exists('default_shift_id', $item) || $oldShiftId !== null) {
                    $item['default_shift_id'] = $mapShifts[$oldShiftId] ?? null;
                }

                $newGuru = Guru::create($item);
                $mapGuru[$oldId] = $newGuru->id;
            }

            // Re-sync wali_kelas & wali_kelas_2 di Kelas
            foreach ($rawWaliKelas as $oldKelasId => $waliData) {
                if (isset($mapKelas[$oldKelasId])) {
                    $newKelasId = $mapKelas[$oldKelasId];
                    $updateData = [];
                    if (!empty($waliData['wali_kelas_id']) && isset($mapGuru[$waliData['wali_kelas_id']])) {
                        $updateData['wali_kelas_id'] = $mapGuru[$waliData['wali_kelas_id']];
                    }
                    if (!empty($waliData['wali_kelas_2_id']) && isset($mapGuru[$waliData['wali_kelas_2_id']])) {
                        $updateData['wali_kelas_2_id'] = $mapGuru[$waliData['wali_kelas_2_id']];
                    }
                    if (!empty($updateData)) {
                        Kelas::where('id', $newKelasId)->update($updateData);
                    }
                }
            }

            // 7. Guru Shift Assignments
            if (Schema::hasTable('guru_shift_assignments') && !empty($data['guru_shift_assignments'])) {
                foreach ($data['guru_shift_assignments'] as $item) {
                    $oldGuruId = $item['guru_id'] ?? null;
                    $oldShiftId = $item['shift_id'] ?? null;
                    if (!isset($mapGuru[$oldGuruId]) || !isset($mapShifts[$oldShiftId])) {
                        continue;
                    }
                    $item = $this->cleanAttributes($item);
                    $item['school_id'] = $schoolId;
                    $item['guru_id']   = $mapGuru[$oldGuruId];
                    $item['shift_id']  = $mapShifts[$oldShiftId];
                    GuruShiftAssignment::create($item);
                }
            }

            // 8. Devices
            foreach ($data['devices'] ?? [] as $item) {
                $oldId = $item['id'];
                $item = $this->cleanAttributes($item);
                $item['school_id'] = $schoolId;

                if (!empty($item['api_key']) && Device::where('api_key', $item['api_key'])->exists()) {
                    $item['api_key'] = $item['api_key'] . '_r' . substr(uniqid(), -5);
                }
                $newDevice = Device::create($item);
                $mapDevices[$oldId] = $newDevice->id;
            }

            // 9. Guru Fingerprints
            foreach ($data['guru_fingerprints'] ?? [] as $item) {
                $oldGuruId = $item['guru_id'] ?? null;
                $oldDevId = $item['device_id'] ?? null;
                if (isset($mapGuru[$oldGuruId]) && isset($mapDevices[$oldDevId])) {
                    $item = $this->cleanAttributes($item);
                    $item['guru_id']   = $mapGuru[$oldGuruId];
                    $item['device_id'] = $mapDevices[$oldDevId];
                    GuruFingerprint::create($item);
                }
            }

            // 10. Siswa (Pertahankan password_hash asli dari backup / DB sebelumnya)
            foreach ($data['siswa'] ?? [] as $item) {
                $oldId = $item['id'];
                $oldStudentUserId = $item['user_id'] ?? null;
                $backupUser = $oldStudentUserId ? ($backupStudentUsersById[$oldStudentUserId] ?? null) : null;

                $item = $this->cleanAttributes($item);
                $item['school_id'] = $schoolId;
                $item['kelas_id'] = isset($item['kelas_id']) ? ($mapKelas[$item['kelas_id']] ?? null) : null;

                $preferredUsername = $backupUser['username'] ?? ($schoolId . $item['nis']);
                $existingStuUser = User::where('username', $preferredUsername)->first();
                if ($existingStuUser && $existingStuUser->school_id != $schoolId) {
                    $preferredUsername = $schoolId . '_' . $item['nis'];
                }

                $baseEmail = $backupUser['email'] ?? ($schoolId . '_' . $item['nis'] . '@siswa.local');
                $email = User::where('email', $baseEmail)->where('username', '!=', $preferredUsername)->exists()
                    ? $schoolId . '_' . $item['nis'] . '_' . substr(uniqid(), -5) . '@siswa.local'
                    : $baseEmail;

                $passwordHash = $backupUser['password_hash']
                    ?? ($existingPasswords[$preferredUsername] ?? Hash::make($item['nis']));

                $userSiswa = User::updateOrCreate(
                    ['username' => $preferredUsername],
                    [
                        'full_name'     => $item['nama'],
                        'email'         => $email,
                        'password_hash' => $passwordHash,
                        'role'          => 'student',
                        'school_id'     => $schoolId,
                    ]
                );

                if ($oldStudentUserId) {
                    $mapUsers[$oldStudentUserId] = $userSiswa->id;
                }
                $item['user_id'] = $userSiswa->id;

                $newSiswa = Siswa::create($item);
                $mapSiswa[$oldId] = $newSiswa->id;
            }

            // 11. Siswa Fingerprints
            foreach ($data['siswa_fingerprints'] ?? [] as $item) {
                $oldSiswaId = $item['student_id'] ?? null;
                $oldDevId = $item['device_id'] ?? null;
                if (isset($mapSiswa[$oldSiswaId]) && isset($mapDevices[$oldDevId])) {
                    $item = $this->cleanAttributes($item);
                    $item['student_id'] = $mapSiswa[$oldSiswaId];
                    $item['device_id']  = $mapDevices[$oldDevId];
                    SiswaFingerprint::create($item);
                }
            }

            // 12. User Face Data (Data Wajah 512-d & 128-d Siswa dan Guru)
            if (Schema::hasTable('user_face_data') && !empty($data['user_face_data'])) {
                foreach ($data['user_face_data'] as $item) {
                    $oldSiswaId = $item['siswa_id'] ?? null;
                    $oldGuruId  = $item['guru_id'] ?? null;
                    $oldUserId  = $item['user_id'] ?? null;

                    $newSiswaId = $oldSiswaId ? ($mapSiswa[$oldSiswaId] ?? null) : null;
                    $newGuruId  = $oldGuruId ? ($mapGuru[$oldGuruId] ?? null) : null;
                    $newUserId  = $oldUserId ? ($mapUsers[$oldUserId] ?? null) : null;

                    if (!$newUserId && $newSiswaId) {
                        $newUserId = Siswa::where('id', $newSiswaId)->value('user_id');
                    }
                    if (!$newUserId && $newGuruId) {
                        $newUserId = Guru::where('id', $newGuruId)->value('user_id');
                    }

                    if (!$newSiswaId && !$newGuruId && !$newUserId) {
                        continue;
                    }

                    $item = $this->cleanAttributes($item);
                    $item['user_id']  = $newUserId;
                    $item['siswa_id'] = $newSiswaId;
                    $item['guru_id']  = $newGuruId;

                    if (is_array($item['face_embedding'] ?? null)) {
                        $item['face_embedding'] = json_encode($item['face_embedding']);
                    }

                    UserFaceData::create($item);
                }
            }

            // 13. Gate Cards & Gate Card Fingerprints
            if (Schema::hasTable('gate_cards') && !empty($data['gate_cards'])) {
                foreach ($data['gate_cards'] as $item) {
                    $oldId = $item['id'];
                    $oldGuruId = $item['guru_id'] ?? null;
                    $item = $this->cleanAttributes($item);
                    $item['school_id'] = $schoolId;
                    $item['guru_id']   = $oldGuruId ? ($mapGuru[$oldGuruId] ?? null) : null;
                    $newCard = GateCard::create($item);
                    $mapGateCards[$oldId] = $newCard->id;
                }
            }

            if (Schema::hasTable('gate_card_fingerprints') && !empty($data['gate_card_fingerprints'])) {
                foreach ($data['gate_card_fingerprints'] as $item) {
                    $oldCardId = $item['gate_card_id'] ?? null;
                    $oldDevId  = $item['device_id'] ?? null;
                    if (isset($mapGateCards[$oldCardId]) && isset($mapDevices[$oldDevId])) {
                        $item = $this->cleanAttributes($item);
                        $item['gate_card_id'] = $mapGateCards[$oldCardId];
                        $item['device_id']    = $mapDevices[$oldDevId];
                        GateCardFingerprint::create($item);
                    }
                }
            }

            // 14. Jadwal Harian, Jam Pelajaran, & Mapel
            foreach ($data['jadwal'] ?? [] as $item) {
                $item = $this->cleanAttributes($item);
                $item['school_id'] = $schoolId;
                Jadwal::create($item);
            }

            if (Schema::hasTable('jam_pelajaran') && !empty($data['jam_pelajaran'])) {
                foreach ($data['jam_pelajaran'] as $item) {
                    $oldId = $item['id'];
                    $item = $this->cleanAttributes($item);
                    $item['school_id'] = $schoolId;
                    $newJam = JamPelajaran::create($item);
                    $mapJamPelajaran[$oldId] = $newJam->id;
                }
            }

            foreach ($data['mapel'] ?? [] as $item) {
                $oldId = $item['id'];
                $item = $this->cleanAttributes($item);
                $item['school_id'] = $schoolId;
                $newMapel = Mapel::create($item);
                $mapMapel[$oldId] = $newMapel->id;
            }

            // 15. Jadwal Pelajaran (Remap kelas_id, guru_id, mapel_id, jam_pelajaran_id, jam_pelajaran_id_akhir)
            foreach ($data['jadwal_pelajaran'] ?? [] as $item) {
                $oldId = $item['id'];
                $item = $this->cleanAttributes($item);
                $item['school_id'] = $schoolId;

                if (!empty($item['kelas_id'])) {
                    $item['kelas_id'] = $mapKelas[$item['kelas_id']] ?? null;
                }
                if (!empty($item['guru_id'])) {
                    $item['guru_id'] = $mapGuru[$item['guru_id']] ?? null;
                }
                if (!empty($item['mapel_id'])) {
                    $item['mapel_id'] = $mapMapel[$item['mapel_id']] ?? null;
                }
                if (!empty($item['jam_pelajaran_id'])) {
                    $item['jam_pelajaran_id'] = $mapJamPelajaran[$item['jam_pelajaran_id']] ?? null;
                }
                if (!empty($item['jam_pelajaran_id_akhir'])) {
                    $item['jam_pelajaran_id_akhir'] = $mapJamPelajaran[$item['jam_pelajaran_id_akhir']] ?? null;
                }

                if (!$item['kelas_id'] || !$item['guru_id'] || !$item['mapel_id']) {
                    continue;
                }

                $newJP = JadwalPelajaran::create($item);
                $mapJadwalPelajaran[$oldId] = $newJP->id;
            }

            // 16. Jurnal Pelajaran & Absensi Pelajaran
            if (Schema::hasTable('jurnal_pelajaran') && !empty($data['jurnal_pelajaran'])) {
                foreach ($data['jurnal_pelajaran'] as $item) {
                    $oldId = $item['id'];
                    $oldJpId = $item['jadwal_pelajaran_id'] ?? null;
                    $oldGuruId = $item['guru_id'] ?? null;

                    if (!isset($mapJadwalPelajaran[$oldJpId]) && !isset($mapGuru[$oldGuruId])) {
                        continue;
                    }

                    $item = $this->cleanAttributes($item);
                    $item['school_id']           = $schoolId;
                    $item['jadwal_pelajaran_id'] = $mapJadwalPelajaran[$oldJpId] ?? null;
                    $item['guru_id']             = $mapGuru[$oldGuruId] ?? null;

                    $newJurnal = JurnalPelajaran::create($item);
                    $mapJurnalPelajaran[$oldId] = $newJurnal->id;
                }
            }

            if (Schema::hasTable('absensi_pelajaran') && !empty($data['absensi_pelajaran'])) {
                foreach ($data['absensi_pelajaran'] as $item) {
                    $oldStuId = $item['student_id'] ?? null;
                    if (!isset($mapSiswa[$oldStuId])) {
                        continue;
                    }

                    $oldJpId     = $item['jadwal_pelajaran_id'] ?? null;
                    $oldJurnalId = $item['jurnal_pelajaran_id'] ?? null;
                    $oldGuruId   = $item['guru_id'] ?? null;

                    $item = $this->cleanAttributes($item);
                    $item['school_id']           = $schoolId;
                    $item['student_id']          = $mapSiswa[$oldStuId];
                    $item['jadwal_pelajaran_id'] = $mapJadwalPelajaran[$oldJpId] ?? null;
                    $item['jurnal_pelajaran_id'] = $mapJurnalPelajaran[$oldJurnalId] ?? null;
                    $item['guru_id']             = $mapGuru[$oldGuruId] ?? null;

                    AbsensiPelajaran::create($item);
                }
            }

            // 17. Kegiatan, Pivot Kelas/Siswa, Sessions, & Attendances
            if (Schema::hasTable('kegiatans') && !empty($data['kegiatans'])) {
                foreach ($data['kegiatans'] as $item) {
                    $oldId = $item['id'];
                    $oldPembinaId = $item['pembina_id'] ?? null;
                    $oldCreatedBy = $item['created_by'] ?? null;

                    $item = $this->cleanAttributes($item);
                    $item['school_id']  = $schoolId;
                    $item['pembina_id'] = $oldPembinaId ? ($mapGuru[$oldPembinaId] ?? null) : null;
                    $item['created_by'] = $oldCreatedBy ? ($mapUsers[$oldCreatedBy] ?? $user->id) : $user->id;

                    $newKeg = Kegiatan::create($item);
                    $mapKegiatan[$oldId] = $newKeg->id;
                }
            }

            if (Schema::hasTable('kegiatan_kelas') && !empty($data['kegiatan_kelas'])) {
                foreach ($data['kegiatan_kelas'] as $item) {
                    $oldKegId = $item['kegiatan_id'] ?? null;
                    $oldKlsId = $item['kelas_id'] ?? null;
                    if (isset($mapKegiatan[$oldKegId]) && isset($mapKelas[$oldKlsId])) {
                        DB::table('kegiatan_kelas')->insertOrIgnore([
                            'kegiatan_id' => $mapKegiatan[$oldKegId],
                            'kelas_id'    => $mapKelas[$oldKlsId],
                            'created_at'  => $item['created_at'] ?? now(),
                            'updated_at'  => $item['updated_at'] ?? now(),
                        ]);
                    }
                }
            }

            if (Schema::hasTable('kegiatan_siswa') && !empty($data['kegiatan_siswa'])) {
                foreach ($data['kegiatan_siswa'] as $item) {
                    $oldKegId = $item['kegiatan_id'] ?? null;
                    $oldStuId = $item['student_id'] ?? null;
                    if (isset($mapKegiatan[$oldKegId]) && isset($mapSiswa[$oldStuId])) {
                        DB::table('kegiatan_siswa')->insertOrIgnore([
                            'kegiatan_id' => $mapKegiatan[$oldKegId],
                            'student_id'  => $mapSiswa[$oldStuId],
                            'created_at'  => $item['created_at'] ?? now(),
                            'updated_at'  => $item['updated_at'] ?? now(),
                        ]);
                    }
                }
            }

            if (Schema::hasTable('kegiatan_sessions') && !empty($data['kegiatan_sessions'])) {
                foreach ($data['kegiatan_sessions'] as $item) {
                    $oldKegId = $item['kegiatan_id'] ?? null;
                    if (!isset($mapKegiatan[$oldKegId])) {
                        continue;
                    }
                    $item = $this->cleanAttributes($item);
                    $item['school_id']   = $schoolId;
                    $item['kegiatan_id'] = $mapKegiatan[$oldKegId];
                    KegiatanSession::create($item);
                }
            }

            if (Schema::hasTable('kegiatan_attendances') && !empty($data['kegiatan_attendances'])) {
                foreach ($data['kegiatan_attendances'] as $item) {
                    $oldKegId = $item['kegiatan_id'] ?? null;
                    $oldStuId = $item['student_id'] ?? null;
                    if (!isset($mapKegiatan[$oldKegId]) || !isset($mapSiswa[$oldStuId])) {
                        continue;
                    }
                    $item = $this->cleanAttributes($item);
                    $item['school_id']   = $schoolId;
                    $item['kegiatan_id'] = $mapKegiatan[$oldKegId];
                    $item['student_id']  = $mapSiswa[$oldStuId];
                    KegiatanAttendance::create($item);
                }
            }

            // 18. Student Leaves (Izin / Sakit / Dispensasi Siswa)
            if (Schema::hasTable('student_leaves') && !empty($data['student_leaves'])) {
                foreach ($data['student_leaves'] as $item) {
                    $oldStuId = $item['student_id'] ?? null;
                    if (!isset($mapSiswa[$oldStuId])) {
                        continue;
                    }
                    $oldApprover = $item['approved_by'] ?? null;
                    $item = $this->cleanAttributes($item);
                    $item['school_id']   = $schoolId;
                    $item['student_id']  = $mapSiswa[$oldStuId];
                    $item['approved_by'] = $oldApprover ? ($mapUsers[$oldApprover] ?? null) : null;

                    if (!empty($item['code']) && StudentLeave::where('code', $item['code'])->exists()) {
                        $item['code'] = StudentLeave::generateCode();
                    }

                    StudentLeave::create($item);
                }
            }

            // 19. Attendance Siswa
            foreach ($data['attendance'] ?? [] as $item) {
                $oldStuId = $item['student_id'] ?? null;
                if (isset($mapSiswa[$oldStuId])) {
                    $item = $this->cleanAttributes($item);
                    $item['student_id'] = $mapSiswa[$oldStuId];
                    Attendance::create($item);
                }
            }

            // 20. Absensi Guru (Remap guru_id, jadwal_pelajaran_id, & shift_id)
            foreach ($data['absensi_guru'] ?? [] as $item) {
                $oldGuruId = $item['guru_id'] ?? null;
                if (!isset($mapGuru[$oldGuruId])) {
                    continue;
                }
                $oldJpId    = $item['jadwal_pelajaran_id'] ?? null;
                $oldShiftId = $item['shift_id'] ?? null;

                $item = $this->cleanAttributes($item);
                $item['school_id'] = $schoolId;
                $item['guru_id']   = $mapGuru[$oldGuruId];

                if ($oldJpId !== null) {
                    $item['jadwal_pelajaran_id'] = $mapJadwalPelajaran[$oldJpId] ?? null;
                }
                if ($oldShiftId !== null) {
                    $item['shift_id'] = $mapShifts[$oldShiftId] ?? null;
                }

                AbsensiGuru::create($item);
            }

            // 21. Teacher Checkout Sessions
            foreach ($data['teacher_checkout_sessions'] ?? [] as $item) {
                $oldTeacherId = $item['teacher_id'] ?? null;
                if (!isset($mapGuru[$oldTeacherId])) {
                    continue;
                }
                $item = $this->cleanAttributes($item);
                $item['teacher_id'] = $mapGuru[$oldTeacherId];
                TeacherCheckoutSession::create($item);
            }

            DB::commit();
            return back()->with('success', 'Data sekolah lengkap (v2.0) berhasil direstore/diimport ulang.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Restore Failed: " . $e->getMessage());
            return back()->with('error', 'Restore Gagal: ' . $e->getMessage());
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
