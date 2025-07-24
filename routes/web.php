<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ArPartnerController;
use App\Http\Controllers\BisnisPartnerController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\PphController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\BankMasukController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('bisnis-partners', BisnisPartnerController::class);
    Route::get('bisnis-partners/{bisnis_partner}/logs', [BisnisPartnerController::class, 'logs']);

    Route::resource('banks', BankController::class);
    Route::get('banks/{bank}/log-activity', [BankController::class, 'logActivity']);
    Route::patch('banks/{bank}/toggle-status', [BankController::class, 'toggleStatus'])->name('banks.toggle-status');
    Route::get('/banks/{bank}/logs', [BankController::class, 'logs'])->name('banks.logs');

    Route::resource('ar-partners', ArPartnerController::class);
    Route::get('/ar-partners/{ar_partner}/logs', [ArPartnerController::class, 'logs']);

    Route::resource('pengeluarans', \App\Http\Controllers\PengeluaranController::class);
    Route::put('/pengeluarans/{pengeluaran}/toggle-status', [PengeluaranController::class, 'toggleStatus'])->name('pengeluarans.toggleStatus');
    Route::resource('pphs', \App\Http\Controllers\PphController::class);
    Route::patch('pphs/{pph}/toggle-status', [\App\Http\Controllers\PphController::class, 'toggleStatus'])->name('pphs.toggle-status');
    Route::resource('bank-accounts', \App\Http\Controllers\BankAccountController::class);
    Route::patch('bank-accounts/{bank_account}/toggle-status', [\App\Http\Controllers\BankAccountController::class, 'toggleStatus'])->name('bank-accounts.toggle-status');
    Route::resource('suppliers', \App\Http\Controllers\SupplierController::class);
    Route::get('/bank-accounts/{bank_account}/logs', [BankAccountController::class, 'logs'])->name('bank-accounts.logs');
    Route::get('/pengeluarans/{pengeluaran}/logs', [PengeluaranController::class, 'logs'])->name('pengeluarans.logs');
    Route::get('/pphs/{pph}/logs', [PphController::class, 'logs'])->name('pphs.logs');
    Route::get('/suppliers/{supplier}/logs', [SupplierController::class, 'logs'])->name('suppliers.logs');

    // Master Data Routes
    Route::resource('departments', DepartmentController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('users', \App\Http\Controllers\UserController::class);
    Route::patch('users/{user}/toggle-status', [\App\Http\Controllers\UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::patch('roles/{role}/toggle-status', [RoleController::class, 'toggleStatus'])->name('roles.toggle-status');
    Route::patch('departments/{department}/toggle-status', [DepartmentController::class, 'toggleStatus'])->name('departments.toggle-status');

    // Test route for message panel
    Route::get('message-test', function () {
        return Inertia::render('MessageTest');
    })->name('message-test');

    Route::get('settings/message', function () {
        return Inertia::render('settings/Message');
    })->name('settings.message');

    Route::get('/bank-masuk/next-number', [\App\Http\Controllers\BankMasukController::class, 'getNextNumber']);
    Route::resource('bank-masuk', BankMasukController::class);
    Route::get('bank-masuk/{bank_masuk}/download', [BankMasukController::class, 'download'])->name('bank-masuk.download');
    Route::get('bank-masuk/{bank_masuk}/log', [BankMasukController::class, 'log'])->name('bank-masuk.log');
    Route::post('bank-masuk/export-excel', [BankMasukController::class, 'exportExcel'])->name('bank-masuk.export-excel');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
