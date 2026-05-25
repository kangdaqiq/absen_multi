<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('kegiatan_id');
            $table->unsignedInteger('student_id'); // siswa.id is int(10) unsigned
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->string('status', 1)->default('H'); // H = Hadir
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('kegiatan_id')->references('id')->on('kegiatans')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('siswa')->onDelete('cascade');


            // Satu siswa hanya bisa satu record per kegiatan per tanggal
            $table->unique(['kegiatan_id', 'student_id', 'tanggal'], 'unique_kegiatan_attendance');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan_attendances');
    }
};
