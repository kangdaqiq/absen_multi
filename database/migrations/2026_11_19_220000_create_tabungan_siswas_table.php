<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tabungan_siswas')) {
            Schema::create('tabungan_siswas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->unsignedBigInteger('student_id')->index();
                $table->unsignedBigInteger('recorded_by')->nullable()->index();
                $table->string('code', 35)->unique();
                $table->date('tanggal')->index();
                $table->string('waktu', 10)->nullable();
                $table->enum('jenis', ['setor', 'tarik'])->default('setor')->index();
                $table->bigInteger('nominal')->default(0);
                $table->bigInteger('saldo_sebelum')->default(0);
                $table->bigInteger('saldo_sesudah')->default(0);
                $table->string('metode', 30)->default('tunai');
                $table->string('keterangan', 500)->nullable();
                $table->timestamps();

                $table->index(['school_id', 'tanggal']);
                $table->index(['student_id', 'tanggal']);
            });
        }

        if (Schema::hasTable('siswa') && !Schema::hasColumn('siswa', 'saldo_tabungan')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->bigInteger('saldo_tabungan')->default(0)->after('nisn');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tabungan_siswas');

        if (Schema::hasTable('siswa') && Schema::hasColumn('siswa', 'saldo_tabungan')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->dropColumn('saldo_tabungan');
            });
        }
    }
};
