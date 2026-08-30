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
        if (!Schema::hasTable('student_leaves')) {
            Schema::create('student_leaves', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->unsignedBigInteger('student_id')->index();
                $table->string('code', 30)->unique();
                $table->enum('jenis', ['sakit', 'izin', 'dispensasi'])->default('izin');
                $table->date('tanggal_mulai');
                $table->date('tanggal_selesai');
                $table->text('keterangan');
                $table->string('bukti_foto')->nullable();
                $table->enum('pengaju', ['ortu', 'siswa', 'guru'])->default('ortu');
                $table->string('nama_pengaju')->nullable();
                $table->string('no_wa_pengaju', 25)->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('rejected_reason')->nullable();
                $table->timestamps();

                $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
                $table->foreign('student_id')->references('id')->on('siswa')->onDelete('cascade');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_leaves');
    }
};
