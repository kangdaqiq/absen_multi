<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'teacher', 'student', 'wali_kelas', 'waka_kurikulum') NOT NULL DEFAULT 'student'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Reverting this might cause data loss if there are users with the new roles.
        // It's safer not to strictly revert the enum removal unless necessary,
        // but for completeness here is the SQL statement.
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'teacher', 'student') NOT NULL DEFAULT 'student'");
    }
};
