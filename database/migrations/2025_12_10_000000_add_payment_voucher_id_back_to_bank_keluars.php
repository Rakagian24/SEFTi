<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_keluars') && !Schema::hasColumn('bank_keluars', 'payment_voucher_id')) {
            Schema::table('bank_keluars', function (Blueprint $table) {
                $table->foreignId('payment_voucher_id')
                    ->nullable()
                    ->after('tanggal')
                    ->constrained('payment_vouchers')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bank_keluars') && Schema::hasColumn('bank_keluars', 'payment_voucher_id')) {
            Schema::table('bank_keluars', function (Blueprint $table) {
                $table->dropForeign(['payment_voucher_id']);
                $table->dropColumn('payment_voucher_id');
            });
        }
    }
};
