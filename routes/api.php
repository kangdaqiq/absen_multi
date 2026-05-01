<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RfidController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::any('/rfid', [RfidController::class, 'handle']);
Route::any('/fingerprint', [App\Http\Controllers\Api\FingerprintController::class, 'handle']);
Route::any('/fingerprint/check-enroll', [App\Http\Controllers\Api\FingerprintController::class, 'checkEnrollRequest']);

// License validation (public — for self-hosted clients)
Route::post('/license/validate', [App\Http\Controllers\Api\LicenseValidateController::class, 'validate']);

Route::get('/debug-db', function() { return DB::connection()->getDatabaseName(); });
Route::get('/debug-db-full', function() { return response()->json(['db' => DB::connection()->getDatabaseName(), 'host' => config('database.connections.mysql.host'), 'user' => config('database.connections.mysql.username'), 'port' => config('database.connections.mysql.port')]); });
