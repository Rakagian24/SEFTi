<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('cicilan', 20, 2)->nullable()->after('dokumen');
            $table->integer('termin')->nullable()->after('cicilan');
            $table->decimal('nominal', 20, 2)->nullable()->after('termin');
            $table->string('keterangan')->nullable()->after('nominal');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['cicilan', 'termin', 'nominal', 'keterangan']);
        });
    }
};
