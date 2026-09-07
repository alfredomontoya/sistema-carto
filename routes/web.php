<?php

use App\Http\Controllers\Admin\AreaController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AreaLookupController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserLookupController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::post('/profile/avatar', [ProfileController::class, 'avatar'])->name('profile.avatar');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/usuarios/buscar', [UserLookupController::class, 'search'])->name('users.search');
    Route::get('/areas/buscar', [AreaLookupController::class, 'search'])->name('areas.search');

    Route::get('/comunicaciones', [CommunicationController::class, 'index'])->name('communications.index');
    Route::get('/comunicaciones/crear', [CommunicationController::class, 'create'])->name('communications.create');
    Route::post('/comunicaciones', [CommunicationController::class, 'store'])->name('communications.store');
    Route::get('/comunicaciones/{communication}/editar', [CommunicationController::class, 'edit'])->name('communications.edit');
    Route::put('/comunicaciones/{communication}', [CommunicationController::class, 'update'])->name('communications.update');
    Route::post('/comunicaciones/{communication}/anular', [CommunicationController::class, 'annul'])->name('communications.annul');
    Route::get('/comunicaciones/{communication}/descargar', [CommunicationController::class, 'download'])->name('communications.download');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('settings', [SettingsController::class, 'index'])
            ->middleware('permission:manage settings')
            ->name('settings.index');
        Route::put('settings', [SettingsController::class, 'update'])
            ->middleware('permission:manage settings')
            ->name('settings.update');

        Route::resource('users', UserController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->middleware('permission:manage users');
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])
            ->middleware('permission:manage users')
            ->name('users.reset-password');

        Route::resource('areas', AreaController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('permission:manage areas');

        Route::post('areas/{area}/reset-numbering', [AreaController::class, 'resetNumbering'])
            ->middleware('permission:manage areas')
            ->name('areas.reset-numbering');

        Route::resource('positions', PositionController::class)
            ->only(['store', 'update', 'destroy'])
            ->middleware('permission:manage areas');
    });
});

require __DIR__.'/auth.php';
