<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ar_partners', function (Blueprint $table) {
            $table->id();
            $table->string('nama_bp');
            $table->string('jenis_bp');
            $table->string('alamat');
            $table->string('email')->nullable();
            $table->string('no_telepon')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bisnis_partners');
    }
};
