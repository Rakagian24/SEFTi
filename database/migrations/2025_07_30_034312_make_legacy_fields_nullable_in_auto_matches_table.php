<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('auto_matches', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_kwitansi_id')->nullable()->change();
            $table->string('legacy_kwitansi_no')->nullable()->change();
            $table->date('legacy_kwitansi_tanggal')->nullable()->change();
            $table->double('legacy_kwitansi_nilai', 20, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auto_matches', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_kwitansi_id')->nullable(false)->change();
            $table->string('legacy_kwitansi_no')->nullable(false)->change();
            $table->date('legacy_kwitansi_tanggal')->nullable(false)->change();
            $table->double('legacy_kwitansi_nilai', 20, 2)->nullable(false)->change();
        });
    }
};
