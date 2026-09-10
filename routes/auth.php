<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\PasswordRecoveryController;
use App\Http\Middleware\EnsurePasswordRecoveryEnabled;
use App\Http\Middleware\RequirePasswordChange;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('recuperar', [PasswordRecoveryController::class, 'create'])
        ->middleware(EnsurePasswordRecoveryEnabled::class)
        ->name('recovery.request');

    Route::post('recuperar', [PasswordRecoveryController::class, 'store'])
        ->middleware([EnsurePasswordRecoveryEnabled::class, 'throttle:5,1'])
        ->name('recovery.send');

    Route::get('recuperar/verificar/{user}/{email}', [PasswordRecoveryController::class, 'verify'])
        ->middleware(['signed', EnsurePasswordRecoveryEnabled::class, 'throttle:5,1'])
        ->where('email', '.*')
        ->name('recovery.verify');

    Route::get('recuperar/{token}', [PasswordRecoveryController::class, 'reset'])
        ->middleware(EnsurePasswordRecoveryEnabled::class)
        ->name('recovery.reset');

    Route::post('recuperar/restablecer', [PasswordRecoveryController::class, 'update'])
        ->middleware([EnsurePasswordRecoveryEnabled::class, 'throttle:5,1'])
        ->name('recovery.update');
});

Route::middleware(['auth', RequirePasswordChange::class])->group(function () {
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
