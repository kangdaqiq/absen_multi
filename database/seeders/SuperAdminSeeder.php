<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\School;
use App\Models\User;
use App\Models\Siswa;
use App\Models\Guru;
use App\Models\Kelas;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            // Create default school
            $defaultSchool = School::create([
                'name' => 'Sekolah Default',
                'code' => 'DEFAULT',
                'address' => 'Alamat Sekolah Default',
                'phone' => '0812345678',
                'email' => 'default@school.com',
                'is_active' => true,
            ]);

            echo "✓ Sekolah default berhasil dibuat (ID: {$defaultSchool->id})\n";

            // Create Super Admin
            $superAdmin = User::create([
                'full_name' => 'Super Administrator',
                'username' => 'superadmin',
                'email' => 'superadmin@absen.com',
                'password_hash' => Hash::make('superadmin123'),
                'role' => 'super_admin',
                'school_id' => null, // Super admin tidak terikat ke sekolah
            ]);

            echo "✓ Super Admin berhasil dibuat\n";
            echo "  Username: superadmin\n";
            echo "  Password: superadmin123\n\n";

            // Assign existing data to default school
            $this->assignExistingDataToDefaultSchool($defaultSchool->id);

            DB::commit();

            echo "\n✓ Seeder berhasil dijalankan!\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "Login sebagai Super Admin:\n";
            echo "Username: superadmin\n";
            echo "Password: superadmin123\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

        } catch (\Exception $e) {
            DB::rollBack();
            echo "✗ Error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * Assign existing data to default school
     */
    private function assignExistingDataToDefaultSchool($schoolId): void
    {
        // Update users (except super admin)
        $usersUpdated = User::whereNull('school_id')
            ->where('role', '!=', 'super_admin')
            ->update(['school_id' => $schoolId]);
        echo "✓ {$usersUpdated} user di-assign ke sekolah default\n";

        // Update siswa
        $siswaUpdated = Siswa::whereNull('school_id')
            ->update(['school_id' => $schoolId]);
        echo "✓ {$siswaUpdated} siswa di-assign ke sekolah default\n";

        // Update guru
        $guruUpdated = Guru::whereNull('school_id')
            ->update(['school_id' => $schoolId]);
        echo "✓ {$guruUpdated} guru di-assign ke sekolah default\n";

        // Update kelas
        $kelasUpdated = Kelas::whereNull('school_id')
            ->update(['school_id' => $schoolId]);
        echo "✓ {$kelasUpdated} kelas di-assign ke sekolah default\n";

        // Update other tables if they exist and have school_id column
        if (\Schema::hasTable('jadwal_pelajaran') && \Schema::hasColumn('jadwal_pelajaran', 'school_id')) {
            $count = DB::table('jadwal_pelajaran')
                ->whereNull('school_id')
                ->update(['school_id' => $schoolId]);
            echo "✓ {$count} jadwal pelajaran di-assign ke sekolah default\n";
        }

        if (\Schema::hasTable('api_keys') && \Schema::hasColumn('api_keys', 'school_id')) {
            $count = DB::table('api_keys')
                ->whereNull('school_id')
                ->update(['school_id' => $schoolId]);
            echo "✓ {$count} API keys di-assign ke sekolah default\n";
        }

        if (\Schema::hasTable('settings') && \Schema::hasColumn('settings', 'school_id')) {
            $count = DB::table('settings')
                ->whereNull('school_id')
                ->update(['school_id' => $schoolId]);
            echo "✓ {$count} settings di-assign ke sekolah default\n";
        }
    }
}
