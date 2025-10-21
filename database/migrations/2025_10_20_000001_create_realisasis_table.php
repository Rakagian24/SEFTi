<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('realisasis', function (Blueprint $table) {
            $table->id();
            $table->string('no_realisasi')->nullable()->index();
            $table->date('tanggal')->nullable()->index();
            $table->foreignId('department_id')->constrained()->cascadeOnUpdate();
            $table->foreignId('po_anggaran_id')->constrained('po_anggarans')->cascadeOnUpdate();
            $table->enum('metode_pembayaran', ['Transfer']);
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete();
            $table->string('nama_rekening');
            $table->string('no_rekening');
            $table->decimal('total_anggaran', 18, 5)->default(0);
            $table->decimal('total_realisasi', 18, 5)->default(0);
            $table->text('note')->nullable();
            $table->string('status')->default('Draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('canceled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realisasis');
    }
};
