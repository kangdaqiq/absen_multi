<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        try {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropForeign(['school_id']);
            });
        } catch (\Exception $e) {
            // Foreign key might not exist, ignore
        }

        // Set existing school_id to 0 so they can be part of primary key
        \Illuminate\Support\Facades\DB::statement('UPDATE settings SET school_id = 0 WHERE school_id IS NULL');

        // Drop the existing primary key
        try {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE settings DROP PRIMARY KEY');
        } catch (\Exception $e) {
            // Ignore if it's already dropped
        }

        // Create a composite primary key
        Schema::table('settings', function (Blueprint $table) {
            // Create composite primary key (setting_key + school_id)
            // This allows same setting_key for different schools
            $table->primary(['setting_key', 'school_id']);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Drop composite primary key
            $table->dropPrimary(['setting_key', 'school_id']);

            // Restore original primary key
            $table->primary('setting_key');
        });
    }
};
