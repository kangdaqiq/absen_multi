<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\School;
use App\Models\User;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Attendance;
use App\Models\AbsensiGuru;
use Carbon\Carbon;

class DummyAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Get or Create Default School
        $school = School::firstOrCreate(
            ['code' => 'DEFAULT'],
            [
                'name' => 'SMK Negeri 1 Teknologi',
                'address' => 'Jl. Pendidikan No. 123, Jakarta',
                'phone' => '081234567890',
                'email' => 'info@smkn1tekno.sch.id',
                'is_active' => true,
                'wa_enabled' => true,
                'bot_enabled' => true,
            ]
        );

        // 2. Jurusan
        $jurusanRpl = Jurusan::firstOrCreate(
            ['school_id' => $school->id, 'nama_jurusan' => 'Rekayasa Perangkat Lunak']
        );

        $jurusanTkj = Jurusan::firstOrCreate(
            ['school_id' => $school->id, 'nama_jurusan' => 'Teknik Komputer dan Jaringan']
        );

        // 3. Guru & User Accounts
        $guruData = [
            [
                'nama' => 'Budi Santoso, S.Kom',
                'nip' => '198501152010011001',
                'username' => 'guru.budi',
                'email' => 'budi.santoso@school.com',
                'no_wa' => '081298765431',
                'uid_rfid' => '1A2B3C4D',
            ],
            [
                'nama' => 'Siti Aminah, M.Pd',
                'nip' => '198803202014022002',
                'username' => 'guru.siti',
                'email' => 'siti.aminah@school.com',
                'no_wa' => '081298765432',
                'uid_rfid' => '2B3C4D5E',
            ],
            [
                'nama' => 'Ahmad Fauzi, S.T',
                'nip' => '199005122019031003',
                'username' => 'guru.fauzi',
                'email' => 'ahmad.fauzi@school.com',
                'no_wa' => '081298765433',
                'uid_rfid' => '3C4D5E6F',
            ],
            [
                'nama' => 'Dewi Lestari, S.Pd',
                'nip' => '199207082020012004',
                'username' => 'guru.dewi',
                'email' => 'dewi.lestari@school.com',
                'no_wa' => '081298765434',
                'uid_rfid' => '4D5E6F7A',
            ],
        ];

        $createdGurus = [];

        foreach ($guruData as $g) {
            // Create user login account
            $user = User::updateOrCreate(
                ['email' => $g['email']],
                [
                    'full_name' => $g['nama'],
                    'username' => $g['username'],
                    'password_hash' => Hash::make('password'),
                    'role' => 'teacher',
                    'school_id' => $school->id,
                ]
            );

            // Create Guru profile
            $guru = Guru::updateOrCreate(
                ['nip' => $g['nip']],
                [
                    'nama' => $g['nama'],
                    'school_id' => $school->id,
                    'user_id' => $user->id,
                    'no_wa' => $g['no_wa'],
                    'uid_rfid' => $g['uid_rfid'],
                    'enroll_status' => true,
                    'bot_access' => true,
                ]
            );

            $createdGurus[] = $guru;
        }

        // 4. Kelas
        $kelas1 = Kelas::firstOrCreate(
            ['school_id' => $school->id, 'nama_kelas' => 'X RPL 1'],
            [
                'jurusan_id' => $jurusanRpl->id,
                'wali_kelas_id' => $createdGurus[0]->id,
                'is_active_attendance' => true,
                'is_active_report' => true,
            ]
        );

        $kelas2 = Kelas::firstOrCreate(
            ['school_id' => $school->id, 'nama_kelas' => 'X TKJ 1'],
            [
                'jurusan_id' => $jurusanTkj->id,
                'wali_kelas_id' => $createdGurus[1]->id,
                'is_active_attendance' => true,
                'is_active_report' => true,
            ]
        );

        $kelas3 = Kelas::firstOrCreate(
            ['school_id' => $school->id, 'nama_kelas' => 'XI RPL 1'],
            [
                'jurusan_id' => $jurusanRpl->id,
                'wali_kelas_id' => $createdGurus[2]->id,
                'is_active_attendance' => true,
                'is_active_report' => true,
            ]
        );

        // 5. Siswa
        $siswaData = [
            // Kelas X RPL 1
            ['nama' => 'Aditya Pratama', 'nis' => '20261001', 'kelas_id' => $kelas1->id, 'uid_rfid' => '04A1B2C1'],
            ['nama' => 'Anisa Rahmawati', 'nis' => '20261002', 'kelas_id' => $kelas1->id, 'uid_rfid' => '04A1B2C2'],
            ['nama' => 'Bagas Saputra', 'nis' => '20261003', 'kelas_id' => $kelas1->id, 'uid_rfid' => '04A1B2C3'],
            ['nama' => 'Cantika Putri', 'nis' => '20261004', 'kelas_id' => $kelas1->id, 'uid_rfid' => '04A1B2C4'],
            ['nama' => 'Dimas Arya', 'nis' => '20261005', 'kelas_id' => $kelas1->id, 'uid_rfid' => '04A1B2C5'],
            ['nama' => 'Eka Nurhaliza', 'nis' => '20261006', 'kelas_id' => $kelas1->id, 'uid_rfid' => '04A1B2C6'],

            // Kelas X TKJ 1
            ['nama' => 'Fajar Hidayat', 'nis' => '20262001', 'kelas_id' => $kelas2->id, 'uid_rfid' => '04B2C3D1'],
            ['nama' => 'Gita Permata', 'nis' => '20262002', 'kelas_id' => $kelas2->id, 'uid_rfid' => '04B2C3D2'],
            ['nama' => 'Hendra Setiawan', 'nis' => '20262003', 'kelas_id' => $kelas2->id, 'uid_rfid' => '04B2C3D3'],
            ['nama' => 'Indah Kusuma', 'nis' => '20262004', 'kelas_id' => $kelas2->id, 'uid_rfid' => '04B2C3D4'],
            ['nama' => 'Joko Nugroho', 'nis' => '20262005', 'kelas_id' => $kelas2->id, 'uid_rfid' => '04B2C3D5'],

            // Kelas XI RPL 1
            ['nama' => 'Kevin Sanjaya', 'nis' => '20251001', 'kelas_id' => $kelas3->id, 'uid_rfid' => '04C3D4E1'],
            ['nama' => 'Lestari Wulandari', 'nis' => '20251002', 'kelas_id' => $kelas3->id, 'uid_rfid' => '04C3D4E2'],
            ['nama' => 'Muhammad Rizky', 'nis' => '20251003', 'kelas_id' => $kelas3->id, 'uid_rfid' => '04C3D4E3'],
            ['nama' => 'Nadia Syahira', 'nis' => '20251004', 'kelas_id' => $kelas3->id, 'uid_rfid' => '04C3D4E4'],
            ['nama' => 'Oki Prasetyo', 'nis' => '20251005', 'kelas_id' => $kelas3->id, 'uid_rfid' => '04C3D4E5'],
        ];

        $createdSiswa = [];
        foreach ($siswaData as $s) {
            $siswa = Siswa::updateOrCreate(
                ['nis' => $s['nis']],
                [
                    'nama' => $s['nama'],
                    'kelas_id' => $s['kelas_id'],
                    'school_id' => $school->id,
                    'uid_rfid' => $s['uid_rfid'],
                    'no_wa' => '0857' . rand(10000000, 99999999),
                    'wa_ortu' => '0812' . rand(10000000, 99999999),
                    'enroll_status' => true,
                    'alamat' => 'Jakarta',
                ]
            );
            $createdSiswa[] = $siswa;
        }

        // 6. Generate 1 Month of Attendance Records (Past 30 Days)
        $today = Carbon::today();
        $startDate = $today->copy()->subDays(30);

        for ($date = $startDate->copy(); $date->lte($today); $date->addDay()) {
            // Skip Sunday (dayOfWeek === 0)
            if ($date->isSunday()) {
                continue;
            }

            $dateStr = $date->format('Y-m-d');

            // Generate Siswa Attendance
            foreach ($createdSiswa as $siswa) {
                // Randomize status based on probability
                $rand = rand(1, 100);

                if ($rand <= 78) {
                    // Hadir Tepat Waktu (06:30 - 06:58)
                    $jamMasuk = sprintf('06:%02d:%02d', rand(30, 58), rand(0, 59));
                    $jamPulang = sprintf('14:%02d:%02d', rand(0, 30), rand(0, 59));
                    $status = 'H';
                    $keterangan = 'Hadir Tepat Waktu';
                } elseif ($rand <= 90) {
                    // Terlambat (07:05 - 07:35)
                    $jamMasuk = sprintf('07:%02d:%02d', rand(5, 35), rand(0, 59));
                    $jamPulang = sprintf('14:%02d:%02d', rand(0, 30), rand(0, 59));
                    $status = 'T';
                    $keterangan = 'Terlambat ' . rand(5, 35) . ' menit';
                } elseif ($rand <= 95) {
                    // Izin
                    $jamMasuk = null;
                    $jamPulang = null;
                    $status = 'I';
                    $keterangan = 'Izin urusan keluarga / kegiatan';
                } elseif ($rand <= 98) {
                    // Sakit
                    $jamMasuk = null;
                    $jamPulang = null;
                    $status = 'S';
                    $keterangan = 'Sakit (surat dokter)';
                } else {
                    // Alpha
                    $jamMasuk = null;
                    $jamPulang = null;
                    $status = 'A';
                    $keterangan = 'Tanpa Keterangan (Alpha)';
                }

                Attendance::updateOrCreate(
                    [
                        'student_id' => $siswa->id,
                        'tanggal' => $dateStr,
                    ],
                    [
                        'jam_masuk' => $jamMasuk,
                        'jam_pulang' => $jamPulang,
                        'status' => $status,
                        'keterangan' => $keterangan,
                        'created_at' => $date->copy()->setTime(7, 0, 0),
                        'updated_at' => $date->copy()->setTime(14, 0, 0),
                    ]
                );
            }

            // Generate Guru Attendance (for Mobile App & Guru Attendance)
            foreach ($createdGurus as $guru) {
                $rand = rand(1, 100);

                if ($rand <= 85) {
                    $jamMasuk = sprintf('06:%02d:%02d', rand(35, 55), rand(0, 59));
                    $jamPulang = sprintf('15:%02d:%02d', rand(0, 45), rand(0, 59));
                    $status = 'Hadir';
                    $statusKehadiran = 'Hadir';
                    $keterangan = 'Hadir via Mobile App / Fingerprint';
                } elseif ($rand <= 93) {
                    $jamMasuk = sprintf('07:%02d:%02d', rand(5, 25), rand(0, 59));
                    $jamPulang = sprintf('15:%02d:%02d', rand(0, 45), rand(0, 59));
                    $status = 'Terlambat';
                    $statusKehadiran = 'Terlambat';
                    $keterangan = 'Terlambat ' . rand(5, 25) . ' menit';
                } elseif ($rand <= 97) {
                    $jamMasuk = null;
                    $jamPulang = null;
                    $status = 'Izin';
                    $statusKehadiran = 'Izin';
                    $keterangan = 'Izin dinas luar';
                } else {
                    $jamMasuk = null;
                    $jamPulang = null;
                    $status = 'Sakit';
                    $statusKehadiran = 'Sakit';
                    $keterangan = 'Sakit flu';
                }

                AbsensiGuru::updateOrCreate(
                    [
                        'guru_id' => $guru->id,
                        'tanggal' => $dateStr,
                    ],
                    [
                        'school_id' => $school->id,
                        'waktu_hadir' => $jamMasuk ? $date->copy()->setTime(6, 45, 0) : $date->copy()->setTime(7, 0, 0),
                        'jam_masuk' => $jamMasuk,
                        'jam_pulang' => $jamPulang,
                        'status' => $status,
                        'status_kehadiran' => $statusKehadiran,
                        'keterangan' => $keterangan,
                        'created_at' => $date->copy()->setTime(7, 0, 0),
                        'updated_at' => $date->copy()->setTime(15, 0, 0),
                    ]
                );
            }
        }

        $this->command->info("Dummy Data Berhasil Dibuat:");
        $this->command->info("- 4 Guru (Password: password)");
        $this->command->info("- 3 Kelas (X RPL 1, X TKJ 1, XI RPL 1)");
        $this->command->info("- 16 Siswa");
        $this->command->info("- 30 Hari Riwayat Absensi Siswa & Guru (1 Bulan Penuh)");
    }
}
