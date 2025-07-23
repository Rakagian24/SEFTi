<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_masuks', function (Blueprint $table) {
            $table->id();
            $table->string('no_bm')->unique();
            $table->date('tanggal');
            $table->enum('tipe_po', ['Reguler', 'Anggaran', 'Lainnya'])->default('Reguler');
            $table->enum('terima_dari', ['Customer', 'Karyawan', 'Penjualan Toko', 'Lainnya']);
            $table->decimal('nilai', 20, 2);
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->onDelete('restrict');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable(); // relasi ke purchase order jika ada
            $table->string('input_lainnya')->nullable(); // jika terima_dari = Lainnya
            $table->enum('status', ['aktif', 'batal'])->default('aktif');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_masuks');
    }
};
