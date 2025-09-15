<?php

namespace App\Observers;

use App\Models\MemoPembayaran;
use App\Models\MemoPembayaranLog;
use Illuminate\Support\Facades\Auth;

class MemoPembayaranObserver
{
    /**
     * Handle the MemoPembayaran "created" event.
     */
    public function created(MemoPembayaran $memoPembayaran): void
    {
        MemoPembayaranLog::create([
            'memo_pembayaran_id' => $memoPembayaran->id,
            'action' => 'created',
            'description' => 'Membuat data Memo Pembayaran',
            'user_id' => Auth::id() ?? $memoPembayaran->created_by,
            'new_values' => $memoPembayaran->toArray(),
        ]);
    }

    /**
     * Handle the MemoPembayaran "updated" event.
     */
    public function updated(MemoPembayaran $memoPembayaran): void
    {
        $changes = $memoPembayaran->getChanges();
        $original = $memoPembayaran->getOriginal();

        // Skip logging for approval state transitions to avoid double activity entries
        $approvalKeys = [
            'status', 'verified_by', 'verified_at', 'validated_by', 'validated_at',
            'approved_by', 'approved_at', 'rejected_by', 'rejected_at', 'rejection_reason',
            'approval_notes'
        ];
        $onlyApprovalChanges = !empty(array_intersect(array_keys($changes), $approvalKeys));
        if ($onlyApprovalChanges && count(array_diff(array_keys($changes), array_merge($approvalKeys, ['updated_at', 'updated_by']))) === 0) {
            return; // do not log pure approval transitions here (handled by controller logs)
        }

        // Filter out timestamps and other non-relevant changes
        $relevantChanges = array_diff_key($changes, array_flip(['updated_at', 'updated_by']));

        if (!empty($relevantChanges)) {
            MemoPembayaranLog::create([
                'memo_pembayaran_id' => $memoPembayaran->id,
                'action' => 'updated',
                'description' => 'Mengubah data Memo Pembayaran',
                'user_id' => Auth::id() ?? $memoPembayaran->updated_by,
                'old_values' => array_intersect_key($original, $relevantChanges),
                'new_values' => $relevantChanges,
            ]);
        }
    }

    /**
     * Handle the MemoPembayaran "deleted" event.
     */
    public function deleted(MemoPembayaran $memoPembayaran): void
    {
        MemoPembayaranLog::create([
            'memo_pembayaran_id' => $memoPembayaran->id,
            'action' => 'deleted',
            'description' => 'Menghapus data Memo Pembayaran',
            'user_id' => Auth::id() ?? $memoPembayaran->canceled_by,
            'old_values' => $memoPembayaran->toArray(),
        ]);
    }

    /**
     * Handle the MemoPembayaran "restored" event.
     */
    public function restored(MemoPembayaran $memoPembayaran): void
    {
        MemoPembayaranLog::create([
            'memo_pembayaran_id' => $memoPembayaran->id,
            'action' => 'restored',
            'description' => 'Mengembalikan data Memo Pembayaran',
            'user_id' => Auth::id() ?? $memoPembayaran->updated_by,
            'new_values' => $memoPembayaran->toArray(),
        ]);
    }

    /**
     * Handle the MemoPembayaran "force deleted" event.
     */
    public function forceDeleted(MemoPembayaran $memoPembayaran): void
    {
        MemoPembayaranLog::create([
            'memo_pembayaran_id' => $memoPembayaran->id,
            'action' => 'force_deleted',
            'description' => 'Menghapus data Memo Pembayaran secara permanen',
            'user_id' => Auth::id() ?? $memoPembayaran->canceled_by,
            'old_values' => $memoPembayaran->toArray(),
        ]);
    }
}
