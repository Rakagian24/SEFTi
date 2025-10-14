<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Make supplier_id nullable and set FK ON DELETE SET NULL
        DB::statement('ALTER TABLE `payment_vouchers` DROP FOREIGN KEY `payment_vouchers_supplier_id_foreign`');
        DB::statement('ALTER TABLE `payment_vouchers` MODIFY `supplier_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `payment_vouchers` ADD CONSTRAINT `payment_vouchers_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON UPDATE CASCADE ON DELETE SET NULL');
    }

    public function down(): void
    {
        // Revert supplier_id to NOT NULL and restrict delete
        DB::statement('ALTER TABLE `payment_vouchers` DROP FOREIGN KEY `payment_vouchers_supplier_id_foreign`');
        DB::statement('ALTER TABLE `payment_vouchers` MODIFY `supplier_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `payment_vouchers` ADD CONSTRAINT `payment_vouchers_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT');
    }
};
