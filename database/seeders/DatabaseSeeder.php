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
        
        // Create Admin User
        User::create([
            'full_name' => 'Administrator',
            'email' => 'admin@gmail.com',
            'password_hash' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'admin' // Ensure role is admin
        ]);
        
        $this->command->info('User Admin created: admin@gmail.com / password');
    }
}
