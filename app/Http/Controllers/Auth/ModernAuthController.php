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
            'otpPhone' => $request->session()->get('otp_phone'),
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

        $normalizedPhone = $this->normalizePhone($request->phone);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $normalizedPhone,
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
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone ?? '');
        if (!$digits) {
            return '';
        }
        // If starts with 0 and intended for Indonesia, replace leading 0 with 62
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }
        // Ensure it starts with country code
        if (!str_starts_with($digits, '62') && !str_starts_with($digits, '1') && !str_starts_with($digits, '44')) {
            // Default to Indonesia if not clearly international
            $digits = '62' . ltrim($digits, '0');
        }
        return '+' . $digits;
    }
}
