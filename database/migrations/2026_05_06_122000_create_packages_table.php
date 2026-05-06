<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price_monthly', 12, 2)->default(0);
            $table->decimal('price_yearly', 12, 2)->default(0);
            $table->unsignedInteger('student_limit')->nullable()->comment('0 or null for unlimited');
            $table->unsignedInteger('teacher_limit')->nullable()->comment('0 or null for unlimited');
            $table->unsignedInteger('bot_user_limit')->default(0)->comment('0 or null for unlimited');
            $table->boolean('wa_enabled')->default(false);
            $table->boolean('bot_enabled')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
