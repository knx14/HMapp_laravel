<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordChallengeController;
use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('new-password', [NewPasswordChallengeController::class, 'create'])
        ->name('password.challenge');

    Route::post('new-password', [NewPasswordChallengeController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('password.challenge.store');

    Route::get('forgot-password', [PasswordResetController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetController::class, 'sendCode'])
        ->middleware('throttle:5,1')
        ->name('password.email');

    Route::get('reset-password', [PasswordResetController::class, 'edit'])
        ->name('password.reset');

    Route::post('reset-password', [PasswordResetController::class, 'update'])
        ->middleware('throttle:10,1')
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
