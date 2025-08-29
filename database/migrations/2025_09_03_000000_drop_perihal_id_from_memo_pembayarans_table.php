<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memo_pembayarans', function (Blueprint $table) {
            if (Schema::hasColumn('memo_pembayarans', 'perihal_id')) {
                // Drop foreign key if exists (Laravel default naming or custom)
                try {
                    $table->dropForeign(['perihal_id']);
                } catch (\Throwable $e) {
                    // ignore if FK name differs
                }
                $table->dropIndex(['perihal_id']);
                $table->dropColumn('perihal_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('memo_pembayarans', function (Blueprint $table) {
            if (!Schema::hasColumn('memo_pembayarans', 'perihal_id')) {
                $table->foreignId('perihal_id')->nullable()->constrained()->onDelete('set null');
                $table->index(['perihal_id']);
            }
        });
    }
};