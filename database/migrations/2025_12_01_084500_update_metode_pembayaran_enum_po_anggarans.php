<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE `po_anggarans` MODIFY COLUMN `metode_pembayaran` ENUM('Transfer','Cek/Giro','Kredit') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `po_anggarans` MODIFY COLUMN `metode_pembayaran` ENUM('Transfer','Cek/Giro') NULL");
    }
};
