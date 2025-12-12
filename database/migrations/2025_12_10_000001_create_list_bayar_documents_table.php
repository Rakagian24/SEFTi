<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('list_bayar_documents', function (Blueprint $table) {
            $table->id();
            $table->string('no_list_bayar')->unique();
            $table->date('tanggal');
            $table->unsignedBigInteger('department_id')->default(0); // 0 = All departments
            $table->unsignedInteger('jumlah_pv')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('list_bayar_documents');
    }
};
