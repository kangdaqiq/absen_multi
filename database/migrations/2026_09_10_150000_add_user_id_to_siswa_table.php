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
        if (Schema::hasTable('siswa') && !Schema::hasColumn('siswa', 'user_id')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('school_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('siswa') && Schema::hasColumn('siswa', 'user_id')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
    }
};
