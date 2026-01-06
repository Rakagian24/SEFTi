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
use App\Http\Controllers\BpbController;
use App\Http\Controllers\StockReportController;
use App\Http\Controllers\StockCardController;
use App\Http\Controllers\StockMutationController;
use Illuminate\Http\Request;
use App\Http\Controllers\PoOutstandingController;
use App\Http\Controllers\PengeluaranBarangController;
use App\Http\Controllers\PelunasanApController;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');



Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// routes/web.php
Route::get('/refresh-csrf', function () {
    return response()->json(['token' => csrf_token()]);
});


Route::middleware(['auth', 'verified'])->group(function () {
    // Public (for purchase_order users) options endpoint must be BEFORE resource to avoid {id} capture
    Route::get('bisnis-partners/options', [BisnisPartnerController::class, 'options'])->middleware(['role:purchase_order'])->name('bisnis-partners.options');
    // Bisnis Partner - Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:bisnis_partner'])->group(function () {
        Route::resource('bisnis-partners', BisnisPartnerController::class);
        Route::get('bisnis-partners/{bisnis_partner}/logs', [BisnisPartnerController::class, 'logs']);

        // Soft Delete Routes (Backend only, no UI changes)
        Route::patch('/bisnis-partners/{id}/restore', [BisnisPartnerController::class, 'restore'])->name('bisnis-partners.restore');
        Route::delete('/bisnis-partners/{id}/force-delete', [BisnisPartnerController::class, 'forceDelete'])->name('bisnis-partners.force-delete');
    });

    // Report: Laporan Stock
    Route::get('/stock', [StockReportController::class, 'index'])->name('stock.index');
    Route::get('/stock/data', [StockReportController::class, 'data'])->name('stock.data');
    Route::get('/stock/export-excel', [StockReportController::class, 'exportExcel'])->name('stock.export-excel');

    // Report: Kartu Stock
    Route::get('/kartu-stock', [StockCardController::class, 'index'])->name('kartu-stock.index');
    Route::get('/kartu-stock/items', [StockCardController::class, 'items'])->name('kartu-stock.items');
    Route::get('/kartu-stock/data', [StockCardController::class, 'data'])->name('kartu-stock.data');
    Route::get('/kartu-stock/export-excel', [StockCardController::class, 'exportExcel'])->name('kartu-stock.export-excel');

    // Report: Mutasi Stock
    Route::get('/mutasi-stock', [StockMutationController::class, 'index'])->name('mutasi-stock.index');
    Route::get('/mutasi-stock/data', [StockMutationController::class, 'data'])->name('mutasi-stock.data');
    Route::get('/mutasi-stock/export-excel', [StockMutationController::class, 'exportExcel'])->name('mutasi-stock.export-excel');

    // Make document download accessible to all authenticated users (for approvals, etc.)
    Route::get('payment-voucher/documents/{document}/download', [PaymentVoucherController::class, 'downloadDocument'])->name('payment-voucher.documents.download');
    Route::get('payment-voucher/documents/{document}/view', [PaymentVoucherController::class, 'viewDocument'])->name('payment-voucher.documents.view');

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
    Route::middleware(['role:supplier,payment_voucher'])->group(function () {
        Route::resource('suppliers', \App\Http\Controllers\SupplierController::class);
        Route::get('/suppliers/{supplier}/logs', [SupplierController::class, 'logs'])->name('suppliers.logs');

        // Soft Delete Routes (Backend only, no UI changes)
        Route::patch('/suppliers/{id}/restore', [SupplierController::class, 'restore'])->name('suppliers.restore');
        Route::delete('/suppliers/{id}/force-delete', [SupplierController::class, 'forceDelete'])->name('suppliers.force-delete');
    });

    // Jenis Barang - Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:jenis_barang'])->group(function () {
        Route::resource('jenis-barangs', \App\Http\Controllers\JenisBarangController::class)->except(['create', 'edit', 'show']);
        Route::patch('jenis-barangs/{jenis_barang}/toggle-status', [\App\Http\Controllers\JenisBarangController::class, 'toggleStatus'])->name('jenis-barangs.toggle-status');
        Route::patch('/jenis-barangs/{id}/restore', [\App\Http\Controllers\JenisBarangController::class, 'restore'])->name('jenis-barangs.restore');
        Route::delete('/jenis-barangs/{id}/force-delete', [\App\Http\Controllers\JenisBarangController::class, 'forceDelete'])->name('jenis-barangs.force-delete');
    });

    // Barang - Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:barang'])->group(function () {
        Route::resource('barangs', \App\Http\Controllers\BarangController::class)->except(['create', 'edit', 'show']);
        Route::patch('barangs/{barang}/toggle-status', [\App\Http\Controllers\BarangController::class, 'toggleStatus'])->name('barangs.toggle-status');
        Route::patch('/barangs/{id}/restore', [\App\Http\Controllers\BarangController::class, 'restore'])->name('barangs.restore');
        Route::delete('/barangs/{id}/force-delete', [\App\Http\Controllers\BarangController::class, 'forceDelete'])->name('barangs.force-delete');
    });

    // User options endpoint for selection modals (accessible to all authenticated users)
    Route::get('users/options', [\App\Http\Controllers\UserController::class, 'options'])->name('users.options');

    // Pengeluaran options for selects (used by PO Anggaran modal) - distinct path to avoid resource collision
    Route::get('pengeluaran-options', [\App\Http\Controllers\PengeluaranController::class, 'options'])->name('pengeluarans.options');

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
        Route::get('/bank-masuk/mutasi/next-number', [\App\Http\Controllers\BankMasukController::class, 'getNextMutasiNumber']);
        Route::get('/bank-masuk/bank-accounts-by-department', [\App\Http\Controllers\BankMasukController::class, 'getBankAccountsByDepartment']);
        Route::get('/bank-masuk/ar-partners', [\App\Http\Controllers\BankMasukController::class, 'getArPartners']);
        Route::resource('bank-masuk', BankMasukController::class);
        Route::post('bank-masuk/{bank_masuk}/mutasi', [BankMasukController::class, 'storeMutasi'])->name('bank-masuk.mutasi');
        Route::get('bank-masuk/{bank_masuk}/download', [BankMasukController::class, 'download'])->name('bank-masuk.download');
        Route::get('bank-masuk/{bank_masuk}/log', [BankMasukController::class, 'log'])->name('bank-masuk.log');
        Route::post('bank-masuk/export-excel', [BankMasukController::class, 'exportExcel'])->name('bank-masuk.export-excel');
    });

    // Bank Keluar - Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:bank_keluar'])->group(function () {
        Route::get('/bank-keluar/next-number', [\App\Http\Controllers\BankKeluarController::class, 'getNextNumber']);
        Route::resource('bank-keluar', \App\Http\Controllers\BankKeluarController::class);
        Route::get('bank-keluar/{bank_keluar}/log', [\App\Http\Controllers\BankKeluarController::class, 'log'])->name('bank-keluar.log');
        Route::get('bank-keluar/documents/{document}/download', [\App\Http\Controllers\BankKeluarController::class, 'downloadDocument'])->name('bank-keluar.documents.download');
        Route::get('bank-keluar/documents/{document}/view', [\App\Http\Controllers\BankKeluarController::class, 'viewDocument'])->name('bank-keluar.documents.view');
        Route::delete('bank-keluar/documents/{document}', [\App\Http\Controllers\BankKeluarController::class, 'deleteDocument'])->name('bank-keluar.documents.delete');
        Route::post('bank-keluar/export-excel', [\App\Http\Controllers\BankKeluarController::class, 'exportExcel'])->name('bank-keluar.export-excel');
    });
});

