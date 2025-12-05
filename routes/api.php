<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PphController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\Settings\MessageController;

Route::middleware(['auth:web', 'web'])->group(function () {
    Route::get('/settings/message', [MessageController::class, 'index']);
    Route::get('/settings/message/{conversation}', [MessageController::class, 'show']);
    Route::post('/settings/message', [MessageController::class, 'store']);
    Route::post('/settings/message/{conversation}/send', [MessageController::class, 'sendMessage']);
    Route::put('/settings/message/{message}', [MessageController::class, 'update']);
    Route::delete('/settings/message/{message}', [MessageController::class, 'destroy']);
    Route::delete('/settings/message/{conversation}', [MessageController::class, 'deleteConversation']);
    Route::get('/settings/message/available-contacts', [MessageController::class, 'availableContacts']);

    // Departments API
    Route::get('/departments', [DepartmentController::class, 'apiIndex']);

    // Perihals API
    Route::get('/perihals', [\App\Http\Controllers\PerihalController::class, 'apiIndex']);

    // PPHs API
    Route::get('/pphs', [PphController::class, 'apiIndex']);

    // Approval API Routes
    Route::prefix('approval')->group(function () {
        // Purchase Order Approval
        Route::get('/purchase-orders/count', [\App\Http\Controllers\ApprovalController::class, 'getPurchaseOrderCount']);
        Route::get('/purchase-orders', [\App\Http\Controllers\ApprovalController::class, 'getPurchaseOrders']);
        Route::post('/purchase-orders/{id}/verify', [\App\Http\Controllers\ApprovalController::class, 'verifyPurchaseOrder']);
        Route::post('/purchase-orders/{id}/validate', [\App\Http\Controllers\ApprovalController::class, 'validatePurchaseOrder']);
        Route::post('/purchase-orders/{id}/approve', [\App\Http\Controllers\ApprovalController::class, 'approvePurchaseOrder']);
        Route::post('/purchase-orders/{id}/reject', [\App\Http\Controllers\ApprovalController::class, 'rejectPurchaseOrder']);
        Route::get('/purchase-orders/{id}/progress', [\App\Http\Controllers\ApprovalController::class, 'getApprovalProgress']);
        Route::post('/purchase-orders/bulk-approve', [\App\Http\Controllers\ApprovalController::class, 'bulkApprovePurchaseOrders']);
        Route::post('/purchase-orders/bulk-reject', [\App\Http\Controllers\ApprovalController::class, 'bulkRejectPurchaseOrders']);

        // Memo Pembayaran Approval
        Route::get('/memo-pembayaran/count', [\App\Http\Controllers\ApprovalController::class, 'getMemoPembayaranCount']);
        Route::get('/memo-pembayarans', [\App\Http\Controllers\ApprovalController::class, 'getMemoPembayarans']);
        Route::post('/memo-pembayarans/{id}/verify', [\App\Http\Controllers\ApprovalController::class, 'verifyMemoPembayaran']);
        Route::post('/memo-pembayarans/{id}/validate', [\App\Http\Controllers\ApprovalController::class, 'validateMemoPembayaran']);
        Route::post('/memo-pembayarans/{id}/approve', [\App\Http\Controllers\ApprovalController::class, 'approveMemoPembayaran']);
        Route::post('/memo-pembayarans/{id}/reject', [\App\Http\Controllers\ApprovalController::class, 'rejectMemoPembayaran']);
        Route::get('/memo-pembayarans/{id}/progress', [\App\Http\Controllers\ApprovalController::class, 'getMemoPembayaranProgress']);
        Route::post('/memo-pembayarans/bulk-approve', [\App\Http\Controllers\ApprovalController::class, 'bulkApproveMemoPembayarans']);
        Route::post('/memo-pembayarans/bulk-reject', [\App\Http\Controllers\ApprovalController::class, 'bulkRejectMemoPembayarans']);

        // Payment Voucher Approval
        Route::get('/payment-vouchers/count', [\App\Http\Controllers\ApprovalController::class, 'getPaymentVoucherCount']);
        Route::get('/payment-vouchers', [\App\Http\Controllers\ApprovalController::class, 'getPaymentVouchers']);
        Route::post('/payment-vouchers/{id}/verify', [\App\Http\Controllers\ApprovalController::class, 'verifyPaymentVoucher']);
        Route::post('/payment-vouchers/{id}/validate', [\App\Http\Controllers\ApprovalController::class, 'validatePaymentVoucher']);
        Route::post('/payment-vouchers/{id}/approve', [\App\Http\Controllers\ApprovalController::class, 'approvePaymentVoucher']);
        Route::post('/payment-vouchers/{id}/reject', [\App\Http\Controllers\ApprovalController::class, 'rejectPaymentVoucher']);
        Route::get('/payment-vouchers/{id}/progress', [\App\Http\Controllers\ApprovalController::class, 'getPaymentVoucherProgress']);
        Route::post('/payment-vouchers/bulk-approve', [\App\Http\Controllers\ApprovalController::class, 'bulkApprovePaymentVouchers']);
        Route::post('/payment-vouchers/bulk-reject', [\App\Http\Controllers\ApprovalController::class, 'bulkRejectPaymentVouchers']);

        // BPB Approval
        Route::get('/bpbs/count', [\App\Http\Controllers\ApprovalController::class, 'getBpbCount']);
        Route::get('/bpbs', [\App\Http\Controllers\ApprovalController::class, 'getBpbs']);
        Route::post('/bpbs/{id}/approve', [\App\Http\Controllers\ApprovalController::class, 'approveBpb']);
        Route::post('/bpbs/{id}/reject', [\App\Http\Controllers\ApprovalController::class, 'rejectBpb']);
        Route::get('/bpbs/{id}/progress', [\App\Http\Controllers\ApprovalController::class, 'getBpbProgress']);
        Route::post('/bpbs/bulk-approve', [\App\Http\Controllers\ApprovalController::class, 'bulkApproveBpbs']);
        Route::post('/bpbs/bulk-reject', [\App\Http\Controllers\ApprovalController::class, 'bulkRejectBpbs']);

        // PO Anggaran Approval
        Route::get('/po-anggaran/count', [\App\Http\Controllers\ApprovalController::class, 'getPoAnggaranCount']);
        Route::get('/po-anggarans', [\App\Http\Controllers\ApprovalController::class, 'getPoAnggarans']);
        Route::post('/po-anggarans/{id}/verify', [\App\Http\Controllers\ApprovalController::class, 'verifyPoAnggaran']);
        Route::post('/po-anggarans/{id}/validate', [\App\Http\Controllers\ApprovalController::class, 'validatePoAnggaran']);
        Route::post('/po-anggarans/{id}/approve', [\App\Http\Controllers\ApprovalController::class, 'approvePoAnggaran']);
        Route::post('/po-anggarans/{id}/reject', [\App\Http\Controllers\ApprovalController::class, 'rejectPoAnggaran']);

        // Realisasi Approval
        Route::get('/realisasi/count', [\App\Http\Controllers\ApprovalController::class, 'getRealisasiCount']);
        Route::get('/realisasis', [\App\Http\Controllers\ApprovalController::class, 'getRealisasis']);
        Route::post('/realisasis/{id}/verify', [\App\Http\Controllers\ApprovalController::class, 'verifyRealisasi']);
        Route::post('/realisasis/{id}/approve', [\App\Http\Controllers\ApprovalController::class, 'approveRealisasi']);

        // Recent Activities
        Route::get('/recent-activities', [\App\Http\Controllers\ApprovalController::class, 'getRecentActivities']);
    });

    // User Role and Permissions
    Route::get('/user/role-permissions', function () {
        try {
            $user = \Illuminate\Support\Facades\Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            return response()->json([
                'role' => $user->role->name ?? '',
                'permissions' => $user->role->permissions ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error: ' . $e->getMessage()], 500);
        }
    });

    // Passcode Verification
    Route::post('/auth/verify-passcode', [\App\Http\Controllers\Settings\PasscodeController::class, 'verify']);
    Route::get('/auth/check-passcode-status', [\App\Http\Controllers\Settings\PasscodeController::class, 'checkStatus']);

    // Debug route for testing approval
    // Route::get('/debug/approval-test', function () {
    //     try {
    //         $user = \Illuminate\Support\Facades\Auth::user();
    //         if (!$user) {
    //             return response()->json(['error' => 'Not authenticated'], 401);
    //         }

    //         $po = \App\Models\PurchaseOrder::first();
    //         if (!$po) {
    //             return response()->json(['error' => 'No PO found'], 404);
    //         }

    //         return response()->json([
    //             'user' => [
    //                 'name' => $user->name,
    //                 'role' => $user->role->name ?? 'No role',
    //                 'departments' => $user->departments->pluck('name', 'id')->toArray()
    //             ],
    //             'po' => [
    //                 'id' => $po->id,
    //                 'no_po' => $po->no_po,
    //                 'status' => $po->status,
    //                 'department_id' => $po->department_id,
    //                 'department_name' => $po->department->name ?? 'No department',
    //                 'approved_by' => $po->approved_by,
    //                 'approved_at' => $po->approved_at,
    //                 'rejected_by' => $po->rejected_by,
    //                 'rejected_at' => $po->rejected_at,
    //             ]
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // });
});
