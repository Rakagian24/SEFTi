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
        Schema::create('pengeluaran_barang_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengeluaran_barang_id')->constrained('pengeluaran_barang')->onDelete('cascade');
            $table->foreignId('barang_id')->constrained('barangs');
            $table->decimal('qty', 10, 2);
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengeluaran_barang_items');
    }
};
