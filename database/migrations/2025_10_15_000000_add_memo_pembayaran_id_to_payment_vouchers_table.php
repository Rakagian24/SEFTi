<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_vouchers', 'memo_pembayaran_id')) {
                $table->foreignId('memo_pembayaran_id')
                    ->nullable()
                    ->constrained('memo_pembayarans')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('payment_vouchers', 'memo_pembayaran_id')) {
                $table->dropConstrainedForeignId('memo_pembayaran_id');
            }
        });
    }
};
