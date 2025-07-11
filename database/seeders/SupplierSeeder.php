<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\Bank;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        // Get available banks
        $banks = Bank::where('status', 'active')->get();

        if ($banks->isEmpty()) {
            // If no banks exist, create some default banks
            $banks = collect([
                Bank::create([
                    'kode_bank' => 'BCA',
                    'nama_bank' => 'Bank BCA',
                    'singkatan' => 'BCA',
                    'status' => 'active',
                ]),
                Bank::create([
                    'kode_bank' => 'MDR',
                    'nama_bank' => 'Bank Mandiri',
                    'singkatan' => 'MDR',
                    'status' => 'active',
                ]),
                Bank::create([
                    'kode_bank' => 'BNI',
                    'nama_bank' => 'Bank BNI',
                    'singkatan' => 'BNI',
                    'status' => 'active',
                ]),
                Bank::create([
                    'kode_bank' => 'BRI',
                    'nama_bank' => 'Bank BRI',
                    'singkatan' => 'BRI',
                    'status' => 'active',
                ]),
            ]);
        }

        $bankNames = $banks->pluck('nama_bank')->toArray();

        $suppliers = [
            // Large Corporations
            [
                'nama_supplier' => 'PT Maju Bersama Sejahtera',
                'alamat' => 'Jl. Sudirman No. 123, Jakarta Pusat, DKI Jakarta 12190',
                'email' => 'procurement@majubersama.com',
                'no_telepon' => '021-555-0123',
                'bank_1' => $bankNames[0] ?? 'Bank BCA',
                'nama_rekening_1' => 'PT Maju Bersama Sejahtera',
                'no_rekening_1' => '1234567890',
                'bank_2' => $bankNames[1] ?? 'Bank Mandiri',
                'nama_rekening_2' => 'PT Maju Bersama Sejahtera',
                'no_rekening_2' => '0987654321',
                'bank_3' => $bankNames[2] ?? 'Bank BNI',
                'nama_rekening_3' => 'PT Maju Bersama Sejahtera',
                'no_rekening_3' => '1122334455',
                'terms_of_payment' => '30 Hari',
            ],
            [
                'nama_supplier' => 'CV Mitra Sukses Abadi',
                'alamat' => 'Jl. Thamrin No. 45, Jakarta Selatan, DKI Jakarta 12150',
                'email' => 'sales@mitrasukses.co.id',
                'no_telepon' => '021-555-0456',
                'bank_1' => $bankNames[3] ?? 'Bank BRI',
                'nama_rekening_1' => 'CV Mitra Sukses Abadi',
                'no_rekening_1' => '5566778899',
                'terms_of_payment' => '15 Hari',
            ],
            [
                'nama_supplier' => 'UD Sentosa Makmur',
                'alamat' => 'Jl. Gatot Subroto No. 67, Jakarta Barat, DKI Jakarta 11470',
                'email' => 'info@sentosamakmur.com',
                'no_telepon' => '021-555-0789',
                'bank_1' => $bankNames[0] ?? 'Bank BCA',
                'nama_rekening_1' => 'UD Sentosa Makmur',
                'no_rekening_1' => '9988776655',
                'bank_2' => $bankNames[1] ?? 'Bank Mandiri',
                'nama_rekening_2' => 'UD Sentosa Makmur',
                'no_rekening_2' => '4433221100',
                'terms_of_payment' => '60 Hari',
            ],

            // Medium Enterprises
            [
                'nama_supplier' => 'PT Jaya Abadi Perkasa',
                'alamat' => 'Jl. Merdeka No. 89, Bandung, Jawa Barat 40111',
                'email' => 'contact@jayaabadi.co.id',
                'no_telepon' => '022-555-0123',
                'bank_1' => $bankNames[2] ?? 'Bank BNI',
                'nama_rekening_1' => 'PT Jaya Abadi Perkasa',
                'no_rekening_1' => '2233445566',
                'bank_2' => $bankNames[3] ?? 'Bank BRI',
                'nama_rekening_2' => 'PT Jaya Abadi Perkasa',
                'no_rekening_2' => '3344556677',
                'terms_of_payment' => '45 Hari',
            ],
            [
                'nama_supplier' => 'CV Prima Sukses Mandiri',
                'alamat' => 'Jl. Diponegoro No. 234, Surabaya, Jawa Timur 60241',
                'email' => 'admin@primasukses.com',
                'no_telepon' => '031-555-0456',
                'bank_1' => $bankNames[0] ?? 'Bank BCA',
                'nama_rekening_1' => 'CV Prima Sukses Mandiri',
                'no_rekening_1' => '4455667788',
                'terms_of_payment' => '7 Hari',
            ],
            [
                'nama_supplier' => 'UD Makmur Jaya',
                'alamat' => 'Jl. Ahmad Yani No. 56, Semarang, Jawa Tengah 50123',
                'email' => 'sales@makmurjaya.co.id',
                'no_telepon' => '024-555-0789',
                'bank_1' => $bankNames[1] ?? 'Bank Mandiri',
                'nama_rekening_1' => 'UD Makmur Jaya',
                'no_rekening_1' => '5566778899',
                'bank_2' => $bankNames[2] ?? 'Bank BNI',
                'nama_rekening_2' => 'UD Makmur Jaya',
                'no_rekening_2' => '6677889900',
                'bank_3' => $bankNames[3] ?? 'Bank BRI',
                'nama_rekening_3' => 'UD Makmur Jaya',
                'no_rekening_3' => '7788990011',
                'terms_of_payment' => '90 Hari',
            ],

            // Small Enterprises
            [
                'nama_supplier' => 'Toko Sumber Rejeki',
                'alamat' => 'Jl. Pasar Baru No. 12, Medan, Sumatera Utara 20112',
                'email' => 'info@sumberrejeki.com',
                'no_telepon' => '061-555-0123',
                'bank_1' => $bankNames[0] ?? 'Bank BCA',
                'nama_rekening_1' => 'Toko Sumber Rejeki',
                'no_rekening_1' => '8899001122',
                'terms_of_payment' => '30 Hari',
            ],
            [
                'nama_supplier' => 'Warung Makmur Sejahtera',
                'alamat' => 'Jl. Veteran No. 78, Palembang, Sumatera Selatan 30111',
                'email' => 'contact@makmursejahtera.co.id',
                'no_telepon' => '0711-555-0456',
                'bank_1' => $bankNames[3] ?? 'Bank BRI',
                'nama_rekening_1' => 'Warung Makmur Sejahtera',
                'no_rekening_1' => '9900112233',
                'bank_2' => $bankNames[1] ?? 'Bank Mandiri',
                'nama_rekening_2' => 'Warung Makmur Sejahtera',
                'no_rekening_2' => '0011223344',
                'terms_of_payment' => '15 Hari',
            ],
            [
                'nama_supplier' => 'Koperasi Sejahtera Bersama',
                'alamat' => 'Jl. Sudirman No. 45, Makassar, Sulawesi Selatan 90111',
                'email' => 'admin@koperasisejahtera.com',
                'no_telepon' => '0411-555-0789',
                'bank_1' => $bankNames[2] ?? 'Bank BNI',
                'nama_rekening_1' => 'Koperasi Sejahtera Bersama',
                'no_rekening_1' => '1122334455',
                'terms_of_payment' => '60 Hari',
            ],

            // Specialized Suppliers
            [
                'nama_supplier' => 'PT Teknologi Maju Indonesia',
                'alamat' => 'Jl. Hayam Wuruk No. 123, Jakarta Pusat, DKI Jakarta 10120',
                'email' => 'tech@teknologimaju.com',
                'no_telepon' => '021-555-0124',
                'bank_1' => $bankNames[0] ?? 'Bank BCA',
                'nama_rekening_1' => 'PT Teknologi Maju Indonesia',
                'no_rekening_1' => '2233445566',
                'bank_2' => $bankNames[1] ?? 'Bank Mandiri',
                'nama_rekening_2' => 'PT Teknologi Maju Indonesia',
                'no_rekening_2' => '3344556677',
                'terms_of_payment' => '30 Hari',
            ],
            [
                'nama_supplier' => 'CV Bahan Bangunan Sukses',
                'alamat' => 'Jl. Industri No. 67, Tangerang, Banten 15111',
                'email' => 'sales@bahanbangunan.co.id',
                'no_telepon' => '021-555-0457',
                'bank_1' => $bankNames[3] ?? 'Bank BRI',
                'nama_rekening_1' => 'CV Bahan Bangunan Sukses',
                'no_rekening_1' => '4455667788',
                'bank_2' => $bankNames[2] ?? 'Bank BNI',
                'nama_rekening_2' => 'CV Bahan Bangunan Sukses',
                'no_rekening_2' => '5566778899',
                'bank_3' => $bankNames[0] ?? 'Bank BCA',
                'nama_rekening_3' => 'CV Bahan Bangunan Sukses',
                'no_rekening_3' => '6677889900',
                'terms_of_payment' => '45 Hari',
            ],
            [
                'nama_supplier' => 'UD Makanan Sehat',
                'alamat' => 'Jl. Pasar Minggu No. 89, Jakarta Selatan, DKI Jakarta 12520',
                'email' => 'info@makanansehat.com',
                'no_telepon' => '021-555-0788',
                'bank_1' => $bankNames[1] ?? 'Bank Mandiri',
                'nama_rekening_1' => 'UD Makanan Sehat',
                'no_rekening_1' => '7788990011',
                'terms_of_payment' => '7 Hari',
            ],

            // Regional Suppliers
            [
                'nama_supplier' => 'PT Supplier Kalimantan',
                'alamat' => 'Jl. Ahmad Yani No. 234, Balikpapan, Kalimantan Timur 76111',
                'email' => 'contact@supplierkalimantan.co.id',
                'no_telepon' => '0542-555-0123',
                'bank_1' => $bankNames[0] ?? 'Bank BCA',
                'nama_rekening_1' => 'PT Supplier Kalimantan',
                'no_rekening_1' => '8899001122',
                'bank_2' => $bankNames[3] ?? 'Bank BRI',
                'nama_rekening_2' => 'PT Supplier Kalimantan',
                'no_rekening_2' => '9900112233',
                'terms_of_payment' => '30 Hari',
            ],
            [
                'nama_supplier' => 'CV Nusantara Jaya',
                'alamat' => 'Jl. Veteran No. 56, Manado, Sulawesi Utara 95111',
                'email' => 'sales@nusantarajaya.com',
                'no_telepon' => '0431-555-0456',
                'bank_1' => $bankNames[2] ?? 'Bank BNI',
                'nama_rekening_1' => 'CV Nusantara Jaya',
                'no_rekening_1' => '0011223344',
                'terms_of_payment' => '60 Hari',
            ],
            [
                'nama_supplier' => 'UD Papua Maju',
                'alamat' => 'Jl. Sudirman No. 78, Jayapura, Papua 99111',
                'email' => 'admin@papuanmaju.co.id',
                'no_telepon' => '0967-555-0789',
                'bank_1' => $bankNames[1] ?? 'Bank Mandiri',
                'nama_rekening_1' => 'UD Papua Maju',
                'no_rekening_1' => '1122334455',
                'bank_2' => $bankNames[0] ?? 'Bank BCA',
                'nama_rekening_2' => 'UD Papua Maju',
                'no_rekening_2' => '2233445566',
                'terms_of_payment' => '90 Hari',
            ],

            // International Suppliers
            [
                'nama_supplier' => 'PT Global Trading Indonesia',
                'alamat' => 'Jl. Asia Afrika No. 123, Bandung, Jawa Barat 40111',
                'email' => 'global@globaltrading.com',
                'no_telepon' => '022-555-0125',
                'bank_1' => $bankNames[0] ?? 'Bank BCA',
                'nama_rekening_1' => 'PT Global Trading Indonesia',
                'no_rekening_1' => '3344556677',
                'bank_2' => $bankNames[1] ?? 'Bank Mandiri',
                'nama_rekening_2' => 'PT Global Trading Indonesia',
                'no_rekening_2' => '4455667788',
                'bank_3' => $bankNames[2] ?? 'Bank BNI',
                'nama_rekening_3' => 'PT Global Trading Indonesia',
                'no_rekening_3' => '5566778899',
                'terms_of_payment' => '30 Hari',
            ],
            [
                'nama_supplier' => 'CV Import Export Sukses',
                'alamat' => 'Jl. Pelabuhan No. 45, Surabaya, Jawa Timur 60111',
                'email' => 'import@importexport.co.id',
                'no_telepon' => '031-555-0458',
                'bank_1' => $bankNames[3] ?? 'Bank BRI',
                'nama_rekening_1' => 'CV Import Export Sukses',
                'no_rekening_1' => '6677889900',
                'bank_2' => $bankNames[0] ?? 'Bank BCA',
                'nama_rekening_2' => 'CV Import Export Sukses',
                'no_rekening_2' => '7788990011',
                'terms_of_payment' => '45 Hari',
            ],

            // Service Providers
            [
                'nama_supplier' => 'PT Jasa Logistik Indonesia',
                'alamat' => 'Jl. Cikarang No. 67, Bekasi, Jawa Barat 17530',
                'email' => 'logistik@jasalogistik.com',
                'no_telepon' => '021-555-0787',
                'bank_1' => $bankNames[1] ?? 'Bank Mandiri',
                'nama_rekening_1' => 'PT Jasa Logistik Indonesia',
                'no_rekening_1' => '8899001122',
                'bank_2' => $bankNames[2] ?? 'Bank BNI',
                'nama_rekening_2' => 'PT Jasa Logistik Indonesia',
                'no_rekening_2' => '9900112233',
                'terms_of_payment' => '15 Hari',
            ],
            [
                'nama_supplier' => 'CV Konsultan Bisnis',
                'alamat' => 'Jl. Sudirman No. 89, Jakarta Pusat, DKI Jakarta 12190',
                'email' => 'konsultan@bisnis.co.id',
                'no_telepon' => '021-555-0126',
                'bank_1' => $bankNames[0] ?? 'Bank BCA',
                'nama_rekening_1' => 'CV Konsultan Bisnis',
                'no_rekening_1' => '0011223344',
                'terms_of_payment' => '30 Hari',
            ],
            [
                'nama_supplier' => 'UD Maintenance Service',
                'alamat' => 'Jl. Industri No. 234, Tangerang, Banten 15111',
                'email' => 'maintenance@service.com',
                'no_telepon' => '021-555-0459',
                'bank_1' => $bankNames[3] ?? 'Bank BRI',
                'nama_rekening_1' => 'UD Maintenance Service',
                'no_rekening_1' => '1122334455',
                'bank_2' => $bankNames[1] ?? 'Bank Mandiri',
                'nama_rekening_2' => 'UD Maintenance Service',
                'no_rekening_2' => '2233445566',
                'bank_3' => $bankNames[0] ?? 'Bank BCA',
                'nama_rekening_3' => 'UD Maintenance Service',
                'no_rekening_3' => '3344556677',
                'terms_of_payment' => '7 Hari',
            ],

            // Retail Suppliers
            [
                'nama_supplier' => 'PT Retail Sukses Mandiri',
                'alamat' => 'Jl. Mall No. 56, Jakarta Selatan, DKI Jakarta 12190',
                'email' => 'retail@suksesmandiri.com',
                'no_telepon' => '021-555-0786',
                'bank_1' => $bankNames[2] ?? 'Bank BNI',
                'nama_rekening_1' => 'PT Retail Sukses Mandiri',
                'no_rekening_1' => '4455667788',
                'bank_2' => $bankNames[3] ?? 'Bank BRI',
                'nama_rekening_2' => 'PT Retail Sukses Mandiri',
                'no_rekening_2' => '5566778899',
                'terms_of_payment' => '15 Hari',
            ],
            [
                'nama_supplier' => 'CV Fashion Trend',
                'alamat' => 'Jl. Fashion No. 78, Bandung, Jawa Barat 40111',
                'email' => 'fashion@trend.co.id',
                'no_telepon' => '022-555-0127',
                'bank_1' => $bankNames[0] ?? 'Bank BCA',
                'nama_rekening_1' => 'CV Fashion Trend',
                'no_rekening_1' => '6677889900',
                'terms_of_payment' => '30 Hari',
            ],
            [
                'nama_supplier' => 'UD Elektronik Maju',
                'alamat' => 'Jl. Elektronik No. 90, Surabaya, Jawa Timur 60111',
                'email' => 'elektronik@maju.com',
                'no_telepon' => '031-555-0460',
                'bank_1' => $bankNames[1] ?? 'Bank Mandiri',
                'nama_rekening_1' => 'UD Elektronik Maju',
                'no_rekening_1' => '7788990011',
                'bank_2' => $bankNames[2] ?? 'Bank BNI',
                'nama_rekening_2' => 'UD Elektronik Maju',
                'no_rekening_2' => '8899001122',
                'bank_3' => $bankNames[3] ?? 'Bank BRI',
                'nama_rekening_3' => 'UD Elektronik Maju',
                'no_rekening_3' => '9900112233',
                'terms_of_payment' => '45 Hari',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
