# Fitur Export CSV Bank Masuk dengan Format Currency Dinamis

## Deskripsi
Fitur export CSV pada modul Bank Masuk mendukung format currency yang dinamis berdasarkan bank account yang dipilih. Data yang diexport akan menampilkan format currency yang sesuai dengan bank masing-masing.

## Format Export

### Format File
- **Format**: CSV (Comma Separated Values)
- **Encoding**: UTF-8
- **Delimiter**: Comma (,)
- **Filename**: `bank_masuk_export.csv`

### Format Currency dalam CSV
- **Bank IDR**: `Rp 1,000,000`
- **Bank USD**: `$1,000,000`

## Implementasi

### Backend (Laravel)

#### BankMasukController.php - Method exportExcel()
```php
public function exportExcel(Request $request)
{
    // Load data dengan relasi bank untuk mendapatkan currency
    $query = BankMasuk::with(['bankAccount.bank', 'creator', 'updater'])
        ->whereIn('id', $ids);
    
    // Format currency berdasarkan bank account
    case 'nilai':
        $currency = $row->bankAccount && $row->bankAccount->bank ? 
                   $row->bankAccount->bank->currency : 'IDR';
        $symbol = $currency === 'USD' ? '$' : 'Rp ';
        $formattedValue = $symbol . number_format($row->nilai, 0, ',', '.');
        $data[] = $formattedValue;
        break;
}
```

### Frontend (Vue.js)

#### Index.vue - Export Modal
- **Field Selection**: User dapat memilih kolom yang ingin diexport
- **Data Selection**: User dapat memilih data yang ingin diexport
- **Download**: Menggunakan `useSecureDownload` composable

## Kolom yang Tersedia untuk Export

1. **no_bm** - No. Bank Masuk
2. **tanggal** - Tanggal transaksi
3. **tipe_po** - Tipe PO (Reguler/Anggaran/Lainnya)
4. **terima_dari** - Terima Dari
5. **input_lainnya** - Input Lainnya
6. **purchase_order_id** - Purchase Order ID
7. **bank_account** - Nama Pemilik Rekening
8. **no_rekening** - Nomor Rekening
9. **nilai** - Nominal (dengan format currency dinamis) ⭐
10. **note** - Catatan
11. **created_at** - Waktu Dibuat
12. **updated_at** - Waktu Diupdate
13. **created_by** - Dibuat Oleh
14. **updated_by** - Diupdate Oleh

## Cara Menggunakan Export

### Langkah 1: Pilih Data
1. Buka halaman Bank Masuk
2. Pilih data yang ingin diexport dengan checkbox
3. Klik tombol "Export" di toolbar

### Langkah 2: Pilih Kolom
1. Modal export akan muncul
2. Pilih kolom yang ingin diexport
3. Pastikan kolom "nilai" dipilih untuk mendapatkan format currency
4. Klik "Export"

### Langkah 3: Download File
1. File CSV akan otomatis terdownload
2. Nama file: `bank_masuk_export.csv`
3. Format currency akan sesuai dengan bank account masing-masing

## Contoh Output CSV

```csv
no_bm,tanggal,tipe_po,terima_dari,nilai,bank_account,note
BM/Andi Wijaya/I-2025/001,2025-01-15,Reguler,Customer,Rp 1,000,000,Andi Wijaya,Test IDR
BM/John Smith/I-2025/002,2025-01-15,Reguler,Customer,$1,000,000,John Smith,Test USD
```

## Keuntungan

1. **Format Currency Konsisten**: Export menampilkan format currency yang sama dengan tampilan aplikasi
2. **User Experience**: User dapat dengan mudah membedakan currency dalam file CSV
3. **Data Integrity**: Format currency otomatis berdasarkan bank account
4. **Flexibility**: Mudah menambah currency baru di masa depan

## Maintenance

### Menambah Currency Baru
1. Update method `exportExcel()` di `BankMasukController.php`
2. Tambahkan case untuk currency baru di switch statement
3. Update symbol dan format sesuai kebutuhan

### Menambah Kolom Baru
1. Tambahkan field baru di `exportFields` di `Index.vue`
2. Tambahkan case baru di method `exportExcel()` jika diperlukan formatting khusus
3. Test export dengan data yang memiliki kolom baru

## Testing

### Test Case 1: Export Data IDR
1. Pilih data bank masuk dengan bank account IDR
2. Export dengan kolom "nilai"
3. Hasil: Format "Rp 1,000,000" di CSV

### Test Case 2: Export Data USD
1. Pilih data bank masuk dengan bank account USD
2. Export dengan kolom "nilai"
3. Hasil: Format "$1,000,000" di CSV

### Test Case 3: Export Mixed Data
1. Pilih data campuran (IDR dan USD)
2. Export dengan kolom "nilai"
3. Hasil: Format currency sesuai dengan masing-masing bank account 
