<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            // Flag to mark attendance records auto-created as Alpha by DailyReportCommand
            // Allows offline sync (RFID scan) to safely override this status
            $table->boolean('is_auto_alpha')->default(false)->after('is_auto_extended');
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn('is_auto_alpha');
        });
    }
};
