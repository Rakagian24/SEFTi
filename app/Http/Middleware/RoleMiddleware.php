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
        $userPermissions = $user->role->permissions ?? [];

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

        return false;
    }
}

