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
