# Payment Voucher Approval Implementation

## ✅ COMPLETED - Backend Implementation

### 1. Database Migration
**File**: `database/migrations/2025_10_14_113242_add_approval_fields_to_payment_vouchers_table.php`
- Added approval workflow fields to `payment_vouchers` table:
  - `verified_by`, `verified_at`, `verification_notes`
  - `approved_by`, `approved_at`, `approval_notes`
  - `rejected_by`, `rejected_at`, `rejection_reason`
  - `canceled_by`, `canceled_at`, `cancellation_reason`
- Migration has been run successfully ✅

### 2. PaymentVoucher Model
**File**: `app/Models/PaymentVoucher.php`
- Added approval fields to `$fillable` array
- Added datetime casts for approval timestamps
- Added approval relationships:
  - `verifier()`, `approver()`, `rejecter()`, `canceller()`

### 3. Approval Workflow Service
**File**: `app/Services/ApprovalWorkflowService.php`
- Added Payment Voucher workflow methods:
  - `getWorkflowForPaymentVoucher()` - Simple workflow: Creator → Kabag (verify) → Direksi (approve)
  - `canUserApprovePaymentVoucher()` - Permission checking
  - `getApprovalProgressForPaymentVoucher()` - Progress tracking
  - `hasUserPerformedActionForPaymentVoucher()` - Duplicate action prevention

**Workflow**: Creator → Kabag (Verify) → Direksi (Approve)
- Steps: `['verified', 'approved']`
- Roles: `[Creator Role, 'Kabag', 'Direksi']`

### 4. Approval Controller
**File**: `app/Http/Controllers/ApprovalController.php`
- Added Payment Voucher approval methods:
  - `paymentVouchers()` - Display approval page
  - `getPaymentVouchers()` - Get PV list with filters
  - `getPaymentVoucherCount()` - Dashboard count
  - `verifyPaymentVoucher()` - Kabag verification
  - `approvePaymentVoucher()` - Direksi approval
  - `rejectPaymentVoucher()` - Rejection
  - `bulkApprovePaymentVouchers()` - Bulk approval
  - `bulkRejectPaymentVouchers()` - Bulk rejection
  - `getPaymentVoucherProgress()` - Progress API
  - `paymentVoucherDetail()` - Detail page
  - `paymentVoucherLog()` - Log page
- Updated `logApprovalActivity()` to handle PaymentVoucher
- Updated `getActionDescription()` to handle PaymentVoucher
- Updated `getWorkflowMapping()` to include payment_voucher workflow

### 5. Routes
**Files**: `routes/web.php` and `routes/api.php`

**Web Routes** (added):
```php
Route::get('approval/payment-vouchers', [ApprovalController::class, 'paymentVouchers']);
Route::post('approval/payment-vouchers/{id}/verify', [ApprovalController::class, 'verifyPaymentVoucher']);
Route::post('approval/payment-vouchers/{id}/approve', [ApprovalController::class, 'approvePaymentVoucher']);
Route::post('approval/payment-vouchers/{id}/reject', [ApprovalController::class, 'rejectPaymentVoucher']);
Route::get('approval/payment-vouchers/{paymentVoucher}/detail', [ApprovalController::class, 'paymentVoucherDetail']);
Route::get('approval/payment-vouchers/{paymentVoucher}/log', [ApprovalController::class, 'paymentVoucherLog']);
```

**API Routes** (added):
```php
Route::get('/payment-vouchers/count', [ApprovalController::class, 'getPaymentVoucherCount']);
Route::get('/payment-vouchers', [ApprovalController::class, 'getPaymentVouchers']);
Route::post('/payment-vouchers/{id}/verify', [ApprovalController::class, 'verifyPaymentVoucher']);
Route::post('/payment-vouchers/{id}/approve', [ApprovalController::class, 'approvePaymentVoucher']);
Route::post('/payment-vouchers/{id}/reject', [ApprovalController::class, 'rejectPaymentVoucher']);
Route::get('/payment-vouchers/{id}/progress', [ApprovalController::class, 'getPaymentVoucherProgress']);
Route::post('/payment-vouchers/bulk-approve', [ApprovalController::class, 'bulkApprovePaymentVouchers']);
Route::post('/payment-vouchers/bulk-reject', [ApprovalController::class, 'bulkRejectPaymentVouchers']);
```

### 6. Approval Index Updated
**File**: `resources/js/pages/approval/Index.vue`
- Uncommented Payment Voucher card
- Added `paymentVoucherCount` ref
- Added Payment Voucher count fetching logic
- Card is now visible for roles: Kabag, Kadiv, Direksi

---

## 📋 TODO - Frontend Implementation

The frontend components need to be created by copying and adapting the Memo Pembayaran approval components. Here's the mapping:

### Components to Create:

1. **PaymentVoucherApproval.vue**
   - Copy from: `resources/js/pages/approval/MemoPembayaranApproval.vue`
   - Location: `resources/js/pages/approval/PaymentVoucherApproval.vue`
   - Changes needed:
     - Replace `MemoPembayaran` → `PaymentVoucher`
     - Replace `memo-pembayaran` → `payment-voucher`
     - Replace `no_mb` → `no_pv`
     - Update workflow logic (only verify and approve, no validate)
     - Update selectable statuses for Kabag (In Progress) and Direksi (Verified)

2. **PaymentVoucherApprovalDetail.vue**
   - Copy from: `resources/js/pages/approval/MemoPembayaranApprovalDetail.vue`
   - Location: `resources/js/pages/approval/PaymentVoucherApprovalDetail.vue`
   - Changes needed:
     - Replace `MemoPembayaran` → `PaymentVoucher`
     - Replace `memo-pembayaran` → `payment-voucher`
     - Update field mappings (PV has different fields than MB)

