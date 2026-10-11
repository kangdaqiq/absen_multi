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
        // 1. Tabel user_face_data (Penyimpanan Vektor Wajah Master)
        if (!Schema::hasTable('user_face_data')) {
            Schema::create('user_face_data', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id')->index();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unsignedBigInteger('siswa_id')->nullable()->index();
                $table->longText('face_embedding'); // Menampung 512-array float vektor wajah (JSON)
                $table->string('sample_photo_path'); // Path file foto master
                $table->boolean('is_verified')->default(true);
                $table->timestamps();
            });
        }

        // 2. Update Tabel Log Kehadiran (attendance, attendances, absensi_guru)
        $attendanceTables = ['attendance', 'attendances', 'absensi_guru'];
        foreach ($attendanceTables as $tbl) {
            if (Schema::hasTable($tbl)) {
                Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                    if (!Schema::hasColumn($tbl, 'photo_checkin_path')) {
                        $table->string('photo_checkin_path')->nullable();
                    }
                    if (!Schema::hasColumn($tbl, 'face_similarity_score')) {
                        $table->float('face_similarity_score')->nullable(); // Contoh: 0.92 (92% cocok)
                    }
                    if (!Schema::hasColumn($tbl, 'is_face_matched')) {
                        $table->boolean('is_face_matched')->default(false);
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_face_data');

        $attendanceTables = ['attendance', 'attendances', 'absensi_guru'];
        foreach ($attendanceTables as $tbl) {
            if (Schema::hasTable($tbl)) {
                Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                    $colsToDrop = [];
                    if (Schema::hasColumn($tbl, 'photo_checkin_path')) {
                        $colsToDrop[] = 'photo_checkin_path';
                    }
                    if (Schema::hasColumn($tbl, 'face_similarity_score')) {
                        $colsToDrop[] = 'face_similarity_score';
                    }
                    if (Schema::hasColumn($tbl, 'is_face_matched')) {
                        $colsToDrop[] = 'is_face_matched';
                    }
                    if (!empty($colsToDrop)) {
                        $table->dropColumn($colsToDrop);
                    }
                });
            }
        }
    }
};
