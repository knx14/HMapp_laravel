<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\FarmController;

Route::middleware(['cognito.jwt'])->group(function () {
    Route::get('/me', [MeController::class, 'show']);
    Route::put('/me', [MeController::class, 'update']);
    Route::patch('/me', [MeController::class, 'update']);
    Route::post('/farms', [FarmController::class, 'store']);
    Route::get('/farms', [FarmController::class, 'index']);
    Route::put('/farms/{farm}', [FarmController::class, 'update']);
    Route::patch('/farms/{farm}', [FarmController::class, 'update']);
});

