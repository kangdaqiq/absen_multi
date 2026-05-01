<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class MigrateFullCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:full';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop all tables, load base schema via PDO, run migrations, and seed (Self-Hosted setup)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!config('app.mode') === 'self_hosted' && !app()->environment('local')) {
            $this->error('Perintah ini hanya dapat dijalankan di environment self_hosted atau local!');
            return;
        }

        if (!$this->confirm('PERINGATAN: Perintah ini akan menghapus SELURUH data di database dan memulai dari awal. Yakin ingin melanjutkan?')) {
            $this->info('Dibatalkan.');
            return;
        }

        $this->info('1. Menghapus seluruh tabel di database...');
        Schema::dropAllTables();

        $schemaPath = database_path('schema/mysql-schema.sql');
        
        if (File::exists($schemaPath)) {
            $this->info('2. Mengimpor tabel dasar dari mysql-schema.sql...');
            $sql = File::get($schemaPath);
            DB::unprepared($sql);
        } else {
            $this->warn('File database/schema/mysql-schema.sql tidak ditemukan. Menggunakan migration standar saja.');
        }

        $this->info('3. Menjalankan migrations...');
        $this->call('migrate', ['--force' => true]);

        $this->info('4. Menjalankan database seeder...');
        $this->call('db:seed', ['--force' => true]);

        $this->info('Selesai! Database Anda sekarang fresh (full migrate).');
    }
}
