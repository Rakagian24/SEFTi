<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pemilik');
            $table->string('no_rekening');
            $table->unsignedBigInteger('bank_id');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unique(['no_rekening', 'bank_id']);
            $table->foreign('bank_id')->references('id')->on('banks')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
