<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pphs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pph');
            $table->string('nama_pph');
            $table->bigInteger('tarif_pph')->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pphs');
    }
};
