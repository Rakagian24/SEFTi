<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('no_po')->nullable()->unique(); // Diisi saat kirim
            $table->foreignId('department_id')->constrained()->onDelete('restrict');
            $table->string('perihal');
            $table->date('tanggal')->nullable(); // Diisi saat kirim
            $table->enum('status', ['Draft', 'In Progress', 'Approved', 'Canceled', 'Rejected'])->default('Draft');
            $table->string('metode_pembayaran')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('canceled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('canceled_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
