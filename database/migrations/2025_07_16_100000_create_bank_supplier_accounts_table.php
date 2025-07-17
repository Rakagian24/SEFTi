<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_supplier_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('bank_id')->constrained('banks')->onDelete('cascade');
            $table->string('nama_rekening');
            $table->string('no_rekening');
            $table->timestamps();
            $table->unique(['supplier_id', 'bank_id', 'no_rekening']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_supplier_accounts');
    }
};
