<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('penanganan_pelanggarans')) {
            Schema::table('penanganan_pelanggarans', function (Blueprint $table) {
                $table->unsignedBigInteger('pelanggaran_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('penanganan_pelanggarans')) {
            Schema::table('penanganan_pelanggarans', function (Blueprint $table) {
                $table->unsignedBigInteger('pelanggaran_id')->nullable(false)->change();
            });
        }
    }
};

