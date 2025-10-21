<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_vouchers', 'credit_card_id')) {
                $table->foreignId('credit_card_id')
                    ->nullable()
                    ->constrained('credit_cards')
                    ->cascadeOnUpdate()
                    ->nullOnDelete()
                    ->after('supplier_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('payment_vouchers', 'credit_card_id')) {
                $table->dropConstrainedForeignId('credit_card_id');
            }
        });
    }
};
