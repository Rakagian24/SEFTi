<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('realisasi_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('realisasi_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // transport, konsumsi, hotel, uang_saku, lainnya
            $table->boolean('active')->default(true);
            $table->string('path')->nullable();
            $table->string('original_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realisasi_documents');
    }
};
