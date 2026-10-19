<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah dok_kbm_enabled pada tabel schools
        if (Schema::hasTable('schools')) {
            Schema::table('schools', function (Blueprint $table) {
                if (!Schema::hasColumn('schools', 'dok_kbm_enabled')) {
                    $table->boolean('dok_kbm_enabled')->default(false)->after('face_enabled');
                }
            });
        }

        // 2. Tambah dok_kbm_enabled pada tabel packages jika ada
        if (Schema::hasTable('packages')) {
            Schema::table('packages', function (Blueprint $table) {
                if (!Schema::hasColumn('packages', 'dok_kbm_enabled')) {
                    $table->boolean('dok_kbm_enabled')->default(false)->after('face_enabled');
                }
            });
        }

        // 3. Tambah dok_kbm_enabled pada tabel licenses jika ada
        if (Schema::hasTable('licenses')) {
            Schema::table('licenses', function (Blueprint $table) {
                if (!Schema::hasColumn('licenses', 'dok_kbm_enabled')) {
                    $table->boolean('dok_kbm_enabled')->default(false)->after('face_enabled');
                }
            });
        }

        // 4. Tambah kolom dokumentasi (JSON) pada tabel jurnal_pelajaran
        if (Schema::hasTable('jurnal_pelajaran')) {
            Schema::table('jurnal_pelajaran', function (Blueprint $table) {
                if (!Schema::hasColumn('jurnal_pelajaran', 'dokumentasi')) {
                    $table->json('dokumentasi')->nullable()->after('catatan');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('schools')) {
            Schema::table('schools', function (Blueprint $table) {
                if (Schema::hasColumn('schools', 'dok_kbm_enabled')) {
                    $table->dropColumn('dok_kbm_enabled');
                }
            });
        }

        if (Schema::hasTable('packages')) {
            Schema::table('packages', function (Blueprint $table) {
                if (Schema::hasColumn('packages', 'dok_kbm_enabled')) {
                    $table->dropColumn('dok_kbm_enabled');
                }
            });
        }

        if (Schema::hasTable('licenses')) {
            Schema::table('licenses', function (Blueprint $table) {
                if (Schema::hasColumn('licenses', 'dok_kbm_enabled')) {
                    $table->dropColumn('dok_kbm_enabled');
                }
            });
        }

        if (Schema::hasTable('jurnal_pelajaran')) {
            Schema::table('jurnal_pelajaran', function (Blueprint $table) {
                if (Schema::hasColumn('jurnal_pelajaran', 'dokumentasi')) {
                    $table->dropColumn('dokumentasi');
                }
            });
        }
    }
};
