<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BankMasuk;
use App\Models\BankAccount;
use App\Models\User;
use Carbon\Carbon;

class AutoMatchSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil user pertama untuk created_by
        $user = User::first();
        if (!$user) {
            $this->command->error('No users found. Please run UserSeeder first.');
            return;
        }

        // Ambil bank account pertama
        $bankAccount = BankAccount::where('status', 'active')->first();
        if (!$bankAccount) {
            $this->command->error('No active bank accounts found. Please run BankAccountSeeder first.');
            return;
        }

        // Create test BankMasuk records with matching dates and values from SjNew data
        $testData = [
            [
                'no_bm' => 'BM/Test/Jul-2025/001',
                'tanggal' => '2025-07-11',
                'tipe_po' => 'Reguler',
                'terima_dari' => 'Customer',
                'nilai' => 78803246.60,
                'bank_account_id' => $bankAccount->id,
                'note' => 'Test data for SJ/M0725/02029/SGT1',
                'status' => 'aktif',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ],
            [
                'no_bm' => 'BM/Test/Jul-2025/002',
                'tanggal' => '2025-07-12',
                'tipe_po' => 'Reguler',
                'terima_dari' => 'Customer',
                'nilai' => 11690000.00,
                'bank_account_id' => $bankAccount->id,
                'note' => 'Test data for SJ/M0725/02061/SGT1',
                'status' => 'aktif',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ],
            [
                'no_bm' => 'BM/Test/Jul-2025/003',
                'tanggal' => '2025-07-15',
                'tipe_po' => 'Reguler',
                'terima_dari' => 'Customer',
                'nilai' => 464100.00,
                'bank_account_id' => $bankAccount->id,
                'note' => 'Test data for SJ/M0725/02103/SGT1',
                'status' => 'aktif',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ],
            [
                'no_bm' => 'BM/Test/Jul-2025/004',
                'tanggal' => '2025-07-15',
                'tipe_po' => 'Reguler',
                'terima_dari' => 'Customer',
                'nilai' => 889000.00,
                'bank_account_id' => $bankAccount->id,
                'note' => 'Test data for SJ/M0725/02101/SGT1',
                'status' => 'aktif',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ],
            [
                'no_bm' => 'BM/Test/Jul-2025/005',
                'tanggal' => '2025-07-15',
                'tipe_po' => 'Reguler',
                'terima_dari' => 'Customer',
                'nilai' => 464100.00,
                'bank_account_id' => $bankAccount->id,
                'note' => 'Test data for SJ/M0725/02102/SGT1',
                'status' => 'aktif',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ],
            // Add test data with decimal values
            [
                'no_bm' => 'BM/Test/Decimal/001',
                'tanggal' => '2025-07-16',
                'tipe_po' => 'Reguler',
                'terima_dari' => 'Customer',
                'nilai' => 1234567.89,
                'bank_account_id' => $bankAccount->id,
                'note' => 'Test data with 2 decimal places',
                'status' => 'aktif',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ],
            [
                'no_bm' => 'BM/Test/Decimal/002',
                'tanggal' => '2025-07-16',
                'tipe_po' => 'Reguler',
                'terima_dari' => 'Customer',
                'nilai' => 9876543.2100,
                'bank_account_id' => $bankAccount->id,
                'note' => 'Test data with 4 decimal places',
                'status' => 'aktif',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ],
            [
                'no_bm' => 'BM/Test/Decimal/003',
                'tanggal' => '2025-07-16',
                'tipe_po' => 'Reguler',
                'terima_dari' => 'Customer',
                'nilai' => 5555555.5555,
                'bank_account_id' => $bankAccount->id,
                'note' => 'Test data with 4 decimal places',
                'status' => 'aktif',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ],
            // Add test data with exact matching values from existing SjNew data
            [
                'no_bm' => 'BM/Test/Exact/001',
                'tanggal' => '2025-07-12',
                'tipe_po' => 'Reguler',
                'terima_dari' => 'Customer',
                'nilai' => 11690000.00,
                'bank_account_id' => $bankAccount->id,
                'note' => 'Exact match test for SJ/M0725/02061/SGT1',
                'status' => 'aktif',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ],
            [
                'no_bm' => 'BM/Test/Exact/002',
                'tanggal' => '2025-07-11',
                'tipe_po' => 'Reguler',
                'terima_dari' => 'Customer',
                'nilai' => 78803246.60,
                'bank_account_id' => $bankAccount->id,
                'note' => 'Exact match test for SJ/M0725/02029/SGT1',
                'status' => 'aktif',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ],
            [
                'no_bm' => 'BM/Test/Exact/003',
                'tanggal' => '2025-07-15',
                'tipe_po' => 'Reguler',
                'terima_dari' => 'Customer',
                'nilai' => 464100.00,
                'bank_account_id' => $bankAccount->id,
                'note' => 'Exact match test for SJ/M0725/02103/SGT1',
                'status' => 'aktif',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]
        ];

        foreach ($testData as $data) {
            BankMasuk::create($data);
        }

        $this->command->info('Auto Match test data created successfully!');
        $this->command->info('Created ' . count($testData) . ' Bank Masuk records for testing.');
        $this->command->info('');
        $this->command->info('Test data created with matching dates and values:');
        $this->command->info('- SJ/M0725/02029/SGT1 (2025-07-11, 78,803,246.6) -> BM/Test/Jul-2025/001');
        $this->command->info('- SJ/M0725/02061/SGT1 (2025-07-12, 11,690,000) -> BM/Test/Jul-2025/002');
        $this->command->info('- SJ/M0725/02103/SGT1 (2025-07-15, 464,100) -> BM/Test/Jul-2025/003');
        $this->command->info('- SJ/M0725/02101/SGT1 (2025-07-15, 889,000) -> BM/Test/Jul-2025/004');
        $this->command->info('- SJ/M0725/02103/SGT1 (2025-07-15, 464,100) -> BM/Test/Jul-2025/005 (duplicate for testing)');
        $this->command->info('');
        $this->command->info('Now you can test the auto matching functionality!');
    }
}
