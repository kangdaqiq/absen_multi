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
        if (Schema::hasTable('kelas') && !Schema::hasColumn('kelas', 'tingkat')) {
            Schema::table('kelas', function (Blueprint $table) {
                $table->unsignedTinyInteger('tingkat')->nullable()->after('nama_kelas');
                $table->index(['school_id', 'tingkat']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('kelas') && Schema::hasColumn('kelas', 'tingkat')) {
            Schema::table('kelas', function (Blueprint $table) {
                $table->dropIndex(['school_id', 'tingkat']);
                $table->dropColumn('tingkat');
            });
        }
    }
};
