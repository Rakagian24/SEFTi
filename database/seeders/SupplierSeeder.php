<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SupplierSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        // Bank aktif
        $banks = [2, 5, 17, 24, 25, 26, 27, 28, 29];

        // Random terms of payment
        $terms = ['Cash', '30 Days', '45 Days', '60 Days'];

        // Data supplier dari pusat
        $suppliers = [
            ['PT. SINGA GLOBAL TEKSTIL (SGT1)', 'JL. RANCAJIGANG NO 110 MAJALAYA', 'IBU HANUM'],
            ['PT. SINGA GLOBAL TEKSTIL (SGT2)', 'JL. RANCAJIGANG NO 110 MAJALAYA', 'IBU MERRY'],
            ['PT. SINGA GLOBAL TEKSTIL (SGT3)', 'JL. RANCAJIGANG NO 110 MAJALAYA', 'KO YAYANG'],
            ['PT. SOLJER ABADI', 'JL. RANCAEKEK MAJALAYA NO. 289 SOLOKAN JERUK', 'KO ANTON'],
            ['PT. SURYA SANTOSA PRIMA ABADI', 'JL. RANCAEKEK MAJALAYA NO. 289 SOLOKAN JERUK', 'KO ANTON'],
            ['PT. NIRWANA ALABARE GARMENT', 'JL. RANCAEKEK MAJALAYA NO. 289 SOLOKAN JERUK', 'KO BOBY'],
            ['PT. NIRWANA ABADI SANTOSA', 'JL. RANCAEKEK MAJALAYA NO. 207 MAJALAYA', 'KO YAYANG'],
            ['PT. GUNAJAYA SANTOSA', 'JL. RANCAJIGANG NO. 110 MAJALAYA', 'KO YANTO'],
            ['INTERNAL', '-', '-'],
            ['PT. INDONESIA LIBOLON FIBER SYSTEM', 'JL.ALAYDRUS NO. 45 A,B,C LANTAI 1, JAKARTA PUSAT', 'BPK. AGUS'],
            ['PT. SUAR PNGO SISTEMINDO', 'JAKARTA', 'PAK BAMBANG'],
        ];

        foreach ($suppliers as $index => $sup) {

            $slug = Str::slug($sup[0], '-');

            // Dummy email & phone
            $email = $slug . '@dummy.com';
            $phone = '08' . rand(1000000000, 9999999999);
            $top   = $terms[array_rand($terms)];

            // Insert supplier
            $supplierId = DB::table('suppliers')->insertGetId([
                'nama_supplier'      => $sup[0],
                'alamat'             => $sup[1],
                'contact'            => $sup[2],
                'email'              => $email,
                'no_telepon'         => $phone,
                'department_id'      => 11,
                'terms_of_payment'   => $top,
                'status'             => 'active',
                'created_at'         => $now,
                'updated_at'         => $now
            ]);

            // Set jumlah rekening per supplier (2 atau 3)
            $accountCount = rand(2, 3);

            // Random rekening untuk supplier ini
            for ($i = 1; $i <= $accountCount; $i++) {

                $noRek = strval(rand(1000000000, 99999999999999));

                DB::table('bank_supplier_accounts')->insert([
                    'supplier_id'   => $supplierId,
                    'bank_id'       => $banks[array_rand($banks)],
                    'nama_rekening' => $sup[0] . " Rekening " . $i,
                    'no_rekening'   => $noRek,
                    'created_at'    => $now,
                    'updated_at'    => $now
                ]);
            }
        }
    }
}
