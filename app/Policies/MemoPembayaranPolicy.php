<?php

namespace App\Policies;

use App\Models\MemoPembayaran;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MemoPembayaranPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasPermission('memo_pembayaran')) {
            return true;
        }
        $roleName = strtolower($user->role->name ?? '');
        // Staff Toko & Staff Digital Marketing can access menu but will be filtered by controller to own documents
        return in_array($roleName, ['admin', 'staff toko', 'staff digital marketing', 'kepala toko'], true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MemoPembayaran $memoPembayaran): bool
    {
        if ($user->hasPermission('memo_pembayaran')) {
            return true;
        }
        $roleName = strtolower($user->role->name ?? '');
        // Staff Toko & Staff Digital Marketing: only view own documents
        if (in_array($roleName, ['staff toko', 'staff digital marketing'], true)) {
            return (int)$memoPembayaran->created_by === (int)$user->id;
        }
        return in_array($roleName, ['admin', 'kepala toko'], true);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->hasPermission('memo_pembayaran')) {
            return true;
        }
        $roleName = strtolower($user->role->name ?? '');
        return in_array($roleName, ['admin', 'staff toko', 'kepala toko'], true);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MemoPembayaran $memoPembayaran): bool
    {
        if (!$user->hasPermission('memo_pembayaran')) {
            return false;
        }

        return $memoPembayaran->canBeEditedByUser($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MemoPembayaran $memoPembayaran): bool
    {
        if (!$user->hasPermission('memo_pembayaran')) {
            return false;
        }

        // Only allow delete if status is Draft
        return $memoPembayaran->status === 'Draft';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MemoPembayaran $memoPembayaran): bool
    {
        return $user->hasPermission('memo_pembayaran');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MemoPembayaran $memoPembayaran): bool
    {
        return $user->hasPermission('memo_pembayaran');
    }

    /**
     * Determine whether the user can send the memo pembayaran.
     */
    public function send(User $user, MemoPembayaran $memoPembayaran): bool
    {
        if (!$user->hasPermission('memo_pembayaran')) {
            return false;
        }

        return $memoPembayaran->canBeSentByUser($user);
    }

    /**
     * Determine whether the user can download the memo pembayaran.
     */
    public function download(User $user, MemoPembayaran $memoPembayaran): bool
    {
        if (!$user->hasPermission('memo_pembayaran')) {
            return false;
        }

        // Allow download if status is In Progress or Approved
        return in_array($memoPembayaran->status, ['In Progress', 'Approved']);
    }

    /**
     * Determine whether the user can view logs.
     */
    public function viewLogs(User $user, MemoPembayaran $memoPembayaran): bool
    {
        return $user->hasPermission('memo_pembayaran');
    }
}
