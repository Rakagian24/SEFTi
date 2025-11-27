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
        Schema::table('bank_keluars', function (Blueprint $table) {
            $table->foreignId('bisnis_partner_id')
                  ->nullable()
                  ->after('supplier_id')
                  ->constrained('bisnis_partners')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_keluars', function (Blueprint $table) {
            $table->dropForeign(['bisnis_partner_id']);
            $table->dropColumn('bisnis_partner_id');
        });
    }
};