3. **PaymentVoucherApprovalFilter.vue**
   - Copy from: `resources/js/components/approval/MemoPembayaranApprovalFilter.vue`
   - Location: `resources/js/components/approval/PaymentVoucherApprovalFilter.vue`
   - Changes needed:
     - Update filter fields to match Payment Voucher fields
     - Keep: department, status, date range, supplier, metode_bayar
     - Remove MB-specific fields if any

4. **PaymentVoucherApprovalTable.vue**
   - Copy from: `resources/js/components/approval/MemoPembayaranApprovalTable.vue`
   - Location: `resources/js/components/approval/PaymentVoucherApprovalTable.vue`
   - Changes needed:
     - Replace `MemoPembayaran` → `PaymentVoucher`
     - Update column definitions for PV fields
     - Update action buttons (only verify/approve, no validate)

5. **PaymentVoucherApprovalLog.vue**
   - Copy from: `resources/js/pages/approval/MemoPembayaranApprovalLog.vue`
   - Location: `resources/js/pages/approval/PaymentVoucherApprovalLog.vue`
   - Changes needed:
     - Replace `MemoPembayaran` → `PaymentVoucher`
     - Replace `memo-pembayaran` → `payment-voucher`

---

## 🔑 Key Differences: Payment Voucher vs Memo Pembayaran

| Aspect | Memo Pembayaran | Payment Voucher |
|--------|----------------|-----------------|
| **Workflow** | Creator → Kepala Toko → Kadiv | Creator → Kabag → Direksi |
| **Steps** | verify, approve (or just approve) | verify, approve |
| **Kabag Role** | Approves for Staff Akunting & Finance | Verifies for all |
| **Direksi Role** | Not involved | Final approver |
| **Document Number** | `no_mb` | `no_pv` |
| **Related PO** | Multiple POs possible | Single PO |

---

## 🎯 Workflow Summary

### Payment Voucher Approval Flow:
1. **Creator** creates Payment Voucher → Status: `Draft`
2. **Creator** sends for approval → Status: `In Progress`
3. **Kabag** verifies → Status: `Verified`
4. **Direksi** approves → Status: `Approved`

### Role Permissions:
- **Admin**: Can perform all actions (bypass)
- **Kabag**: Can verify PV when status is `In Progress`
- **Kadiv**: Can verify PV when status is `In Progress` (same as Kabag)
- **Direksi**: Can approve PV when status is `Verified`

### Rejection:
- Can be done at any stage (`In Progress` or `Verified`)
- User who already performed an action cannot reject
- Sets status to `Rejected`

---

## 📝 Implementation Steps for Frontend

1. **Copy the Memo Pembayaran components** to create Payment Voucher versions
2. **Find and replace** all instances:
   - `MemoPembayaran` → `PaymentVoucher`
   - `memo-pembayaran` → `payment-voucher`
   - `memo_pembayaran` → `payment_voucher`
   - `no_mb` → `no_pv`
   - `Memo Pembayaran` → `Payment Voucher`

3. **Update workflow logic**:
   - Remove `validate` action (PV only has verify and approve)
   - Update `bulkActionType` computed to return only `verify` or `approve`
   - Update `selectableStatuses`:
     - Kabag: `['In Progress']`
     - Direksi: `['Verified']`
     - Admin: `['In Progress', 'Verified']`

4. **Update field mappings**:
   - Payment Voucher has: `no_pv`, `tanggal`, `nominal`, `metode_bayar`, `keterangan`
   - Ensure table columns match PV fields

5. **Test the workflow**:
   - Create a PV as any user
   - Send for approval
   - Login as Kabag → Verify
   - Login as Direksi → Approve
   - Test rejection at both stages
   - Test bulk operations

---

## ✅ Verification Checklist

- [x] Migration created and run
- [x] Model updated with approval fields and relationships
- [x] Workflow service implemented
- [x] Controller methods implemented
- [x] Web routes added
- [x] API routes added
- [x] Approval index updated with PV card
- [ ] PaymentVoucherApproval.vue created
- [ ] PaymentVoucherApprovalDetail.vue created
- [ ] PaymentVoucherApprovalFilter.vue created
- [ ] PaymentVoucherApprovalTable.vue created
- [ ] PaymentVoucherApprovalLog.vue created
- [ ] End-to-end testing completed

---

## 🚀 Quick Start Commands

```bash
# Backend is already complete, just need to create frontend components

# Copy and adapt components (example):
cp resources/js/pages/approval/MemoPembayaranApproval.vue resources/js/pages/approval/PaymentVoucherApproval.vue
cp resources/js/pages/approval/MemoPembayaranApprovalDetail.vue resources/js/pages/approval/PaymentVoucherApprovalDetail.vue
cp resources/js/components/approval/MemoPembayaranApprovalFilter.vue resources/js/components/approval/PaymentVoucherApprovalFilter.vue
cp resources/js/components/approval/MemoPembayaranApprovalTable.vue resources/js/components/approval/PaymentVoucherApprovalTable.vue
cp resources/js/pages/approval/MemoPembayaranApprovalLog.vue resources/js/pages/approval/PaymentVoucherApprovalLog.vue

# Then do find/replace in each file as described above
```

---

## 📞 Support

If you encounter any issues:
1. Check the browser console for errors
2. Check Laravel logs: `storage/logs/laravel.log`
3. Verify routes are registered: `php artisan route:list | grep payment-voucher`
4. Test API endpoints directly using Postman or similar tools

---

**Implementation Date**: October 14, 2025
**Workflow**: Creator → Kabag (Verify) → Direksi (Approve)
