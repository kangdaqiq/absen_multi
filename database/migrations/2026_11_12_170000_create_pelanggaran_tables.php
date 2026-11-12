<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('kategori_pelanggarans')) {
            Schema::create('kategori_pelanggarans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->string('kode', 30)->nullable();
                $table->string('nama', 150);
                $table->enum('kategori', ['ringan', 'sedang', 'berat'])->default('ringan')->index();
                $table->unsignedInteger('poin')->default(5);
                $table->text('sanksi_default')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pelanggarans')) {
            Schema::create('pelanggarans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->unsignedInteger('student_id')->index();
                $table->unsignedBigInteger('kategori_pelanggaran_id')->nullable()->index();
                $table->unsignedBigInteger('recorded_by')->nullable()->index();
                $table->string('code', 30)->unique();
                $table->date('tanggal')->index();
                $table->string('waktu', 10)->nullable();
                $table->string('judul_pelanggaran', 200);
                $table->enum('kategori', ['ringan', 'sedang', 'berat'])->default('ringan')->index();
                $table->unsignedInteger('poin')->default(5);
                $table->text('keterangan')->nullable();
                $table->text('tindak_lanjut')->nullable();
                $table->string('bukti_foto')->nullable();
                $table->enum('status', ['tercatat', 'diproses', 'selesai'])->default('tercatat')->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggarans');
        Schema::dropIfExists('kategori_pelanggarans');
    }
};

