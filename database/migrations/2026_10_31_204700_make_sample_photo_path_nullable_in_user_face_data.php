<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('user_face_data') && Schema::hasColumn('user_face_data', 'sample_photo_path')) {
            // Gunakan direct raw SQL ALTER TABLE untuk menghindari dependensi doctrine/dbal jika belum terpasang
            DB::statement("ALTER TABLE `user_face_data` MODIFY `sample_photo_path` VARCHAR(255) NULL DEFAULT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('user_face_data') && Schema::hasColumn('user_face_data', 'sample_photo_path')) {
            DB::statement("ALTER TABLE `user_face_data` MODIFY `sample_photo_path` VARCHAR(255) NOT NULL");
        }
    }
};

