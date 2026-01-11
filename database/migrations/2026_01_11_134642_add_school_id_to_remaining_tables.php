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
        // Add school_id to jadwal table (jam masuk/pulang)
        if (Schema::hasTable('jadwal') && !Schema::hasColumn('jadwal', 'school_id')) {
            Schema::table('jadwal', function (Blueprint $table) {
                $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->onDelete('cascade');
            });
        }

        // Add school_id to hari_libur table
        if (Schema::hasTable('hari_libur') && !Schema::hasColumn('hari_libur', 'school_id')) {
            Schema::table('hari_libur', function (Blueprint $table) {
                $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->onDelete('cascade');
            });
        }

        // Add school_id to mapel table
        if (Schema::hasTable('mapel') && !Schema::hasColumn('mapel', 'school_id')) {
            Schema::table('mapel', function (Blueprint $table) {
                $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('jadwal') && Schema::hasColumn('jadwal', 'school_id')) {
            Schema::table('jadwal', function (Blueprint $table) {
                $table->dropForeign(['school_id']);
                $table->dropColumn('school_id');
            });
        }

        if (Schema::hasTable('hari_libur') && Schema::hasColumn('hari_libur', 'school_id')) {
            Schema::table('hari_libur', function (Blueprint $table) {
                $table->dropForeign(['school_id']);
                $table->dropColumn('school_id');
            });
        }

        if (Schema::hasTable('mapel') && Schema::hasColumn('mapel', 'school_id')) {
            Schema::table('mapel', function (Blueprint $table) {
                $table->dropForeign(['school_id']);
                $table->dropColumn('school_id');
            });
        }
    }
};
