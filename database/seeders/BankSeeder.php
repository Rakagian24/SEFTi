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
        \App\Models\Bank::insert([
            [
                'kode_bank' => '001',
                'nama_bank' => 'Bank Central Asia',
                'singkatan' => 'BCA',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode_bank' => '002',
                'nama_bank' => 'Bank Mandiri',
                'singkatan' => 'Mandiri',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode_bank' => '003',
                'nama_bank' => 'Bank Negara Indonesia',
                'singkatan' => 'BNI',
                'status' => 'inactive',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode_bank' => '004',
                'nama_bank' => 'Bank Rakyat Indonesia',
                'singkatan' => 'BRI',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
