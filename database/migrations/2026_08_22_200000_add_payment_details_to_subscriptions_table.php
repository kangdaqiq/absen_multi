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
            if (!Schema::hasColumn('subscriptions', 'payment_ref')) {
                $table->string('payment_ref')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('subscriptions', 'unique_code')) {
                $table->integer('unique_code')->default(0)->after('amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'payment_ref')) {
                $table->dropColumn('payment_ref');
            }
            if (Schema::hasColumn('subscriptions', 'unique_code')) {
                $table->dropColumn('unique_code');
            }
        });
    }
};
