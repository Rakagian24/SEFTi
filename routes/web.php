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
        Route::post('/ar-partners/migrate', [ArPartnerController::class, 'migrate'])->name('ar-partners.migrate');
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
        Route::get('bank-matching/export-matched-data', [BankMatchingController::class, 'exportMatchedData'])->name('bank-matching.export-matched-data');
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

// Test route for bank masuk summary
Route::get('/test-bank-masuk-summary', function () {
    try {
        // Check basic data
        $totalRecords = \App\Models\BankMasuk::count();
        $activeRecords = \App\Models\BankMasuk::where('status', 'aktif')->count();

        // Check if there are any records at all
        if ($activeRecords == 0) {
            return response()->json([
                'error' => 'No active bank masuk records found',
                'total_records' => $totalRecords,
                'active_records' => $activeRecords,
                'status' => 'No data to summarize'
            ]);
        }

        // Try simple summary first
        $simpleSummary = \App\Models\BankMasuk::where('status', 'aktif')
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(nilai) as total_nilai,
                SUM(CASE WHEN match_date IS NOT NULL THEN 1 ELSE 0 END) as total_matched
            ')
            ->first();

        // Try with joins
        $summaryWithJoins = \App\Models\BankMasuk::where('bank_masuks.status', 'aktif')
            ->join('bank_accounts', 'bank_masuks.bank_account_id', '=', 'bank_accounts.id')
            ->join('banks', 'bank_accounts.bank_id', '=', 'banks.id')
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(CASE WHEN banks.currency = "IDR" THEN bank_masuks.nilai ELSE 0 END) as total_idr,
                SUM(CASE WHEN banks.currency = "USD" THEN bank_masuks.nilai ELSE 0 END) as total_usd,
                SUM(CASE WHEN bank_masuks.match_date IS NOT NULL THEN 1 ELSE 0 END) as total_matched
            ')
            ->first();

        // Get sample records
        $sampleRecords = \App\Models\BankMasuk::where('status', 'aktif')
            ->with(['bankAccount.bank'])
            ->take(3)
            ->get()
            ->map(function($record) {
                return [
                    'id' => $record->id,
                    'no_bm' => $record->no_bm,
                    'nilai' => $record->nilai,
                    'status' => $record->status,
                    'bank_account_id' => $record->bank_account_id,
                    'bank_currency' => $record->bankAccount->bank->currency ?? 'N/A',
                    'match_date' => $record->match_date
                ];
            });

        return response()->json([
            'success' => true,
            'total_records' => $totalRecords,
            'active_records' => $activeRecords,
            'simple_summary' => $simpleSummary,
            'summary_with_joins' => $summaryWithJoins,
            'sample_records' => $sampleRecords,
            'query_sql' => \App\Models\BankMasuk::where('status', 'aktif')->toSql(),
            'query_bindings' => \App\Models\BankMasuk::where('status', 'aktif')->getBindings()
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});

// Test route that bypasses DepartmentScope
Route::get('/test-bank-masuk-summary-no-scope', function () {
    try {
        // Bypass DepartmentScope by using withoutGlobalScope
        $totalRecords = \App\Models\BankMasuk::withoutGlobalScope(\App\Scopes\DepartmentScope::class)->count();
        $activeRecords = \App\Models\BankMasuk::withoutGlobalScope(\App\Scopes\DepartmentScope::class)->where('status', 'aktif')->count();

        // Check if there are any records at all
        if ($activeRecords == 0) {
            return response()->json([
                'error' => 'No active bank masuk records found (even without scope)',
                'total_records' => $totalRecords,
                'active_records' => $activeRecords,
                'status' => 'No data to summarize'
            ]);
        }

        // Try summary without scope
        $summaryWithoutScope = \App\Models\BankMasuk::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
            ->where('status', 'aktif')
            ->join('bank_accounts', 'bank_masuks.bank_account_id', '=', 'bank_accounts.id')
            ->join('banks', 'bank_accounts.bank_id', '=', 'banks.id')
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(CASE WHEN banks.currency = "IDR" THEN bank_masuks.nilai ELSE 0 END) as total_idr,
                SUM(CASE WHEN banks.currency = "USD" THEN bank_masuks.nilai ELSE 0 END) as total_usd,
                SUM(CASE WHEN bank_masuks.match_date IS NOT NULL THEN 1 ELSE 0 END) as total_matched
            ')
            ->first();

        // Get sample records without scope
        $sampleRecords = \App\Models\BankMasuk::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
            ->where('status', 'aktif')
            ->with(['bankAccount.bank'])
            ->take(3)
            ->get()
            ->map(function($record) {
                return [
                    'id' => $record->id,
                    'no_bm' => $record->no_bm,
                    'nilai' => $record->nilai,
                    'status' => $record->status,
                    'bank_account_id' => $record->bank_account_id,
                    'bank_currency' => $record->bankAccount->bank->currency ?? 'N/A',
                    'match_date' => $record->match_date
                ];
            });

        return response()->json([
            'success' => true,
            'total_records' => $totalRecords,
            'active_records' => $activeRecords,
            'summary_without_scope' => $summaryWithoutScope,
            'sample_records' => $sampleRecords,
            'query_sql' => \App\Models\BankMasuk::withoutGlobalScope(\App\Scopes\DepartmentScope::class)->where('status', 'aktif')->toSql(),
            'query_bindings' => \App\Models\BankMasuk::withoutGlobalScope(\App\Scopes\DepartmentScope::class)->where('status', 'aktif')->getBindings()
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});

// Simple test route to check bank masuk data
Route::get('/test-bank-masuk-simple', function () {
    try {
        // Check if we can connect to database
        $dbName = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();

        // Check basic table counts
        $bankMasukCount = \Illuminate\Support\Facades\DB::table('bank_masuks')->count();
        $activeBankMasukCount = \Illuminate\Support\Facades\DB::table('bank_masuks')->where('status', 'aktif')->count();

        // Check if there are any records at all
        if ($activeBankMasukCount == 0) {
            return response()->json([
                'error' => 'No active bank masuk records found',
                'database' => $dbName,
                'total_records' => $bankMasukCount,
                'active_records' => $activeBankMasukCount,
                'status' => 'No data to summarize'
            ]);
        }

        // Get sample records
        $sampleRecords = \Illuminate\Support\Facades\DB::table('bank_masuks')
            ->where('status', 'aktif')
            ->take(3)
            ->get();

        // Try to get summary data
        $summaryData = \Illuminate\Support\Facades\DB::table('bank_masuks')
            ->where('bank_masuks.status', 'aktif')
            ->join('bank_accounts', 'bank_masuks.bank_account_id', '=', 'bank_accounts.id')
            ->join('banks', 'bank_accounts.bank_id', '=', 'banks.id')
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(CASE WHEN banks.currency = "IDR" THEN bank_masuks.nilai ELSE 0 END) as total_idr,
                SUM(CASE WHEN banks.currency = "USD" THEN bank_masuks.nilai ELSE 0 END) as total_usd,
                SUM(CASE WHEN bank_masuks.match_date IS NOT NULL THEN 1 ELSE 0 END) as total_matched
            ')
            ->first();

        return response()->json([
            'success' => true,
            'database' => $dbName,
            'total_records' => $bankMasukCount,
            'active_records' => $activeBankMasukCount,
            'sample_records' => $sampleRecords,
            'summary_data' => $summaryData
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});

// Simple test route to check database and basic data
Route::get('/test-db', function () {
    try {
        // Check if we can connect to database
        $dbName = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();

        // Check basic table counts
        $bankMasukCount = \Illuminate\Support\Facades\DB::table('bank_masuks')->count();
        $bankAccountCount = \Illuminate\Support\Facades\DB::table('bank_accounts')->count();
        $bankCount = \Illuminate\Support\Facades\DB::table('banks')->count();

        // Check if there are any records with status 'aktif'
        $activeBankMasukCount = \Illuminate\Support\Facades\DB::table('bank_masuks')->where('status', 'aktif')->count();

        // Check sample data
        $sampleBankMasuk = \Illuminate\Support\Facades\DB::table('bank_masuks')->first();
        $sampleBankAccount = \Illuminate\Support\Facades\DB::table('bank_accounts')->first();
        $sampleBank = \Illuminate\Support\Facades\DB::table('banks')->first();

        return response()->json([
            'success' => true,
            'database' => $dbName,
            'table_counts' => [
                'bank_masuks' => $bankMasukCount,
                'bank_accounts' => $bankAccountCount,
                'banks' => $bankCount,
                'active_bank_masuks' => $activeBankMasukCount
            ],
            'sample_data' => [
                'bank_masuk' => $sampleBankMasuk,
                'bank_account' => $sampleBankAccount,
                'bank' => $sampleBank
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }
});


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
