<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('realisasi_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('realisasi_id')->constrained('realisasis')->cascadeOnDelete();
            $table->foreignId('jenis_pengeluaran_id')->nullable()->constrained('pengeluarans')->nullOnDelete();
            $table->string('jenis_pengeluaran_text')->nullable();
            $table->string('keterangan')->nullable();
            $table->decimal('harga', 18, 5)->default(0);
            $table->decimal('qty', 18, 5)->default(0);
            $table->string('satuan')->nullable();
            $table->decimal('subtotal', 18, 5)->default(0);
            $table->decimal('realisasi', 18, 5)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realisasi_items');
    }
};
