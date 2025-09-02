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
            // Check if columns already exist before adding them
            if (!Schema::hasColumn('purchase_orders', 'status')) {
                $table->enum('status', ['Draft', 'In Progress', 'Approved', 'Canceled', 'Rejected'])->default('Draft')->after('id');
            }

            if (!Schema::hasColumn('purchase_orders', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('status');
            }

            if (!Schema::hasColumn('purchase_orders', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (!Schema::hasColumn('purchase_orders', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('approved_at');
            }

            if (!Schema::hasColumn('purchase_orders', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }

            if (!Schema::hasColumn('purchase_orders', 'approval_notes')) {
                $table->text('approval_notes')->nullable()->after('rejected_at');
            }

            if (!Schema::hasColumn('purchase_orders', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approval_notes');
            }
        });

        // Add indexes if they don't exist
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasIndex('purchase_orders', 'purchase_orders_status_index')) {
                $table->index('status');
            }

            if (!Schema::hasIndex('purchase_orders', 'purchase_orders_approved_by_index')) {
                $table->index('approved_by');
            }

            if (!Schema::hasIndex('purchase_orders', 'purchase_orders_rejected_by_index')) {
                $table->index('rejected_by');
            }
        });

        // Add foreign key constraints if they don't exist
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'approved_by_foreign')) {
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            }

            if (!Schema::hasColumn('purchase_orders', 'rejected_by_foreign')) {
                $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: We won't drop columns in rollback since they might be used by other parts of the system
        // Only drop foreign keys and indexes
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Drop foreign keys if they exist
            try {
                $table->dropForeign(['approved_by']);
            } catch (\Exception $e) {
                // Foreign key doesn't exist
            }

            try {
                $table->dropForeign(['rejected_by']);
            } catch (\Exception $e) {
                // Foreign key doesn't exist
            }
        });
    }
};
