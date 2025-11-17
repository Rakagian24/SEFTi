<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Extend tipe_pv enum to include DP
        DB::statement("ALTER TABLE payment_vouchers MODIFY tipe_pv ENUM('Reguler','Anggaran','Lainnya','Pajak','Manual','DP') NULL");

        // Table to store allocations of DP PVs against regular PVs
        Schema::create('payment_voucher_dp_allocations', function (Blueprint $table) {
            $table->id();
            // PV Reguler yang menggunakan DP sebagai pemotong
            $table->foreignId('payment_voucher_id')
                ->constrained('payment_vouchers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            // PV tipe DP yang menjadi sumber pemotongan
            $table->foreignId('dp_payment_voucher_id')
                ->constrained('payment_vouchers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            // Nilai DP yang dipakai pada PV Reguler ini
            $table->decimal('amount', 18, 5)->default(0);

            $table->timestamps();

            $table->unique(['payment_voucher_id', 'dp_payment_voucher_id'], 'pv_dp_alloc_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_voucher_dp_allocations');

        // Revert tipe_pv enum back to previous set (without DP)
        DB::statement("ALTER TABLE payment_vouchers MODIFY tipe_pv ENUM('Reguler','Anggaran','Lainnya','Pajak','Manual') NULL");
    }
};
