# Sistem Otorisasi Role - SEFTI

## Overview

Sistem otorisasi role telah diimplementasikan untuk mengontrol akses pengguna ke berbagai menu dan fitur aplikasi berdasarkan role dan permission yang dimiliki.

## Struktur Role dan Permission

### Role yang Tersedia

1. **Staff Toko**
   - Permissions: `purchase_order`, `memo_pembayaran`, `bpb`, `anggaran`
   - Akses: Purchase Order, Memo Pembayaran, BPB, Anggaran

2. **Kepala Toko**
   - Permissions: `purchase_order`, `memo_pembayaran`, `bpb`, `anggaran`, `approval`
   - Akses: Purchase Order, Memo Pembayaran, BPB, Anggaran, Approval

3. **Staff Akunting & Finance**
   - Permissions: `purchase_order`, `bank`, `supplier`, `bisnis_partner`, `memo_pembayaran`, `bpb`, `payment_voucher`, `daftar_list_bayar`, `bank_masuk`, `bank_keluar`, `po_outstanding`
   - Akses: Purchase Order, Bank, Supplier, Bisnis Partner, Memo Pembayaran, BPB, Payment Voucher, Daftar List Bayar, Bank Masuk, Bank Keluar, PO Outstanding

4. **Kabag**
   - Permissions: `purchase_order`, `bank`, `supplier`, `bisnis_partner`, `memo_pembayaran`, `bpb`, `payment_voucher`, `daftar_list_bayar`, `bank_masuk`, `bank_keluar`, `po_outstanding`, `approval`
   - Akses: Semua akses Staff Akunting & Finance + Approval

5. **Kadiv**
   - Permissions: `approval`
   - Akses: Approval

6. **Direksi**
   - Permissions: `approval`
   - Akses: Approval

7. **Admin**
   - Permissions: `*` (wildcard - semua permission)
   - Akses: Semua menu dan fitur

## Implementasi Teknis

### 1. Middleware RoleMiddleware

File: `app/Http/Middleware/RoleMiddleware.php`

```php
// Penggunaan di routes
Route::middleware(['role:purchase_order'])->group(function () {
    Route::resource('purchase-orders', PurchaseOrderController::class);
});
```

### 2. User Model Extensions

File: `app/Models/User.php`

Method yang tersedia:
- `hasPermission($permission)` - Cek permission tunggal
- `hasAnyPermission($permissions)` - Cek salah satu permission
- `hasAllPermissions($permissions)` - Cek semua permission
- `hasRole($roleName)` - Cek role spesifik
- `hasAnyRole($roleNames)` - Cek salah satu role
- `canAccessMenu($menuPermission)` - Cek akses menu

### 3. Frontend Composable

File: `resources/js/composables/usePermissions.ts`

```typescript
const { 
  hasPermission, 
  canAccessPurchaseOrder, 
  canAccessBank,
  // ... dll
} = usePermissions();
```

### 4. Service Provider

File: `app/Providers/RoleAuthorizationServiceProvider.php`

Mendaftarkan:
- Middleware alias
- Blade directives
- Laravel Gates

## Penggunaan di Frontend

### 1. Menu Filtering di Sidebar

```vue
<script setup>
import { usePermissions } from '@/composables/usePermissions';

const { canAccessBank, canAccessSupplier } = usePermissions();

const filterMenuItems = (items) => {
  return items.filter(item => {
    switch (item.href) {
      case '/banks':
        return canAccessBank.value;
      case '/suppliers':
        return canAccessSupplier.value;
      default:
        return true;
    }
  });
};
</script>
```

### 2. Conditional Rendering

```vue
<template>
  <div v-if="canAccessPurchaseOrder">
    <PurchaseOrderForm />
  </div>
  
  <button v-if="hasPermission('approval')">
    Approve
  </button>
</template>
```

## Routes dengan Otorisasi

### Master Data (Admin Only)
```php
Route::middleware(['role:*'])->group(function () {
    Route::resource('departments', DepartmentController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('users', UserController::class);
    Route::resource('perihals', PerihalController::class);
});
```

