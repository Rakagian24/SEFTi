<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_vouchers', 'bank_supplier_account_id')) {
                $table->foreignId('bank_supplier_account_id')
                    ->nullable()
                    ->after('supplier_id')
                    ->constrained('bank_supplier_accounts');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('payment_vouchers', 'bank_supplier_account_id')) {
                $table->dropConstrainedForeignId('bank_supplier_account_id');
            }
        });
    }
};
