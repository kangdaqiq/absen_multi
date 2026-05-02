<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // First, update all NULL school_id to 0 (representing global settings)
        DB::statement('UPDATE settings SET school_id = 0 WHERE school_id IS NULL');

        // Then modify the column to have default value of 0 and NOT NULL
        DB::statement('ALTER TABLE settings MODIFY school_id BIGINT UNSIGNED NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        // Revert back to nullable
        DB::statement('ALTER TABLE settings MODIFY school_id BIGINT UNSIGNED NULL');

        // Convert 0 back to NULL
        DB::statement('UPDATE settings SET school_id = NULL WHERE school_id = 0');
    }
};
