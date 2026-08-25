<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('guru') && !Schema::hasColumn('guru', 'default_shift_id')) {
            Schema::table('guru', function (Blueprint $table) {
                $table->unsignedBigInteger('default_shift_id')->nullable()->after('school_id');
                $table->foreign('default_shift_id')->references('id')->on('shifts')->nullOnDelete();
            });
        }

        if (Schema::hasTable('absensi_guru')) {
            Schema::table('absensi_guru', function (Blueprint $table) {
                if (!Schema::hasColumn('absensi_guru', 'shift_id')) {
                    $table->unsignedBigInteger('shift_id')->nullable()->after('jadwal_pelajaran_id');
                    $table->foreign('shift_id')->references('id')->on('shifts')->nullOnDelete();
                }
                if (!Schema::hasColumn('absensi_guru', 'menit_terlambat')) {
                    $table->integer('menit_terlambat')->default(0)->after('jam_pulang');
                }
                if (!Schema::hasColumn('absensi_guru', 'status_kehadiran')) {
                    $table->string('status_kehadiran', 30)->nullable()->after('status')->comment('tepat_waktu, terlambat, dll');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('guru') && Schema::hasColumn('guru', 'default_shift_id')) {
            Schema::table('guru', function (Blueprint $table) {
                $table->dropForeign(['default_shift_id']);
                $table->dropColumn('default_shift_id');
            });
        }

        if (Schema::hasTable('absensi_guru')) {
            Schema::table('absensi_guru', function (Blueprint $table) {
                if (Schema::hasColumn('absensi_guru', 'shift_id')) {
                    $table->dropForeign(['shift_id']);
                    $table->dropColumn('shift_id');
                }
                if (Schema::hasColumn('absensi_guru', 'menit_terlambat')) {
                    $table->dropColumn('menit_terlambat');
                }
                if (Schema::hasColumn('absensi_guru', 'status_kehadiran')) {
                    $table->dropColumn('status_kehadiran');
                }
            });
        }
    }
};
