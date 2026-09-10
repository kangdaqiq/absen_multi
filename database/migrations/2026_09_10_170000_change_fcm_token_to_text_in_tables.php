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
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'fcm_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('fcm_token')->nullable()->change();
            });
        }

        if (Schema::hasTable('siswa') && Schema::hasColumn('siswa', 'fcm_token')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->text('fcm_token')->nullable()->change();
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
                $table->string('fcm_token', 255)->nullable()->change();
            });
        }

        if (Schema::hasTable('siswa') && Schema::hasColumn('siswa', 'fcm_token')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->string('fcm_token', 255)->nullable()->change();
            });
        }
    }
};
