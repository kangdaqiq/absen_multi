<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shifts')) {
            Schema::create('shifts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->nullable();
                $table->string('nama_shift', 100);
                $table->string('kode_shift', 50)->nullable();
                $table->time('jam_masuk');
                $table->time('jam_pulang');
                $table->time('jam_terlambat')->nullable();
                $table->time('awal_absen_masuk')->default('06:00:00');
                $table->time('akhir_absen_masuk')->default('09:00:00');
                $table->time('awal_absen_pulang')->default('13:30:00');
                $table->time('akhir_absen_pulang')->default('18:00:00');
                $table->json('hari_kerja')->nullable()->comment('Array index hari: [1,2,3,4,5] (1=Senin..7=Minggu)');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('guru_shift_assignments')) {
            Schema::create('guru_shift_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->nullable();
                $table->unsignedBigInteger('guru_id');
                $table->unsignedBigInteger('shift_id');
                $table->date('tanggal')->nullable();
                $table->tinyInteger('index_hari')->nullable()->comment('1: Senin s/d 7: Minggu');
                $table->timestamps();

                $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
                $table->foreign('guru_id')->references('id')->on('guru')->onDelete('cascade');
                $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('cascade');

                $table->index(['guru_id', 'tanggal']);
                $table->index(['guru_id', 'index_hari']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('guru_shift_assignments');
        Schema::dropIfExists('shifts');
    }
};
