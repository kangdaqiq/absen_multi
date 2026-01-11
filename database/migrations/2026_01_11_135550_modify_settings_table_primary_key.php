<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop the old primary key and create a composite primary key
        Schema::table('settings', function (Blueprint $table) {
            // Drop the existing primary key
            $table->dropPrimary('PRIMARY');

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
