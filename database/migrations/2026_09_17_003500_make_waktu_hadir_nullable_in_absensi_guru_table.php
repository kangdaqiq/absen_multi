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
        if (Schema::hasTable('absensi_guru') && Schema::hasColumn('absensi_guru', 'waktu_hadir')) {
            Schema::table('absensi_guru', function (Blueprint $table) {
                $table->datetime('waktu_hadir')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('absensi_guru') && Schema::hasColumn('absensi_guru', 'waktu_hadir')) {
            Schema::table('absensi_guru', function (Blueprint $table) {
                $table->datetime('waktu_hadir')->nullable(false)->change();
            });
        }
    }
};
