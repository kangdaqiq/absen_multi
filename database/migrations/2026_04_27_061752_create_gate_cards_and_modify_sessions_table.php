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
        Schema::create('gate_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('uid_rfid', 50)->nullable();
            $table->string('name', 100);
            $table->string('enroll_status', 20)->default('done'); // requested, done
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
        });

        Schema::table('teacher_checkout_sessions', function (Blueprint $table) {
            $table->unsignedInteger('teacher_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_checkout_sessions', function (Blueprint $table) {
            $table->unsignedInteger('teacher_id')->nullable(false)->change();
        });
        
        Schema::dropIfExists('gate_cards');
    }
};
