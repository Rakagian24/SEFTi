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
use App\Http\Controllers\CreditCardController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\PphController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\BankMasukController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\MemoPembayaranController;
use App\Http\Controllers\PerihalController;
use App\Http\Controllers\TerminController;
use App\Http\Controllers\BankMatchingController;
use App\Http\Controllers\PaymentVoucherController;
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

        // Soft Delete Routes (Backend only, no UI changes)
        Route::patch('/bisnis-partners/{id}/restore', [BisnisPartnerController::class, 'restore'])->name('bisnis-partners.restore');
        Route::delete('/bisnis-partners/{id}/force-delete', [BisnisPartnerController::class, 'forceDelete'])->name('bisnis-partners.force-delete');
    });

    // Bank - Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:bank'])->group(function () {
        Route::resource('banks', BankController::class);
        Route::get('banks/{bank}/log-activity', [BankController::class, 'logActivity']);
        Route::patch('banks/{bank}/toggle-status', [BankController::class, 'toggleStatus'])->name('banks.toggle-status');
        Route::get('/banks/{bank}/logs', [BankController::class, 'logs'])->name('banks.logs');

        // Soft Delete Routes (Backend only, no UI changes)
        Route::patch('/banks/{id}/restore', [BankController::class, 'restore'])->name('banks.restore');
        Route::delete('/banks/{id}/force-delete', [BankController::class, 'forceDelete'])->name('banks.force-delete');
    });

    // AR Partner - Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:bisnis_partner'])->group(function () {
        Route::resource('ar-partners', ArPartnerController::class);
        Route::get('/ar-partners/{ar_partner}/logs', [ArPartnerController::class, 'logs']);
        Route::post('/ar-partners/migrate', [ArPartnerController::class, 'migrate'])->name('ar-partners.migrate');

        // Soft Delete Routes (Backend only, no UI changes)
        Route::patch('/ar-partners/{id}/restore', [ArPartnerController::class, 'restore'])->name('ar-partners.restore');
        Route::delete('/ar-partners/{id}/force-delete', [ArPartnerController::class, 'forceDelete'])->name('ar-partners.force-delete');
    });

    // Pengeluaran - Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:payment_voucher'])->group(function () {
        Route::resource('pengeluarans', \App\Http\Controllers\PengeluaranController::class);
        Route::put('/pengeluarans/{pengeluaran}/toggle-status', [PengeluaranController::class, 'toggleStatus'])->name('pengeluarans.toggleStatus');
        Route::get('/pengeluarans/{pengeluaran}/logs', [PengeluaranController::class, 'logs'])->name('pengeluarans.logs');

        // Soft Delete Routes (Backend only, no UI changes)
        Route::patch('/pengeluarans/{id}/restore', [PengeluaranController::class, 'restore'])->name('pengeluarans.restore');
        Route::delete('/pengeluarans/{id}/force-delete', [PengeluaranController::class, 'forceDelete'])->name('pengeluarans.force-delete');
    });

    // Approval - Purchase Order Logs (share same data source with Purchase Order logs)
    Route::get('approval/purchase-orders/{purchase_order}/log', [\App\Http\Controllers\ApprovalController::class, 'purchaseOrderLog'])
        ->name('approval.purchase-orders.log');

    // Approval - Purchase Order Detail (separate page)
    Route::get('approval/purchase-orders/{purchase_order}', [\App\Http\Controllers\ApprovalController::class, 'purchaseOrderDetail'])
        ->name('approval.purchase-orders.detail');

    // PPH - Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:payment_voucher'])->group(function () {
        Route::resource('pphs', \App\Http\Controllers\PphController::class);
        Route::patch('pphs/{pph}/toggle-status', [\App\Http\Controllers\PphController::class, 'toggleStatus'])->name('pphs.toggle-status');
        Route::get('/pphs/{pph}/logs', [PphController::class, 'logs'])->name('pphs.logs');

        // Soft Delete Routes (Backend only, no UI changes)
        Route::patch('/pphs/{id}/restore', [PphController::class, 'restore'])->name('pphs.restore');
        Route::delete('/pphs/{id}/force-delete', [PphController::class, 'forceDelete'])->name('pphs.force-delete');
    });

    // Bank Account - Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:bank'])->group(function () {
        Route::resource('bank-accounts', \App\Http\Controllers\BankAccountController::class);
        Route::patch('bank-accounts/{bank_account}/toggle-status', [\App\Http\Controllers\BankAccountController::class, 'toggleStatus'])->name('bank-accounts.toggle-status');
        Route::get('/bank-accounts/{bank_account}/logs', [BankAccountController::class, 'logs'])->name('bank-accounts.logs');

        // Soft Delete Routes (Backend only, no UI changes)
        Route::patch('/bank-accounts/{id}/restore', [BankAccountController::class, 'restore'])->name('bank-accounts.restore');
        Route::delete('/bank-accounts/{id}/force-delete', [BankAccountController::class, 'forceDelete'])->name('bank-accounts.force-delete');

        // Credit Cards (exclude index here; public index route defined below for broader access)
        Route::resource('credit-cards', CreditCardController::class)->except(['index', 'show', 'create', 'edit']);
        Route::patch('credit-cards/{credit_card}/toggle-status', [CreditCardController::class, 'toggleStatus'])->name('credit-cards.toggle-status');
        Route::get('/credit-cards/{credit_card}/logs', [CreditCardController::class, 'logs'])->name('credit-cards.logs');
    });

    // Supplier - Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:supplier'])->group(function () {
        Route::resource('suppliers', \App\Http\Controllers\SupplierController::class);
        Route::get('/suppliers/{supplier}/logs', [SupplierController::class, 'logs'])->name('suppliers.logs');

        // Soft Delete Routes (Backend only, no UI changes)
        Route::patch('/suppliers/{id}/restore', [SupplierController::class, 'restore'])->name('suppliers.restore');
        Route::delete('/suppliers/{id}/force-delete', [SupplierController::class, 'forceDelete'])->name('suppliers.force-delete');
    });

    // Master Data Routes - Admin only
    Route::middleware(['role:*'])->group(function () {
        Route::resource('departments', DepartmentController::class);
        Route::resource('roles', RoleController::class);
        Route::resource('users', \App\Http\Controllers\UserController::class);
        Route::patch('users/{user}/toggle-status', [\App\Http\Controllers\UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::patch('roles/{role}/toggle-status', [RoleController::class, 'toggleStatus'])->name('roles.toggle-status');
        Route::patch('departments/{department}/toggle-status', [DepartmentController::class, 'toggleStatus'])->name('departments.toggle-status');

        // Soft Delete Routes (Backend only, no UI changes)
        Route::patch('/departments/{id}/restore', [DepartmentController::class, 'restore'])->name('departments.restore');
        Route::delete('/departments/{id}/force-delete', [DepartmentController::class, 'forceDelete'])->name('departments.force-delete');
        Route::patch('/roles/{id}/restore', [RoleController::class, 'restore'])->name('roles.restore');
        Route::delete('/roles/{id}/force-delete', [RoleController::class, 'forceDelete'])->name('roles.force-delete');
        Route::patch('/users/{id}/restore', [\App\Http\Controllers\UserController::class, 'restore'])->name('users.restore');
        Route::delete('/users/{id}/force-delete', [\App\Http\Controllers\UserController::class, 'forceDelete'])->name('users.force-delete');
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
    // Credit Cards index - accessible to users with either Bank or Purchase Order permissions
    Route::get('credit-cards', [CreditCardController::class, 'index'])->middleware(['role:bank,purchase_order']);
    // Purchase Order - Staff Toko, Kepala Toko, Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:purchase_order'])->group(function () {
        Route::resource('purchase-orders', PurchaseOrderController::class);
        Route::post('purchase-orders/send', [PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
        Route::post('purchase-orders/add-pph', [PurchaseOrderController::class, 'addPph'])->name('purchase-orders.add-pph');
        Route::post('purchase-orders/add-perihal', [PurchaseOrderController::class, 'addPerihal'])->name('purchase-orders.add-perihal');
        Route::post('purchase-orders/add-termin', [PurchaseOrderController::class, 'addTermin'])->name('purchase-orders.add-termin');
        Route::post('purchase-orders/supplier-bank-accounts', [PurchaseOrderController::class, 'getSupplierBankAccounts'])->name('purchase-orders.supplier-bank-accounts');
        Route::get('purchase-orders/{purchase_order}/preview', [PurchaseOrderController::class, 'preview'])->name('purchase-orders.preview');
        Route::get('purchase-orders/{purchase_order}/download', [PurchaseOrderController::class, 'download'])->name('purchase-orders.download');
        Route::get('purchase-orders/{purchase_order}/log', [PurchaseOrderController::class, 'log'])->name('purchase-orders.log');
        Route::post('purchase-orders/preview-number', [PurchaseOrderController::class, 'getPreviewNumber'])->name('purchase-orders.preview-number');
        Route::get('purchase-orders/termin-info/{termin}', [PurchaseOrderController::class, 'getTerminInfo'])->name('purchase-orders.termin-info');
        Route::get('purchase-orders/termins/search', [PurchaseOrderController::class, 'searchTermins'])->name('purchase-orders.termins.search');
        Route::get('purchase-orders/termins/by-department', [PurchaseOrderController::class, 'getTerminsByDepartment'])->name('purchase-orders.termins.by-department');
        Route::get('purchase-orders/suppliers/by-department', [PurchaseOrderController::class, 'getSuppliersByDepartment'])->name('purchase-orders.suppliers.by-department');

        // Soft Delete Routes (Backend only, no UI changes)
        Route::patch('/purchase-orders/{id}/restore', [PurchaseOrderController::class, 'restore'])->name('purchase-orders.restore');
        Route::delete('/purchase-orders/{id}/force-delete', [PurchaseOrderController::class, 'forceDelete'])->name('purchase-orders.force-delete');
    });

    // Approval - Based on user role and permissions
    Route::middleware(['auth'])->group(function () {
        Route::get('approval', [\App\Http\Controllers\ApprovalController::class, 'index'])->name('approval.index');
        Route::get('approval/purchase-orders', [\App\Http\Controllers\ApprovalController::class, 'purchaseOrders'])->name('approval.purchase-orders');
        // Alias singular path to plural to avoid 404s from frontend links
        Route::get('approval/memo-pembayaran', [\App\Http\Controllers\ApprovalController::class, 'memoPembayarans'])->name('approval.memo-pembayaran');
        Route::get('approval/memo-pembayarans', [\App\Http\Controllers\ApprovalController::class, 'memoPembayarans'])->name('approval.memo-pembayarans');
        Route::get('approval/memo-pembayarans/data', [\App\Http\Controllers\ApprovalController::class, 'getMemoPembayarans'])->name('approval.memo-pembayarans.data');
        Route::post('approval/memo-pembayarans/{id}/verify', [\App\Http\Controllers\ApprovalController::class, 'verifyMemoPembayaran'])->name('approval.memo-pembayarans.verify');
        Route::post('approval/memo-pembayarans/{id}/validate', [\App\Http\Controllers\ApprovalController::class, 'validateMemoPembayaran'])->name('approval.memo-pembayarans.validate');
        Route::post('approval/memo-pembayarans/{id}/approve', [\App\Http\Controllers\ApprovalController::class, 'approveMemoPembayaran'])->name('approval.memo-pembayarans.approve');
        Route::post('approval/memo-pembayarans/{id}/reject', [\App\Http\Controllers\ApprovalController::class, 'rejectMemoPembayaran'])->name('approval.memo-pembayarans.reject');
        Route::get('approval/memo-pembayarans/{memoPembayaran}/detail', [\App\Http\Controllers\ApprovalController::class, 'memoPembayaranDetail'])->name('approval.memo-pembayarans.detail');
    });

    // Memo Pembayaran - Staff Toko, Kepala Toko, Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:memo_pembayaran'])->group(function () {
        Route::resource('memo-pembayaran', MemoPembayaranController::class);
        Route::post('memo-pembayaran/send', [MemoPembayaranController::class, 'send'])->name('memo-pembayaran.send');
        Route::post('memo-pembayaran/add-pph', [MemoPembayaranController::class, 'addPph'])->name('memo-pembayaran.add-pph');
        Route::get('memo-pembayaran/{memo_pembayaran}/download', [MemoPembayaranController::class, 'download'])->name('memo-pembayaran.download');
        Route::get('memo-pembayaran/{memo_pembayaran}/log', [MemoPembayaranController::class, 'log'])->name('memo-pembayaran.log');
        Route::post('memo-pembayaran/preview-number', [MemoPembayaranController::class, 'getPreviewNumber'])->name('memo-pembayaran.preview-number');
// Termin preview number
Route::post('termins/preview-number', [\App\Http\Controllers\TerminController::class, 'generatePreviewNumber'])->name('termins.preview-number');


        Route::get('memo-pembayaran/purchase-orders/search', [MemoPembayaranController::class, 'searchPurchaseOrders'])->name('memo-pembayaran.purchase-orders.search');
        Route::get('memo-pembayaran/suppliers/options', [MemoPembayaranController::class, 'suppliersOptions'])->name('memo-pembayaran.suppliers.options');
        Route::get('memo-pembayaran/giro-numbers', [MemoPembayaranController::class, 'giroNumbers'])->name('memo-pembayaran.giro-numbers');

        // Approval Routes
        Route::post('memo-pembayaran/{memo_pembayaran}/verify', [MemoPembayaranController::class, 'verify'])->name('memo-pembayaran.verify');
        Route::post('memo-pembayaran/{memo_pembayaran}/validate', [MemoPembayaranController::class, 'validateMemo'])->name('memo-pembayaran.validate');
        Route::post('memo-pembayaran/{memo_pembayaran}/approve', [MemoPembayaranController::class, 'approve'])->name('memo-pembayaran.approve');
        Route::post('memo-pembayaran/{memo_pembayaran}/reject', [MemoPembayaranController::class, 'reject'])->name('memo-pembayaran.reject');
        Route::get('memo-pembayaran/{memo_pembayaran}/approval-progress', [MemoPembayaranController::class, 'approvalProgress'])->name('memo-pembayaran.approval-progress');

        // Soft Delete Routes (Backend only, no UI changes)
        Route::patch('/memo-pembayaran/{id}/restore', [MemoPembayaranController::class, 'restore'])->name('memo-pembayaran.restore');
        Route::delete('/memo-pembayaran/{id}/force-delete', [MemoPembayaranController::class, 'forceDelete'])->name('memo-pembayaran.force-delete');
    });

    // Perihal - Admin only
    Route::middleware(['role:*'])->group(function () {
        Route::resource('perihals', PerihalController::class);
        Route::patch('perihals/{perihal}/toggle-status', [PerihalController::class, 'toggleStatus'])->name('perihals.toggle-status');

        // Soft Delete Routes (Backend only, no UI changes)
        Route::patch('/perihals/{id}/restore', [PerihalController::class, 'restore'])->name('perihals.restore');
        Route::delete('/perihals/{id}/force-delete', [PerihalController::class, 'forceDelete'])->name('perihals.force-delete');
    });

    // Termin - Staff Toko, Staff Akunting & Finance, and Admin
    Route::middleware(['role:termin'])->group(function () {
        Route::resource('termins', TerminController::class);
        Route::patch('termins/{termin}/toggle-status', [TerminController::class, 'toggleStatus'])->name('termins.toggle-status');

        // Soft Delete Routes (Backend only, no UI changes)
        Route::patch('/termins/{id}/restore', [TerminController::class, 'restore'])->name('termins.restore');
        Route::delete('/termins/{id}/force-delete', [TerminController::class, 'forceDelete'])->name('termins.force-delete');
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

    // Payment Voucher - Admin, Staff Akunting & Finance, Kabag Akunting
    Route::middleware(['role:payment_voucher'])->group(function () {
        Route::get('payment-voucher', [PaymentVoucherController::class, 'index'])->name('payment-voucher.index');
        Route::get('payment-voucher/create', [PaymentVoucherController::class, 'create'])->name('payment-voucher.create');
        Route::get('payment-voucher/{id}/edit', [PaymentVoucherController::class, 'edit'])->name('payment-voucher.edit');
        Route::get('payment-voucher/{id}', [PaymentVoucherController::class, 'show'])->name('payment-voucher.show');
        Route::get('payment-voucher/{id}/download', [PaymentVoucherController::class, 'download'])->name('payment-voucher.download');
        Route::get('payment-voucher/{id}/log', [PaymentVoucherController::class, 'log'])->name('payment-voucher.log');
        Route::post('payment-voucher/send', [PaymentVoucherController::class, 'send'])->name('payment-voucher.send');
        Route::post('payment-voucher/{id}/cancel', [PaymentVoucherController::class, 'cancel'])->name('payment-voucher.cancel');
        Route::post('payment-voucher/store-draft', [PaymentVoucherController::class, 'storeDraft'])->name('payment-voucher.store-draft');
        // Documents
        Route::post('payment-voucher/{id}/documents', [PaymentVoucherController::class, 'uploadDocument'])->name('payment-voucher.documents.upload');
        Route::patch('payment-voucher/{id}/documents/{document}/toggle', [PaymentVoucherController::class, 'toggleDocument'])->name('payment-voucher.documents.toggle');
        Route::get('payment-voucher/documents/{document}/download', [PaymentVoucherController::class, 'downloadDocument'])->name('payment-voucher.documents.download');
        Route::delete('payment-voucher/documents/{document}', [PaymentVoucherController::class, 'deleteDocument'])->name('payment-voucher.documents.delete');
    });
});

// Test route for PDF generation
Route::get('/test-pdf', function () {
    try {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('purchase_order_pdf', [
            'po' => (object) [
                'id' => 1,
                'no_po' => 'TEST-001',
                'tipe_po' => 'Reguler',
                'department' => (object) ['name' => 'Test Department'],
                'perihal' => (object) ['nama' => 'Test Perihal'],
                'items' => [],
                'harga' => 100000,
                'metode_pembayaran' => 'Transfer',
                'keterangan' => 'Test PO'
            ],
            'tanggal' => '21 Agustus 2025',
            'total' => 100000,
            'diskon' => 0,
            'ppn' => 0,
            'pph' => 0,
            'pphPersen' => 0,
            'grandTotal' => 100000,
            'cicilan' => 0,
            'logoSrc' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            'signatureSrc' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            'approvedSrc' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
        ])
        ->setOptions(config('dompdf.options'))
        ->setPaper('a4', 'portrait');

        return $pdf->download('test.pdf');
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    }
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
            ->with(['bankAccount.bank', 'arPartner'])
            ->limit(5)
            ->get();

        return response()->json([
            'summary_without_scope' => $summaryWithoutScope,
            'sample_records' => $sampleRecords,
            'total_records' => $totalRecords,
            'active_records' => $activeRecords,
            'status' => 'Success'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'status' => 'Error'
        ], 500);
    }
});

// Test route for Purchase Orders debugging
Route::get('/test-purchase-orders-debug', function () {
    try {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // Get user departments
        $userDepartments = $user->departments;

        // Check total purchase orders without scope
        $totalPOWithoutScope = \App\Models\PurchaseOrder::withoutGlobalScope(\App\Scopes\DepartmentScope::class)->count();

        // Check total purchase orders with scope
        $totalPOWithScope = \App\Models\PurchaseOrder::count();

        // Check purchase orders for user's departments
        $poForUserDepts = \App\Models\PurchaseOrder::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
            ->whereIn('department_id', $userDepartments->pluck('id'))
            ->count();

        // Get sample purchase orders without scope
        $samplePO = \App\Models\PurchaseOrder::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
            ->with(['department', 'perihal'])
            ->limit(5)
            ->get();

        // Check if there are any purchase orders at all
        if ($totalPOWithoutScope == 0) {
            return response()->json([
                'error' => 'No purchase orders found in database at all',
                'user_departments' => $userDepartments->toArray(),
                'total_po_without_scope' => $totalPOWithoutScope,
                'total_po_with_scope' => $totalPOWithScope,
                'po_for_user_depts' => $poForUserDepts,
                'status' => 'No data exists'
            ]);
        }

        return response()->json([
            'user_departments' => $userDepartments->toArray(),
            'total_po_without_scope' => $totalPOWithoutScope,
            'total_po_with_scope' => $totalPOWithScope,
            'po_for_user_depts' => $poForUserDepts,
            'sample_po' => $samplePO,
            'status' => 'Success'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'status' => 'Error'
        ], 500);
    }
});

// Simple test route to check PO data
Route::get('/test-po-simple', function () {
    try {
        $po = \App\Models\PurchaseOrder::with(['department', 'perihal'])->first();
        if (!$po) {
            return response()->json(['error' => 'No PO found']);
        }

        return response()->json([
            'po_id' => $po->id,
            'department' => $po->department ? $po->department->name : 'No department',
            'perihal' => $po->perihal ? $po->perihal->nama : 'No perihal',
            'status' => $po->status
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});

// Test route to create a simple PO
Route::post('/test-create-po', function () {
    try {
        $po = \App\Models\PurchaseOrder::create([
            'tipe_po' => 'Reguler',
            'department_id' => 1,
            'perihal_id' => 1,
            'no_invoice' => 'TEST-INV-001',
            'harga' => 100000.00,
            'total' => 100000.00,
            'detail_keperluan' => 'Test PO dari route',
            'status' => 'Draft',
            'metode_pembayaran' => 'Transfer',
            'created_by' => 1,
        ]);

        return response()->json([
            'success' => true,
            'po_id' => $po->id,
            'message' => 'Test PO created successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});

// Test route to check PO without DepartmentScope
Route::get('/test-po-no-scope', function () {
    try {
        $po = \App\Models\PurchaseOrder::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
            ->with(['department', 'perihal'])
            ->first();

        if (!$po) {
            return response()->json(['error' => 'No PO found even without scope']);
        }

        return response()->json([
            'po_id' => $po->id,
            'department' => $po->department ? $po->department->name : 'No department',
            'perihal' => $po->perihal ? $po->perihal->nama : 'No perihal',
            'status' => $po->status,
            'created_at' => $po->created_at
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});

// Test route to check latest POs
Route::get('/test-po-latest', function () {
    try {
        $pos = \App\Models\PurchaseOrder::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
            ->with(['department', 'perihal'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if ($pos->isEmpty()) {
            return response()->json(['error' => 'No POs found']);
        }

        return response()->json([
            'total' => $pos->count(),
            'pos' => $pos->map(function($po) {
                return [
                    'id' => $po->id,
                    'no_po' => $po->no_po,
                    'tipe_po' => $po->tipe_po,
                    'department' => $po->department ? $po->department->name : 'No department',
                    'perihal' => $po->perihal ? $po->perihal->nama : 'No perihal',
                    'status' => $po->status,
                    'created_at' => $po->created_at,
                    'created_by' => $po->created_by
                ];
            })
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});

// Test route to check authentication status
Route::get('/test-auth-status', function () {
    try {
        $user = \Illuminate\Support\Facades\Auth::user();
        $sessionId = session()->getId();
        $sessionData = session()->all();

        return response()->json([
            'authenticated' => $user ? true : false,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ? $user->role->name : 'No role'
            ] : null,
            'session_id' => $sessionId,
            'session_data' => $sessionData,
            'csrf_token' => csrf_token()
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});

// Test route to simulate the failing API call
Route::get('/test-api-approval-count', function () {
    try {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated', 'count' => 0], 401);
        }

        $userRole = $user->role->name ?? '';

        // Simulate the exact logic from ApprovalController
        $query = \App\Models\PurchaseOrder::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
            ->where('status', 'In Progress');

        // Apply department scope based on user role
        if ($userRole !== 'Admin' && $userRole !== 'Kabag' && $userRole !== 'Direksi') {
            $userDepartments = $user->departments->pluck('id')->toArray();
            if (!empty($userDepartments)) {
                $query->whereIn('department_id', $userDepartments);
            } else {
                return response()->json(['count' => 0, 'message' => 'No departments assigned to user']);
            }
        }

        $count = $query->count();

        return response()->json([
            'count' => $count,
            'user_role' => $userRole,
            'user_departments' => $user->departments->pluck('name')->toArray(),
            'debug_info' => [
                'total_po_without_scope' => \App\Models\PurchaseOrder::withoutGlobalScope(\App\Scopes\DepartmentScope::class)->count(),
                'total_po_in_progress' => \App\Models\PurchaseOrder::withoutGlobalScope(\App\Scopes\DepartmentScope::class)->where('status', 'In Progress')->count()
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage(), 'count' => 0], 500);
    }
});

// Test route to debug DepartmentScope issue
Route::get('/test-department-scope-debug', function () {
    try {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // Get user info
        $userInfo = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'department_id' => $user->department_id,
            'departments' => $user->departments->map(function($dept) {
                return ['id' => $dept->id, 'name' => $dept->name];
            })
        ];

        // Check PO counts
        $totalPO = \App\Models\PurchaseOrder::withoutGlobalScope(\App\Scopes\DepartmentScope::class)->count();
        $poWithScope = \App\Models\PurchaseOrder::count();
        $poWithoutScope = \App\Models\PurchaseOrder::withoutGlobalScope(\App\Scopes\DepartmentScope::class)->count();

        // Check PO by user's departments
        $userDepartmentIds = $user->departments->pluck('id')->toArray();
        $poForUserDepts = \App\Models\PurchaseOrder::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
            ->whereIn('department_id', $userDepartmentIds)
            ->count();

        // Check if user has 'All' department access
        $hasAllAccess = $user->departments->contains('name', 'All');

        return response()->json([
            'user_info' => $userInfo,
            'department_scope_debug' => [
                'total_po_without_scope' => $totalPO,
                'total_po_with_scope' => $poWithScope,
                'po_for_user_departments' => $poForUserDepts,
                'user_department_ids' => $userDepartmentIds,
                'has_all_access' => $hasAllAccess
            ],
            'status' => 'Success'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'status' => 'Error'
        ], 500);
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
