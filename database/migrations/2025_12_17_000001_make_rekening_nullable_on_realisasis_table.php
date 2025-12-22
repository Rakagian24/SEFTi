<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('realisasis', function (Blueprint $table) {
            // Izinkan nama_rekening & no_rekening kosong untuk draft
            $table->string('nama_rekening')->nullable()->change();
            $table->string('no_rekening')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('realisasis', function (Blueprint $table) {
            // Kembalikan seperti semula (tidak nullable)
            $table->string('nama_rekening')->nullable(false)->change();
            $table->string('no_rekening')->nullable(false)->change();
        });
    }
};
