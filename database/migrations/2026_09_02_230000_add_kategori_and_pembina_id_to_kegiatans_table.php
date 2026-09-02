<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kegiatans', function (Blueprint $table) {
            if (!Schema::hasColumn('kegiatans', 'kategori')) {
                $table->string('kategori', 20)->default('sekolah')->after('target_type'); // 'sekolah' atau 'ekskul'
            }
            if (!Schema::hasColumn('kegiatans', 'pembina_id')) {
                $table->unsignedInteger('pembina_id')->nullable()->after('kategori');
                $table->foreign('pembina_id')->references('id')->on('guru')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kegiatans', function (Blueprint $table) {
            if (Schema::hasColumn('kegiatans', 'pembina_id')) {
                $table->dropForeign(['pembina_id']);
                $table->dropColumn('pembina_id');
            }
            if (Schema::hasColumn('kegiatans', 'kategori')) {
                $table->dropColumn('kategori');
            }
        });
    }
};
