<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PasscodeController extends Controller
{
    /**
     * Show the user's passcode settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        return Inertia::render('settings/Security', [
            'has_passcode' => !empty($user->passcode),
            'return' => $request->query('return'),
            'action_data' => $request->query('action_data'),
        ]);
    }

    /**
     * Update the user's passcode.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $hasPasscode = !empty($user->passcode);

        $rules = [
            'passcode' => ['required', 'digits:6', 'numeric', 'confirmed'],
        ];
        if ($hasPasscode) {
            $rules['old_passcode'] = ['required', 'digits:6', 'numeric'];
        }

        $validated = $request->validate($rules);

        if ($hasPasscode) {
            if (!Hash::check($validated['old_passcode'], $user->passcode)) {
                return back()->withErrors(['old_passcode' => 'Passcode lama salah.']);
            }
        }

        $user->passcode = Hash::make($validated['passcode']);
        $user->save();

        // Refresh user data from database
        $user->refresh();

        // ✅ Sync ke session supaya Auth::user() langsung pakai data terbaru
        \Illuminate\Support\Facades\Auth::setUser($user);

        // If a return URL is provided (from approval flow), redirect back there once
        $returnUrl = $request->query('return');
        $actionData = $request->query('action_data');
        if (!empty($returnUrl)) {
            $redirectUrl = $returnUrl;
            if (!empty($actionData)) {
                $redirectUrl .= (strpos($returnUrl, '?') !== false ? '&' : '?') . 'auto_passcode_dialog=1&action_data=' . urlencode($actionData);
            }
            return redirect($redirectUrl)->with('status', 'Passcode berhasil diperbarui!');
        }

        return back()->with('status', 'Passcode berhasil diperbarui!');
    }

        /**
     * Check if user has passcode set
     */
    public function checkStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        // Clear all caches and force reload user from database
        cache()->forget('users_active');
        cache()->forget('users_all');

        // Force reload user from database to ensure latest passcode is loaded
        $user = \App\Models\User::find($user->id);
        if (!$user) {
            return response()->json([
                'has_passcode' => false,
                'message' => 'User tidak ditemukan'
            ]);
        }

        // Debug logging
        Log::info('PasscodeController::checkStatus', [
            'user_id' => $user->id,
            'has_passcode' => !empty($user->passcode),
            'passcode_length' => $user->passcode ? strlen($user->passcode) : 0,
        ]);

        return response()->json([
            'has_passcode' => !empty($user->passcode),
            'message' => empty($user->passcode)
                ? 'Passcode belum diatur. Silakan atur passcode terlebih dahulu.'
                : 'Passcode sudah diatur.'
        ]);
    }

    /**
     * Verify the user's passcode for approval actions.
     */
    public function verify(Request $request): JsonResponse
    {
        $user = $request->user();

        // Clear all caches and force reload user from database
        cache()->forget('users_active');
        cache()->forget('users_all');

        // Force reload user from database to ensure latest passcode is loaded
        $user = \App\Models\User::find($user->id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        // Debug logging
        Log::info('PasscodeController::verify', [
            'user_id' => $user->id,
            'has_passcode' => !empty($user->passcode),
            'passcode_length' => $user->passcode ? strlen($user->passcode) : 0,
            'input_passcode' => $request->input('passcode'),
            'passcode_hash' => $user->passcode ? substr($user->passcode, 0, 20) . '...' : 'null',
            'session_id' => $request->session()->getId(),
            'hash_check_result' => Hash::check($request->input('passcode'), $user->passcode),
        ]);

        // Check if user has a passcode set
        if (empty($user->passcode)) {
            return response()->json([
                'success' => false,
                'message' => 'Passcode belum diatur. Silakan atur passcode terlebih dahulu.'
            ], 400);
        }

        $validated = $request->validate([
            'passcode' => ['required', 'digits:6', 'numeric']
        ]);

        // Verify the passcode
        $isValid = Hash::check($validated['passcode'], $user->passcode);

        Log::info('PasscodeController::verify - Hash check result', [
            'user_id' => $user->id,
            'input_passcode' => $validated['passcode'],
            'stored_hash' => $user->passcode,
            'is_valid' => $isValid,
        ]);

        if ($isValid) {
            return response()->json([
                'success' => true,
                'message' => 'Passcode valid'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Passcode tidak valid'
            ], 401);
        }
    }
}
