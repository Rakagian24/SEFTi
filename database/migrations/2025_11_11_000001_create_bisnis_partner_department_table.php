<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bisnis_partner_department')) {
            Schema::create('bisnis_partner_department', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bisnis_partner_id');
                $table->unsignedBigInteger('department_id');
                $table->timestamps();

                $table->foreign('bisnis_partner_id')->references('id')->on('bisnis_partners')->onDelete('cascade');
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
                $table->unique(['bisnis_partner_id', 'department_id'], 'bp_dept_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bisnis_partner_department');
    }
};
