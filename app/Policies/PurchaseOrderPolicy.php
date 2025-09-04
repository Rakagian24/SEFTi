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
        return $this->hasAccess($user);
    }

    public function create(User $user)
    {
        return $this->hasAccess($user);
    }

    public function update(User $user, PurchaseOrder $po)
    {
        return $this->hasAccess($user) && $po->status === 'Draft';
    }

    public function delete(User $user, PurchaseOrder $po)
    {
        return $this->hasAccess($user) && $po->status === 'Draft';
    }

    public function send(User $user, PurchaseOrder $po)
    {
        return $this->hasAccess($user) && $po->status === 'Draft';
    }

    public function download(User $user, PurchaseOrder $po)
    {
        return $this->hasAccess($user) && in_array($po->status, ['Draft', 'In Progress', 'Verified', 'Validated', 'Approved']);
    }

    public function log(User $user, PurchaseOrder $po)
    {
        return $this->hasAccess($user);
    }
}
