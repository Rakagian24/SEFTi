<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ArPartner;

class ArPartnerSeeder extends Seeder
{
    public function run(): void
    {
        // Buat beberapa data manual sebagai contoh
        $arPartners = [
            [
                'nama_ap' => 'Ahmad Rizki',
                'jenis_ap' => 'Customer',
                'alamat' => 'Jl. Sudirman No. 123, Jakarta Pusat',
                'email' => 'ahmad.rizki@email.com',
                'no_telepon' => '081234567890',
            ],
            [
                'nama_ap' => 'Siti Nurhaliza',
                'jenis_ap' => 'Customer',
                'alamat' => 'Jl. Thamrin No. 45, Jakarta Pusat',
                'email' => 'siti.nurhaliza@email.com',
                'no_telepon' => '081234567891',
            ],
            [
                'nama_ap' => 'Budi Santoso',
                'jenis_ap' => 'Karyawan',
                'alamat' => 'Jl. Gatot Subroto No. 67, Jakarta Selatan',
                'email' => 'budi.santoso@company.com',
                'no_telepon' => '081234567892',
            ],
            [
                'nama_ap' => 'Dewi Sartika',
                'jenis_ap' => 'Karyawan',
                'alamat' => 'Jl. Rasuna Said No. 89, Jakarta Selatan',
                'email' => 'dewi.sartika@company.com',
                'no_telepon' => '081234567893',
            ],
            [
                'nama_ap' => 'Toko Maju Jaya',
                'jenis_ap' => 'Penjualan Toko',
                'alamat' => 'Jl. Hayam Wuruk No. 12, Jakarta Barat',
                'email' => 'info@majujaya.com',
                'no_telepon' => '021-5550123',
            ],
            [
                'nama_ap' => 'Toko Sukses Mandiri',
                'jenis_ap' => 'Penjualan Toko',
                'alamat' => 'Jl. Gajah Mada No. 34, Jakarta Barat',
                'email' => 'contact@suksesmandiri.com',
                'no_telepon' => '021-5550456',
            ],
            [
                'nama_ap' => 'Rina Melati',
                'jenis_ap' => 'Customer',
                'alamat' => 'Jl. Kebon Jeruk No. 56, Jakarta Barat',
                'email' => 'rina.melati@email.com',
                'no_telepon' => '081234567894',
            ],
            [
                'nama_ap' => 'Toko Berkah Abadi',
                'jenis_ap' => 'Penjualan Toko',
                'alamat' => 'Jl. Mangga Dua No. 78, Jakarta Utara',
                'email' => 'berkahabadi@gmail.com',
                'no_telepon' => '021-5550789',
            ],
            [
                'nama_ap' => 'Joko Widodo',
                'jenis_ap' => 'Customer',
                'alamat' => 'Jl. Kelapa Gading No. 90, Jakarta Utara',
                'email' => 'joko.widodo@email.com',
                'no_telepon' => '081234567895',
            ],
            [
                'nama_ap' => 'Sri Mulyani',
                'jenis_ap' => 'Karyawan',
                'alamat' => 'Jl. Pluit No. 23, Jakarta Utara',
                'email' => 'sri.mulyani@company.com',
                'no_telepon' => '081234567896',
            ],
            [
                'nama_ap' => 'Toko Makmur Sejahtera',
                'jenis_ap' => 'Penjualan Toko',
                'alamat' => 'Jl. Cempaka Putih No. 45, Jakarta Pusat',
                'email' => 'makmursejahtera@gmail.com',
                'no_telepon' => '021-5550124',
            ],
            [
                'nama_ap' => 'Bambang Trihatmodjo',
                'jenis_ap' => 'Customer',
                'alamat' => 'Jl. Senayan No. 67, Jakarta Selatan',
                'email' => 'bambang.trihatmodjo@email.com',
                'no_telepon' => '081234567897',
            ],
            [
                'nama_ap' => 'Megawati Soekarnoputri',
                'jenis_ap' => 'Customer',
                'alamat' => 'Jl. Kuningan No. 89, Jakarta Selatan',
                'email' => 'megawati.soekarnoputri@email.com',
                'no_telepon' => '081234567898',
            ],
            [
                'nama_ap' => 'Toko Indah Permai',
                'jenis_ap' => 'Penjualan Toko',
                'alamat' => 'Jl. Tanah Abang No. 12, Jakarta Pusat',
                'email' => 'indahpermai@gmail.com',
                'no_telepon' => '021-5550457',
            ],
            [
                'nama_ap' => 'Susilo Bambang Yudhoyono',
                'jenis_ap' => 'Customer',
                'alamat' => 'Jl. Menteng No. 34, Jakarta Pusat',
                'email' => 'susilo.bambang@email.com',
                'no_telepon' => '081234567899',
            ],
        ];

        // Insert data manual
        foreach ($arPartners as $ap) {
            ArPartner::create($ap);
        }

        $this->command->info('AR Partner data seeded successfully!');
    }
}
