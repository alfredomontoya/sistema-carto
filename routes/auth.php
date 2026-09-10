<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\PasswordRecoveryController;
use App\Http\Middleware\RequirePasswordChange;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('recuperar', [PasswordRecoveryController::class, 'create'])
        ->name('recovery.request');

    Route::post('recuperar', [PasswordRecoveryController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('recovery.send');

    Route::get('recuperar/verificar/{user}', [PasswordRecoveryController::class, 'verify'])
        ->middleware('signed')
        ->name('recovery.verify');

    Route::get('recuperar/{token}', [PasswordRecoveryController::class, 'reset'])
        ->name('recovery.reset');

    Route::post('recuperar/restablecer', [PasswordRecoveryController::class, 'update'])
        ->middleware('throttle:5,1')
        ->name('recovery.update');
});

Route::middleware(['auth', RequirePasswordChange::class])->group(function () {
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
