<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('po_anggarans', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_id')->nullable()->after('bank_id');
            $table->unsignedBigInteger('bank_supplier_account_id')->nullable()->after('supplier_id');

            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('bank_supplier_account_id')->references('id')->on('bank_supplier_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('po_anggarans', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['bank_supplier_account_id']);
            $table->dropColumn(['supplier_id', 'bank_supplier_account_id']);
        });
    }
};
