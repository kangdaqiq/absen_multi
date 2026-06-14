<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_queues', function (Blueprint $table) {
            if (!Schema::hasColumn('message_queues', 'scheduled_at')) {
                // Waktu minimal pesan boleh dikirim (null = kirim segera)
                $table->timestamp('scheduled_at')->nullable()->after('status');
                // Index composite untuk performa query di ProcessWhatsappQueue
                $table->index(['status', 'scheduled_at'], 'mq_status_scheduled_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('message_queues', function (Blueprint $table) {
            if (Schema::hasColumn('message_queues', 'scheduled_at')) {
                $table->dropIndex('mq_status_scheduled_idx');
                $table->dropColumn('scheduled_at');
            }
        });
    }
};
