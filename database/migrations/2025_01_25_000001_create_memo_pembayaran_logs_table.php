<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memo_pembayaran_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memo_pembayaran_id')->constrained()->onDelete('cascade');
            $table->string('action');
            $table->text('description');
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamps();

            // Add indexes for better performance
            $table->index(['memo_pembayaran_id']);
            $table->index(['user_id']);
            $table->index(['action']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memo_pembayaran_logs');
    }
};
