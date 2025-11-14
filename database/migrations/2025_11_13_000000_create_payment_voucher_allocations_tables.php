<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_voucher_bpb_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_voucher_id');
            $table->unsignedBigInteger('bpb_id');
            $table->decimal('amount', 20, 5)->default(0);
            $table->timestamps();

            $table->index(['payment_voucher_id']);
            $table->index(['bpb_id']);
            $table->unique(['payment_voucher_id','bpb_id']);

            $table->foreign('payment_voucher_id')->references('id')->on('payment_vouchers')->cascadeOnDelete();
            $table->foreign('bpb_id')->references('id')->on('bpbs')->cascadeOnDelete();
        });

        Schema::create('payment_voucher_memo_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_voucher_id');
            $table->unsignedBigInteger('memo_pembayaran_id');
            $table->decimal('amount', 20, 5)->default(0);
            $table->timestamps();

            $table->index(['payment_voucher_id']);
            $table->index(['memo_pembayaran_id']);
            $table->unique(['payment_voucher_id','memo_pembayaran_id']);

            $table->foreign('payment_voucher_id')->references('id')->on('payment_vouchers')->cascadeOnDelete();
            $table->foreign('memo_pembayaran_id')->references('id')->on('memo_pembayarans')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_voucher_memo_allocations');
        Schema::dropIfExists('payment_voucher_bpb_allocations');
    }
};
