<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_keluars') && Schema::hasColumn('bank_keluars', 'payment_voucher_id')) {
            Schema::table('bank_keluars', function (Blueprint $table) {
                $table->dropForeign(['payment_voucher_id']);
                $table->dropColumn('payment_voucher_id');
            });
        }

        if (Schema::hasTable('bank_keluar_documents')) {
            Schema::dropIfExists('bank_keluar_documents');
        }
    }

    public function down(): void
    {
        Schema::table('bank_keluars', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_keluars', 'payment_voucher_id')) {
                $table->foreignId('payment_voucher_id')
                    ->nullable()
                    ->constrained('payment_vouchers')
                    ->nullOnDelete();
            }
        });

        if (!Schema::hasTable('bank_keluar_documents')) {
            Schema::create('bank_keluar_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bank_keluar_id')->constrained('bank_keluars')->cascadeOnDelete();
                $table->string('filename');
                $table->string('original_filename');
                $table->string('mime_type');
                $table->integer('size');
                $table->string('path');
                $table->boolean('is_active')->default(true);
                $table->foreignId('uploaded_by')->constrained('users');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }
};
