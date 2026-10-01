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
        // 1. Table schools
        if (Schema::hasTable('schools') && !Schema::hasColumn('schools', 'photo_enabled')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->boolean('photo_enabled')->default(true)->after('is_active');
            });
        }

        // 2. Table licenses
        if (Schema::hasTable('licenses') && !Schema::hasColumn('licenses', 'photo_enabled')) {
            Schema::table('licenses', function (Blueprint $table) {
                $table->boolean('photo_enabled')->default(true)->after('is_active');
            });
        }

        // 3. Table packages
        if (Schema::hasTable('packages') && !Schema::hasColumn('packages', 'photo_enabled')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->boolean('photo_enabled')->default(true)->after('is_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('schools') && Schema::hasColumn('schools', 'photo_enabled')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->dropColumn('photo_enabled');
            });
        }

        if (Schema::hasTable('licenses') && Schema::hasColumn('licenses', 'photo_enabled')) {
            Schema::table('licenses', function (Blueprint $table) {
                $table->dropColumn('photo_enabled');
            });
        }

        if (Schema::hasTable('packages') && Schema::hasColumn('packages', 'photo_enabled')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->dropColumn('photo_enabled');
            });
        }
    }
};
