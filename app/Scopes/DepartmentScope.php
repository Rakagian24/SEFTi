<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class DepartmentScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (Auth::check()) {
            $user = Auth::user();
            // Admin bypasses all department filtering
            $roleName = optional($user->role)->name;
            if ($roleName === 'Admin') {
                return;
            }
            // Jika user punya department 'All', jangan filter apapun
            if ($user->departments->contains(function ($dept) { return $dept->name === 'All'; })) {
                return;
            }

            $departmentIds = $user->departments->pluck('id')->toArray();
            if (empty($departmentIds)) {
                $builder->whereRaw('0=1');
                return;
            }

            // Cek apakah ada filter department aktif dari request
            $activeDepartment = request()->get('activeDepartment');
            if ($activeDepartment && in_array($activeDepartment, $departmentIds)) {
                // Filter hanya untuk department aktif
                $builder->where($model->getTable() . '.department_id', $activeDepartment);
            } else {
                // Filter untuk semua department user
                $builder->whereIn($model->getTable() . '.department_id', $departmentIds);
            }
        }
    }
}
