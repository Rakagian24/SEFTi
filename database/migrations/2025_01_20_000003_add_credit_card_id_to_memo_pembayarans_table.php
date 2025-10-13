<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('memo_pembayarans', function (Blueprint $table) {
            if (!Schema::hasColumn('memo_pembayarans', 'credit_card_id')) {
                $table->foreignId('credit_card_id')->nullable()->constrained('credit_cards')->onDelete('set null')->after('bank_supplier_account_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memo_pembayarans', function (Blueprint $table) {
            $table->dropForeign(['credit_card_id']);
            $table->dropColumn('credit_card_id');
        });
    }
};
