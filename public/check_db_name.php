<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "DB Database: " . config('database.connections.mysql.database') . "\n";
echo "DB Host: " . config('database.connections.mysql.host') . "\n";
echo "DB Port: " . config('database.connections.mysql.port') . "\n";
