<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            // Drop lama unique constraint yang hanya pada nama_kelas (global)
            $table->dropUnique('nama_kelas');

            // Tambah composite unique: nama_kelas unik per school_id
            $table->unique(['nama_kelas', 'school_id'], 'kelas_nama_school_unique');
        });
    }

    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropUnique('kelas_nama_school_unique');
            $table->unique('nama_kelas');
        });
    }
};
