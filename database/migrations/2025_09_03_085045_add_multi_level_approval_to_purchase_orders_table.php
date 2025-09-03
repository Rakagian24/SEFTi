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
            // Add verified fields
            if (!Schema::hasColumn('purchase_orders', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->after('rejection_reason');
            }

            if (!Schema::hasColumn('purchase_orders', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by');
            }

            if (!Schema::hasColumn('purchase_orders', 'verification_notes')) {
                $table->text('verification_notes')->nullable()->after('verified_at');
            }

            // Add validated fields
            if (!Schema::hasColumn('purchase_orders', 'validated_by')) {
                $table->unsignedBigInteger('validated_by')->nullable()->after('verification_notes');
            }

            if (!Schema::hasColumn('purchase_orders', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('validated_by');
            }

            if (!Schema::hasColumn('purchase_orders', 'validation_notes')) {
                $table->text('validation_notes')->nullable()->after('validated_at');
            }

            // Update status enum to include Verified and Validated
            $table->enum('status', ['Draft', 'In Progress', 'Verified', 'Validated', 'Approved', 'Canceled', 'Rejected'])
                  ->default('Draft')
                  ->change();
        });

        // Add indexes
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasIndex('purchase_orders', 'purchase_orders_verified_by_index')) {
                $table->index('verified_by');
            }

            if (!Schema::hasIndex('purchase_orders', 'purchase_orders_validated_by_index')) {
                $table->index('validated_by');
            }
        });

        // Add foreign key constraints
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'verified_by_foreign')) {
                $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
            }

            if (!Schema::hasColumn('purchase_orders', 'validated_by_foreign')) {
                $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Drop foreign keys
            try {
                $table->dropForeign(['verified_by']);
            } catch (\Exception $e) {
                // Foreign key doesn't exist
            }

            try {
                $table->dropForeign(['validated_by']);
            } catch (\Exception $e) {
                // Foreign key doesn't exist
            }

            // Drop indexes
            try {
                $table->dropIndex('purchase_orders_verified_by_index');
            } catch (\Exception $e) {
                // Index doesn't exist
            }

            try {
                $table->dropIndex('purchase_orders_validated_by_index');
            } catch (\Exception $e) {
                // Index doesn't exist
            }

            // Drop columns
            $table->dropColumn([
                'verified_by',
                'verified_at',
                'verification_notes',
                'validated_by',
                'validated_at',
                'validation_notes'
            ]);

            // Revert status enum
            $table->enum('status', ['Draft', 'In Progress', 'Approved', 'Canceled', 'Rejected'])
                  ->default('Draft')
                  ->change();
        });
    }
};
