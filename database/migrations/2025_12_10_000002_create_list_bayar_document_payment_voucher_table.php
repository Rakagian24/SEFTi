<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('list_bayar_document_payment_voucher', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('list_bayar_document_id');
            $table->unsignedBigInteger('payment_voucher_id');
            $table->timestamps();

            $table->index(['list_bayar_document_id']);
            $table->index(['payment_voucher_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('list_bayar_document_payment_voucher');
    }
};
