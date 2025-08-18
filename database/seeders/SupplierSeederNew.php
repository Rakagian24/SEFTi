<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeederNew extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'nama_supplier' => 'PT Maju Bersama Sejahtera',
                'alamat' => 'Jl. Sudirman No. 123, Jakarta Pusat, DKI Jakarta 12190',
                'email' => 'procurement@majubersama.com',
                'no_telepon' => '021-555-0123',
                'terms_of_payment' => '30 Hari',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_supplier' => 'CV Mitra Sukses Abadi',
                'alamat' => 'Jl. Thamrin No. 45, Jakarta Selatan, DKI Jakarta 12150',
                'email' => 'sales@mitrasukses.co.id',
                'no_telepon' => '021-555-0456',
                'terms_of_payment' => '15 Hari',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_supplier' => 'UD Sentosa Makmur',
                'alamat' => 'Jl. Gatot Subroto No. 67, Jakarta Barat, DKI Jakarta 11470',
                'email' => 'info@sentosamakmur.com',
                'no_telepon' => '021-555-0789',
                'terms_of_payment' => '60 Hari',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_supplier' => 'PT Jaya Abadi Perkasa',
                'alamat' => 'Jl. Merdeka No. 89, Bandung, Jawa Barat 40111',
                'email' => 'contact@jayaabadi.co.id',
                'no_telepon' => '022-555-0123',
                'terms_of_payment' => '45 Hari',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_supplier' => 'CV Sukses Mandiri',
                'alamat' => 'Jl. Asia Afrika No. 12, Bandung, Jawa Barat 40111',
                'email' => 'info@suksesmandiri.co.id',
                'no_telepon' => '022-555-0456',
                'terms_of_payment' => '30 Hari',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('suppliers')->insert($suppliers);
    }
}
