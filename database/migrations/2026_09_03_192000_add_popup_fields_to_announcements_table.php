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
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('type')->default('info')->after('title'); // info, release, feature, warning, maintenance
            $table->string('version')->nullable()->after('type'); // e.g. v2.4.0
            $table->boolean('is_popup')->default(true)->after('is_active');
            $table->string('action_url')->nullable()->after('is_popup');
            $table->string('action_text')->nullable()->after('action_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn(['type', 'version', 'is_popup', 'action_url', 'action_text']);
        });
    }
};
