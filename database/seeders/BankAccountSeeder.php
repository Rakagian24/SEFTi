<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BankAccount;
use App\Models\Bank;

class BankAccountSeeder extends Seeder
{
    public function run(): void
    {
        $banks = Bank::take(3)->get();
        $data = [
            [
                'nama_pemilik' => 'Andi Wijaya',
                'no_rekening' => '1234567890',
                'bank_id' => $banks[0]->id ?? null,
                'status' => $banks[0]->status ?? 'aktif',
            ],
            [
                'nama_pemilik' => 'Siti Aminah',
                'no_rekening' => '9876543210',
                'bank_id' => $banks[1]->id ?? null,
                'status' => $banks[1]->status ?? 'aktif',
            ],
            [
                'nama_pemilik' => 'Budi Santoso',
                'no_rekening' => '1122334455',
                'bank_id' => $banks[2]->id ?? null,
                'status' => $banks[2]->status ?? 'nonaktif',
            ],
        ];

        foreach ($data as $item) {
            BankAccount::create($item);
        }
    }
}
