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
        Schema::table('memo_pembayarans', function (Blueprint $table) {
            // Add verification fields
            if (!Schema::hasColumn('memo_pembayarans', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->after('rejected_at');
            }
            if (!Schema::hasColumn('memo_pembayarans', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by');
            }

            // Add validation fields
            if (!Schema::hasColumn('memo_pembayarans', 'validated_by')) {
                $table->unsignedBigInteger('validated_by')->nullable()->after('verified_at');
            }
            if (!Schema::hasColumn('memo_pembayarans', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('validated_by');
            }

            // Add approval notes and rejection reason
            if (!Schema::hasColumn('memo_pembayarans', 'approval_notes')) {
                $table->text('approval_notes')->nullable()->after('validated_at');
            }
            if (!Schema::hasColumn('memo_pembayarans', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approval_notes');
            }

            // Update status enum to include Verified and Validated
            $table->enum('status', ['Draft', 'In Progress', 'Verified', 'Validated', 'Approved', 'Canceled', 'Rejected'])->default('Draft')->change();
        });

        // Add foreign key constraints
        Schema::table('memo_pembayarans', function (Blueprint $table) {
            if (Schema::hasColumn('memo_pembayarans', 'verified_by')) {
                $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
            }
            if (Schema::hasColumn('memo_pembayarans', 'validated_by')) {
                $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memo_pembayarans', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['verified_by']);
            $table->dropForeign(['validated_by']);

            // Drop columns
            $table->dropColumn([
                'verified_by',
                'verified_at',
                'validated_by',
                'validated_at',
                'approval_notes',
                'rejection_reason'
            ]);

            // Revert status enum
            $table->enum('status', ['Draft', 'In Progress', 'Approved', 'Canceled', 'Rejected'])->default('Draft')->change();
        });
    }
};
