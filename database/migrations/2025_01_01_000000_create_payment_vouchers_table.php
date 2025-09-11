<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('no_pv')->nullable(); // auto when send
            $table->date('tanggal')->nullable(); // auto when send

            $table->enum('tipe_pv', ['Reguler', 'Anggaran', 'Lainnya'])->nullable();

            $table->foreignId('supplier_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('supplier_phone')->nullable();
            $table->string('supplier_address')->nullable();

            $table->foreignId('department_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('perihal_id')->nullable()->constrained('perihals')->cascadeOnUpdate()->nullOnDelete();

            $table->decimal('nominal', 18, 2)->default(0);
            $table->string('metode_bayar')->nullable(); // Transfer, Cek, Giro
            $table->string('no_giro')->nullable();
            $table->date('tanggal_giro')->nullable();
            $table->date('tanggal_cair')->nullable();

            $table->text('note')->nullable();
            $table->text('keterangan')->nullable();

            $table->string('no_bk')->nullable(); // Bank Keluar number when exists

            $table->enum('status', ['Draft','In Progress','Rejected','Approved','Canceled'])->default('Draft');

            $table->foreignId('creator_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_vouchers');
    }
};

