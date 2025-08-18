<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Supplier;
use App\Models\Bank;

class BankSupplierAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get available banks and suppliers
        $banks = Bank::where('status', 'active')->get();
        $suppliers = Supplier::all();

        if ($banks->isEmpty() || $suppliers->isEmpty()) {
            return;
        }

        $bankSupplierAccounts = [];

        foreach ($suppliers as $supplier) {
            // Each supplier gets 1-3 bank accounts
            $numAccounts = rand(1, 3);
            $selectedBanks = $banks->random($numAccounts);

            foreach ($selectedBanks as $bank) {
                $bankSupplierAccounts[] = [
                    'supplier_id' => $supplier->id,
                    'bank_id' => $bank->id,
                    'nama_rekening' => $supplier->nama_supplier,
                    'no_rekening' => $this->generateAccountNumber(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Insert bank supplier accounts
        if (!empty($bankSupplierAccounts)) {
            DB::table('bank_supplier_accounts')->insert($bankSupplierAccounts);
        }
    }

    /**
     * Generate a random account number
     */
    private function generateAccountNumber(): string
    {
        return (string) rand(1000000000, 9999999999);
    }
}
