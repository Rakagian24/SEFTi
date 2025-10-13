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
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'bank_supplier_account_id')) {
                $table->foreignId('bank_supplier_account_id')->nullable()->constrained('bank_supplier_accounts')->onDelete('set null')->after('supplier_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['bank_supplier_account_id']);
            $table->dropColumn('bank_supplier_account_id');
        });
    }
};
