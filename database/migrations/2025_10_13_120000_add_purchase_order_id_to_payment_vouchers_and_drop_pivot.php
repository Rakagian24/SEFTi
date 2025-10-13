<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add purchase_order_id to payment_vouchers
        Schema::table('payment_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_vouchers', 'purchase_order_id')) {
                $table->foreignId('purchase_order_id')
                    ->nullable()
                    ->constrained('purchase_orders')
                    ->nullOnDelete()
                    ->after('no_pv');
            }
        });

        // Backfill from pivot (pick the first linked PO per PV)
        if (Schema::hasTable('payment_voucher_purchase_order')) {
            $rows = DB::table('payment_voucher_purchase_order')
                ->select('payment_voucher_id', DB::raw('MIN(purchase_order_id) as purchase_order_id'))
                ->groupBy('payment_voucher_id')
                ->get();

            foreach ($rows as $row) {
                DB::table('payment_vouchers')
                    ->where('id', $row->payment_voucher_id)
                    ->update(['purchase_order_id' => $row->purchase_order_id]);
            }

            // Drop pivot table
            Schema::dropIfExists('payment_voucher_purchase_order');
        }
    }

    public function down(): void
    {
        // Recreate pivot table (minimal) if needed
        if (!Schema::hasTable('payment_voucher_purchase_order')) {
            Schema::create('payment_voucher_purchase_order', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_voucher_id')->constrained('payment_vouchers')->cascadeOnDelete();
                $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
                $table->decimal('subtotal', 18, 2)->default(0);
                $table->timestamps();
            });
        }

        Schema::table('payment_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('payment_vouchers', 'purchase_order_id')) {
                $table->dropConstrainedForeignId('purchase_order_id');
            }
        });
    }
};


