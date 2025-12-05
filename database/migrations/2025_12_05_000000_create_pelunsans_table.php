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
        Schema::create('pelunasan_aps', function (Blueprint $table) {
            $table->id();
            $table->string('no_pl')->nullable()->unique();
            $table->date('tanggal')->nullable();
            $table->enum('tipe_pelunasan', ['Bank Keluar', 'Mutasi', 'Retur'])->default('Bank Keluar');
            $table->unsignedBigInteger('bank_keluar_id')->nullable();
            $table->unsignedBigInteger('bank_mutasi_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->decimal('nilai_dokumen_referensi', 20, 5)->default(0);
            $table->text('keterangan')->nullable();
            $table->enum('status', ['Draft', 'In Progress', 'Approved', 'Rejected', 'Canceled'])->default('Draft');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('creator_id')->nullable();

            // Approval fields
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->text('validation_notes')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('canceled_by')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Foreign keys
            $table->foreign('bank_keluar_id')->references('id')->on('bank_keluars')->onDelete('set null');
            $table->foreign('bank_mutasi_id')->references('id')->on('bank_mutasis')->onDelete('set null');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('canceled_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelunasan_aps');
    }
};
