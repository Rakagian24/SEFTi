<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized - User not authenticated.');
        }

        if (!$user->role) {
            abort(403, 'Unauthorized - User has no role assigned.');
        }

        // Check if user has admin role (full access)
        if ($user->role->name === 'Admin') {
            return $next($request);
        }

        // Check if user has required permissions
        if (!$this->hasPermission($user, $permissions)) {
            abort(403, 'Unauthorized - Insufficient permissions.');
        }

        return $next($request);
    }

    protected function hasPermission($user, $permissions)
    {
        $userPermissions = method_exists($user, 'getPermissions')
            ? ($user->getPermissions() ?? [])
            : ($user->role->permissions ?? []);

        // If role has wildcard permission, allow all
        if (in_array('*', $userPermissions)) {
            return true;
        }

        // Check if user has any of the required permissions
        foreach ($permissions as $permission) {
            if (in_array($permission, $userPermissions)) {
                return true;
            }
        }

        // Fallback: role-name based allowance for well-known document permissions
        // This helps roles like Direksi that are allowed by policy but may not have explicit string permissions set
        $roleName = $user->role->name ?? '';
        if (in_array('purchase_order', $permissions)) {
            if (in_array($roleName, [
                'Admin',
                'Direksi',
                'Kadiv',
                'Kabag',
                'Kepala Toko',
                'Staff Toko',
                'Staff Digital Marketing',
                'Staff Akunting & Finance',
            ])) {
                return true;
            }
        }

        if (in_array('memo_pembayaran', $permissions)) {
            if (in_array($roleName, [
                'Admin',
                'Direksi',
                'Kadiv',
                'Kabag',
                'Kepala Toko',
                'Staff Toko',
                'Staff Digital Marketing',
                'Staff Akunting & Finance',
            ])) {
                return true;
            }
        }

        if (in_array('anggaran', $permissions)) {
            if (in_array($roleName, [
                'Admin',
                'Direksi',
                'Kadiv',
                'Kabag',
                'Kepala Toko',
                'Staff Toko',
                'Staff Digital Marketing',
                'Staff Akunting & Finance',
            ])) {
                return true;
            }
        }

        if (in_array('termin', $permissions)) {
            if (in_array($roleName, [
                'Admin',
                'Staff Toko',
                'Staff Akunting & Finance',
            ])) {
                return true;
            }
        }

        return false;
    }
}

