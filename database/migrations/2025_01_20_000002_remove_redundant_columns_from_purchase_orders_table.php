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
            // Drop foreign key constraints first
            $table->dropForeign(['bank_id']);

            // Remove redundant bank/account columns
            $table->dropColumn([
                'nama_rekening',
                'no_rekening',
                'bank_id',
            ]);

            // Remove redundant credit card column
            $table->dropColumn([
                'no_kartu_kredit',
            ]);

            // Remove redundant customer columns
            $table->dropColumn([
                'customer_nama_rekening',
                'customer_no_rekening',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Restore redundant bank/account columns
            $table->string('nama_rekening')->nullable()->after('bank_supplier_account_id');
            $table->string('no_rekening')->nullable()->after('nama_rekening');
            $table->foreignId('bank_id')->nullable()->constrained('banks')->onDelete('set null')->after('no_rekening');

            // Restore redundant credit card column
            $table->string('no_kartu_kredit')->nullable()->after('no_rekening');

            // Restore redundant customer columns
            $table->string('customer_nama_rekening')->nullable()->after('customer_bank_id');
            $table->string('customer_no_rekening')->nullable()->after('customer_nama_rekening');
        });
    }
};
