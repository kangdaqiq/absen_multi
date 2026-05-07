<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('api_logs', function (Blueprint $table) {
            // Simpan raw API key penuh (untuk kasus auth_failed — tidak punya school_id)
            // api_key yang ada sudah ada, tapi kita tambah index untuk filter cepat
            $table->index('action'); // untuk filter auth_failed cepat
        });
    }

    public function down()
    {
        Schema::table('api_logs', function (Blueprint $table) {
            $table->dropIndex(['action']);
        });
    }
};
