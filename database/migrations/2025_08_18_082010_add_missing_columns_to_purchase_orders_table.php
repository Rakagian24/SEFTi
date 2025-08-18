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
            // Add the missing columns that are referenced in foreign key constraints
            // Add them at the end of the table to avoid positioning issues
            $table->foreignId('perihal_id')->nullable()->constrained('perihals')->onDelete('set null');
            $table->foreignId('bank_id')->nullable()->constrained('banks')->onDelete('set null');
            $table->foreignId('pph_id')->nullable()->constrained('pphs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Drop the added columns
            $table->dropForeign(['perihal_id']);
            $table->dropForeign(['bank_id']);
            $table->dropForeign(['pph_id']);
            $table->dropColumn(['perihal_id', 'bank_id', 'pph_id']);
        });
    }
};
