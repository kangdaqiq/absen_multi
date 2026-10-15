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
        if (Schema::hasTable('jam_pelajaran')) {
            Schema::table('jam_pelajaran', function (Blueprint $table) {
                $table->string('hari', 255)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('jam_pelajaran')) {
            Schema::table('jam_pelajaran', function (Blueprint $table) {
                $table->string('hari', 50)->nullable()->change();
            });
        }
    }
};
