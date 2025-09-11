<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_voucher_purchase_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->timestamps();
            $table->unique(['payment_voucher_id','purchase_order_id'], 'pv_po_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_voucher_purchase_order');
    }
};

