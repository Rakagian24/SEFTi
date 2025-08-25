<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, let's safely handle existing data by rounding to 5 decimal places
        // This prevents data truncation errors

        // Update memo_pembayarans table decimal columns
        DB::statement('UPDATE memo_pembayarans SET
            diskon = ROUND(diskon, 5),
            grand_total = ROUND(grand_total, 5),
            pph_nominal = ROUND(pph_nominal, 5),
            ppn_nominal = ROUND(ppn_nominal, 5),
            total = ROUND(total, 5)');

        Schema::table('memo_pembayarans', function (Blueprint $table) {
            $table->decimal('diskon', 20, 5)->change();
            $table->decimal('grand_total', 20, 5)->change();
            $table->decimal('pph_nominal', 20, 5)->change();
            $table->decimal('ppn_nominal', 20, 5)->change();
            $table->decimal('total', 20, 5)->change();
        });

        // Update purchase_orders table decimal columns
        DB::statement('UPDATE purchase_orders SET
            cicilan = ROUND(COALESCE(cicilan, 0), 5),
            diskon = ROUND(COALESCE(diskon, 0), 5),
            grand_total = ROUND(COALESCE(grand_total, 0), 5),
            harga = ROUND(COALESCE(harga, 0), 5),
            nominal = ROUND(COALESCE(nominal, 0), 5),
            pph_nominal = ROUND(COALESCE(pph_nominal, 0), 5),
            ppn_nominal = ROUND(COALESCE(ppn_nominal, 0), 5),
            total = ROUND(COALESCE(total, 0), 5)');

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('cicilan', 20, 5)->change();
            $table->decimal('diskon', 20, 5)->change();
            $table->decimal('grand_total', 20, 5)->change();
            $table->decimal('harga', 20, 5)->change();
            $table->decimal('nominal', 20, 5)->change();
            $table->decimal('pph_nominal', 20, 5)->change();
            $table->decimal('ppn_nominal', 20, 5)->change();
            $table->decimal('total', 20, 5)->change();
        });

        // Update purchase_order_items table decimal columns
        DB::statement('UPDATE purchase_order_items SET
            harga = ROUND(harga, 5)');

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->decimal('harga', 20, 5)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert memo_pembayarans table decimal columns back to 20,2
        Schema::table('memo_pembayarans', function (Blueprint $table) {
            $table->decimal('diskon', 20, 2)->change();
            $table->decimal('grand_total', 20, 2)->change();
            $table->decimal('pph_nominal', 20, 2)->change();
            $table->decimal('ppn_nominal', 20, 2)->change();
            $table->decimal('total', 20, 2)->change();
        });

        // Revert purchase_orders table decimal columns back to 20,2
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('cicilan', 20, 2)->change();
            $table->decimal('diskon', 20, 2)->change();
            $table->decimal('grand_total', 20, 2)->change();
            $table->decimal('harga', 20, 2)->change();
            $table->decimal('nominal', 20, 2)->change();
            $table->decimal('pph_nominal', 20, 2)->change();
            $table->decimal('ppn_nominal', 20, 2)->change();
            $table->decimal('total', 20, 2)->change();
        });

        // Revert purchase_order_items table decimal columns back to 20,2
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->decimal('harga', 20, 2)->change();
        });
    }
};
