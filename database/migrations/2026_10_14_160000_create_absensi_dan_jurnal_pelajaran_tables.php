<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambah kolom jam_ke dan ruangan ke jadwal_pelajaran jika belum ada
        if (Schema::hasTable('jadwal_pelajaran')) {
            Schema::table('jadwal_pelajaran', function (Blueprint $table) {
                if (!Schema::hasColumn('jadwal_pelajaran', 'jam_ke')) {
                    $table->string('jam_ke', 50)->nullable()->after('hari');
                }
                if (!Schema::hasColumn('jadwal_pelajaran', 'ruangan')) {
                    $table->string('ruangan', 100)->nullable()->after('jam_selesai');
                }
                if (!Schema::hasColumn('jadwal_pelajaran', 'keterangan')) {
                    $table->text('keterangan')->nullable()->after('ruangan');
                }
            });
        }

        // 2. Buat tabel jurnal_pelajaran (Jurnal KBM per jadwal per tanggal)
        if (!Schema::hasTable('jurnal_pelajaran')) {
            Schema::create('jurnal_pelajaran', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('cascade');
                $table->foreignId('jadwal_pelajaran_id')->constrained('jadwal_pelajaran')->onDelete('cascade');
                $table->unsignedInteger('guru_id')->nullable();
                $table->foreign('guru_id')->references('id')->on('guru')->onDelete('set null');
                $table->date('tanggal');
                $table->string('jam_ke', 50)->nullable();
                $table->text('materi');
                $table->text('catatan')->nullable();
                $table->time('jam_mulai')->nullable();
                $table->time('jam_selesai')->nullable();
                $table->timestamps();

                $table->index(['school_id', 'tanggal']);
                $table->unique(['jadwal_pelajaran_id', 'tanggal'], 'unique_jurnal_jadwal_tanggal');
            });
        }

        // 3. Buat tabel absensi_pelajaran (Presensi siswa per jam pelajaran)
        if (!Schema::hasTable('absensi_pelajaran')) {
            Schema::create('absensi_pelajaran', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('cascade');
                $table->foreignId('jadwal_pelajaran_id')->constrained('jadwal_pelajaran')->onDelete('cascade');
                $table->foreignId('jurnal_pelajaran_id')->nullable()->constrained('jurnal_pelajaran')->onDelete('set null');
                $table->unsignedInteger('student_id');
                $table->foreign('student_id')->references('id')->on('siswa')->onDelete('cascade');
                $table->unsignedInteger('guru_id')->nullable();
                $table->foreign('guru_id')->references('id')->on('guru')->onDelete('set null');
                $table->date('tanggal');
                $table->string('status', 10)->default('H'); // H=Hadir, S=Sakit, I=Izin, A=Alpa, T=Terlambat, D=Dispen
                $table->string('keterangan', 255)->nullable();
                $table->timestamps();

                $table->index(['school_id', 'tanggal']);
                $table->index(['jadwal_pelajaran_id', 'tanggal']);
                $table->unique(['jadwal_pelajaran_id', 'student_id', 'tanggal'], 'unique_absensi_siswa_jadwal_tanggal');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensi_pelajaran');
        Schema::dropIfExists('jurnal_pelajaran');

        if (Schema::hasTable('jadwal_pelajaran')) {
            Schema::table('jadwal_pelajaran', function (Blueprint $table) {
                if (Schema::hasColumn('jadwal_pelajaran', 'keterangan')) {
                    $table->dropColumn('keterangan');
                }
                if (Schema::hasColumn('jadwal_pelajaran', 'ruangan')) {
                    $table->dropColumn('ruangan');
                }
                if (Schema::hasColumn('jadwal_pelajaran', 'jam_ke')) {
                    $table->dropColumn('jam_ke');
                }
            });
        }
    }
};
