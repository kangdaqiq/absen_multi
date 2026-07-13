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
        Schema::table('gate_cards', function (Blueprint $table) {
            $table->integer('id_finger')->nullable()->after('uid_rfid');
            $table->string('enroll_finger_status', 20)->default('none')->after('id_finger');
        });

        Schema::create('gate_card_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gate_card_id');
            $table->integer('device_id');
            $table->integer('finger_id');
            $table->timestamps();

            $table->foreign('gate_card_id')->references('id')->on('gate_cards')->onDelete('cascade');
            $table->unique(['device_id', 'finger_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gate_card_fingerprints');

        Schema::table('gate_cards', function (Blueprint $table) {
            $table->dropColumn(['id_finger', 'enroll_finger_status']);
        });
    }
};
