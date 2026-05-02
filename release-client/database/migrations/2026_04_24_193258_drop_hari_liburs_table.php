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
        Schema::dropIfExists('hari_liburs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('hari_liburs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->date('tanggal');
            $table->string('keterangan');
            $table->timestamps();
        });
    }
};
