<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ArPartnerController;
use App\Http\Controllers\BisnisPartnerController;
use App\Http\Controllers\BankController;

Route::get('/', function () {
    return Inertia::render('login');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('bisnis-partners', BisnisPartnerController::class);
    Route::get('bisnis-partners/{bisnis_partner}/log', [BisnisPartnerController::class, 'log']);

    Route::resource('banks', BankController::class);
    Route::get('banks/{bank}/log-activity', [BankController::class, 'logActivity']);
    Route::patch('banks/{bank}/toggle-status', [BankController::class, 'toggleStatus'])->name('banks.toggle-status');

    Route::resource('ar-partners', ArPartnerController::class);
    Route::get('ar-partners/{ar_partner}/log-activity', [ArPartnerController::class, 'logActivity']);

    Route::resource('pengeluarans', \App\Http\Controllers\PengeluaranController::class);
    Route::resource('pphs', \App\Http\Controllers\PphController::class);
    Route::patch('pphs/{pph}/toggle-status', [\App\Http\Controllers\PphController::class, 'toggleStatus'])->name('pphs.toggle-status');
    Route::resource('bank-accounts', \App\Http\Controllers\BankAccountController::class);
    Route::patch('bank-accounts/{bank_account}/toggle-status', [\App\Http\Controllers\BankAccountController::class, 'toggleStatus'])->name('bank-accounts.toggle-status');
    Route::resource('suppliers', \App\Http\Controllers\SupplierController::class);

    // Test route for message panel
    Route::get('message-test', function () {
        return Inertia::render('MessageTest');
    })->name('message-test');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
