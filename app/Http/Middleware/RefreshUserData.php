<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class RefreshUserData
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only refresh user data for authenticated users
        if (Auth::check()) {
            $user = Auth::user();

            // Clear user-related caches
            cache()->forget('users_active');
            cache()->forget('users_all');

            // Force reload user from database to ensure latest data
            $freshUser = User::find($user->id);
            if ($freshUser) {
                Auth::setUser($freshUser);
            }
        }

        return $next($request);
    }
}
