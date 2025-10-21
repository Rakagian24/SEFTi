<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('po_anggarans', function (Blueprint $table) {
            $table->id();
            $table->string('no_po_anggaran')->nullable();
            $table->date('tanggal')->nullable();
            $table->foreignId('department_id')->constrained()->cascadeOnUpdate();
            $table->text('detail_keperluan')->nullable();
            $table->enum('metode_pembayaran', ['Transfer', 'Cek/Giro'])->nullable();
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete();
            $table->string('nama_rekening')->nullable();
            $table->string('no_rekening')->nullable();
            $table->string('no_giro')->nullable();
            $table->date('tanggal_giro')->nullable();
            $table->date('tanggal_cair')->nullable();
            $table->decimal('nominal', 20, 5)->default(0);
            $table->text('note')->nullable();
            $table->enum('status', ['Draft','In Progress','Rejected','Approved','Canceled'])->default('Draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('canceled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_anggarans');
    }
};
