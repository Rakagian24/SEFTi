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
            $table->foreignId('bank_supplier_account_id')->nullable()->after('bank_id')->constrained('bank_supplier_accounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memo_pembayarans', function (Blueprint $table) {
            $table->dropForeign(['bank_supplier_account_id']);
            $table->dropColumn('bank_supplier_account_id');
        });
    }
};
