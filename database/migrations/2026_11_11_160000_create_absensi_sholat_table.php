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
        if (!Schema::hasTable('absensi_sholat')) {
            Schema::create('absensi_sholat', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->unsignedBigInteger('device_id')->nullable()->index();
                $table->enum('person_type', ['siswa', 'guru'])->default('siswa')->index();
                $table->unsignedBigInteger('student_id')->nullable()->index();
                $table->unsignedBigInteger('guru_id')->nullable()->index();
                $table->date('tanggal')->index();
                $table->string('sholat', 20)->comment('subuh, dhuha, dzuhur, ashar, maghrib, isya, jumat');
                $table->time('jam_absen');
                $table->time('waktu_sholat')->nullable()->comment('Estimasi waktu hisab sholat');
                $table->enum('status', ['hadir', 'terlambat', 'jamaah'])->default('hadir');
                $table->text('keterangan')->nullable();
                $table->timestamps();

                $table->index(['school_id', 'tanggal', 'sholat']);
                $table->unique(['school_id', 'tanggal', 'sholat', 'student_id'], 'uniq_sholat_student');
                $table->unique(['school_id', 'tanggal', 'sholat', 'guru_id'], 'uniq_sholat_guru');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensi_sholat');
    }
};

