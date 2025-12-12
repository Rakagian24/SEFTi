<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            // Kolom sisa nominal PV
            $table->decimal('remaining_nominal', 20, 5)->nullable()->after('nominal');
        });

        // Inisialisasi remaining_nominal berdasarkan nominal - total BK aktif
        if (Schema::hasTable('payment_vouchers') && Schema::hasTable('bank_keluars')) {
            DB::table('payment_vouchers')
                ->orderBy('id')
                ->chunkById(500, function ($vouchers) {
                    foreach ($vouchers as $pv) {
                        $used = DB::table('bank_keluars')
                            ->whereNull('deleted_at')
                            ->where('status', 'aktif')
                            ->where('payment_voucher_id', $pv->id)
                            ->sum('nominal');

                        $remaining = ($pv->nominal ?? 0) - $used;
                        if ($remaining < 0) {
                            $remaining = 0;
                        }

                        DB::table('payment_vouchers')
                            ->where('id', $pv->id)
                            ->update(['remaining_nominal' => $remaining]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('payment_vouchers', 'remaining_nominal')) {
                $table->dropColumn('remaining_nominal');
            }
        });
    }
};
