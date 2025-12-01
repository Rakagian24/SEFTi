<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PurchaseOrder;

class PurchaseOrderPolicy
{
    protected $allowedRoles = [
        'Admin',
        'Staff Toko',
        'Staff Digital Marketing',
        'Kepala Toko',
        'Staff Akunting & Finance',
        'Kabag',
        'Kadiv',
        'Direksi',
    ];

    protected function hasAccess(User $user)
    {
        return in_array($user->role->name ?? '', $this->allowedRoles);
    }

    public function viewAny(User $user)
    {
        return $this->hasAccess($user);
    }

    public function view(User $user, PurchaseOrder $po)
    {
        // $role = strtolower($user->role->name ?? '');
        // if (in_array($role, ['staff toko','staff digital marketing'], true)) {
        //     return (int)$po->created_by === (int)$user->id;
        // }
        return $this->hasAccess($user);
    }

    public function create(User $user)
    {
        return $this->hasAccess($user);
    }

    public function update(User $user, PurchaseOrder $po)
    {
        if (!$this->hasAccess($user)) {
            return false;
        }

        return $po->canBeEditedByUser($user);
    }

    public function delete(User $user, PurchaseOrder $po)
    {
        return $this->hasAccess($user) && in_array($po->status, ['Draft', 'Rejected']);
    }

    public function send(User $user, PurchaseOrder $po)
    {
        if (!$this->hasAccess($user)) {
            return false;
        }

        return $po->canBeSentByUser($user);
    }

    public function download(User $user, PurchaseOrder $po)
    {
        // if (in_array(strtolower($user->role->name ?? ''), ['staff toko','staff digital marketing'], true)) {
        //     if ((int)$po->created_by !== (int)$user->id) {
        //         return false;
        //     }
        // }
        return $this->hasAccess($user) && in_array($po->status, ['Draft', 'In Progress', 'Verified', 'Validated', 'Approved']);
    }

    public function log(User $user, PurchaseOrder $po)
    {
        // $role = strtolower($user->role->name ?? '');
        // if (in_array($role, ['staff toko','staff digital marketing'], true)) {
        //     return (int)$po->created_by === (int)$user->id;
        // }
        return $this->hasAccess($user);
    }
}
