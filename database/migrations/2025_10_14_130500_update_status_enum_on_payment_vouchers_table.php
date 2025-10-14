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
        Schema::table('payment_vouchers', function (Blueprint $table) {
            // Add 'Verified' to the status enum
            $table->enum('status', ['Draft', 'In Progress', 'Verified', 'Approved', 'Rejected', 'Canceled'])
                  ->default('Draft')
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            // Revert to previous enum (without 'Verified')
            $table->enum('status', ['Draft', 'In Progress', 'Approved', 'Rejected', 'Canceled'])
                  ->default('Draft')
                  ->change();
        });
    }
};
