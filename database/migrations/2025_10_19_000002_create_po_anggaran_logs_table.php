<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('po_anggaran_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_anggaran_id')->constrained('po_anggarans')->cascadeOnDelete();
            $table->string('action');
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_anggaran_logs');
    }
};
