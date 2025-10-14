<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bpb_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bpb_id')->constrained('bpbs')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->string('ip_address', 45)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['bpb_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bpb_logs');
    }
};
