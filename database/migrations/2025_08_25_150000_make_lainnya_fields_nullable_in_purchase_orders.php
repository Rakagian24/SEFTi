<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Ensure fields used only for tipe "Lainnya" are nullable
            $table->decimal('cicilan', 20, 5)->nullable()->change();
            $table->integer('termin')->nullable()->change();
            $table->decimal('nominal', 20, 5)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Revert to non-null decimals without defaults only if needed (keeping precision)
            $table->decimal('cicilan', 20, 5)->nullable(false)->change();
            $table->integer('termin')->nullable(false)->change();
            $table->decimal('nominal', 20, 5)->nullable(false)->change();
        });
    }
};

