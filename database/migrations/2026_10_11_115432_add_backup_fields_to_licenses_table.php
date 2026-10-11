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
        Schema::table('licenses', function (Blueprint $table) {
            $table->timestamp('last_backup_at')->nullable()->after('last_ping_at');
            $table->string('backup_file')->nullable()->after('last_backup_at');
            $table->unsignedBigInteger('backup_size')->nullable()->after('backup_file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn(['last_backup_at', 'backup_file', 'backup_size']);
        });
    }
};
