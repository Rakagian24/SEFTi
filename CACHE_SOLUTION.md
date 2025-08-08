# Solusi Masalah Cache Bank

## Masalah yang Ditemukan

Ketika user menambahkan data bank baru, dropdown di form create bank account tidak langsung menampilkan data bank yang baru ditambahkan. Data baru hanya muncul setelah melakukan `cache:clear` manual.

### Root Cause
1. **Cache TTL 1 jam** - Data bank di-cache selama 1 jam
2. **Cache tidak di-clear** saat ada perubahan data bank
3. **Multiple cache keys** - Data bank di-cache di berbagai tempat dengan key berbeda

## Solusi yang Diterapkan

### 1. Cache Clearing di BankController

Menambahkan cache clearing di semua method yang mengubah data bank:

```php
// Di BankController
private function clearBankCaches()
{
    cache()->forget('banks_active_accounts');
    cache()->forget('banks_all');
    cache()->forget('banks_active');
    cache()->forget('bank_accounts_active');
}
```

### 2. Bank Observer

Membuat `BankObserver` untuk auto-clear cache saat ada perubahan data bank:

```php
// app/Observers/BankObserver.php
class BankObserver
{
    public function created(Bank $bank): void
    {
        $this->clearBankCaches();
    }

    public function updated(Bank $bank): void
    {
        $this->clearBankCaches();
    }

    public function deleted(Bank $bank): void
    {
        $this->clearBankCaches();
    }
}
```

### 3. BankAccount Observer

Membuat `BankAccountObserver` untuk auto-clear cache saat ada perubahan data bank account:

```php
// app/Observers/BankAccountObserver.php
class BankAccountObserver
{
    public function created(BankAccount $bankAccount): void
    {
        $this->clearBankAccountCaches();
    }

    public function updated(BankAccount $bankAccount): void
    {
        $this->clearBankAccountCaches();
    }

    public function deleted(BankAccount $bankAccount): void
    {
        $this->clearBankAccountCaches();
    }
}
```

### 4. Observer Registration

Mendaftarkan observer di `AppServiceProvider`:

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    Bank::observe(BankObserver::class);
    BankAccount::observe(BankAccountObserver::class);
}
```

### 5. Manual Cache Clear Command

Membuat command untuk clear cache secara manual jika diperlukan:

```bash
# Clear bank caches only
php artisan cache:clear-bank

# Clear all bank and related caches
php artisan cache:clear-bank --all
```

## Cache Keys yang Dikelola

### Bank Caches
- `banks_active_accounts` - Bank untuk form bank account
- `banks_all` - Semua bank untuk bisnis partner
- `banks_active` - Bank aktif untuk supplier

### Bank Account Caches
- `bank_accounts_active` - Bank account aktif
- `departments_active_bank_masuk` - Department untuk bank masuk

## Testing

### Test Case 1: Menambah Bank Baru
1. Buka halaman Bank
2. Tambah bank baru (misal: "Bank Test")
3. Buka form create bank account
4. **Expected**: Bank baru muncul di dropdown tanpa perlu cache:clear

### Test Case 2: Update Bank
1. Edit bank yang sudah ada
2. Buka form create bank account
3. **Expected**: Perubahan bank ter-reflect di dropdown

### Test Case 3: Delete Bank
1. Hapus bank
2. Buka form create bank account
3. **Expected**: Bank yang dihapus tidak muncul di dropdown

## Monitoring

### Cache Hit Ratio
Monitor cache hit ratio untuk memastikan cache masih efektif:

```php
// Di controller, bisa tambahkan logging
Log::info('Cache hit for banks', ['cache_key' => 'banks_active_accounts']);
```

### Performance Impact
- Cache clearing tidak mempengaruhi performa secara signifikan
- Data bank relatif kecil dan jarang berubah
- Observer pattern memastikan cache selalu fresh

## Troubleshooting

### Jika cache masih tidak ter-clear:
1. Check observer registration di `AppServiceProvider`
2. Clear cache manual: `php artisan cache:clear-bank`
3. Check log untuk error observer

### Jika dropdown masih kosong:
1. Check apakah data bank ada di database
2. Check apakah bank status = 'active'
3. Clear cache manual dan refresh halaman

## Best Practices

1. **Gunakan Observer** untuk auto-clear cache
2. **Centralize cache keys** di satu tempat
3. **Monitor cache performance** secara regular
4. **Test cache behavior** setelah deploy
5. **Document cache strategy** untuk tim

## Future Improvements

1. **Redis Cache** - Untuk performa yang lebih baik
2. **Cache Warming** - Pre-load cache saat startup
3. **Cache Tags** - Untuk grouping cache yang lebih baik
4. **Cache Versioning** - Untuk cache invalidation yang lebih precise 
