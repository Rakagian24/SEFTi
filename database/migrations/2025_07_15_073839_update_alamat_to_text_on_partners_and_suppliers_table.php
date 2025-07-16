<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ar_partners', function (Blueprint $table) {
            $table->text('alamat')->change();
        });
        Schema::table('bisnis_partners', function (Blueprint $table) {
            $table->text('alamat')->change();
        });
        Schema::table('suppliers', function (Blueprint $table) {
            $table->text('alamat')->change();
        });
    }

    public function down(): void
    {
        Schema::table('ar_partners', function (Blueprint $table) {
            $table->string('alamat')->change();
        });
        Schema::table('bisnis_partners', function (Blueprint $table) {
            $table->string('alamat')->change();
        });
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('alamat')->change();
        });
    }
};
