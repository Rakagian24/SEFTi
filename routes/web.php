<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ArPartnerController;
use App\Http\Controllers\BisnisPartnerController;
use App\Http\Controllers\BankController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('bisnis-partners', BisnisPartnerController::class);
    Route::get('bisnis-partners/{bisnis_partner}/log', [BisnisPartnerController::class, 'log']);

    Route::resource('banks', BankController::class);
    Route::get('banks/{bank}/log-activity', [BankController::class, 'logActivity']);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
