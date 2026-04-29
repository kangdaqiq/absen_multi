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
        Schema::table('jadwal', function (Blueprint $table) {
            $table->dropColumn('toleransi');
            $table->time('awal_absen_masuk')->nullable()->after('jam_masuk');
            $table->time('akhir_absen_masuk')->nullable()->after('awal_absen_masuk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal', function (Blueprint $table) {
            $table->integer('toleransi')->default(15);
            $table->dropColumn(['awal_absen_masuk', 'akhir_absen_masuk']);
        });
    }
};
