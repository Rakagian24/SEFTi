<?php

// database/migrations/2025_07_15_XXXXXX_create_ar_partner_logs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ar_partner_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ar_partner_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action');
            $table->string('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('ar_partner_logs');
    }
};
