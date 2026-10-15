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
        if (Schema::hasTable('jadwal_pelajaran')) {
            Schema::table('jadwal_pelajaran', function (Blueprint $table) {
                if (!Schema::hasColumn('jadwal_pelajaran', 'jam_pelajaran_id_akhir')) {
                    $column = $table->foreignId('jam_pelajaran_id_akhir')->nullable();

                    if (Schema::hasColumn('jadwal_pelajaran', 'jam_pelajaran_id')) {
                        $column->after('jam_pelajaran_id');
                    }

                    if (Schema::hasTable('jam_pelajaran')) {
                        $column->constrained('jam_pelajaran')->onDelete('set null');
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('jadwal_pelajaran')) {
            Schema::table('jadwal_pelajaran', function (Blueprint $table) {
                if (Schema::hasColumn('jadwal_pelajaran', 'jam_pelajaran_id_akhir')) {
                    $table->dropForeign(['jam_pelajaran_id_akhir']);
                    $table->dropColumn('jam_pelajaran_id_akhir');
                }
            });
        }
    }
};
