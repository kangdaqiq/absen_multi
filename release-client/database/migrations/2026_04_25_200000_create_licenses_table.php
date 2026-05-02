<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_key', 32)->unique();
            $table->string('client_name');
            $table->unsignedSmallInteger('max_schools')->default(1);   // 0 = unlimited
            $table->unsignedSmallInteger('max_students')->default(0);  // 0 = unlimited
            $table->date('expired_at')->nullable();                     // null = selamanya
            $table->boolean('is_active')->default(true);
            $table->string('allowed_hostname')->nullable();             // lock per hostname
            $table->text('notes')->nullable();
            $table->timestamp('last_ping_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
