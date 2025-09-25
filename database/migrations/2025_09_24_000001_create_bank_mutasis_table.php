<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_mutasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_masuk_id')->constrained('bank_masuks')->onDelete('cascade');
            $table->string('no_mutasi');
            $table->date('tanggal');
            $table->foreignId('tujuan_department_id')->constrained('departments');
            $table->unsignedBigInteger('ar_partner_id')->nullable();
            $table->boolean('unrealized')->default(false);
            $table->decimal('nominal', 20, 2);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['no_mutasi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_mutasis');
    }
};


