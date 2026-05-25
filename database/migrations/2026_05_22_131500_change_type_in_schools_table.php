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
        Schema::table('schools', function (Blueprint $table) {
            // Ubah tipe enum menjadi string untuk mendukung tipe baru 'pesantren'
            $table->string('type', 20)->default('school')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            // Jika rollback, biarkan string agar data 'pesantren' tidak corrupt di tingkat RDBMS,
            // atau jika rollback murni, kita bisa kembalikan ke enum (tetapi data 'pesantren' akan bermasalah di MySQL).
            // Maka kita pertahankan string.
        });
    }
};
