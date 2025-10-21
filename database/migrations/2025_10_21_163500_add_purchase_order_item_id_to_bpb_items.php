<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bpb_items', function (Blueprint $table) {
            $table->foreignId('purchase_order_item_id')
                ->nullable()
                ->after('bpb_id')
                ->constrained('purchase_order_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bpb_items', function (Blueprint $table) {
            if (Schema::hasColumn('bpb_items', 'purchase_order_item_id')) {
                $table->dropConstrainedForeignId('purchase_order_item_id');
            }
        });
    }
};
