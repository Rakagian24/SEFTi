<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Termin;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Models\Department;
use App\Models\Perihal;
use App\Models\Supplier;
use App\Models\Bank;

class TerminTestSeeder extends Seeder
{
    public function run(): void
    {
        // Create test termin
        $termin = Termin::create([
            'no_referensi' => 'TERM-001',
            'jumlah_termin' => 3,
            'keterangan' => 'Test termin untuk pembayaran cicilan',
            'status' => 'active',
        ]);

        // Create test department
        $department = Department::firstOrCreate(
            ['name' => 'Test Department'],
            ['alias' => 'TEST', 'status' => 'active']
        );

        // Create test perihal
        $perihal = Perihal::firstOrCreate(
            ['nama' => 'Test Perihal'],
            ['status' => 'active']
        );

        // Create test supplier
        $supplier = Supplier::firstOrCreate(
            ['nama_supplier' => 'Test Supplier'],
            ['alamat' => 'Test Address', 'status' => 'active']
        );

        // Create test bank
        $bank = Bank::firstOrCreate(
            ['nama_bank' => 'Test Bank'],
            ['singkatan' => 'TB', 'status' => 'active']
        );

        // Create test user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'status' => 'active'
            ]
        );

        // Create first PO for termin
        $po1 = PurchaseOrder::create([
            'tipe_po' => 'Lainnya',
            'department_id' => $department->id,
            'perihal_id' => $perihal->id,
            'supplier_id' => $supplier->id,
            'bank_id' => $bank->id,
            'nama_rekening' => 'Test Rekening',
            'no_rekening' => '1234567890',
            'metode_pembayaran' => 'Transfer',
            'cicilan' => 2000000,
            'termin_id' => $termin->id,
            'grand_total' => 6000000,
            'status' => 'Approved',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        // Create PO items for first PO
        PurchaseOrderItem::create([
            'purchase_order_id' => $po1->id,
            'nama_barang' => 'Test Barang 1',
            'qty' => 2,
            'satuan' => 'PCS',
            'harga' => 1500000,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po1->id,
            'nama_barang' => 'Test Barang 2',
            'qty' => 1,
            'satuan' => 'UNIT',
            'harga' => 3000000,
        ]);

        $this->command->info('Termin test data created successfully!');
        $this->command->info('Termin ID: ' . $termin->id);
        $this->command->info('PO ID: ' . $po1->id);
    }
}
