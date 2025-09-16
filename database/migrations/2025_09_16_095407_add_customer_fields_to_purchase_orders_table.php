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
            $table->unsignedBigInteger('customer_id')->nullable()->after('supplier_id');
            $table->unsignedBigInteger('customer_bank_id')->nullable()->after('bank_id');
            $table->string('customer_nama_rekening')->nullable()->after('nama_rekening');
            $table->string('customer_no_rekening')->nullable()->after('no_rekening');

            $table->foreign('customer_id')->references('id')->on('ar_partners')->onDelete('set null');
            $table->foreign('customer_bank_id')->references('id')->on('banks')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['customer_bank_id']);
            $table->dropColumn(['customer_id', 'customer_bank_id', 'customer_nama_rekening', 'customer_no_rekening']);
        });
    }
};
