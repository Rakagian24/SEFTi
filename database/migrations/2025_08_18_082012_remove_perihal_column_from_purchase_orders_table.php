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
            // Check if the perihal column exists before dropping it
            if (Schema::hasColumn('purchase_orders', 'perihal')) {
                $table->dropColumn(['perihal']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Only add the column if it doesn't exist
            if (!Schema::hasColumn('purchase_orders', 'perihal')) {
                $table->string('perihal')->nullable()->after('department_id');
            }
        });
    }
};
