<?php

use App\Http\Controllers\Settings\AdminController;
use App\Http\Controllers\Settings\BrandingController;
use App\Http\Controllers\Settings\DatabaseController;
use App\Http\Controllers\Settings\FileController;
use App\Http\Controllers\Settings\FilePreviewController;
use App\Http\Controllers\Settings\LogController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->name('security.edit');

    Route::patch('settings/kode', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-kode.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('settings/admins', [AdminController::class, 'index'])->name('settings.admins.index');
    Route::post('settings/admins', [AdminController::class, 'store'])->name('settings.admins.store');
    Route::put('settings/admins/{admin}', [AdminController::class, 'update'])->name('settings.admins.update');
    Route::delete('settings/admins/{admin}', [AdminController::class, 'destroy'])->name('settings.admins.destroy');

    Route::get('settings/database', [DatabaseController::class, 'index'])->name('settings.database.index');
    Route::post('settings/database/vacuum', [DatabaseController::class, 'vacuum'])->name('settings.database.vacuum');
    Route::post('settings/database/reset', [DatabaseController::class, 'resetTable'])->name('settings.database.reset');
    Route::post('settings/database/reset-all', [DatabaseController::class, 'resetAll'])->name('settings.database.reset-all');

    Route::get('settings/logs', [LogController::class, 'index'])->name('settings.logs.index');
    Route::get('settings/logs/tail', [LogController::class, 'tail'])->name('settings.logs.tail');
    Route::post('settings/logs/reset', [LogController::class, 'reset'])->name('settings.logs.reset');
    Route::post('settings/storage/clean', [LogController::class, 'cleanStorage'])->name('settings.storage.clean');
    Route::post('settings/storage/clean-all', [LogController::class, 'cleanAllStorage'])->name('settings.storage.clean-all');

    Route::get('settings/files', [FileController::class, 'index'])->name('settings.files.index');
    Route::get('settings/files/preview', [FilePreviewController::class, 'show'])->name('settings.files.preview');
    Route::delete('settings/files', [FileController::class, 'destroy'])->name('settings.files.destroy');

    Route::get('settings/branding', [BrandingController::class, 'edit'])->name('settings.branding.edit');
    Route::post('settings/branding', [BrandingController::class, 'update'])->name('settings.branding.update');
    Route::post('settings/branding/reset', [BrandingController::class, 'reset'])->name('settings.branding.reset');
});
