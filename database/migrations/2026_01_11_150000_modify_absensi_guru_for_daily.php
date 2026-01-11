<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('absensi_guru', function (Blueprint $table) {
            // Make jadwal_pelajaran_id nullable
            $table->unsignedBigInteger('jadwal_pelajaran_id')->nullable()->change();

            // Add new columns for daily attendance
            if (!Schema::hasColumn('absensi_guru', 'jam_masuk')) {
                $table->time('jam_masuk')->nullable()->after('tanggal');
            }
            if (!Schema::hasColumn('absensi_guru', 'jam_pulang')) {
                $table->time('jam_pulang')->nullable()->after('jam_masuk');
            }
            if (!Schema::hasColumn('absensi_guru', 'keterangan')) {
                $table->string('keterangan')->nullable()->after('status');
            }
            if (!Schema::hasColumn('absensi_guru', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->after('guru_id');
                // We won't add foreign constraint immediately to avoid issues if data exists, 
                // but usually safe if nullable. Let's add it if strictly needed, but for now just index.
                $table->index('school_id');
            }

            // Drop existing unique index if exists to avoid conflict when jadwal_id is null
            // We'll manage uniqueness via application logic or a better unique index
            try {
                // Drop foreign key if exists (common convention: table_column_foreign)
                $table->dropForeign(['jadwal_pelajaran_id']);
                $table->dropUnique('absensi_guru_jadwal_pelajaran_id_tanggal_unique');
            } catch (\Exception $e) {
                // Ignore if not exists or if foreign key name is different (try catching explicitly)
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensi_guru', function (Blueprint $table) {
            // Revert changes (approximate)
            // Note: modifying back to non-nullable might fail if nulls exist
            // $table->unsignedBigInteger('jadwal_pelajaran_id')->nullable(false)->change();

            $table->dropColumn(['jam_masuk', 'jam_pulang', 'keterangan', 'school_id']);
        });
    }
};
