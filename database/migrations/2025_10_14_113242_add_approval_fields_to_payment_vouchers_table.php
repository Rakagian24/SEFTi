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
            // Approval workflow fields
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->text('verification_notes')->nullable()->after('verified_at');
            
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('verification_notes');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_notes')->nullable()->after('approved_at');
            
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete()->after('approval_notes');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
            
            $table->foreignId('canceled_by')->nullable()->constrained('users')->nullOnDelete()->after('rejection_reason');
            $table->timestamp('canceled_at')->nullable()->after('canceled_by');
            $table->text('cancellation_reason')->nullable()->after('canceled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['verified_at', 'verification_notes']);
            
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['approved_at', 'approval_notes']);
            
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn(['rejected_at', 'rejection_reason']);
            
            $table->dropConstrainedForeignId('canceled_by');
            $table->dropColumn(['canceled_at', 'cancellation_reason']);
        });
    }
};
