<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PerihalSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $data = [
            [
                'nama' => 'Top Up Flazz BCA',
                'deskripsi' => 'Pengisian saldo kartu Flazz BCA',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nama' => 'Top Up Kartu Kredit',
                'deskripsi' => 'Pembayaran atau pengisian saldo kartu kredit',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nama' => 'Pembayaran Dividen',
                'deskripsi' => 'Pembagian dividen kepada pemegang saham',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nama' => 'Biaya Admin',
                'deskripsi' => 'Biaya administrasi transaksi',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nama' => 'Biaya Admin Business Debit Card',
                'deskripsi' => 'Biaya administrasi kartu debit bisnis',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nama' => 'Biaya Pajak',
                'deskripsi' => 'Pembayaran pajak perusahaan',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nama' => 'Tarikan Tunai Kas Kecil',
                'deskripsi' => 'Penarikan dana untuk kebutuhan kas kecil',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('perihals')->insert($data);
    }
}
