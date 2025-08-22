<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memo_pembayaran_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memo_pembayaran_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Ensure unique combination
            $table->unique(['memo_pembayaran_id', 'purchase_order_id'], 'memo_po_unique');

            // Add indexes for better performance
            $table->index(['memo_pembayaran_id']);
            $table->index(['purchase_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memo_pembayaran_purchase_orders');
    }
};
