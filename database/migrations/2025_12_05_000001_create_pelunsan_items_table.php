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
        Schema::create('pelunasan_ap_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pelunasan_ap_id');
            $table->unsignedBigInteger('payment_voucher_id');
            $table->decimal('nilai_pv', 20, 5)->default(0);
            $table->decimal('outstanding', 20, 5)->default(0);
            $table->decimal('nilai_pelunasan', 20, 5)->default(0);
            $table->decimal('sisa', 20, 5)->default(0);
            $table->timestamps();

            // Foreign keys
            $table->foreign('pelunasan_ap_id')->references('id')->on('pelunasan_aps')->onDelete('cascade');
            $table->foreign('payment_voucher_id')->references('id')->on('payment_vouchers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelunasan_ap_items');
    }
};
