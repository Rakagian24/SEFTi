<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Staff Toko',
                'description' => 'Role ini hanya dapat mengakses menu Purchase Order, Memo Pembayaran, BPB, Anggaran, dan Termin.',
                'permissions' => ['purchase_order', 'memo_pembayaran', 'bpb', 'anggaran', 'termin'],
                'status' => 'active'
            ],
            [
                'name' => 'Staff Digital Marketing',
                'description' => 'Role ini hanya dapat mengakses menu Purchase Order, Memo Pembayaran, BPB, dan Anggaran. (Setara dengan Staff Toko)',
                'permissions' => ['purchase_order', 'memo_pembayaran', 'bpb', 'anggaran'],
                'status' => 'active'
            ],
            [
                'name' => 'Kepala Toko',
                'description' => 'Role ini hanya dapat mengakses menu Purchase Order, Memo Pembayaran, BPB, Anggaran dan Approval.',
                'permissions' => ['purchase_order', 'memo_pembayaran', 'bpb', 'anggaran', 'approval'],
                'status' => 'active'
            ],
            [
                'name' => 'Staff Akunting & Finance',
                'description' => 'Role ini hanya dapat mengakses menu Purchase Order, Bank, Supplier, Bisnis Partner, Memo Pembayaran, BPB, Payment Voucher, Daftar List Bayar, Bank Masuk, Bank Keluar dan PO Outstanding.',
                'permissions' => ['purchase_order', 'bank', 'supplier', 'bisnis_partner', 'memo_pembayaran', 'bpb', 'payment_voucher', 'daftar_list_bayar', 'bank_masuk', 'bank_keluar', 'po_outstanding'],
                'status' => 'active'
            ],
            [
                'name' => 'Kabag',
                'description' => 'Role ini hanya dapat mengakses menu Purchase Order, Bank, Supplier, Bisnis Partner, Memo Pembayaran, BPB, Payment Voucher, Daftar List Bayar, Bank Masuk, Bank Keluar, PO Outstanding dan Approval.',
                'permissions' => ['purchase_order', 'bank', 'supplier', 'bisnis_partner', 'memo_pembayaran', 'bpb', 'payment_voucher', 'daftar_list_bayar', 'bank_masuk', 'bank_keluar', 'po_outstanding', 'approval'],
                'status' => 'active'
            ],
            [
                'name' => 'Kadiv',
                'description' => 'Role ini hanya dapat mengakses menu Approval',
                'permissions' => ['approval'],
                'status' => 'active'
            ],
            [
                'name' => 'Direksi',
                'description' => 'Role ini hanya dapat mengakses menu Approval',
                'permissions' => ['approval'],
                'status' => 'active'
            ],
            [
                'name' => 'Admin',
                'description' => 'Role ini dapat mengakses keseluruhan menu',
                'permissions' => ['*'],
                'status' => 'active'
            ],
            [
                'name' => 'Kadiv Finance',
                'description' => 'Role ini hanya dapat mengakses menu Approval untuk Payment Voucher dan Tipe Pajak',
                'permissions' => ['approval'],
                'status' => 'active'
            ],
            [
                'name' => 'Direksi Finance',
                'description' => 'Role ini hanya dapat mengakses menu Approval untuk Payment Voucher dan Tipe Pajak',
                'permissions' => ['approval'],
                'status' => 'active'
            ]
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
