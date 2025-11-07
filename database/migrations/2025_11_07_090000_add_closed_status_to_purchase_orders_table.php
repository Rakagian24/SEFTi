<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Add Closed to enum list
            $table->enum('status', ['Draft', 'In Progress', 'Verified', 'Validated', 'Approved', 'Canceled', 'Rejected', 'Closed'])
                  ->default('Draft')
                  ->change();
        });

        // Migrate existing data: set Kredit POs that were Approved to Closed
        DB::table('purchase_orders')
            ->where('metode_pembayaran', 'Kredit')
            ->where('status', 'Approved')
            ->update(['status' => 'Closed']);
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Revert to previous list without Closed
            $table->enum('status', ['Draft', 'In Progress', 'Verified', 'Validated', 'Approved', 'Canceled', 'Rejected'])
                  ->default('Draft')
                  ->change();
        });
    }
};
