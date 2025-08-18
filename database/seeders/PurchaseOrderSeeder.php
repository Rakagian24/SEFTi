<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // First, let's check if we have departments and perihals
        $departmentId = DB::table('departments')->value('id') ?? 1;
        $perihalId = DB::table('perihals')->value('id') ?? 1;
        $userId = DB::table('users')->value('id') ?? 1;

        DB::table('purchase_orders')->insert([
            [
                'no_po' => null,
                'tipe_po' => 'Reguler',
                'department_id' => $departmentId,
                'perihal_id' => $perihalId,
                'no_invoice' => 'INV-001',
                'harga' => 1000000.00,
                'total' => 1000000.00,
                'detail_keperluan' => 'Pembelian Barang A untuk kebutuhan operasional',
                'tanggal' => null,
                'status' => 'Draft',
                'metode_pembayaran' => 'Transfer',
                'bank_id' => null,
                'nama_rekening' => null,
                'no_rekening' => null,
                'no_giro' => null,
                'tanggal_giro' => null,
                'tanggal_cair' => null,
                'created_by' => $userId,
                'updated_by' => null,
                'canceled_by' => null,
                'canceled_at' => null,
                'approved_by' => null,
                'approved_at' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'dokumen' => null,
                'cicilan' => null,
                'termin' => null,
                'nominal' => null,
                'keterangan' => null,
                'diskon' => 0.00,
                'ppn' => false,
                'ppn_nominal' => 0.00,
                'pph_id' => null,
                'pph_nominal' => 0.00,
                'grand_total' => 1000000.00,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'no_po' => 'PO-20240722-001',
                'tipe_po' => 'Reguler',
                'department_id' => $departmentId,
                'perihal_id' => $perihalId,
                'no_invoice' => 'INV-002',
                'harga' => 500000.00,
                'total' => 500000.00,
                'detail_keperluan' => 'Pembayaran Ongkir',
                'tanggal' => $now->copy()->subDays(2),
                'status' => 'In Progress',
                'metode_pembayaran' => 'Cash',
                'bank_id' => null,
                'nama_rekening' => null,
                'no_rekening' => null,
                'no_giro' => null,
                'tanggal_giro' => null,
                'tanggal_cair' => null,
                'created_by' => $userId,
                'updated_by' => null,
                'canceled_by' => null,
                'canceled_at' => null,
                'approved_by' => null,
                'approved_at' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'dokumen' => null,
                'cicilan' => null,
                'termin' => null,
                'nominal' => null,
                'keterangan' => null,
                'diskon' => 0.00,
                'ppn' => false,
                'ppn_nominal' => 0.00,
                'pph_id' => null,
                'pph_nominal' => 0.00,
                'grand_total' => 500000.00,
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(2),
            ],
            [
                'no_po' => 'PO-20240722-002',
                'tipe_po' => 'Lainnya',
                'department_id' => $departmentId,
                'perihal_id' => $perihalId,
                'no_invoice' => 'INV-003',
                'harga' => 750000.00,
                'total' => 750000.00,
                'detail_keperluan' => 'Pembelian Barang B',
                'tanggal' => $now->copy()->subDays(5),
                'status' => 'Approved',
                'metode_pembayaran' => 'Transfer',
                'bank_id' => null,
                'nama_rekening' => null,
                'no_rekening' => null,
                'no_giro' => null,
                'tanggal_giro' => null,
                'tanggal_cair' => null,
                'created_by' => $userId,
                'updated_by' => null,
                'canceled_by' => null,
                'canceled_at' => null,
                'approved_by' => $userId,
                'approved_at' => $now->copy()->subDays(4),
                'rejected_by' => null,
                'rejected_at' => null,
                'dokumen' => null,
                'cicilan' => null,
                'termin' => null,
                'nominal' => null,
                'keterangan' => null,
                'diskon' => 0.00,
                'ppn' => false,
                'ppn_nominal' => 0.00,
                'pph_id' => null,
                'pph_nominal' => 0.00,
                'grand_total' => 750000.00,
                'created_at' => $now->copy()->subDays(5),
                'updated_at' => $now->copy()->subDays(4),
            ],
        ]);

        // Insert sample purchase order items
        $poIds = DB::table('purchase_orders')->pluck('id');

        foreach ($poIds as $poId) {
            DB::table('purchase_order_items')->insert([
                [
                    'purchase_order_id' => $poId,
                    'nama_barang' => 'Barang Sample ' . $poId,
                    'qty' => 10,
                    'satuan' => 'pcs',
                    'harga' => 100000.00,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'purchase_order_id' => $poId,
                    'nama_barang' => 'Barang Sample 2 ' . $poId,
                    'qty' => 5,
                    'satuan' => 'unit',
                    'harga' => 50000.00,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }
    }
}
