<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banks = [
            [
                'nama_bank' => 'Bank Central Asia',
                'singkatan' => 'BCA',
                'status' => 'active',
                'currency' => 'IDR',
            ],
            [
                'nama_bank' => 'Bank Mandiri',
                'singkatan' => 'Mandiri',
                'status' => 'active',
                'currency' => 'IDR',
            ],
            [
                'nama_bank' => 'Bank Negara Indonesia',
                'singkatan' => 'BNI',
                'status' => 'inactive',
                'currency' => 'IDR',
            ],
            [
                'nama_bank' => 'Bank Rakyat Indonesia',
                'singkatan' => 'BRI',
                'status' => 'active',
                'currency' => 'IDR',
            ],
            [
                'nama_bank' => 'Citibank',
                'singkatan' => 'Citi',
                'status' => 'active',
                'currency' => 'USD',
            ],
            [
                'nama_bank' => 'Standard Chartered Bank',
                'singkatan' => 'SCB',
                'status' => 'active',
                'currency' => 'USD',
            ],
        ];

        foreach ($banks as $bank) {
            \App\Models\Bank::updateOrCreate(
                ['nama_bank' => $bank['nama_bank']],
                $bank
            );
        }
    }
}
