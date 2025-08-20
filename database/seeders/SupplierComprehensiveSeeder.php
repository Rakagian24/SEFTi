<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\Bank;
use App\Models\BankSupplierAccount;
use App\Models\Department;

class SupplierComprehensiveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get available banks and departments
        $banks = Bank::where('status', 'active')->get();
        $departments = Department::where('status', 'active')->get();

        // Ensure we have banks and departments
        if ($banks->isEmpty()) {
            $this->command->error('No active banks found. Please run BankSeeder first.');
            return;
        }

        if ($departments->isEmpty()) {
            $this->command->error('No active departments found. Please run DepartmentSeeder first.');
            return;
        }

        $suppliers = [
            // Large Corporations
            [
                'nama_supplier' => 'PT Maju Bersama Sejahtera',
                'alamat' => 'Jl. Sudirman No. 123, Jakarta Pusat, DKI Jakarta 12190',
                'email' => 'procurement@majubersama.com',
                'no_telepon' => '021-555-0123',
                'department_id' => $departments->random()->id,
                'terms_of_payment' => '30 Hari',
                'bank_accounts' => [
                    [
                        'bank_id' => $banks->where('singkatan', 'BCA')->first()->id,
                        'nama_rekening' => 'PT Maju Bersama Sejahtera',
                        'no_rekening' => '1234567890',
                    ],
                    [
                        'bank_id' => $banks->where('singkatan', 'Mandiri')->first()->id,
                        'nama_rekening' => 'PT Maju Bersama Sejahtera',
                        'no_rekening' => '0987654321',
                    ],
                    [
                        'bank_id' => $banks->where('singkatan', 'BNI')->first()->id,
                        'nama_rekening' => 'PT Maju Bersama Sejahtera',
                        'no_rekening' => '1122334455',
                    ],
                ],
            ],
            [
                'nama_supplier' => 'CV Mitra Sukses Abadi',
                'alamat' => 'Jl. Thamrin No. 45, Jakarta Selatan, DKI Jakarta 12150',
                'email' => 'sales@mitrasukses.co.id',
                'no_telepon' => '021-555-0456',
                'department_id' => $departments->random()->id,
                'terms_of_payment' => '15 Hari',
                'bank_accounts' => [
                    [
                        'bank_id' => $banks->where('singkatan', 'BRI')->first()->id,
                        'nama_rekening' => 'CV Mitra Sukses Abadi',
                        'no_rekening' => '5566778899',
                    ],
                ],
            ],
            [
                'nama_supplier' => 'UD Sentosa Makmur',
                'alamat' => 'Jl. Gatot Subroto No. 67, Jakarta Barat, DKI Jakarta 11470',
                'email' => 'info@sentosamakmur.com',
                'no_telepon' => '021-555-0789',
                'department_id' => $departments->random()->id,
                'terms_of_payment' => '60 Hari',
                'bank_accounts' => [
                    [
                        'bank_id' => $banks->where('singkatan', 'BCA')->first()->id,
                        'nama_rekening' => 'UD Sentosa Makmur',
                        'no_rekening' => '9988776655',
                    ],
                    [
                        'bank_id' => $banks->where('singkatan', 'Mandiri')->first()->id,
                        'nama_rekening' => 'UD Sentosa Makmur',
                        'no_rekening' => '4433221100',
                    ],
                ],
            ],

            // Medium Enterprises
            [
                'nama_supplier' => 'PT Jaya Abadi Perkasa',
                'alamat' => 'Jl. Merdeka No. 89, Bandung, Jawa Barat 40111',
                'email' => 'contact@jayaabadi.co.id',
                'no_telepon' => '022-555-0123',
                'department_id' => $departments->random()->id,
                'terms_of_payment' => '45 Hari',
                'bank_accounts' => [
                    [
                        'bank_id' => $banks->where('singkatan', 'BNI')->first()->id,
                        'nama_rekening' => 'PT Jaya Abadi Perkasa',
                        'no_rekening' => '2233445566',
                    ],
                    [
                        'bank_id' => $banks->where('singkatan', 'BRI')->first()->id,
                        'nama_rekening' => 'PT Jaya Abadi Perkasa',
                        'no_rekening' => '3344556677',
                    ],
                ],
            ],
            [
                'nama_supplier' => 'CV Sukses Mandiri',
                'alamat' => 'Jl. Industri No. 234, Tangerang, Banten 15111',
                'email' => 'info@suksesmandiri.co.id',
                'no_telepon' => '021-555-0456',
                'department_id' => $departments->random()->id,
                'terms_of_payment' => '30 Hari',
                'bank_accounts' => [
                    [
                        'bank_id' => $banks->where('singkatan', 'Citi')->first()->id,
                        'nama_rekening' => 'CV Sukses Mandiri',
                        'no_rekening' => '4455667788',
                    ],
                ],
            ],

            // Manufacturing Suppliers
            [
                'nama_supplier' => 'PT Industri Maju Indonesia',
                'alamat' => 'Jl. Kawasan Industri No. 12, Bekasi, Jawa Barat 17530',
                'email' => 'sales@industrimaju.co.id',
                'no_telepon' => '021-555-0789',
                'department_id' => $departments->random()->id,
                'terms_of_payment' => '60 Hari',
                'bank_accounts' => [
                    [
                        'bank_id' => $banks->where('singkatan', 'Mandiri')->first()->id,
                        'nama_rekening' => 'PT Industri Maju Indonesia',
                        'no_rekening' => '5566778899',
                    ],
                    [
                        'bank_id' => $banks->where('singkatan', 'BCA')->first()->id,
                        'nama_rekening' => 'PT Industri Maju Indonesia',
                        'no_rekening' => '6677889900',
                    ],
                    [
                        'bank_id' => $banks->where('singkatan', 'BNI')->first()->id,
                        'nama_rekening' => 'PT Industri Maju Indonesia',
                        'no_rekening' => '7788990011',
                    ],
                ],
            ],
            [
                'nama_supplier' => 'CV Teknik Sukses',
                'alamat' => 'Jl. Teknik No. 56, Surabaya, Jawa Timur 60111',
                'email' => 'teknik@sukses.co.id',
                'no_telepon' => '031-555-0123',
                'department_id' => $departments->random()->id,
                'terms_of_payment' => '30 Hari',
                'bank_accounts' => [
                    [
                        'bank_id' => $banks->where('singkatan', 'BRI')->first()->id,
                        'nama_rekening' => 'CV Teknik Sukses',
                        'no_rekening' => '8899001122',
                    ],
                    [
                        'bank_id' => $banks->where('singkatan', 'Citi')->first()->id,
                        'nama_rekening' => 'CV Teknik Sukses',
                        'no_rekening' => '9900112233',
                    ],
                ],
            ],

            // Import/Export Companies
            [
                'nama_supplier' => 'PT Global Trade Indonesia',
                'alamat' => 'Jl. Pelabuhan No. 78, Jakarta Utara, DKI Jakarta 14450',
                'email' => 'trade@globalindonesia.co.id',
                'no_telepon' => '021-555-0457',
                'department_id' => $departments->random()->id,
                'terms_of_payment' => '90 Hari',
                'bank_accounts' => [
                    [
                        'bank_id' => $banks->where('singkatan', 'BCA')->first()->id,
                        'nama_rekening' => 'PT Global Trade Indonesia',
                        'no_rekening' => '0011223344',
                    ],
                    [
                        'bank_id' => $banks->where('singkatan', 'Mandiri')->first()->id,
                        'nama_rekening' => 'PT Global Trade Indonesia',
                        'no_rekening' => '1122334455',
                    ],
                ],
            ],
            [
                'nama_supplier' => 'CV Import Export Sukses',
                'alamat' => 'Jl. Pelabuhan No. 90, Surabaya, Jawa Timur 60111',
                'email' => 'import@importexport.co.id',
                'no_telepon' => '031-555-0458',
                'department_id' => $departments->random()->id,
                'terms_of_payment' => '45 Hari',
                'bank_accounts' => [
                    [
                        'bank_id' => $banks->where('singkatan', 'BRI')->first()->id,
                        'nama_rekening' => 'CV Import Export Sukses',
                        'no_rekening' => '6677889900',
                    ],
                    [
                        'bank_id' => $banks->where('singkatan', 'BCA')->first()->id,
                        'nama_rekening' => 'CV Import Export Sukses',
                        'no_rekening' => '7788990011',
                    ],
                ],
            ],

            // Service Providers
            [
                'nama_supplier' => 'PT Jasa Logistik Indonesia',
                'alamat' => 'Jl. Cikarang No. 67, Bekasi, Jawa Barat 17530',
                'email' => 'logistik@jasalogistik.com',
                'no_telepon' => '021-555-0787',
                'department_id' => $departments->random()->id,
                'terms_of_payment' => '15 Hari',
                'bank_accounts' => [
                    [
                        'bank_id' => $banks->where('singkatan', 'Mandiri')->first()->id,
                        'nama_rekening' => 'PT Jasa Logistik Indonesia',
                        'no_rekening' => '8899001122',
                    ],
                    [
                        'bank_id' => $banks->where('singkatan', 'BNI')->first()->id,
                        'nama_rekening' => 'PT Jasa Logistik Indonesia',
                        'no_rekening' => '9900112233',
                    ],
                ],
            ],
            [
                'nama_supplier' => 'CV Konsultan Bisnis',
                'alamat' => 'Jl. Sudirman No. 89, Jakarta Pusat, DKI Jakarta 12190',
                'email' => 'konsultan@bisnis.co.id',
                'no_telepon' => '021-555-0126',
                'department_id' => $departments->random()->id,
                'terms_of_payment' => '30 Hari',
                'bank_accounts' => [
                    [
                        'bank_id' => $banks->where('singkatan', 'BCA')->first()->id,
                        'nama_rekening' => 'CV Konsultan Bisnis',
                        'no_rekening' => '0011223344',
                    ],
                ],
            ],
            [
                'nama_supplier' => 'UD Maintenance Service',
                'alamat' => 'Jl. Industri No. 234, Tangerang, Banten 15111',
                'email' => 'maintenance@service.com',
                'no_telepon' => '021-555-0459',
                'department_id' => $departments->random()->id,
                'terms_of_payment' => '7 Hari',
                'bank_accounts' => [
                    [
                        'bank_id' => $banks->where('singkatan', 'BRI')->first()->id,
                        'nama_rekening' => 'UD Maintenance Service',
                        'no_rekening' => '1122334455',
                    ],
                    [
                        'bank_id' => $banks->where('singkatan', 'Mandiri')->first()->id,
                        'nama_rekening' => 'UD Maintenance Service',
                        'no_rekening' => '2233445566',
                    ],
                    [
                        'bank_id' => $banks->where('singkatan', 'BCA')->first()->id,
                        'nama_rekening' => 'UD Maintenance Service',
                        'no_rekening' => '3344556677',
                    ],
                ],
            ],

            // Retail Suppliers
            [
                'nama_supplier' => 'PT Retail Sukses Mandiri',
                'alamat' => 'Jl. Mall No. 56, Jakarta Selatan, DKI Jakarta 12190',
                'email' => 'retail@suksesmandiri.com',
                'no_telepon' => '021-555-0786',
                'department_id' => $departments->random()->id,
                'terms_of_payment' => '15 Hari',
                'bank_accounts' => [
                    [
                        'bank_id' => $banks->where('singkatan', 'BNI')->first()->id,
                        'nama_rekening' => 'PT Retail Sukses Mandiri',
                        'no_rekening' => '4455667788',
                    ],
                    [
                        'bank_id' => $banks->where('singkatan', 'BRI')->first()->id,
                        'nama_rekening' => 'PT Retail Sukses Mandiri',
                        'no_rekening' => '5566778899',
                    ],
                ],
            ],
            [
                'nama_supplier' => 'CV Fashion Trend',
                'alamat' => 'Jl. Fashion No. 78, Bandung, Jawa Barat 40111',
                'email' => 'fashion@trend.co.id',
                'no_telepon' => '022-555-0127',
                'department_id' => $departments->random()->id,
                'terms_of_payment' => '30 Hari',
                'bank_accounts' => [
                    [
                        'bank_id' => $banks->where('singkatan', 'BCA')->first()->id,
                        'nama_rekening' => 'CV Fashion Trend',
                        'no_rekening' => '6677889900',
                    ],
                ],
            ],
            [
                'nama_supplier' => 'UD Elektronik Maju',
                'alamat' => 'Jl. Elektronik No. 90, Surabaya, Jawa Timur 60111',
                'email' => 'elektronik@maju.com',
                'no_telepon' => '031-555-0460',
                'department_id' => $departments->random()->id,
                'terms_of_payment' => '45 Hari',
                'bank_accounts' => [
                    [
                        'bank_id' => $banks->where('singkatan', 'Mandiri')->first()->id,
                        'nama_rekening' => 'UD Elektronik Maju',
                        'no_rekening' => '7788990011',
                    ],
                    [
                        'bank_id' => $banks->where('singkatan', 'BNI')->first()->id,
                        'nama_rekening' => 'UD Elektronik Maju',
                        'no_rekening' => '8899001122',
                    ],
                    [
                        'bank_id' => $banks->where('singkatan', 'BRI')->first()->id,
                        'nama_rekening' => 'UD Elektronik Maju',
                        'no_rekening' => '9900112233',
                    ],
                ],
            ],

            // Technology Suppliers
            [
                'nama_supplier' => 'PT Tech Solutions Indonesia',
                'alamat' => 'Jl. Digital No. 123, Jakarta Selatan, DKI Jakarta 12190',
                'email' => 'tech@techsolutions.co.id',
                'no_telepon' => '021-555-0128',
                'department_id' => $departments->random()->id,
                'terms_of_payment' => '30 Hari',
                'bank_accounts' => [
                    [
                        'bank_id' => $banks->where('singkatan', 'BCA')->first()->id,
                        'nama_rekening' => 'PT Tech Solutions Indonesia',
                        'no_rekening' => '1112223334',
                    ],
                    [
                        'bank_id' => $banks->where('singkatan', 'Citi')->first()->id,
                        'nama_rekening' => 'PT Tech Solutions Indonesia',
                        'no_rekening' => '2223334445',
                    ],
                ],
            ],
            [
                'nama_supplier' => 'CV Software House',
                'alamat' => 'Jl. IT No. 456, Bandung, Jawa Barat 40111',
                'email' => 'software@house.co.id',
                'no_telepon' => '022-555-0129',
                'department_id' => $departments->random()->id,
                'terms_of_payment' => '45 Hari',
                'bank_accounts' => [
                    [
                        'bank_id' => $banks->where('singkatan', 'BNI')->first()->id,
                        'nama_rekening' => 'CV Software House',
                        'no_rekening' => '3334445556',
                    ],
                ],
            ],
        ];

        foreach ($suppliers as $supplierData) {
            // Extract bank accounts data
            $bankAccounts = $supplierData['bank_accounts'];
            unset($supplierData['bank_accounts']);

            // Create supplier
            $supplier = Supplier::create($supplierData);

            // Create bank supplier accounts
            foreach ($bankAccounts as $bankAccount) {
                BankSupplierAccount::create([
                    'supplier_id' => $supplier->id,
                    'bank_id' => $bankAccount['bank_id'],
                    'nama_rekening' => $bankAccount['nama_rekening'],
                    'no_rekening' => $bankAccount['no_rekening'],
                ]);
            }
        }
    }
}
