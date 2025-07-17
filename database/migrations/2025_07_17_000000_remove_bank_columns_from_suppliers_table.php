<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'bank_1', 'nama_rekening_1', 'no_rekening_1',
                'bank_2', 'nama_rekening_2', 'no_rekening_2',
                'bank_3', 'nama_rekening_3', 'no_rekening_3',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('bank_1')->nullable();
            $table->string('nama_rekening_1')->nullable();
            $table->string('no_rekening_1')->nullable();
            $table->string('bank_2')->nullable();
            $table->string('nama_rekening_2')->nullable();
            $table->string('no_rekening_2')->nullable();
            $table->string('bank_3')->nullable();
            $table->string('nama_rekening_3')->nullable();
            $table->string('no_rekening_3')->nullable();
        });
    }
};
