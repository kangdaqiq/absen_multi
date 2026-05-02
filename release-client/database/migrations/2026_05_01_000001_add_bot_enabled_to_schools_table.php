<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->boolean('bot_enabled')->default(true)->after('wa_enabled');
            // 0 = unlimited, angka = max guru yang boleh pakai bot
            $table->unsignedInteger('bot_user_limit')->default(0)->after('bot_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['bot_enabled', 'bot_user_limit']);
        });
    }
};

