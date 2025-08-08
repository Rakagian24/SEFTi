# Perbaikan Sistem Penomoran Bank Masuk (BM)

## Masalah yang Ditemukan

Sistem penomoran BM sebelumnya menggunakan logika `count()` untuk menghitung jumlah record yang sudah ada, kemudian menambahkan 1 untuk mendapatkan nomor berikutnya. Ini menyebabkan masalah ketika ada gap di nomor urut (misalnya record yang dihapus atau ada nomor yang hilang).

### Contoh Masalah:
- BM yang sudah ada: 0001, 0002, 0003, 0005, 0006, 0007
- Sistem menghitung: 6 record
- Generate nomor: 0007 (sudah ada!)

## Solusi yang Diterapkan

### 1. Mengubah Logika Penomoran
Mengganti penggunaan `count()` dengan pencarian nomor urut terbesar yang sudah ada:

**Sebelum:**
```php
$count = \App\Models\BankMasuk::where('no_bm', 'like', $like)->count();
$autoNum = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
```

**Sesudah:**
```php
// Cari nomor urut terbesar yang sudah ada
$maxNumber = \App\Models\BankMasuk::where('no_bm', 'like', $like)
    ->get()
    ->map(function($item) {
        // Ekstrak nomor urut dari no_bm (4 digit terakhir)
        if (preg_match('/\/(\d{4})$/', $item->no_bm, $matches)) {
            return intval($matches[1]);
        }
        return 0;
    })
    ->max();

$nextNumber = $maxNumber ? $maxNumber + 1 : 1;
$autoNum = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
```

### 2. File yang Diperbaiki

1. **`app/Http/Controllers/BankMasukController.php`**
   - Method `store()` - untuk pembuatan BM baru
   - Method `update()` - untuk update BM yang ada
   - Method `getNextNumber()` - untuk preview nomor BM

### 3. Logika yang Diperbaiki

#### A. Pembuatan BM Baru (store method)
- Sekarang mencari nomor urut terbesar untuk departemen dan periode tertentu
- Generate nomor berikutnya berdasarkan nomor terbesar + 1

#### B. Update BM (update method)
- Mempertahankan logika khusus untuk perubahan departemen/tanggal
- Menggunakan logika yang sama untuk generate nomor baru

#### C. Preview Nomor (getNextNumber method)
- Menggunakan logika yang sama untuk konsistensi
- Exclude record yang sedang diedit

## Hasil Perbaikan

### Sebelum Perbaikan:
- BM yang ada: 0001, 0002, 0003, 0005, 0006, 0007
- Generate: 0007 (duplikat!)

### Sesudah Perbaikan:
- BM yang ada: 0001, 0002, 0003, 0005, 0006, 0007
- Generate: 0008 (benar!)

## Testing

Untuk memastikan perbaikan bekerja dengan baik:

1. **Test dengan data yang ada:**
   ```bash
   php artisan tinker
   # Cek data BM yang sudah ada
   \App\Models\BankMasuk::where('no_bm', 'like', 'BM/BKR92/VIII-2025/%')->orderBy('no_bm')->get()->pluck('no_bm')
   ```

2. **Test pembuatan BM baru:**
   - Buat BM baru dengan departemen BKR92 dan tanggal Agustus 2025
   - Pastikan nomor yang dihasilkan adalah 0020 (bukan 0019 yang sudah ada)

## Catatan Penting

1. **Backward Compatibility:** Perbaikan ini tidak mengubah format nomor BM yang sudah ada
2. **Performance:** Logika baru menggunakan `get()` dan `map()` yang mungkin sedikit lebih lambat, tapi lebih akurat
3. **Consistency:** Semua method yang terkait dengan penomoran BM sekarang menggunakan logika yang sama

## Monitoring

Setelah deploy, monitor:
1. Apakah masih ada duplikasi nomor BM
2. Apakah performa sistem masih baik
3. Apakah ada error yang muncul

Jika ada masalah, bisa rollback ke versi sebelumnya atau lakukan penyesuaian lebih lanjut. 
