<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shifts') && !Schema::hasColumn('shifts', 'hari_kerja')) {
            Schema::table('shifts', function (Blueprint $table) {
                $table->json('hari_kerja')->nullable()->after('akhir_absen_pulang');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shifts') && Schema::hasColumn('shifts', 'hari_kerja')) {
            Schema::table('shifts', function (Blueprint $table) {
                $table->dropColumn('hari_kerja');
            });
        }
    }
};
