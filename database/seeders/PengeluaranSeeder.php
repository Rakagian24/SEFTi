<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pengeluaran;

class PengeluaranSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'nama' => 'Pembelian ATK',
                'deskripsi' => 'Pengeluaran untuk alat tulis kantor',
            ],
            [
                'nama' => 'Biaya Listrik',
                'deskripsi' => 'Pembayaran tagihan listrik bulanan',
            ],
            [
                'nama' => 'Transportasi',
                'deskripsi' => 'Pengeluaran untuk transportasi operasional',
            ],
            [
                'nama' => 'Konsumsi Rapat',
                'deskripsi' => 'Pengeluaran makanan dan minuman saat rapat',
            ],
            [
                'nama' => 'Perawatan Gedung',
                'deskripsi' => 'Biaya perawatan dan kebersihan gedung',
            ],
        ];

        foreach ($data as $item) {
            Pengeluaran::create($item);
        }
    }
}
