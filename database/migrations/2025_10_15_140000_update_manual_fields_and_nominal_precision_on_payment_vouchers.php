<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add manual fields (Indonesian names, prefixed with manual_ to avoid collisions)
        Schema::table('payment_vouchers', function (Blueprint $table) {
            $table->string('manual_supplier')->nullable()->after('supplier_id');
            $table->string('manual_no_telepon')->nullable()->after('manual_supplier');
            $table->string('manual_alamat')->nullable()->after('manual_no_telepon');
            $table->string('manual_nama_bank')->nullable()->after('manual_alamat');
            $table->string('manual_nama_pemilik_rekening')->nullable()->after('manual_nama_bank');
            $table->string('manual_no_rekening')->nullable()->after('manual_nama_pemilik_rekening');
        });

        // Update tipe_pv enum to include Manual and Pajak (if not already)
        DB::statement("ALTER TABLE payment_vouchers MODIFY tipe_pv ENUM('Reguler','Anggaran','Lainnya','Pajak','Manual') NULL");

        // Change nominal precision to (18,5)
        DB::statement("ALTER TABLE payment_vouchers MODIFY nominal DECIMAL(18,5) NOT NULL DEFAULT 0");
    }

    public function down(): void
    {
        // Revert nominal precision back to (18,2)
        DB::statement("ALTER TABLE payment_vouchers MODIFY nominal DECIMAL(18,2) NOT NULL DEFAULT 0");

        // Revert tipe_pv enum (remove Manual and Pajak)
        DB::statement("ALTER TABLE payment_vouchers MODIFY tipe_pv ENUM('Reguler','Anggaran','Lainnya') NULL");

        // Drop manual fields
        Schema::table('payment_vouchers', function (Blueprint $table) {
            $table->dropColumn([
                'manual_supplier',
                'manual_no_telepon',
                'manual_alamat',
                'manual_nama_bank',
                'manual_nama_pemilik_rekening',
                'manual_no_rekening',
            ]);
        });
    }
};
