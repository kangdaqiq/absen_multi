<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        try {
            Schema::table('settings', function (Blueprint $table) {
                // Drop the foreign key constraint
                $table->dropForeign(['school_id']);
            });
        } catch (\Exception $e) {
            // Ignore if it's already dropped
        }
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Restore the foreign key constraint
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
        });
    }
};
