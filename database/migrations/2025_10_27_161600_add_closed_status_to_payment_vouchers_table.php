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
            // Extend enum to include 'Closed'
            $table->enum('status', ['Draft', 'In Progress', 'Verified', 'Approved', 'Rejected', 'Canceled', 'Closed'])
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
            // Revert to previous enum without 'Closed'
            $table->enum('status', ['Draft', 'In Progress', 'Verified', 'Approved', 'Rejected', 'Canceled'])
                  ->default('Draft')
                  ->change();
        });
    }
};
