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
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'invoice_token')) {
                $table->string('invoice_token', 64)->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('subscriptions', 'invoice_number')) {
                $table->string('invoice_number', 50)->nullable()->index()->after('invoice_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'invoice_token')) {
                $table->dropColumn('invoice_token');
            }
            if (Schema::hasColumn('subscriptions', 'invoice_number')) {
                $table->dropColumn('invoice_number');
            }
        });
    }
};
