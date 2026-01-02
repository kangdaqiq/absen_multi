<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "Columns according to Laravel Schema:\n";
$cols = Illuminate\Support\Facades\Schema::getColumnListing('guru');
print_r($cols);
