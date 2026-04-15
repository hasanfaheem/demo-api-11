<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\InstrumentController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;

// Auth routes — rate limited to 10 attempts per minute
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

// Protected routes — requires valid Sanctum token
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/patients',    [PatientController::class, 'store']);
    Route::post('/instruments', [InstrumentController::class, 'store']);

    Route::prefix('/patients/{patient}')->group(function () {
        Route::post('/submissions',              [SubmissionController::class, 'store']);
        Route::get('/submissions',               [SubmissionController::class, 'index']);
        Route::get('/submissions/{submission}',  [SubmissionController::class, 'show']);
        Route::get('/summary',                   [SubmissionController::class, 'summary']);
    });
});