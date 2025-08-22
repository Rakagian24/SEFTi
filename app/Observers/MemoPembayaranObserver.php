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
            'description' => 'Memo Pembayaran dibuat',
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

        // Filter out timestamps and other non-relevant changes
        $relevantChanges = array_diff_key($changes, array_flip(['updated_at', 'updated_by']));

        if (!empty($relevantChanges)) {
            MemoPembayaranLog::create([
                'memo_pembayaran_id' => $memoPembayaran->id,
                'action' => 'updated',
                'description' => 'Memo Pembayaran diperbarui',
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
            'description' => 'Memo Pembayaran dihapus',
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
            'description' => 'Memo Pembayaran dipulihkan',
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
            'description' => 'Memo Pembayaran dihapus permanen',
            'user_id' => Auth::id() ?? $memoPembayaran->canceled_by,
            'old_values' => $memoPembayaran->toArray(),
        ]);
    }
}
