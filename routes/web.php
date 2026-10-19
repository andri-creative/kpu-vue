<?php

use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CandidateProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardExportController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\PemilihController;
use App\Http\Controllers\ResetController;
use App\Http\Controllers\StudentCardController;
use App\Http\Controllers\StudentImportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoteResultController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Welcome');
    })->name('home');
});

// Dokumentasi publik — tanpa middleware auth maupun guest,
// sehingga tetap bisa dibuka oleh pengunjung maupun user yang sudah login.
Route::get('documentation', [DocumentationController::class, 'index'])
    ->name('documentation.index');
Route::get('documentation/{slug}', [DocumentationController::class, 'show'])
    ->where('slug', '[a-z0-9\-]+')
    ->name('documentation.show');

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/export/excel', [DashboardExportController::class, 'excel'])->name('dashboard.export.excel');
    Route::get('dashboard/export/pdf', [DashboardExportController::class, 'pdf'])->name('dashboard.export.pdf');
});

Route::middleware(['auth', 'role:kandidat'])->group(function () {
    Route::get('kandidat', CandidateProfileController::class)->name('kandidat');
    Route::patch('kandidat', [CandidateProfileController::class, 'update'])->name('kandidat.update');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('users/import/template', [StudentImportController::class, 'template'])->name('users.import.template');
    Route::get('users/import', [StudentImportController::class, 'create'])->name('users.import');
    Route::post('users/import/preview', [StudentImportController::class, 'preview'])->name('users.import.preview');
    Route::post('users/import', [StudentImportController::class, 'store'])->name('users.import.store');

    Route::post('users/cards/print', [StudentCardController::class, 'print'])->name('users.cards.print');

    // Declared before the resource so it is not captured as users/{user}.
    Route::patch('users/reset-kode-kosong', [UserController::class, 'resetKodeKosong'])->name('users.reset-kode-kosong');

    Route::resource('users', UserController::class);
    Route::patch('users/{user}/reset-kode', [UserController::class, 'resetKode'])->name('users.reset-kode');
    Route::resource('candidates', CandidateController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('candidates/reorder', [CandidateController::class, 'reorder'])->name('candidates.reorder');
    Route::get('hasil', [VoteResultController::class, 'index'])->name('hasil');
    Route::get('reset-pemilih', [ResetController::class, 'index'])->name('reset.index');
    Route::post('reset-pemilih', [ResetController::class, 'reset_voting'])->name('reset.execute');
});

Route::middleware(['auth', 'role:pemilih', 'voting.active'])->group(function () {
    Route::get('pemilih', [PemilihController::class, 'index'])->name('pemilih');
    Route::post('pemilih/vote', [PemilihController::class, 'store'])->name('pemilih.vote');
});

require __DIR__.'/settings.php';
