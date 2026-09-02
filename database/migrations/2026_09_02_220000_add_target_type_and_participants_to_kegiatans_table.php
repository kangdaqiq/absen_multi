<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah target_type ke tabel kegiatans
        Schema::table('kegiatans', function (Blueprint $table) {
            if (!Schema::hasColumn('kegiatans', 'target_type')) {
                $table->string('target_type', 20)->default('all')->after('frekuensi'); // 'all', 'kelas', 'siswa'
            }
        });

        // 2. Tabel pivot kegiatan_kelas untuk cakupan per kelas
        if (!Schema::hasTable('kegiatan_kelas')) {
            Schema::create('kegiatan_kelas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('kegiatan_id')->index();
                $table->unsignedInteger('kelas_id')->index();
                $table->timestamps();

                $table->foreign('kegiatan_id')->references('id')->on('kegiatans')->onDelete('cascade');
                $table->foreign('kelas_id')->references('id')->on('kelas')->onDelete('cascade');
                $table->unique(['kegiatan_id', 'kelas_id'], 'unique_kegiatan_kelas');
            });
        }

        // 3. Tabel pivot kegiatan_siswa untuk cakupan siswa tertentu
        if (!Schema::hasTable('kegiatan_siswa')) {
            Schema::create('kegiatan_siswa', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('kegiatan_id')->index();
                $table->unsignedInteger('student_id')->index();
                $table->timestamps();

                $table->foreign('kegiatan_id')->references('id')->on('kegiatans')->onDelete('cascade');
                $table->foreign('student_id')->references('id')->on('siswa')->onDelete('cascade');
                $table->unique(['kegiatan_id', 'student_id'], 'unique_kegiatan_siswa');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan_siswa');
        Schema::dropIfExists('kegiatan_kelas');
        Schema::table('kegiatans', function (Blueprint $table) {
            if (Schema::hasColumn('kegiatans', 'target_type')) {
                $table->dropColumn('target_type');
            }
        });
    }
};
