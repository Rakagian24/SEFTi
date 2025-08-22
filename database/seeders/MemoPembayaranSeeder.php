<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MemoPembayaran;
use App\Models\Department;
use App\Models\Perihal;
use App\Models\Supplier;
use App\Models\Bank;
use App\Models\Pph;
use App\Models\User;
use App\Services\DocumentNumberService;
use Carbon\Carbon;

class MemoPembayaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get sample data
        $departments = Department::all();
        $perihals = Perihal::all();
        $suppliers = Supplier::all();
        $banks = Bank::all();
        $pphs = Pph::all();
        $users = User::all();

        if ($departments->isEmpty() || $perihals->isEmpty() || $users->isEmpty()) {
            $this->command->warn('Required data not found. Please run other seeders first.');
            return;
        }

        $statuses = ['Draft', 'In Progress', 'Approved', 'Rejected', 'Canceled'];
        $metodePembayaran = ['Transfer', 'Cek/Giro', 'Kredit'];

        for ($i = 1; $i <= 20; $i++) {
            $department = $departments->random();
            $perihal = $perihals->random();
            $user = $users->random();
            $status = $statuses[array_rand($statuses)];
            $metode = $metodePembayaran[array_rand($metodePembayaran)];

            // Generate document number for non-draft statuses
            $noMb = null;
            $tanggal = null;
            if ($status !== 'Draft') {
                $departmentAlias = $department->alias ?? substr($department->name, 0, 3);
                $noMb = "MP/{$departmentAlias}/VIII/2025/" . str_pad($i, 4, '0', STR_PAD_LEFT);
                $tanggal = Carbon::now()->subDays(rand(1, 30));
            }

            $total = rand(1000000, 10000000);
            $diskon = rand(0, 500000);
            $subtotal = $total - $diskon;
            $ppn = rand(0, 1);
            $ppnNominal = $ppn ? $subtotal * 0.11 : 0;
            $pph = $pphs->random();
            $pphNominal = $subtotal * ($pph->persentase / 100);
            $grandTotal = $subtotal + $ppnNominal + $pphNominal;

            $memoPembayaran = MemoPembayaran::create([
                'no_mb' => $noMb,
                'department_id' => $department->id,
                'perihal_id' => $perihal->id,
                'purchase_order_id' => null, // You can add PO relationship if needed
                'detail_keperluan' => "Detail keperluan untuk memo pembayaran #{$i}",
                'total' => $total,
                'metode_pembayaran' => $metode,
                'supplier_id' => $suppliers->isNotEmpty() ? $suppliers->random()->id : null,
                'bank_id' => $banks->isNotEmpty() ? $banks->random()->id : null,
                'nama_rekening' => $metode === 'Transfer' ? 'Nama Rekening Sample' : null,
                'no_rekening' => $metode === 'Transfer' ? '1234567890' : null,
                'no_kartu_kredit' => $metode === 'Kredit' ? '1234-5678-9012-3456' : null,
                'no_giro' => $metode === 'Cek/Giro' ? 'GIR' . str_pad($i, 6, '0', STR_PAD_LEFT) : null,
                'tanggal_giro' => $metode === 'Cek/Giro' ? Carbon::now()->addDays(rand(1, 30)) : null,
                'tanggal_cair' => $metode === 'Cek/Giro' ? Carbon::now()->addDays(rand(31, 60)) : null,
                'keterangan' => "Keterangan tambahan untuk memo pembayaran #{$i}",
                'diskon' => $diskon,
                'ppn' => $ppn,
                'ppn_nominal' => $ppnNominal,
                'pph_id' => $pph->id,
                'pph_nominal' => $pphNominal,
                'grand_total' => $grandTotal,
                'tanggal' => $tanggal,
                'status' => $status,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Add approval/rejection data for non-draft statuses
            if ($status === 'Approved') {
                $approver = $users->random();
                $memoPembayaran->update([
                    'approved_by' => $approver->id,
                    'approved_at' => $tanggal->addHours(rand(1, 24)),
                ]);
            } elseif ($status === 'Rejected') {
                $rejecter = $users->random();
                $memoPembayaran->update([
                    'rejected_by' => $rejecter->id,
                    'rejected_at' => $tanggal->addHours(rand(1, 24)),
                ]);
            } elseif ($status === 'Canceled') {
                $canceler = $users->random();
                $memoPembayaran->update([
                    'canceled_by' => $canceler->id,
                    'canceled_at' => $tanggal->addHours(rand(1, 24)),
                ]);
            }
        }

        $this->command->info('Memo Pembayaran seeder completed successfully!');
    }
}
