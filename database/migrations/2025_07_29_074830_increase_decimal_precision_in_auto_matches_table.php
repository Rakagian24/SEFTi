<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auto_matches', function (Blueprint $table) {
            $table->double('kwitansi_nilai', 20, 5)->change();
            $table->double('bank_masuk_nilai', 20, 5)->change();
        });
    }

    public function down(): void
    {
        Schema::table('auto_matches', function (Blueprint $table) {
            $table->double('kwitansi_nilai', 20, 2)->change();
            $table->double('bank_masuk_nilai', 20, 2)->change();
        });
    }
};
