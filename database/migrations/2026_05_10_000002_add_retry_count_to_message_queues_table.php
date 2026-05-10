<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_queues', function (Blueprint $table) {
            $table->unsignedTinyInteger('retry_count')->default(0)->after('last_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('message_queues', function (Blueprint $table) {
            $table->dropColumn('retry_count');
        });
    }
};
