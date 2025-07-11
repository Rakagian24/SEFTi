<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BisnisPartner;
use App\Models\Bank;

class BisnisPartnerSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan ada data bank terlebih dahulu
        if (Bank::count() === 0) {
            $this->command->info('No banks found. Please run BankSeeder first.');
            return;
        }

        // Buat beberapa data manual sebagai contoh
        $banks = Bank::all();

        $bisnisPartners = [
            [
                'nama_bp' => 'PT Maju Bersama',
                'jenis_bp' => 'Customer',
                'alamat' => 'Jl. Sudirman No. 123, Jakarta',
                'email' => 'info@majubersama.com',
                'no_telepon' => '021-5550123',
                'bank_id' => $banks->where('nama_bank', 'Bank Central Asia')->first()->id,
                'nama_rekening' => 'PT Maju Bersama',
                'no_rekening_va' => '1234567890',
                'terms_of_payment' => '30 Hari',
            ],
            [
                'nama_bp' => 'CV Sukses Mandiri',
                'jenis_bp' => 'Customer',
                'alamat' => 'Jl. Thamrin No. 45, Jakarta',
                'email' => 'contact@suksesmandiri.co.id',
                'no_telepon' => '021-5550456',
                'bank_id' => $banks->where('nama_bank', 'Bank Mandiri')->first()->id,
                'nama_rekening' => 'CV Sukses Mandiri',
                'no_rekening_va' => '0987654321',
                'terms_of_payment' => '15 Hari',
            ],
            [
                'nama_bp' => 'UD Berkah Jaya',
                'jenis_bp' => 'Customer',
                'alamat' => 'Jl. Gatot Subroto No. 67, Jakarta',
                'email' => 'berkahjaya@gmail.com',
                'no_telepon' => '021-5550789',
                'bank_id' => $banks->where('nama_bank', 'Bank Rakyat Indonesia')->first()->id,
                'nama_rekening' => 'UD Berkah Jaya',
                'no_rekening_va' => '1122334455',
                'terms_of_payment' => '7 Hari',
            ],
        ];

        // Insert data manual
        foreach ($bisnisPartners as $bp) {
            BisnisPartner::create($bp);
        }

        // Buat data random menggunakan factory
        BisnisPartner::factory()->count(25)->create();
    }
}
