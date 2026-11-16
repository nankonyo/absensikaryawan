<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Attendance API
Route::middleware('auth:sanctum')->prefix('attendance')->group(function () {
    Route::post('/check-in', [App\Http\Controllers\Api\AttendanceController::class, 'checkIn']);
    Route::post('/check-out', [App\Http\Controllers\Api\AttendanceController::class, 'checkOut']);
    Route::get('/today', [App\Http\Controllers\Api\AttendanceController::class, 'today']);
});

// GPS Validation API
Route::middleware('auth:sanctum')->prefix('gps')->group(function () {
    Route::post('/validate', [App\Http\Controllers\Api\GpsController::class, 'validate']);
});
