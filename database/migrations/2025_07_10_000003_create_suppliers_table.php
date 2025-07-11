<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('nama_supplier');
            $table->string('alamat');
            $table->string('email')->nullable();
            $table->string('no_telepon')->nullable();
            $table->unsignedBigInteger('bank_id');
            $table->unsignedBigInteger('bank_account_id');
            $table->string('terms_of_payment')->nullable();
            $table->timestamps();

            $table->foreign('bank_id')->references('id')->on('banks')->onDelete('cascade');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
