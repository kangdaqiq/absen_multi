<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('message_queues', function (Blueprint $table) {
            if (!Schema::hasColumn('message_queues', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->after('id');
                $table->index('school_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_queues', function (Blueprint $table) {
            //
        });
    }
};
