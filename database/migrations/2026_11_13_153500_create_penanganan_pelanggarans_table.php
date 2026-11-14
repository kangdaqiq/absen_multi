<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('penanganan_pelanggarans')) {
            Schema::create('penanganan_pelanggarans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->unsignedBigInteger('pelanggaran_id')->index();
                $table->unsignedInteger('student_id')->index();
                $table->unsignedBigInteger('handled_by')->nullable()->index(); // User guru BK / wali kelas / admin
                $table->date('tanggal')->index();
                $table->enum('tindakan_sp', ['pembinaan', 'teguran_lisan', 'teguran_tertulis', 'sp_1', 'sp_2', 'sp_3', 'sp_4', 'skorsing', 'dikembalikan_ortu'])->default('pembinaan')->index();
                $table->text('catatan_penanganan');
                $table->text('hasil_kesepakatan')->nullable();
                $table->string('link_gdrive', 1000)->nullable();
                $table->string('lampiran_file')->nullable();
                $table->enum('status_penanganan', ['dalam_proses', 'selesai', 'evaluasi_lanjutan'])->default('selesai')->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('penanganan_pelanggarans');
    }
};

