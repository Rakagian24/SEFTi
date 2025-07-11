<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pph;

class PphSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'kode_pph' => '21',
                'nama_pph' => 'PPh Pasal 21',
                'deskripsi' => 'Pajak atas penghasilan sehubungan dengan pekerjaan, jasa, dan kegiatan.',
                'status' => 'active',
            ],
            [
                'kode_pph' => '22',
                'nama_pph' => 'PPh Pasal 22',
                'deskripsi' => 'Pajak atas kegiatan impor atau kegiatan usaha tertentu.',
                'status' => 'active',
            ],
            [
                'kode_pph' => '23',
                'nama_pph' => 'PPh Pasal 23',
                'deskripsi' => 'Pajak atas penghasilan dari modal, penyerahan jasa, atau hadiah.',
                'status' => 'inactive',
            ],
            [
                'kode_pph' => '25',
                'nama_pph' => 'PPh Pasal 25',
                'deskripsi' => 'Angsuran pajak penghasilan bagi wajib pajak tertentu.',
                'status' => 'active',
            ],
        ];

        foreach ($data as $item) {
            Pph::create($item);
        }
    }
}
