<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the existing table
        Schema::dropIfExists('auto_matches');

        // Create new table with the desired structure
        Schema::create('auto_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_masuk_id')->constrained('bank_masuks')->onDelete('cascade');
            $table->string('sj_no'); // Surat Jalan number
            $table->date('sj_tanggal'); // Surat Jalan date
            $table->double('sj_nilai', 20, 2); // Surat Jalan value
            $table->string('bm_no'); // Bank Masuk number
            $table->date('bm_tanggal'); // Bank Masuk date
            $table->double('bm_nilai', 20, 2); // Bank Masuk value
            $table->date('match_date');
            $table->enum('status', ['pending', 'confirmed', 'rejected'])->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_matches');
    }
};
