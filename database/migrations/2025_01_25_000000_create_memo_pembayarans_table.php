<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memo_pembayarans', function (Blueprint $table) {
            $table->id();
            $table->string('no_mb')->nullable()->unique(); // Diisi saat kirim
            $table->foreignId('department_id')->constrained()->onDelete('restrict');
            $table->foreignId('perihal_id')->constrained()->onDelete('restrict');
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->onDelete('set null');
            $table->text('detail_keperluan');
            $table->decimal('total', 20, 2);
            $table->string('metode_pembayaran')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('bank_id')->nullable()->constrained()->onDelete('set null');
            $table->string('nama_rekening')->nullable();
            $table->string('no_rekening')->nullable();
            $table->string('no_kartu_kredit')->nullable();
            $table->string('no_giro')->nullable();
            $table->date('tanggal_giro')->nullable();
            $table->date('tanggal_cair')->nullable();
            $table->text('keterangan')->nullable();
            $table->decimal('diskon', 20, 2)->default(0);
            $table->boolean('ppn')->default(false);
            $table->decimal('ppn_nominal', 20, 2)->default(0);
            $table->foreignId('pph_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('pph_nominal', 20, 2)->default(0);
            $table->decimal('grand_total', 20, 2);
            $table->date('tanggal')->nullable(); // Diisi saat kirim
            $table->enum('status', ['Draft', 'In Progress', 'Approved', 'Canceled', 'Rejected'])->default('Draft');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('canceled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('canceled_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            // Add indexes for better performance
            $table->index(['tanggal', 'status']);
            $table->index(['no_mb']);
            $table->index(['department_id']);
            $table->index(['perihal_id']);
            $table->index(['purchase_order_id']);
            $table->index(['supplier_id']);
            $table->index(['created_by']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memo_pembayarans');
    }
};
