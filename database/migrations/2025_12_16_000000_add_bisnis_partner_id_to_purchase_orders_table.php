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
            if (!Schema::hasColumn('purchase_orders', 'bisnis_partner_id')) {
                $table->unsignedBigInteger('bisnis_partner_id')->nullable()->after('bank_supplier_account_id');
                $table->foreign('bisnis_partner_id')
                    ->references('id')
                    ->on('bisnis_partners')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_orders', 'bisnis_partner_id')) {
                $table->dropForeign(['bisnis_partner_id']);
                $table->dropColumn('bisnis_partner_id');
            }
        });
    }
};
