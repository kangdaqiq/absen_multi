<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('guru', function (Blueprint $table) {
            if (!Schema::hasColumn('guru', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('school_id');
                // You can add foreign key if you want, but since users table might be cleared, 
                // nullable without strict FK constraint is often safer in some legacy apps.
                // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('guru', function (Blueprint $table) {
            if (Schema::hasColumn('guru', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }
};
