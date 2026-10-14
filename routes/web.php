<?php

use App\Http\Controllers\Admin\AreaController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\CommunicationTypeController;
use App\Http\Controllers\Admin\NumberingController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AreaLookupController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserLookupController;
use App\Http\Middleware\RequirePasswordChange;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', RequirePasswordChange::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/reporte', [DashboardController::class, 'report'])->name('dashboard.report');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::post('/profile/avatar', [ProfileController::class, 'avatar'])->name('profile.avatar');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/usuarios/buscar', [UserLookupController::class, 'search'])
        ->middleware('throttle:30,1')
        ->name('users.search');
    Route::get('/areas/buscar', [AreaLookupController::class, 'search'])
        ->middleware('throttle:30,1')
        ->name('areas.search');

    Route::get('/comunicaciones', [CommunicationController::class, 'index'])->name('communications.index');
    Route::get('/comunicaciones/crear', [CommunicationController::class, 'create'])->name('communications.create');
    Route::post('/comunicaciones', [CommunicationController::class, 'store'])->name('communications.store');
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

        Route::get('backups', [BackupController::class, 'index'])
            ->middleware('permission:manage backups')
            ->name('backups.index');
        Route::post('backups', [BackupController::class, 'store'])
            ->middleware('permission:manage backups')
            ->name('backups.store');
        Route::post('backups/restaurar', [BackupController::class, 'restore'])
            ->middleware('permission:manage backups')
            ->name('backups.restore');
        Route::post('backups/restaurar-archivo', [BackupController::class, 'uploadRestore'])
            ->middleware('permission:manage backups')
            ->name('backups.upload-restore');
        Route::put('backups/ajustes', [BackupController::class, 'update'])
            ->middleware('permission:manage backups')
            ->name('backups.update');
        Route::get('backups/{file}/descargar', [BackupController::class, 'download'])
            ->middleware('permission:manage backups')
            ->name('backups.download');

        Route::resource('areas', AreaController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('permission:manage areas');

        Route::get('numbering', [NumberingController::class, 'index'])
            ->middleware('permission:manage areas')
            ->name('numbering.index');
        Route::post('numbering/{area}', [NumberingController::class, 'setNumber'])
            ->middleware('permission:manage areas')
            ->name('numbering.set');
        Route::post('numbering/{area}/reset', [NumberingController::class, 'resetAll'])
            ->middleware('permission:manage areas')
            ->name('numbering.reset');

        Route::resource('communication-types', CommunicationTypeController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('permission:manage areas');

        Route::resource('positions', PositionController::class)
            ->only(['store', 'update', 'destroy'])
            ->middleware('permission:manage areas');
    });
});

require __DIR__.'/auth.php';
