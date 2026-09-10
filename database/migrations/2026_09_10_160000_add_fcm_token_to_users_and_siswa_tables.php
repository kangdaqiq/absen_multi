<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'fcm_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('fcm_token', 255)->nullable()->after('school_id');
            });
        }

        if (Schema::hasTable('siswa') && !Schema::hasColumn('siswa', 'fcm_token')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->string('fcm_token', 255)->nullable()->after('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'fcm_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('fcm_token');
            });
        }

        if (Schema::hasTable('siswa') && Schema::hasColumn('siswa', 'fcm_token')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->dropColumn('fcm_token');
            });
        }
    }
};
