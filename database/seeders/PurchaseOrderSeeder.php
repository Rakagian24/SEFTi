<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        DB::table('purchase_orders')->insert([
            [
                'no_po' => null,
                'department_id' => 1,
                'perihal' => 'Pembelian Barang A',
                'tanggal' => null,
                'status' => 'Draft',
                'metode_pembayaran' => 'Transfer',
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'no_po' => 'PO-20240722-001',
                'department_id' => 1,
                'perihal' => 'Pembayaran Ongkir',
                'tanggal' => $now->copy()->subDays(2),
                'status' => 'In Progress',
                'metode_pembayaran' => 'Cash',
                'created_by' => 1,
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(2),
            ],
            [
                'no_po' => 'PO-20240722-002',
                'department_id' => 1,
                'perihal' => 'Pembelian Barang B',
                'tanggal' => $now->copy()->subDays(5),
                'status' => 'Approved',
                'metode_pembayaran' => 'Transfer',
                'created_by' => 1,
                'approved_by' => 1,
                'approved_at' => $now->copy()->subDays(4),
                'created_at' => $now->copy()->subDays(5),
                'updated_at' => $now->copy()->subDays(4),
            ],
            [
                'no_po' => 'PO-20240722-003',
                'department_id' => 1,
                'perihal' => 'Pembelian Barang C',
                'tanggal' => $now->copy()->subDays(7),
                'status' => 'Canceled',
                'metode_pembayaran' => 'Transfer',
                'created_by' => 1,
                'canceled_by' => 1,
                'canceled_at' => $now->copy()->subDays(6),
                'created_at' => $now->copy()->subDays(7),
                'updated_at' => $now->copy()->subDays(6),
            ],
            [
                'no_po' => 'PO-20240722-004',
                'department_id' => 1,
                'perihal' => 'Pembelian Barang D',
                'tanggal' => $now->copy()->subDays(10),
                'status' => 'Rejected',
                'metode_pembayaran' => 'Cash',
                'created_by' => 1,
                'rejected_by' => 1,
                'rejected_at' => $now->copy()->subDays(9),
                'created_at' => $now->copy()->subDays(10),
                'updated_at' => $now->copy()->subDays(9),
            ],
        ]);
    }
}
