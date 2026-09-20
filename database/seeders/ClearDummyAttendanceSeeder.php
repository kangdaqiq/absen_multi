<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Attendance;
use App\Models\AbsensiGuru;

class ClearDummyAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Data Dummy Identifiers
        $dummyNips = [
            '198501152010011001',
            '198803202014022002',
            '199005122019031003',
            '199207082020012004',
        ];

        $dummyTeacherEmails = [
            'budi.santoso@school.com',
            'siti.aminah@school.com',
            'ahmad.fauzi@school.com',
            'dewi.lestari@school.com',
        ];

        $dummyNis = [
            '20261001', '20261002', '20261003', '20261004', '20261005', '20261006',
            '20262001', '20262002', '20262003', '20262004', '20262005',
            '20251001', '20251002', '20251003', '20251004', '20251005',
        ];

        $dummyKelasNames = [
            'X RPL 1',
            'X TKJ 1',
            'XI RPL 1',
        ];

        // 2. Hapus Riwayat Absensi Siswa Dummy
        $siswaIds = Siswa::whereIn('nis', $dummyNis)->pluck('id');
        $attendanceCount = Attendance::whereIn('student_id', $siswaIds)->delete();

        // 3. Hapus Siswa Dummy
        $siswaCount = Siswa::whereIn('nis', $dummyNis)->delete();

        // 4. Hapus Riwayat Absensi Guru Dummy
        $guruIds = Guru::whereIn('nip', $dummyNips)->pluck('id');
        $absensiGuruCount = AbsensiGuru::whereIn('guru_id', $guruIds)->delete();

        // 5. Lepas relasi wali_kelas pada Kelas sebelum menghapus Guru/Kelas
        Kelas::whereIn('nama_kelas', $dummyKelasNames)->update(['wali_kelas_id' => null]);

        // 6. Hapus Guru Dummy
        $guruCount = Guru::whereIn('nip', $dummyNips)->delete();

        // 7. Hapus User Guru Dummy
        $userCount = User::whereIn('email', $dummyTeacherEmails)->delete();

        // 8. Hapus Kelas Dummy
        $kelasCount = Kelas::whereIn('nama_kelas', $dummyKelasNames)->delete();

        // 9. Hapus Jurusan Dummy jika sudah tidak memiliki kelas lain
        Jurusan::whereIn('nama_jurusan', ['Rekayasa Perangkat Lunak', 'Teknik Komputer dan Jaringan'])
            ->whereDoesntHave('kelas')
            ->delete();

        $this->command->info("Data Dummy Berhasil Dihapus:");
        $this->command->info("- {$attendanceCount} Riwayat Absensi Siswa");
        $this->command->info("- {$absensiGuruCount} Riwayat Absensi Guru");
        $this->command->info("- {$siswaCount} Siswa Dummy");
        $this->command->info("- {$guruCount} Guru Dummy ({$userCount} Akun User)");
        $this->command->info("- {$kelasCount} Kelas Dummy");
    }
}
