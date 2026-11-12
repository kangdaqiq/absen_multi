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
        if (Schema::hasTable('api_keys') && !Schema::hasColumn('api_keys', 'device_category')) {
            Schema::table('api_keys', function (Blueprint $table) {
                $table->string('device_category', 30)->default('attendance')->after('type')->comment('attendance = Presensi Masuk/Pulang, sholat = Presensi Sholat');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('api_keys') && Schema::hasColumn('api_keys', 'device_category')) {
            Schema::table('api_keys', function (Blueprint $table) {
                $table->dropColumn('device_category');
            });
        }
    }
};

