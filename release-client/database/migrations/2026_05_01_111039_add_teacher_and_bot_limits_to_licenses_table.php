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
        Schema::table('licenses', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_teachers')->default(0)->after('max_students')->comment('0 means unlimited');
            $table->unsignedSmallInteger('max_bot_users')->default(0)->after('max_teachers')->comment('0 means unlimited');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn(['max_teachers', 'max_bot_users']);
        });
    }
};
