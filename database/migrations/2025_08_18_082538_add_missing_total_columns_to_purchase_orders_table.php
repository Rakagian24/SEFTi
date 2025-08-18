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
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Add missing columns for totals and calculations
            $table->decimal('total', 20, 2)->nullable()->after('harga');
            $table->decimal('ppn_nominal', 20, 2)->nullable()->after('ppn');
            $table->decimal('pph_nominal', 20, 2)->nullable()->after('pph_id');
            $table->decimal('grand_total', 20, 2)->nullable()->after('pph_nominal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Drop the added columns
            $table->dropColumn(['total', 'grand_total', 'ppn_nominal', 'pph_nominal']);
        });
    }
};
