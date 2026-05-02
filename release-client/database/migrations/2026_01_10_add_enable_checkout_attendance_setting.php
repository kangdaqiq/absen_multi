<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if setting already exists
        $exists = DB::table('settings')
            ->where('setting_key', 'enable_checkout_attendance')
            ->exists();

        if (!$exists) {
            DB::table('settings')->insert([
                'setting_key' => 'enable_checkout_attendance',
                'setting_value' => 'true',
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')
            ->where('setting_key', 'enable_checkout_attendance')
            ->delete();
    }
};
