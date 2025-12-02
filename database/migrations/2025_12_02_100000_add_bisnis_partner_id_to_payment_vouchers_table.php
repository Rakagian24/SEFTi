<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_vouchers', 'bisnis_partner_id')) {
                $table->foreignId('bisnis_partner_id')
                    ->nullable()
                    ->after('po_anggaran_id')
                    ->constrained('bisnis_partners')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('payment_vouchers', 'bisnis_partner_id')) {
                $table->dropForeign(['bisnis_partner_id']);
                $table->dropColumn('bisnis_partner_id');
            }
        });
    }
};
