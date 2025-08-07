<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
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
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PerihalController;
use App\Http\Controllers\BankMatchingController;
use Illuminate\Http\Request;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    // Bisnis Partner - Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:bisnis_partner'])->group(function () {
        Route::resource('bisnis-partners', BisnisPartnerController::class);
        Route::get('bisnis-partners/{bisnis_partner}/logs', [BisnisPartnerController::class, 'logs']);
    });

    // Bank - Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:bank'])->group(function () {
        Route::resource('banks', BankController::class);
        Route::get('banks/{bank}/log-activity', [BankController::class, 'logActivity']);
        Route::patch('banks/{bank}/toggle-status', [BankController::class, 'toggleStatus'])->name('banks.toggle-status');
        Route::get('/banks/{bank}/logs', [BankController::class, 'logs'])->name('banks.logs');
    });

    // AR Partner - Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:bisnis_partner'])->group(function () {
        Route::resource('ar-partners', ArPartnerController::class);
        Route::get('/ar-partners/{ar_partner}/logs', [ArPartnerController::class, 'logs']);
    });

    // Pengeluaran - Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:payment_voucher'])->group(function () {
        Route::resource('pengeluarans', \App\Http\Controllers\PengeluaranController::class);
        Route::put('/pengeluarans/{pengeluaran}/toggle-status', [PengeluaranController::class, 'toggleStatus'])->name('pengeluarans.toggleStatus');
        Route::get('/pengeluarans/{pengeluaran}/logs', [PengeluaranController::class, 'logs'])->name('pengeluarans.logs');
    });

    // PPH - Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:payment_voucher'])->group(function () {
        Route::resource('pphs', \App\Http\Controllers\PphController::class);
        Route::patch('pphs/{pph}/toggle-status', [\App\Http\Controllers\PphController::class, 'toggleStatus'])->name('pphs.toggle-status');
        Route::get('/pphs/{pph}/logs', [PphController::class, 'logs'])->name('pphs.logs');
    });

    // Bank Account - Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:bank'])->group(function () {
        Route::resource('bank-accounts', \App\Http\Controllers\BankAccountController::class);
        Route::patch('bank-accounts/{bank_account}/toggle-status', [\App\Http\Controllers\BankAccountController::class, 'toggleStatus'])->name('bank-accounts.toggle-status');
        Route::get('/bank-accounts/{bank_account}/logs', [BankAccountController::class, 'logs'])->name('bank-accounts.logs');
    });

    // Supplier - Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:supplier'])->group(function () {
        Route::resource('suppliers', \App\Http\Controllers\SupplierController::class);
        Route::get('/suppliers/{supplier}/logs', [SupplierController::class, 'logs'])->name('suppliers.logs');
    });

    // Master Data Routes - Admin only
    Route::middleware(['role:*'])->group(function () {
        Route::resource('departments', DepartmentController::class);
        Route::resource('roles', RoleController::class);
        Route::resource('users', \App\Http\Controllers\UserController::class);
        Route::patch('users/{user}/toggle-status', [\App\Http\Controllers\UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::patch('roles/{role}/toggle-status', [RoleController::class, 'toggleStatus'])->name('roles.toggle-status');
        Route::patch('departments/{department}/toggle-status', [DepartmentController::class, 'toggleStatus'])->name('departments.toggle-status');
    });

    // Test route for message panel - Admin only
    Route::middleware(['role:*'])->group(function () {
        Route::get('message-test', function () {
            return Inertia::render('MessageTest');
        })->name('message-test');

        Route::get('settings/message', function () {
            return Inertia::render('settings/Message');
        })->name('settings.message');
    });

    // Bank Masuk - Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:bank_masuk'])->group(function () {
        Route::get('/bank-masuk/next-number', [\App\Http\Controllers\BankMasukController::class, 'getNextNumber']);
        Route::get('/bank-masuk/bank-accounts-by-department', [\App\Http\Controllers\BankMasukController::class, 'getBankAccountsByDepartment']);
        Route::get('/bank-masuk/ar-partners', [\App\Http\Controllers\BankMasukController::class, 'getArPartners']);
        Route::resource('bank-masuk', BankMasukController::class);
        Route::get('bank-masuk/{bank_masuk}/download', [BankMasukController::class, 'download'])->name('bank-masuk.download');
        Route::get('bank-masuk/{bank_masuk}/log', [BankMasukController::class, 'log'])->name('bank-masuk.log');
        Route::post('bank-masuk/export-excel', [BankMasukController::class, 'exportExcel'])->name('bank-masuk.export-excel');
    });
});

Route::middleware(['auth'])->group(function () {
    // Purchase Order - Staff Toko, Kepala Toko, Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:purchase_order'])->group(function () {
        Route::resource('purchase-orders', PurchaseOrderController::class);
        Route::post('purchase-orders/send', [PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
        Route::get('purchase-orders/{id}/download', [PurchaseOrderController::class, 'download'])->name('purchase-orders.download');
        Route::get('purchase-orders/{id}/log', [PurchaseOrderController::class, 'log'])->name('purchase-orders.log');
    });

    // Perihal - Admin only
    Route::middleware(['role:*'])->group(function () {
        Route::resource('perihals', PerihalController::class);
        Route::patch('perihals/{perihal}/toggle-status', [PerihalController::class, 'toggleStatus'])->name('perihals.toggle-status');
    });

    // Bank Matching - Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:bank_masuk'])->group(function () {
        Route::get('bank-matching', [BankMatchingController::class, 'index'])->name('bank-matching.index');
        Route::post('bank-matching', [BankMatchingController::class, 'store'])->name('bank-matching.store')->middleware('web');
        Route::get('bank-matching/export-excel', [BankMatchingController::class, 'exportExcel'])->name('bank-matching.export-excel');
        Route::get('bank-matching/matched-data', [BankMatchingController::class, 'getMatchedData'])->name('bank-matching.matched-data');
        Route::get('bank-matching/all-invoices', [BankMatchingController::class, 'getAllInvoices'])->name('bank-matching.all-invoices');
        Route::get('bank-matching/test-db', [BankMatchingController::class, 'testDatabaseConnection'])->name('bank-matching.test-db');
        Route::get('bank-matching/test-simple', [BankMatchingController::class, 'testSimple'])->name('bank-matching.test-simple');
        Route::get('bank-matching/test-connection', [BankMatchingController::class, 'testConnection'])->name('bank-matching.test-connection');
        Route::get('bank-matching/test-basic', [BankMatchingController::class, 'testBasic'])->name('bank-matching.test-basic');
        Route::get('bank-matching/test', [BankMatchingController::class, 'test'])->name('bank-matching.test');
        Route::post('bank-matching/test-store', [BankMatchingController::class, 'testStore'])->name('bank-matching.test-store')->middleware('web');
    });
});




require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
