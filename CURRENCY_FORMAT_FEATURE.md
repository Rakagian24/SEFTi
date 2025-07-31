# Currency Format Feature Implementation

## Overview
Implementasi format currency untuk modul bank masuk dengan dukungan pemisah ribuan dan decimal yang sesuai dengan standar Indonesia.

## Fitur yang Diimplementasikan

### 1. Format Currency di Frontend
- **Form Input**: Input nominal dengan format otomatis (contoh: 200,000.50)
- **Table Display**: Tampilan nominal dengan format yang sama di tabel
- **Currency Support**: Mendukung IDR dan USD dengan symbol yang sesuai

### 2. Utility Functions (`resources/js/lib/currencyUtils.ts`)
- `formatCurrency()`: Format angka ke string currency dengan pemisah ribuan
- `parseCurrency()`: Parse string currency kembali ke angka raw
- `isValidCurrencyInput()`: Validasi input currency
- `getCurrencySymbol()`: Mendapatkan symbol currency

### 3. Composable (`resources/js/composables/useCurrencyInput.ts`)
- `useCurrencyInput()`: Composable untuk handling input currency
- Mendukung real-time formatting
- Validasi input keyboard
- Mencegah multiple decimal points

## Implementasi di Komponen

### BankMasukForm.vue
- Menggunakan `useCurrencyInput` composable
- Input nominal dengan format otomatis
- Support decimal input (contoh: 125,000.5)
- Validasi keyboard input

### BankMasukTable.vue
- Menggunakan `formatCurrency` utility
- Display nominal dengan format yang konsisten
- Support multiple currencies (IDR/USD)

## Format yang Didukung

### IDR (Indonesian Rupiah)
- Symbol: `Rp `
- Thousand separator: `,` (koma)
- Decimal separator: `.` (titik)
- Contoh: `Rp 200,000.50`

### USD (US Dollar)
- Symbol: `$`
- Thousand separator: `,` (koma)
- Decimal separator: `.` (titik)
- Contoh: `$1,250.75`

## Backend Handling
- Data disimpan dalam format raw number (tanpa formatting)
- Decimal menggunakan titik (.) sebagai separator
- Tidak ada pemisah ribuan di database
- Contoh: `200000.50` disimpan sebagai `200000.50`

## Contoh Penggunaan

### Input User
- User input: `200000` → Display: `Rp 200,000`
- User input: `125000.5` → Display: `Rp 125,000.5`
- User input: `1000000.25` → Display: `Rp 1,000,000.25`

### Database Storage
- `200000` → disimpan sebagai `200000`
- `125000.5` → disimpan sebagai `125000.5`
- `1000000.25` → disimpan sebagai `1000000.25`

## Keamanan Data
- Data sensitive (uang) disimpan dengan presisi penuh
- Decimal tidak boleh hilang atau berubah
- Format hanya untuk display, tidak mempengaruhi data asli
- Validasi input untuk mencegah data yang tidak valid

## Testing
- Build berhasil tanpa error
- Semua utility functions terimplementasi dengan benar
- Composable dapat digunakan di komponen lain
- Format konsisten antara form dan table 
