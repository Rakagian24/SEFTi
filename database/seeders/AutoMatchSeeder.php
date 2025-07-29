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

        // Data Bank Masuk untuk testing
        $bankMasukData = [
            [
                'no_bm' => 'BM/Andi/Jul-2025/001',
                'tanggal' => '2025-07-15',
                'tipe_po' => 'Reguler',
                'terima_dari' => 'Customer',
                'nilai' => 1000000,
                'bank_account_id' => $bankAccount->id,
                'note' => 'Pembayaran Invoice INV-001',
                'status' => 'aktif',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ],
            [
                'no_bm' => 'BM/Andi/Jul-2025/002',
                'tanggal' => '2025-07-15',
                'tipe_po' => 'Reguler',
                'terima_dari' => 'Customer',
                'nilai' => 2000000,
                'bank_account_id' => $bankAccount->id,
                'note' => 'Pembayaran Invoice INV-002',
                'status' => 'aktif',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ],
            [
                'no_bm' => 'BM/Andi/Jul-2025/003',
                'tanggal' => '2025-07-16',
                'tipe_po' => 'Reguler',
                'terima_dari' => 'Customer',
                'nilai' => 1500000,
                'bank_account_id' => $bankAccount->id,
                'note' => 'Pembayaran Invoice INV-003',
                'status' => 'aktif',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ],
            [
                'no_bm' => 'BM/Andi/Jul-2025/004',
                'tanggal' => '2025-07-17',
                'tipe_po' => 'Reguler',
                'terima_dari' => 'Customer',
                'nilai' => 3000000,
                'bank_account_id' => $bankAccount->id,
                'note' => 'Pembayaran Invoice INV-004',
                'status' => 'aktif',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ],
            [
                'no_bm' => 'BM/Andi/Jul-2025/005',
                'tanggal' => '2025-07-18',
                'tipe_po' => 'Reguler',
                'terima_dari' => 'Customer',
                'nilai' => 500000,
                'bank_account_id' => $bankAccount->id,
                'note' => 'Pembayaran Invoice INV-005',
                'status' => 'aktif',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ],
        ];

        foreach ($bankMasukData as $data) {
            BankMasuk::create($data);
        }

        $this->command->info('Auto Match test data created successfully!');
        $this->command->info('Created ' . count($bankMasukData) . ' Bank Masuk records for testing.');
        $this->command->info('');
        $this->command->info('Note: You need to manually create corresponding kwitansi data in the gjtrading3 database with matching dates and values:');
        $this->command->info('- INV-001: 1,000,000 on 2025-07-15');
        $this->command->info('- INV-002: 2,000,000 on 2025-07-15');
        $this->command->info('- INV-003: 1,500,000 on 2025-07-16');
        $this->command->info('- INV-004: 3,000,000 on 2025-07-17');
        $this->command->info('- INV-005: 500,000 on 2025-07-18');
    }
}
