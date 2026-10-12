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
        // 1. Tambah kolom foto pada tabel guru
        if (Schema::hasTable('guru') && !Schema::hasColumn('guru', 'foto')) {
            Schema::table('guru', function (Blueprint $table) {
                $table->string('foto')->nullable()->after('no_wa');
            });
        }

        // 2. Tambah kolom guru_id pada tabel user_face_data
        if (Schema::hasTable('user_face_data') && !Schema::hasColumn('user_face_data', 'guru_id')) {
            Schema::table('user_face_data', function (Blueprint $table) {
                $table->unsignedBigInteger('guru_id')->nullable()->after('siswa_id')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('guru') && Schema::hasColumn('guru', 'foto')) {
            Schema::table('guru', function (Blueprint $table) {
                $table->dropColumn('foto');
            });
        }

        if (Schema::hasTable('user_face_data') && Schema::hasColumn('user_face_data', 'guru_id')) {
            Schema::table('user_face_data', function (Blueprint $table) {
                $table->dropColumn('guru_id');
            });
        }
    }
};
