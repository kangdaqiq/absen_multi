<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sesi aktif scan kegiatan — mirip TeacherCheckoutSession untuk gate
        // Ketika kartu kegiatan di-scan, sesi ini dibuat.
        // Scan siswa dalam sesi aktif akan dicatat sebagai absen kegiatan.
        Schema::create('kegiatan_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('kegiatan_id');
            $table->string('uid_kartu');           // UID kartu yang mengaktifkan sesi ini
            $table->datetime('expires_at');        // sesi berakhir otomatis setelah X menit
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('kegiatan_id')->references('id')->on('kegiatans')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan_sessions');
    }
};
