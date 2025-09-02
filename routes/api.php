<?php

use App\Http\Controllers\Settings\MessageController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\PphController;
use Illuminate\Support\Facades\Route;

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
        Route::post('/purchase-orders/{id}/approve', [\App\Http\Controllers\ApprovalController::class, 'approvePurchaseOrder']);
        Route::post('/purchase-orders/{id}/reject', [\App\Http\Controllers\ApprovalController::class, 'rejectPurchaseOrder']);
        Route::post('/purchase-orders/bulk-approve', [\App\Http\Controllers\ApprovalController::class, 'bulkApprovePurchaseOrders']);
        Route::post('/purchase-orders/bulk-reject', [\App\Http\Controllers\ApprovalController::class, 'bulkRejectPurchaseOrders']);

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
    Route::get('/debug/approval-test', function () {
        try {
            $user = \Illuminate\Support\Facades\Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            $po = \App\Models\PurchaseOrder::first();
            if (!$po) {
                return response()->json(['error' => 'No PO found'], 404);
            }

            return response()->json([
                'user' => [
                    'name' => $user->name,
                    'role' => $user->role->name ?? 'No role',
                    'departments' => $user->departments->pluck('name', 'id')->toArray()
                ],
                'po' => [
                    'id' => $po->id,
                    'no_po' => $po->no_po,
                    'status' => $po->status,
                    'department_id' => $po->department_id,
                    'department_name' => $po->department->name ?? 'No department',
                    'approved_by' => $po->approved_by,
                    'approved_at' => $po->approved_at,
                    'rejected_by' => $po->rejected_by,
                    'rejected_at' => $po->rejected_at,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});
