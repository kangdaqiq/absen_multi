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
        if (!Schema::hasTable('storage_files')) {
            Schema::create('storage_files', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->unsignedBigInteger('license_id')->nullable()->index();
                $table->string('disk', 32)->default('r2')->index();
                $table->string('path', 500)->index();
                $table->text('url');
                $table->string('original_name')->nullable();
                $table->unsignedBigInteger('size_bytes')->default(0)->index();
                $table->string('mime_type', 120)->nullable();
                $table->string('category', 60)->default('general')->index(); // kbm, face, siswa, guru, attendance, logo, general
                $table->unsignedBigInteger('uploader_id')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_files');
    }
};
