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
        // 1. Buat tabel master jam_pelajaran
        if (!Schema::hasTable('jam_pelajaran')) {
            Schema::create('jam_pelajaran', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('cascade');
                $table->string('nama_jam', 100); // contoh: "Jam Ke-1", "Jam 1-2", "Sesi Pagi"
                $table->string('jam_ke', 50)->nullable(); // contoh: "1", "1-2"
                $table->time('jam_mulai');
                $table->time('jam_selesai');
                $table->string('hari', 50)->nullable(); // null = berlaku semua hari, atau "Jumat", "Senin", dll.
                $table->unsignedSmallInteger('urutan')->default(1);
                $table->boolean('is_active')->default(true);
                $table->string('keterangan', 255)->nullable();
                $table->timestamps();

                $table->index(['school_id', 'is_active', 'urutan']);
            });
        }

        // 2. Tambah kolom jam_pelajaran_id ke jadwal_pelajaran jika tabel ada
        if (Schema::hasTable('jadwal_pelajaran')) {
            Schema::table('jadwal_pelajaran', function (Blueprint $table) {
                if (!Schema::hasColumn('jadwal_pelajaran', 'jam_pelajaran_id')) {
                    $table->foreignId('jam_pelajaran_id')
                        ->nullable()
                        ->after('hari')
                        ->constrained('jam_pelajaran')
                        ->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('jadwal_pelajaran')) {
            Schema::table('jadwal_pelajaran', function (Blueprint $table) {
                if (Schema::hasColumn('jadwal_pelajaran', 'jam_pelajaran_id')) {
                    $table->dropForeign(['jam_pelajaran_id']);
                    $table->dropColumn('jam_pelajaran_id');
                }
            });
        }

        Schema::dropIfExists('jam_pelajaran');
    }
};
