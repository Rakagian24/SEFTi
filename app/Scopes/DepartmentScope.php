<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use App\Models\Department;

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

            // Jika user punya department 'All' (baik di pivot departments maupun di kolom utama), jangan filter apapun
            $hasAllInPivot = $user->departments->contains(function ($dept) {
                return $dept->name === 'All';
            });
            $mainDepartmentIsAll = optional($user->department)->name === 'All';
            if ($hasAllInPivot || $mainDepartmentIsAll) {
                return;
            }

            // Kumpulkan daftar department dari relasi many-to-many
            $departmentIds = $user->departments->pluck('id')->toArray();

            // Jika pivot departments kosong, fallback ke department utama (jika ada)
            if (empty($departmentIds) && $user->department) {
                $departmentIds[] = $user->department->id;
            }

            static $allDepartmentId = null;
            if ($allDepartmentId === null) {
                $allDepartmentId = Department::where('name', 'All')->value('id');
            }
            if ($allDepartmentId) {
                $departmentIds[] = $allDepartmentId;
            }

            if (empty($departmentIds)) {
                $builder->whereRaw('0=1');
                return;
            }

            // Cek apakah ada filter department aktif dari request
            $activeDepartment = request()->get('activeDepartment');
            if ($activeDepartment && in_array((int) $activeDepartment, $departmentIds, true)) {
                // Filter hanya untuk department aktif
                $builder->where('department_id', $activeDepartment);
            } else {
                // Filter untuk semua department user
                $builder->whereIn('department_id', $departmentIds);
            }
        }
    }
}
