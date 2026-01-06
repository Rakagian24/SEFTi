<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->foreignId('bisnis_partner_id')
                ->nullable()
                ->after('purchase_order_id')
                ->constrained('bisnis_partners')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign(['bisnis_partner_id']);
            $table->dropColumn('bisnis_partner_id');
        });
    }
};
