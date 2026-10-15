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
        if (!Schema::hasTable('kelas_mapel')) {
            Schema::create('kelas_mapel', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mapel_id')->constrained('mapel')->onDelete('cascade');
                $table->unsignedInteger('kelas_id');
                $table->foreign('kelas_id')->references('id')->on('kelas')->onDelete('cascade');
                $table->timestamps();

                $table->unique(['mapel_id', 'kelas_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas_mapel');
    }
};
