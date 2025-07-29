<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BankAccount;
use App\Models\Bank;

class BankAccountSeeder extends Seeder
{
    public function run(): void
    {
        $banks = Bank::all();
        $idrBanks = $banks->where('currency', 'IDR')->take(2);
        $usdBanks = $banks->where('currency', 'USD')->take(2);

        $data = [
            // Bank Account IDR
            [
                'nama_pemilik' => 'Andi Wijaya',
                'no_rekening' => '1234567890',
                'bank_id' => $idrBanks->first()->id ?? null,
                'status' => 'active',
            ],
            [
                'nama_pemilik' => 'Siti Aminah',
                'no_rekening' => '9876543210',
                'bank_id' => $idrBanks->last()->id ?? null,
                'status' => 'active',
            ],
            // Bank Account USD
            [
                'nama_pemilik' => 'John Smith',
                'no_rekening' => '1122334455',
                'bank_id' => $usdBanks->first()->id ?? null,
                'status' => 'active',
            ],
            [
                'nama_pemilik' => 'Jane Doe',
                'no_rekening' => '9988776655',
                'bank_id' => $usdBanks->last()->id ?? null,
                'status' => 'active',
            ],
        ];

        foreach ($data as $item) {
            BankAccount::updateOrCreate(
                ['no_rekening' => $item['no_rekening']],
                $item
            );
        }
    }
}
