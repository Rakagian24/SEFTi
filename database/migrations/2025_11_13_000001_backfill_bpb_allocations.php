<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Copy existing BPB -> PV links into allocation rows with full grand_total
        $rows = DB::table('bpbs')
            ->whereNotNull('payment_voucher_id')
            ->get(['id as bpb_id','payment_voucher_id','grand_total']);
        foreach ($rows as $r) {
            // Skip if allocation already exists
            $exists = DB::table('payment_voucher_bpb_allocations')
                ->where('payment_voucher_id', $r->payment_voucher_id)
                ->where('bpb_id', $r->bpb_id)
                ->exists();
            if ($exists) continue;
            DB::table('payment_voucher_bpb_allocations')->insert([
                'payment_voucher_id' => $r->payment_voucher_id,
                'bpb_id' => $r->bpb_id,
                'amount' => (float) ($r->grand_total ?? 0),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // No-op: keep allocations; they reflect historical truth even if link column existed
    }
};
