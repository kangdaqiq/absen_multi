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
            $table->unsignedInteger('guru_id')->nullable()->after('school_id');
            $table->string('name', 100)->nullable()->change();

            $table->foreign('guru_id')->references('id')->on('guru')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gate_cards', function (Blueprint $table) {
            $table->dropForeign(['guru_id']);
            $table->dropColumn('guru_id');
            $table->string('name', 100)->nullable(false)->change();
        });
    }
};