Route::middleware(['auth'])->group(function () {
    // Credit Cards index - accessible to users with either Bank or Purchase Order permissions
    Route::get('credit-cards', [CreditCardController::class, 'index'])->middleware(['role:bank,purchase_order']);
    // Purchase Order - Staff Toko, Kepala Toko, Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:purchase_order'])->group(function () {
        // Specific routes must come BEFORE resource routes to avoid conflicts
        Route::post('purchase-orders/send', [PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
        Route::post('purchase-orders/add-perihal', [PurchaseOrderController::class, 'addPerihal'])->name('purchase-orders.add-perihal');
        Route::post('purchase-orders/add-termin', [PurchaseOrderController::class, 'addTermin'])->name('purchase-orders.add-termin');
        Route::post('purchase-orders/add-pph', [PurchaseOrderController::class, 'addPph'])->name('purchase-orders.add-pph');
        Route::post('purchase-orders/supplier-bank-accounts', [PurchaseOrderController::class, 'getSupplierBankAccounts'])->name('purchase-orders.supplier-bank-accounts');
        Route::post('purchase-orders/preview-number', [PurchaseOrderController::class, 'getPreviewNumber'])->name('purchase-orders.preview-number');
        Route::get('purchase-orders/termin-info/{termin}', [PurchaseOrderController::class, 'getTerminInfo'])->name('purchase-orders.termin-info');
        Route::get('purchase-orders/termins/search', [PurchaseOrderController::class, 'searchTermins'])->name('purchase-orders.termins.search');
        Route::get('purchase-orders/termins/by-department', [PurchaseOrderController::class, 'getTerminsByDepartment'])->name('purchase-orders.termins.by-department');
        Route::get('purchase-orders/suppliers/by-department', [PurchaseOrderController::class, 'getSuppliersByDepartment'])->name('purchase-orders.suppliers.by-department');
        Route::get('purchase-orders/ar-partners', [PurchaseOrderController::class, 'getArPartners'])->name('purchase-orders.ar-partners');
        Route::get('purchase-orders/credit-cards/by-department', [PurchaseOrderController::class, 'getCreditCardsByDepartment'])->name('purchase-orders.credit-cards.by-department');
        // Jenis Barang & Barang options for Reguler PO
        Route::get('purchase-orders/jenis-barangs', [PurchaseOrderController::class, 'getJenisBarangs'])->name('purchase-orders.jenis-barangs');
        Route::get('purchase-orders/barangs', [PurchaseOrderController::class, 'getBarangs'])->name('purchase-orders.barangs');
        // Close Purchase Order
        Route::post('purchase-orders/{purchase_order}/close', [PurchaseOrderController::class, 'close'])->name('purchase-orders.close');

        // Resource routes come after specific routes
        Route::resource('purchase-orders', PurchaseOrderController::class);
        Route::get('purchase-orders/{purchase_order}/json', [PurchaseOrderController::class, 'showJson'])->name('purchase-orders.json');
        Route::get('purchase-orders/{purchase_order}/preview', [PurchaseOrderController::class, 'preview'])->name('purchase-orders.preview');
        Route::get('purchase-orders/{purchase_order}/download', [PurchaseOrderController::class, 'download'])->name('purchase-orders.download');
        Route::get('purchase-orders/{purchase_order}/log', [PurchaseOrderController::class, 'log'])->name('purchase-orders.log');

        // Soft Delete Routes (Backend only, no UI changes)
        Route::patch('/purchase-orders/{id}/restore', [PurchaseOrderController::class, 'restore'])->name('purchase-orders.restore');
        Route::delete('/purchase-orders/{id}/force-delete', [PurchaseOrderController::class, 'forceDelete'])->name('purchase-orders.force-delete');
    });

    // Approval - Based on user role and permissions
    Route::middleware(['auth'])->group(function () {
        Route::get('approval', [\App\Http\Controllers\ApprovalController::class, 'index'])->name('approval.index');
        Route::get('approval/purchase-orders', [\App\Http\Controllers\ApprovalController::class, 'purchaseOrders'])->name('approval.purchase-orders');
        // PO Anggaran Approval
        Route::get('approval/po-anggaran', [\App\Http\Controllers\ApprovalController::class, 'poAnggarans'])->name('approval.po-anggaran');
        Route::get('approval/po-anggaran/{po_anggaran}/detail', [\App\Http\Controllers\ApprovalController::class, 'poAnggaranDetail'])->name('approval.po-anggaran.detail');
        Route::get('approval/po-anggaran/{po_anggaran}/log', [\App\Http\Controllers\ApprovalController::class, 'poAnggaranLog'])->name('approval.po-anggaran.log');
        // Realisasi Approval
        Route::get('approval/realisasi', [\App\Http\Controllers\ApprovalController::class, 'realisasis'])->name('approval.realisasi');
        Route::get('approval/realisasi/{realisasi}/detail', [\App\Http\Controllers\ApprovalController::class, 'realisasiDetail'])->name('approval.realisasi.detail');
        Route::get('approval/realisasi/{realisasi}/log', [\App\Http\Controllers\ApprovalController::class, 'realisasiLog'])->name('approval.realisasi.log');
        // BPB Approval
        Route::get('approval/bpbs', [\App\Http\Controllers\ApprovalController::class, 'bpbs'])->name('approval.bpbs');
        Route::get('approval/bpbs/{bpb}/detail', [\App\Http\Controllers\ApprovalController::class, 'bpbDetail'])->name('approval.bpbs.detail');
        Route::get('approval/bpbs/{bpb}/log', [\App\Http\Controllers\ApprovalController::class, 'bpbLog'])->name('approval.bpbs.log');

        // Memo Pembayaran Approval
        Route::get('approval/memo-pembayaran', [\App\Http\Controllers\ApprovalController::class, 'memoPembayarans'])->name('approval.memo-pembayaran');
        Route::get('approval/memo-pembayarans', [\App\Http\Controllers\ApprovalController::class, 'memoPembayarans'])->name('approval.memo-pembayarans');
        Route::get('approval/memo-pembayarans/data', [\App\Http\Controllers\ApprovalController::class, 'getMemoPembayarans'])->name('approval.memo-pembayarans.data');
        Route::post('approval/memo-pembayarans/{id}/verify', [\App\Http\Controllers\ApprovalController::class, 'verifyMemoPembayaran'])->name('approval.memo-pembayarans.verify');
        Route::post('approval/memo-pembayarans/{id}/validate', [\App\Http\Controllers\ApprovalController::class, 'validateMemoPembayaran'])->name('approval.memo-pembayarans.validate');
        Route::post('approval/memo-pembayarans/{id}/approve', [\App\Http\Controllers\ApprovalController::class, 'approveMemoPembayaran'])->name('approval.memo-pembayarans.approve');
        Route::post('approval/memo-pembayarans/{id}/reject', [\App\Http\Controllers\ApprovalController::class, 'rejectMemoPembayaran'])->name('approval.memo-pembayarans.reject');
        Route::get('approval/memo-pembayarans/{memoPembayaran}/detail', [\App\Http\Controllers\ApprovalController::class, 'memoPembayaranDetail'])->name('approval.memo-pembayarans.detail');
        Route::get('approval/memo-pembayaran/{memoPembayaran}/log', [\App\Http\Controllers\ApprovalController::class, 'memoPembayaranLog'])->name('approval.memo-pembayarans.log');

        // Payment Voucher Approval
        Route::get('approval/payment-vouchers', [\App\Http\Controllers\ApprovalController::class, 'paymentVouchers'])->name('approval.payment-vouchers');
        Route::post('approval/payment-vouchers/{id}/verify', [\App\Http\Controllers\ApprovalController::class, 'verifyPaymentVoucher'])->name('approval.payment-vouchers.verify');
        Route::post('approval/payment-vouchers/{id}/validate', [\App\Http\Controllers\ApprovalController::class, 'validatePaymentVoucher'])->name('approval.payment-vouchers.validate');
        Route::post('approval/payment-vouchers/{id}/approve', [\App\Http\Controllers\ApprovalController::class, 'approvePaymentVoucher'])->name('approval.payment-vouchers.approve');
        Route::post('approval/payment-vouchers/{id}/reject', [\App\Http\Controllers\ApprovalController::class, 'rejectPaymentVoucher'])->name('approval.payment-vouchers.reject');
        Route::get('approval/payment-vouchers/{paymentVoucher}/detail', [\App\Http\Controllers\ApprovalController::class, 'paymentVoucherDetail'])->name('approval.payment-vouchers.detail');
        Route::get('approval/payment-vouchers/{paymentVoucher}/log', [\App\Http\Controllers\ApprovalController::class, 'paymentVoucherLog'])->name('approval.payment-vouchers.log');
    });

    // BPB - Admin, Staff Toko, Kepala Toko, Staff Akunting & Finance, Kabag Akunting
    Route::middleware(['role:bpb'])->group(function () {
        Route::get('bpb', [BpbController::class, 'index'])->name('bpb.index');
        Route::get('bpb/create', [BpbController::class, 'create'])->name('bpb.create');
        Route::get('bpb/{bpb}/edit', [BpbController::class, 'edit'])->name('bpb.edit');
        Route::get('bpb/purchase-orders/eligible', [BpbController::class, 'eligiblePurchaseOrders'])->name('bpb.purchase-orders.eligible');
        Route::post('bpb', [BpbController::class, 'store'])->name('bpb.store');
        Route::post('bpb/store-draft', [BpbController::class, 'storeDraft'])->name('bpb.store-draft');
        Route::post('bpb/store-and-send', [BpbController::class, 'storeAndSend'])->name('bpb.store-and-send');
        Route::put('bpb/{bpb}', [BpbController::class, 'update'])->name('bpb.update');
        Route::get('bpb/{bpb}', [BpbController::class, 'show'])->name('bpb.show');
        Route::get('bpb/{bpb}/detail', [BpbController::class, 'detail'])->name('bpb.detail');
        Route::get('bpb/{bpb}/download', [BpbController::class, 'downloadPdf'])->name('bpb.download');
        Route::get('bpb/{bpb}/preview', [BpbController::class, 'preview'])->name('bpb.preview');
        Route::post('bpb/send', [BpbController::class, 'send'])->name('bpb.send');
        Route::post('bpb/{bpb}/cancel', [BpbController::class, 'cancel'])->name('bpb.cancel');
        Route::get('bpb/{bpb}/log', [BpbController::class, 'log'])->name('bpb.log');
    });

    // Memo Pembayaran - Staff Toko, Kepala Toko, Staff Akunting & Finance, Kabag, Admin
    Route::middleware(['role:memo_pembayaran'])->group(function () {
        Route::get('memo-pembayaran/giro-numbers', [MemoPembayaranController::class, 'giroNumbers'])->name('memo-pembayaran.giro-numbers');
        Route::resource('memo-pembayaran', MemoPembayaranController::class);
        Route::post('memo-pembayaran/send', [MemoPembayaranController::class, 'send'])->name('memo-pembayaran.send');
        Route::post('memo-pembayaran/add-pph', [MemoPembayaranController::class, 'addPph'])->name('memo-pembayaran.add-pph');
        Route::get('memo-pembayaran/{memo_pembayaran}/preview', [MemoPembayaranController::class, 'preview'])->name('memo-pembayaran.preview');
        Route::get('memo-pembayaran/{memo_pembayaran}/download', [MemoPembayaranController::class, 'download'])->name('memo-pembayaran.download');
        Route::get('memo-pembayaran/{memo_pembayaran}/log', [MemoPembayaranController::class, 'log'])->name('memo-pembayaran.log');
        Route::post('memo-pembayaran/preview-number', [MemoPembayaranController::class, 'getPreviewNumber'])->name('memo-pembayaran.preview-number');
        // Termin preview number
        Route::post('termins/preview-number', [\App\Http\Controllers\TerminController::class, 'generatePreviewNumber'])->name('termins.preview-number');

        Route::get('memo-pembayaran/purchase-orders/search', [MemoPembayaranController::class, 'searchPurchaseOrders'])->name('memo-pembayaran.purchase-orders.search');
        Route::get('memo-pembayaran/suppliers/options', [MemoPembayaranController::class, 'suppliersOptions'])->name('memo-pembayaran.suppliers.options');

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
        Route::get('payment-voucher/purchase-orders/search', [PaymentVoucherController::class, 'searchPurchaseOrders'])->name('payment-voucher.purchase-orders.search');
        Route::get('payment-voucher/purchase-orders/{purchase_order}/bpbs', [PaymentVoucherController::class, 'getBpbsForPurchaseOrder'])->name('payment-voucher.purchase-orders.bpbs');
        Route::get('payment-voucher/purchase-orders/{purchase_order}/memos', [PaymentVoucherController::class, 'getMemosForPurchaseOrder'])->name('payment-voucher.purchase-orders.memos');
        Route::get('payment-voucher/memos/search', [PaymentVoucherController::class, 'searchMemos'])->name('payment-voucher.memos.search');
        Route::get('payment-voucher/po-anggaran/search', [PaymentVoucherController::class, 'searchPoAnggaran'])->name('payment-voucher.po-anggaran.search');
        Route::get('payment-voucher/dp/search', [PaymentVoucherController::class, 'searchDpPaymentVouchers'])->name('payment-voucher.dp.search');
        Route::get('payment-voucher/{id}/edit', [PaymentVoucherController::class, 'edit'])->name('payment-voucher.edit');
        Route::patch('payment-voucher/{id}', [PaymentVoucherController::class, 'update'])->name('payment-voucher.update');
        Route::get('payment-voucher/{id}', [PaymentVoucherController::class, 'show'])->name('payment-voucher.show');
        Route::get('payment-voucher/{id}/preview', [PaymentVoucherController::class, 'preview'])->name('payment-voucher.preview');
        Route::get('payment-voucher/{id}/download', [PaymentVoucherController::class, 'download'])->name('payment-voucher.download');
        Route::get('payment-voucher/{id}/log', [PaymentVoucherController::class, 'log'])->name('payment-voucher.log');
        Route::post('payment-voucher/send', [PaymentVoucherController::class, 'send'])->name('payment-voucher.send');
        Route::post('payment-voucher/{id}/cancel', [PaymentVoucherController::class, 'cancel'])->name('payment-voucher.cancel');
        Route::post('payment-voucher/store-draft', [PaymentVoucherController::class, 'storeDraft'])->name('payment-voucher.store-draft');
        // Documents
        Route::post('payment-voucher/{id}/documents', [PaymentVoucherController::class, 'uploadDocument'])->name('payment-voucher.documents.upload');
        Route::patch('payment-voucher/{id}/documents/{document}/toggle', [PaymentVoucherController::class, 'toggleDocument'])->name('payment-voucher.documents.toggle');
        Route::post('payment-voucher/{id}/documents/set-active', [PaymentVoucherController::class, 'setDocumentActive'])->name('payment-voucher.documents.set-active');
        Route::delete('payment-voucher/documents/{document}', [PaymentVoucherController::class, 'deleteDocument'])->name('payment-voucher.documents.delete');

        // Export Excel
        Route::post('payment-voucher/export-excel', [PaymentVoucherController::class, 'exportExcel'])->name('payment-voucher.export-excel');

        // List Bayar - Admin, Staff Akunting & Finance, Kabag Akunting
        Route::get('list-bayar', [\App\Http\Controllers\ListBayarController::class, 'index'])->name('list-bayar.index');
        Route::get('list-bayar/export-pdf', [\App\Http\Controllers\ListBayarController::class, 'exportPdf'])->name('list-bayar.export-pdf');
        Route::get('list-bayar/documents/{document}/edit', [\App\Http\Controllers\ListBayarController::class, 'editDocument'])->name('list-bayar.documents.edit');
        Route::get('list-bayar/documents/{document}/export-pdf', [\App\Http\Controllers\ListBayarController::class, 'exportDocumentPdf'])->name('list-bayar.documents.export-pdf');
    });

    // PO Outstanding - Admin, Staff Akunting & Finance, Kabag Akunting
    Route::middleware(['role:po_outstanding'])->group(function () {
        Route::get('po-outstanding', [PoOutstandingController::class, 'index'])->name('po-outstanding.index');
        Route::get('po-outstanding/data', [PoOutstandingController::class, 'data'])->name('po-outstanding.data');
        Route::post('po-outstanding/export-excel', [PoOutstandingController::class, 'exportExcel'])->name('po-outstanding.export-excel');
        Route::post('po-outstanding/export-pdf', [PoOutstandingController::class, 'exportPdf'])->name('po-outstanding.export-pdf');
    });

    // PO Anggaran - Admin, Staff Toko, Staff Marketing, Kepala Toko, Staff Akunting & Finance, Kabag Akunting
    Route::middleware(['role:purchase_order'])->group(function () {
        Route::get('po-anggaran', [\App\Http\Controllers\PoAnggaranController::class, 'index'])->name('po-anggaran.index');
        Route::get('po-anggaran/create', [\App\Http\Controllers\PoAnggaranController::class, 'create'])->name('po-anggaran.create');
        Route::post('po-anggaran', [\App\Http\Controllers\PoAnggaranController::class, 'store'])->name('po-anggaran.store');
        Route::get('po-anggaran/{po_anggaran}/edit', [\App\Http\Controllers\PoAnggaranController::class, 'edit'])->name('po-anggaran.edit');
        Route::put('po-anggaran/{po_anggaran}', [\App\Http\Controllers\PoAnggaranController::class, 'update'])->name('po-anggaran.update');
        Route::get('po-anggaran/{po_anggaran}', [\App\Http\Controllers\PoAnggaranController::class, 'show'])->name('po-anggaran.show');
        Route::get('po-anggaran/{po_anggaran}/download', [\App\Http\Controllers\PoAnggaranController::class, 'download'])->name('po-anggaran.download');
        Route::get('po-anggaran/{po_anggaran}/log', [\App\Http\Controllers\PoAnggaranController::class, 'log'])->name('po-anggaran.log');
        Route::post('po-anggaran/send', [\App\Http\Controllers\PoAnggaranController::class, 'send'])->name('po-anggaran.send');
        // Approval actions
        Route::post('po-anggaran/{po_anggaran}/verify', [\App\Http\Controllers\PoAnggaranController::class, 'verify'])->name('po-anggaran.verify');
        Route::post('po-anggaran/{po_anggaran}/validate', [\App\Http\Controllers\PoAnggaranController::class, 'validatePo'])->name('po-anggaran.validate');
        Route::post('po-anggaran/{po_anggaran}/approve', [\App\Http\Controllers\PoAnggaranController::class, 'approve'])->name('po-anggaran.approve');
        Route::post('po-anggaran/{po_anggaran}/reject', [\App\Http\Controllers\PoAnggaranController::class, 'reject'])->name('po-anggaran.reject');
        Route::delete('po-anggaran/{po_anggaran}', [\App\Http\Controllers\PoAnggaranController::class, 'cancel'])->name('po-anggaran.cancel');
    });

    // Pengeluaran Barang - Admin, Staff Toko, Branch Manager
    Route::middleware(['auth'])->group(function () {
        // Specific helper endpoints must come BEFORE resource route to avoid {id} capture
        Route::get('pengeluaran-barang/barang-stock', [PengeluaranBarangController::class, 'getBarangWithStock'])->name('pengeluaran-barang.barang-stock');
        Route::get('pengeluaran-barang/preview-number', [PengeluaranBarangController::class, 'generatePreviewNumber'])->name('pengeluaran-barang.preview-number');
        Route::post('pengeluaran-barang/export-excel', [PengeluaranBarangController::class, 'exportExcel'])->name('pengeluaran-barang.export-excel');
        Route::delete('pengeluaran-barang/bulk-delete', [PengeluaranBarangController::class, 'bulkDelete'])->name('pengeluaran-barang.bulk-delete');

        Route::resource('pengeluaran-barang', PengeluaranBarangController::class);
    });

    // Realisasi - Admin, Staff Toko, Staff Marketing, Kepala Toko, Staff Akunting & Finance, Accounting, Kabag Akunting
    Route::middleware(['role:anggaran'])->group(function () {
        // Helper endpoints
        Route::get('realisasi/po-anggaran/options', [\App\Http\Controllers\RealisasiController::class, 'poAnggaranOptions'])->name('realisasi.po-anggaran.options');
        Route::get('realisasi/po-anggaran/{po_anggaran}', [\App\Http\Controllers\RealisasiController::class, 'poAnggaranDetail'])->name('realisasi.po-anggaran.detail');

        Route::get('realisasi', [\App\Http\Controllers\RealisasiController::class, 'index'])->name('realisasi.index');
        Route::get('realisasi/create', [\App\Http\Controllers\RealisasiController::class, 'create'])->name('realisasi.create');
        Route::post('realisasi', [\App\Http\Controllers\RealisasiController::class, 'store'])->name('realisasi.store');
        // Draft helpers for Realisasi (JSON endpoints for SPA-style create flow)
        Route::post('realisasi/store-draft', [\App\Http\Controllers\RealisasiController::class, 'storeDraft'])->name('realisasi.store-draft');
        Route::patch('realisasi/{realisasi}/update-draft', [\App\Http\Controllers\RealisasiController::class, 'updateDraft'])->name('realisasi.update-draft');
        Route::get('realisasi/{realisasi}/edit', [\App\Http\Controllers\RealisasiController::class, 'edit'])->name('realisasi.edit');
        Route::put('realisasi/{realisasi}', [\App\Http\Controllers\RealisasiController::class, 'update'])->name('realisasi.update');
        Route::get('realisasi/{realisasi}', [\App\Http\Controllers\RealisasiController::class, 'show'])->name('realisasi.show');
        Route::get('realisasi/{realisasi}/download', [\App\Http\Controllers\RealisasiController::class, 'download'])->name('realisasi.download');
        Route::get('realisasi/{realisasi}/log', [\App\Http\Controllers\RealisasiController::class, 'log'])->name('realisasi.log');
        Route::post('realisasi/send', [\App\Http\Controllers\RealisasiController::class, 'send'])->name('realisasi.send');

        // Close Realisasi
        Route::post('realisasi/{realisasi}/close', [\App\Http\Controllers\RealisasiController::class, 'close'])->name('realisasi.close');

        // Dokumen Realisasi (mirror PV docs behaviour)
        Route::post('realisasi/{id}/documents', [\App\Http\Controllers\RealisasiController::class, 'uploadDocument'])->name('realisasi.documents.upload');
        Route::post('realisasi/{id}/documents/set-active', [\App\Http\Controllers\RealisasiController::class, 'setDocumentActive'])->name('realisasi.documents.set-active');
        Route::delete('realisasi/documents/{document}', [\App\Http\Controllers\RealisasiController::class, 'deleteDocument'])->name('realisasi.documents.delete');
        Route::get('realisasi/documents/{document}/download', [\App\Http\Controllers\RealisasiController::class, 'downloadDocument'])->name('realisasi.documents.download');
        Route::get('realisasi/documents/{document}/view', [\App\Http\Controllers\RealisasiController::class, 'viewDocument'])->name('realisasi.documents.view');

        // Approval actions
        Route::post('realisasi/{realisasi}/verify', [\App\Http\Controllers\RealisasiController::class, 'verify'])->name('realisasi.verify');
        Route::post('realisasi/{realisasi}/approve', [\App\Http\Controllers\RealisasiController::class, 'approve'])->name('realisasi.approve');
        Route::delete('realisasi/{realisasi}', [\App\Http\Controllers\RealisasiController::class, 'cancel'])->name('realisasi.cancel');
    });

    // Pelunasan AP - Admin, Staff AP, Kabag Finance & Akunting
    Route::middleware(['role:pelunasan'])->group(function () {
        Route::get('pelunasan-ap', [PelunasanApController::class, 'index'])->name('pelunasan-ap.index');
        Route::get('pelunasan-ap/create', [PelunasanApController::class, 'create'])->name('pelunasan-ap.create');
        Route::post('pelunasan-ap', [PelunasanApController::class, 'store'])->name('pelunasan-ap.store');
        Route::get('pelunasan-ap/{pelunasan_ap}/edit', [PelunasanApController::class, 'edit'])->name('pelunasan-ap.edit');
        Route::put('pelunasan-ap/{pelunasan_ap}', [PelunasanApController::class, 'update'])->name('pelunasan-ap.update');
        Route::get('pelunasan-ap/{pelunasan_ap}', [PelunasanApController::class, 'show'])->name('pelunasan-ap.show');
        Route::get('pelunasan-ap/{pelunasan_ap}/log', [PelunasanApController::class, 'logs'])->name('pelunasan-ap.log');
        Route::post('pelunasan-ap/send', [PelunasanApController::class, 'send'])->name('pelunasan-ap.send');
        Route::post('pelunasan-ap/{pelunasan_ap}/cancel', [PelunasanApController::class, 'cancel'])->name('pelunasan-ap.cancel');

        // Helper endpoints
        Route::get('pelunasan-ap/bank-keluars/search', [PelunasanApController::class, 'getBankKeluars'])->name('pelunasan-ap.bank-keluars.search');
        Route::get('pelunasan-ap/payment-vouchers/search', [PelunasanApController::class, 'getPaymentVouchers'])->name('pelunasan-ap.payment-vouchers.search');
    });
});


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
