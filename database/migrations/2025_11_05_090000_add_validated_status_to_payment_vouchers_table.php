<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            // Add 'Validated' to the status enum while keeping existing values
            $table->enum('status', ['Draft', 'In Progress', 'Verified', 'Validated', 'Approved', 'Rejected', 'Canceled', 'Closed'])
                  ->default('Draft')
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            // Revert to previous enum without 'Validated'
            $table->enum('status', ['Draft', 'In Progress', 'Verified', 'Approved', 'Rejected', 'Canceled', 'Closed'])
                  ->default('Draft')
                  ->change();
        });
    }
};
