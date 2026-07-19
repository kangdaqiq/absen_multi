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
        Schema::table('kelas', function (Blueprint $table) {
            $table->unsignedBigInteger('wali_kelas_2_id')->nullable()->after('wali_kelas_id');
            $table->foreign('wali_kelas_2_id')->references('id')->on('guru')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropForeign(['wali_kelas_2_id']);
            $table->dropColumn('wali_kelas_2_id');
        });
    }
};
