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
        Schema::table('ar_partners', function (Blueprint $table) {
            // Add index for nama_ap to improve search performance
            $table->index('nama_ap', 'ar_partners_nama_ap_search_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ar_partners', function (Blueprint $table) {
            $table->dropIndex('ar_partners_nama_ap_search_index');
        });
    }
};
