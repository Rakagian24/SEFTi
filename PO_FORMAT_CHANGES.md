# Perubahan Format Nomor PO - DocumentNumberService

## Ringkasan Perubahan

Perubahan ini mengatur format nomor PO untuk tipe "Lainnya" agar menggunakan format `PO/ETC/VIII/2025/0001` (tanpa department alias) dan juga mengatur agar form input nominal terisi otomatis mengikuti total di grid.

## Perubahan yang Dibuat

### 1. Penambahan Konstanta Baru

```php
// Documents that don't have department alias (format: DOKUMEN/TIPE/BULAN/TAHUN/NOMOR_URUT)
const DOCUMENTS_WITHOUT_DEPARTMENT = ['PO'];
```

### 2. Perubahan Format Nomor Dokumen

#### Sebelum:
- PO Reguler: `PO/REG/DEPARTMENT_ALIAS/BULAN_ROMWI/TAHUN/NOMOR_URUT`
- PO Anggaran: `PO/AGR/DEPARTMENT_ALIAS/BULAN_ROMWI/TAHUN/NOMOR_URUT`
- PO Lainnya: `PO/ETC/DEPARTMENT_ALIAS/BULAN_ROMWI/TAHUN/NOMOR_URUT`

#### Sesudah:
- PO Reguler: `PO/REG/DEPARTMENT_ALIAS/BULAN_ROMWI/TAHUN/NOMOR_URUT`
- PO Anggaran: `PO/AGR/DEPARTMENT_ALIAS/BULAN_ROMWI/TAHUN/NOMOR_URUT`
- PO Lainnya: `PO/ETC/BULAN_ROMWI/TAHUN/NOMOR_URUT` (tanpa department alias)

### 3. Method yang Diubah

#### a. generateNumber()
- Menambahkan kondisi untuk `DOCUMENTS_WITHOUT_DEPARTMENT`
- Format PO Lainnya: `PO/ETC/BULAN_ROMWI/TAHUN/NOMOR_URUT`

#### b. generateNumberForDate()
- Menambahkan kondisi untuk `DOCUMENTS_WITHOUT_DEPARTMENT`
- Format PO Lainnya: `PO/ETC/BULAN_ROMWI/TAHUN/NOMOR_URUT`

#### c. generatePreviewNumber()
- Menambahkan kondisi untuk `DOCUMENTS_WITHOUT_DEPARTMENT`
- Format PO Lainnya: `PO/ETC/BULAN_ROMWI/TAHUN/NOMOR_URUT`

#### d. generatePreviewNumberForDate()
- Menambahkan kondisi untuk `DOCUMENTS_WITHOUT_DEPARTMENT`
- Format PO Lainnya: `PO/ETC/BULAN_ROMWI/TAHUN/NOMOR_URUT`

#### e. generateFormPreviewNumber()
- Menambahkan kondisi untuk `DOCUMENTS_WITHOUT_DEPARTMENT`
- Format PO Lainnya: `PO/ETC/BULAN_ROMWI/TAHUN/NOMOR_URUT`

### 4. Method Sequence yang Diubah

#### a. getNextSequence()
- Menambahkan kondisi untuk `DOCUMENTS_WITHOUT_DEPARTMENT`
- Mendukung format 5 bagian untuk PO Lainnya

#### b. getNextSequenceExcludeDraft()
- Menambahkan kondisi untuk `DOCUMENTS_WITHOUT_DEPARTMENT`
- Mendukung format 5 bagian untuk PO Lainnya

#### c. getNextSequenceForForm()
- Menambahkan kondisi untuk `DOCUMENTS_WITHOUT_DEPARTMENT`
- Mendukung format 5 bagian untuk PO Lainnya

### 5. Method Database yang Diubah

#### a. getLastDocument()
- PO Lainnya sekarang memiliki sequence terpisah tanpa department
- Query tidak lagi menggunakan `department_id` untuk PO Lainnya

#### b. getLastDocumentExcludeDraft()
- PO Lainnya sekarang memiliki sequence terpisah tanpa department
- Query tidak lagi menggunakan `department_id` untuk PO Lainnya

### 6. Method Parser yang Diubah

#### a. parseDocumentNumber()
- Mendukung format baru PO Lainnya: `PO/TIPE/BULAN/TAHUN/NOMOR_URUT`
- Deteksi otomatis format berdasarkan jumlah bagian dan tipe dokumen

### 7. Method Baru yang Ditambahkan

#### a. hasDepartment()
```php
public static function hasDepartment(string $documentType): bool
```
- Mengecek apakah dokumen memiliki field department

#### b. getDocumentFormatDescription()
```php
public static function getDocumentFormatDescription(string $documentType, ?string $tipe = null): string
```
- Memberikan deskripsi format dokumen untuk UI

## Contoh Penggunaan

### Generate Nomor PO Lainnya
```php
$nomorPO = DocumentNumberService::generateNumber(
    'Purchase Order', 
    'Lainnya', 
    $departmentId, 
    $departmentAlias
);
// Hasil: PO/ETC/VIII/2025/0001
```

### Generate Preview Nomor PO Lainnya
```php
$previewNomor = DocumentNumberService::generatePreviewNumber(
    'Purchase Order', 
    'Lainnya', 
    $departmentId, 
    $departmentAlias
);
// Hasil: PO/ETC/VIII/2025/0001
```

### Cek Format Dokumen
```php
$format = DocumentNumberService::getDocumentFormatDescription('Purchase Order', 'Lainnya');
// Hasil: "Format: PO/ETC/BULAN/TAHUN/NOMOR_URUT"
```

## Dampak Perubahan

### 1. Database
- PO Lainnya sekarang memiliki sequence terpisah dari PO Reguler
- Tidak lagi bergantung pada `department_id` untuk sequence

### 2. Frontend
- Form input nominal untuk PO Lainnya akan terisi otomatis mengikuti total di grid
- Format nomor PO Lainnya lebih sederhana dan mudah dibaca

### 3. Backend
- Service sekarang mendukung multiple format dokumen
- Lebih fleksibel untuk penambahan tipe dokumen baru

## Testing

Pastikan untuk melakukan testing pada:
1. Generate nomor PO Lainnya baru
2. Generate preview nomor PO Lainnya
3. Sequence number untuk PO Lainnya
4. Parsing nomor PO Lainnya yang sudah ada
5. Validasi uniqueness nomor PO Lainnya

## Catatan Penting

- Perubahan ini tidak mempengaruhi format nomor PO yang sudah ada
- PO Reguler dan Anggaran tetap menggunakan format lama dengan department alias
- Hanya PO Lainnya yang menggunakan format baru tanpa department alias
- Sequence number untuk PO Lainnya sekarang terpisah dan independen 
