<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Don't use factory() in production (--no-dev)
        // Create Default School if not exists
        $school = \App\Models\School::firstOrCreate(
            ['code' => 'DEFAULT'],
            [
                'name' => 'Sekolah Default',
                'address' => 'Alamat Sekolah Default',
                'phone' => '0812345678',
                'email' => 'default@school.com',
                'is_active' => true,
            ]
        );

        // Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'full_name' => 'Administrator',
                'password_hash' => \Illuminate\Support\Facades\Hash::make('password'),
                'role' => 'admin', // Ensure role is admin
                'school_id' => $school->id
            ]
        );
        
        $this->command->info('User Admin created/verified: admin@gmail.com / password');
    }
}
