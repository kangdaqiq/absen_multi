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
        Schema::table('api_keys', function (Blueprint $table) {
            $table->unsignedSmallInteger('finger_id_min')->default(1)->nullable()->after('type');
            $table->unsignedSmallInteger('finger_id_max')->default(200)->nullable()->after('finger_id_min');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropColumn(['finger_id_min', 'finger_id_max']);
        });
    }
};
