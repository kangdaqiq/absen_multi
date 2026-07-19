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
        Schema::table('siswa', function (Blueprint $table) {
            if (!Schema::hasColumn('siswa', 'last_seen_siswa')) {
                $table->dateTime('last_seen_siswa')->nullable();
            }
            if (!Schema::hasColumn('siswa', 'last_seen_ortu')) {
                $table->dateTime('last_seen_ortu')->nullable();
            }
        });

        Schema::table('guru', function (Blueprint $table) {
            if (!Schema::hasColumn('guru', 'last_seen')) {
                $table->dateTime('last_seen')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn(['last_seen_siswa', 'last_seen_ortu']);
        });

        Schema::table('guru', function (Blueprint $table) {
            $table->dropColumn('last_seen');
        });
    }
};
