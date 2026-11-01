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
        if (Schema::hasTable('licenses')) {
            Schema::table('licenses', function (Blueprint $table) {
                if (!Schema::hasColumn('licenses', 'storage_used_bytes')) {
                    $table->unsignedBigInteger('storage_used_bytes')->default(0)->after('storage_quota_mb');
                }
                if (!Schema::hasColumn('licenses', 'storage_files_count')) {
                    $table->unsignedInteger('storage_files_count')->default(0)->after('storage_used_bytes');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('licenses')) {
            Schema::table('licenses', function (Blueprint $table) {
                if (Schema::hasColumn('licenses', 'storage_files_count')) {
                    $table->dropColumn('storage_files_count');
                }
                if (Schema::hasColumn('licenses', 'storage_used_bytes')) {
                    $table->dropColumn('storage_used_bytes');
                }
            });
        }
    }
};