### Bank Management
```php
Route::middleware(['role:bank'])->group(function () {
    Route::resource('banks', BankController::class);
    Route::resource('bank-accounts', BankAccountController::class);
});
```

### Purchase Order
```php
Route::middleware(['role:purchase_order'])->group(function () {
    Route::resource('purchase-orders', PurchaseOrderController::class);
});
```

### Bank Masuk & Matching
```php
Route::middleware(['role:bank_masuk'])->group(function () {
    Route::resource('bank-masuk', BankMasukController::class);
    Route::get('bank-matching', [BankMatchingController::class, 'index']);
});
```

## Error Handling

### 1. Custom 403 Error Page

File: `resources/js/pages/Error403.vue`

Menampilkan:
- Pesan error yang informatif
- Role dan permissions user
- Tombol kembali ke dashboard

### 2. Exception Handler

File: `app/Exceptions/Handler.php`

Menangani error 403 dengan:
- JSON response untuk API calls
- Inertia response untuk web requests

## Blade Directives

Tersedia directive untuk template Blade:

```blade
@role('Admin')
    <div>Admin content</div>
@endrole

@permission('purchase_order')
    <div>Purchase Order content</div>
@endpermission

@anypermission(['bank', 'supplier'])
    <div>Bank or Supplier content</div>
@endanypermission
```

## Laravel Gates

Gates yang tersedia untuk authorization:

```php
// Di controller atau policy
if (Gate::allows('access-purchase-order')) {
    // Allow access
}

// Atau menggunakan authorize
$this->authorize('access-bank');
```

## Testing

### 1. Unit Tests

```php
public function test_user_can_access_purchase_order()
{
    $user = User::factory()->create([
        'role_id' => Role::where('name', 'Staff Toko')->first()->id
    ]);
    
    $this->actingAs($user);
    
    $response = $this->get('/purchase-orders');
    $response->assertStatus(200);
}
```

### 2. Feature Tests

```php
public function test_unauthorized_access_returns_403()
{
    $user = User::factory()->create([
        'role_id' => Role::where('name', 'Staff Toko')->first()->id
    ]);
    
    $this->actingAs($user);
    
    $response = $this->get('/banks');
    $response->assertStatus(403);
}
```

## Maintenance

### 1. Menambah Permission Baru

1. Update `RoleSeeder.php` dengan permission baru
2. Update `RoleAuthorizationServiceProvider.php` dengan gate baru
3. Update `usePermissions.ts` dengan computed property baru
4. Update routes dengan middleware yang sesuai

### 2. Menambah Role Baru

1. Tambah role di `RoleSeeder.php`
2. Definisikan permissions untuk role tersebut
3. Jalankan seeder: `php artisan db:seed --class=RoleSeeder`

### 3. Update Permission Existing Role

1. Update data di database
2. Clear cache: `php artisan cache:clear`

## Security Considerations

1. **Always verify on server-side**: Frontend filtering hanya untuk UX, selalu verifikasi di backend
2. **Use middleware**: Terapkan middleware di semua routes yang memerlukan otorisasi
3. **Validate permissions**: Gunakan policy untuk operasi CRUD yang kompleks
4. **Log access attempts**: Monitor akses yang ditolak untuk security audit
5. **Regular review**: Review permissions secara berkala

## Troubleshooting

### 1. Permission tidak berfungsi
- Cek apakah user memiliki role yang benar
- Cek apakah role memiliki permissions yang benar
- Clear cache: `php artisan cache:clear`

### 2. Menu tidak muncul di sidebar
- Cek `usePermissions` composable
- Cek filter logic di `AppSidebar.vue`
- Pastikan route sudah terdaftar dengan benar

### 3. 403 Error tidak menampilkan halaman custom
- Cek `Exception Handler`
- Pastikan `Error403.vue` sudah dibuat
- Clear view cache: `php artisan view:clear` 
