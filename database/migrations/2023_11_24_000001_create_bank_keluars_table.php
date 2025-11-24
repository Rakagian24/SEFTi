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
        Schema::create('bank_keluars', function (Blueprint $table) {
            $table->id();
            $table->string('no_bk')->nullable();
            $table->date('tanggal');
            $table->foreignId('payment_voucher_id')->nullable()->constrained('payment_vouchers')->nullOnDelete();
            $table->string('tipe_pv')->nullable(); // Reguler, Anggaran, Lainnya
            $table->foreignId('department_id')->constrained('departments');
            $table->foreignId('perihal_id')->nullable()->constrained('perihals')->nullOnDelete();
            $table->decimal('nominal', 15, 5);
            $table->string('metode_bayar'); // Transfer, Cash, etc.
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete();
            $table->string('nama_pemilik_rekening')->nullable();
            $table->string('no_rekening')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('aktif'); // aktif, batal
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_keluars');
    }
};
