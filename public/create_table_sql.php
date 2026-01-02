<?php
// public/create_table_sql.php
try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->handle(Illuminate\Http\Request::capture());

    $sql = "CREATE TABLE IF NOT EXISTS `guru_fingerprints` (
        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `guru_id` int(10) unsigned NOT NULL,
        `device_id` int(11) NOT NULL,
        `finger_id` int(11) NOT NULL,
        `created_at` timestamp NULL DEFAULT NULL,
        `updated_at` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `guru_fingerprints_device_id_finger_id_unique` (`device_id`,`finger_id`),
        KEY `guru_fingerprints_guru_id_index` (`guru_id`),
        KEY `guru_fingerprints_device_id_index` (`device_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    Illuminate\Support\Facades\DB::statement($sql);
    echo "Table created successfully.";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
