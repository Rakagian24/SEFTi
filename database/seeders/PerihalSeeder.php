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

        // Check if perihals already exist to avoid duplicates
        $existingPerihals = DB::table('perihals')->pluck('nama')->toArray();

        // Define the required perihal options for Memo Pembayaran
        $requiredPerihals = [
            [
                'nama' => 'Permintaan Pembayaran Ongkir',
                'deskripsi' => 'Pembayaran untuk biaya pengiriman atau ongkos kirim',
                'status' => 'active',
            ],
            [
                'nama' => 'Permintaan Pembayaran Dinas',
                'deskripsi' => 'Pembayaran untuk keperluan dinas atau perjalanan dinas',
                'status' => 'active',
            ],
            [
                'nama' => 'Permintaan Pembayaran Barang/Jasa',
                'deskripsi' => 'Pembayaran untuk pembelian barang atau jasa',
                'status' => 'active',
            ],
            [
                'nama' => 'Permintaan Pembayaran Refund Konsumen',
                'deskripsi' => 'Pembayaran refund atau pengembalian dana kepada konsumen',
                'status' => 'active',
            ],
        ];

        // Insert only if they don't exist
        foreach ($requiredPerihals as $perihal) {
            if (!in_array($perihal['nama'], $existingPerihals)) {
                DB::table('perihals')->insert([
                    'nama' => $perihal['nama'],
                    'deskripsi' => $perihal['deskripsi'],
                    'status' => $perihal['status'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->command->info('Perihal seeder completed successfully!');
    }
}
