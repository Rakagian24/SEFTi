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
        Schema::table('po_anggarans', function (Blueprint $table) {
            if (!Schema::hasColumn('po_anggarans', 'credit_card_id')) {
                $table->foreignId('credit_card_id')
                    ->nullable()
                    ->constrained('credit_cards')
                    ->onDelete('set null')
                    ->after('bisnis_partner_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('po_anggarans', function (Blueprint $table) {
            if (Schema::hasColumn('po_anggarans', 'credit_card_id')) {
                $table->dropForeign(['credit_card_id']);
                $table->dropColumn('credit_card_id');
            }
        });
    }
};
