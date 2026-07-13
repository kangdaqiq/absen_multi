<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('telegram_bot_token')->nullable()->after('wa_enabled');
            $table->boolean('telegram_enabled')->default(false)->after('telegram_bot_token');
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->after('wa_ortu');
            $table->string('telegram_ortu_chat_id')->nullable()->after('telegram_chat_id');
        });

        Schema::table('guru', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->after('no_wa');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['telegram_bot_token', 'telegram_enabled']);
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn(['telegram_chat_id', 'telegram_ortu_chat_id']);
        });

        Schema::table('guru', function (Blueprint $table) {
            $table->dropColumn('telegram_chat_id');
        });
    }
};
