<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class MigrasiPelangganService
{
    protected $cabangMap = [
        'HSD09' => 4,
        'BKR92' => 5,
        'HOS199' => 6,
        'BALI292' => 7,
        'SBY299' => 8,
    ];

    public function jalankanMigrasi(): int
    {
        // Ambil daftar external_id yang sudah dimigrasi
        $sudahMigrasi = DB::table('ar_partners')
            ->pluck('external_id')
            ->toArray();

        // Ambil hanya data yang belum dimigrasi dari PostgreSQL
        $pelanggan = DB::connection('pgsql_nirwana')
            ->table('trpelanggan')
            ->select('pel_id', 'nama', 'alamat', 'nomor_telp', 'kontak', 'cabang_id')
            ->whereNotIn('pel_id', $sudahMigrasi) // Filter di level database
            ->get();

        $jumlahMigrasi = 0;

        foreach ($pelanggan as $item) {
            DB::table('ar_partners')->insert([
                'external_id'     => $item->pel_id, // pel_id dari Postgre
                'nama_ap'         => $item->nama,
                'jenis_ap'        => 'Penjualan Toko',
                'alamat'          => $item->alamat,
                'email'           => null,
                'no_telepon'      => $item->nomor_telp,
                'contact_person'  => $item->kontak,
                'department_id'   => $this->cabangMap[$item->cabang_id] ?? null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            $jumlahMigrasi++;
        }

        return $jumlahMigrasi;
    }
}
