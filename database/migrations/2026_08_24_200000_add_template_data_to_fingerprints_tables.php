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
        if (Schema::hasTable('siswa_fingerprints') && !Schema::hasColumn('siswa_fingerprints', 'template_data')) {
            Schema::table('siswa_fingerprints', function (Blueprint $table) {
                $table->longText('template_data')->nullable()->after('finger_id');
            });
        }

        if (Schema::hasTable('guru_fingerprints') && !Schema::hasColumn('guru_fingerprints', 'template_data')) {
            Schema::table('guru_fingerprints', function (Blueprint $table) {
                $table->longText('template_data')->nullable()->after('finger_id');
            });
        }

        if (Schema::hasTable('gate_card_fingerprints') && !Schema::hasColumn('gate_card_fingerprints', 'template_data')) {
            Schema::table('gate_card_fingerprints', function (Blueprint $table) {
                $table->longText('template_data')->nullable()->after('finger_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('siswa_fingerprints') && Schema::hasColumn('siswa_fingerprints', 'template_data')) {
            Schema::table('siswa_fingerprints', function (Blueprint $table) {
                $table->dropColumn('template_data');
            });
        }

        if (Schema::hasTable('guru_fingerprints') && Schema::hasColumn('guru_fingerprints', 'template_data')) {
            Schema::table('guru_fingerprints', function (Blueprint $table) {
                $table->dropColumn('template_data');
            });
        }

        if (Schema::hasTable('gate_card_fingerprints') && Schema::hasColumn('gate_card_fingerprints', 'template_data')) {
            Schema::table('gate_card_fingerprints', function (Blueprint $table) {
                $table->dropColumn('template_data');
            });
        }
    }
};
