<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Models\Department;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class ModernAuthController extends Controller
{
    /**
     * Show the modern auth page.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        // Redirect to dashboard if user is already authenticated
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('auth/ModernAuth', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
            'departments' => Department::where('status', 'active')->get(),
            'roles' => Role::where('status', 'active')->get(),
        ]);
    }

    /**
     * Handle login request.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Clear all caches and reload user data to ensure latest passcode is loaded
        $user = Auth::user();
        if ($user) {
            // Clear user-related caches
            cache()->forget('users_active');
            cache()->forget('users_all');

            // Force reload user from database
            $user = \App\Models\User::find($user->id);
            if ($user) {
                Auth::setUser($user);
                // Force session to save the updated user
                $request->session()->put('login_web_' . sha1(\App\Models\User::class), $user->id);
            }
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Handle register request.
     */
    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
            'department_ids' => 'required|array|min:1',
            'department_ids.*' => 'exists:departments,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
        ]);

        // Ensure unique, integer-cast department IDs and sync to avoid duplicates
        $departmentIds = collect($request->input('department_ids', []))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $user->departments()->sync($departmentIds);

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
