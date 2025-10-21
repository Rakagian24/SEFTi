<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('po_anggaran_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_anggaran_id')->constrained('po_anggarans')->cascadeOnDelete();
            $table->foreignId('jenis_pengeluaran_id')->nullable()->constrained('pengeluarans')->nullOnDelete();
            $table->string('jenis_pengeluaran_text')->nullable();
            $table->text('keterangan')->nullable();
            $table->decimal('harga', 20, 5)->default(0);
            $table->decimal('qty', 20, 5)->default(0);
            $table->string('satuan')->nullable();
            $table->decimal('subtotal', 20, 5)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_anggaran_items');
    }
};
