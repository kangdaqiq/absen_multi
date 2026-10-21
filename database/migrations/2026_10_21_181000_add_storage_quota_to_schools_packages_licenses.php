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
        // 1. Tambah kolom kuota & kredensial storage pada tabel schools
        if (Schema::hasTable('schools')) {
            Schema::table('schools', function (Blueprint $table) {
                if (!Schema::hasColumn('schools', 'storage_quota_mb')) {
                    $table->integer('storage_quota_mb')->default(1000)->after('dok_kbm_enabled');
                }
                if (!Schema::hasColumn('schools', 'storage_api_key')) {
                    $table->string('storage_api_key', 100)->nullable()->unique()->after('storage_quota_mb');
                }
                if (!Schema::hasColumn('schools', 'storage_user_id')) {
                    $table->string('storage_user_id', 100)->nullable()->after('storage_api_key');
                }
            });
        }

        // 2. Tambah kolom storage_quota_mb pada tabel packages
        if (Schema::hasTable('packages')) {
            Schema::table('packages', function (Blueprint $table) {
                if (!Schema::hasColumn('packages', 'storage_quota_mb')) {
                    $table->integer('storage_quota_mb')->default(1000)->after('dok_kbm_enabled');
                }
            });
        }

        // 3. Tambah kolom kuota & kredensial storage pada tabel licenses
        if (Schema::hasTable('licenses')) {
            Schema::table('licenses', function (Blueprint $table) {
                if (!Schema::hasColumn('licenses', 'storage_quota_mb')) {
                    $table->integer('storage_quota_mb')->default(1000)->after('dok_kbm_enabled');
                }
                if (!Schema::hasColumn('licenses', 'storage_api_key')) {
                    $table->string('storage_api_key', 100)->nullable()->after('storage_quota_mb');
                }
                if (!Schema::hasColumn('licenses', 'storage_user_id')) {
                    $table->string('storage_user_id', 100)->nullable()->after('storage_api_key');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('schools')) {
            Schema::table('schools', function (Blueprint $table) {
                if (Schema::hasColumn('schools', 'storage_user_id')) {
                    $table->dropColumn('storage_user_id');
                }
                if (Schema::hasColumn('schools', 'storage_api_key')) {
                    $table->dropColumn('storage_api_key');
                }
                if (Schema::hasColumn('schools', 'storage_quota_mb')) {
                    $table->dropColumn('storage_quota_mb');
                }
            });
        }

        if (Schema::hasTable('packages')) {
            Schema::table('packages', function (Blueprint $table) {
                if (Schema::hasColumn('packages', 'storage_quota_mb')) {
                    $table->dropColumn('storage_quota_mb');
                }
            });
        }

        if (Schema::hasTable('licenses')) {
            Schema::table('licenses', function (Blueprint $table) {
                if (Schema::hasColumn('licenses', 'storage_user_id')) {
                    $table->dropColumn('storage_user_id');
                }
                if (Schema::hasColumn('licenses', 'storage_api_key')) {
                    $table->dropColumn('storage_api_key');
                }
                if (Schema::hasColumn('licenses', 'storage_quota_mb')) {
                    $table->dropColumn('storage_quota_mb');
                }
            });
        }
    }
};
