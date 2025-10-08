<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bpb_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bpb_id')->constrained('bpbs')->onDelete('cascade');
            $table->string('nama_barang');
            $table->decimal('qty', 20, 5);
            $table->string('satuan', 50);
            $table->decimal('harga', 20, 2);
            $table->timestamps();
        });

        Schema::table('bpbs', function (Blueprint $table) {
            $table->decimal('subtotal', 20, 2)->default(0)->after('keterangan');
            $table->decimal('diskon', 20, 2)->default(0)->after('subtotal');
            $table->decimal('dpp', 20, 2)->default(0)->after('diskon');
            $table->decimal('ppn', 20, 2)->default(0)->after('dpp');
            $table->decimal('pph', 20, 2)->default(0)->after('ppn');
            $table->decimal('grand_total', 20, 2)->default(0)->after('pph');
        });
    }

    public function down(): void
    {
        Schema::table('bpbs', function (Blueprint $table) {
            $table->dropColumn(['subtotal','diskon','dpp','ppn','pph','grand_total']);
        });
        Schema::dropIfExists('bpb_items');
    }
};


