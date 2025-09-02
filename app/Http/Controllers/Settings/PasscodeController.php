<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
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

        $user->passcode = $validated['passcode'];
        $user->save();

        return back()->with('status', 'Passcode berhasil diperbarui!');
    }

        /**
     * Check if user has passcode set
     */
    public function checkStatus(Request $request): JsonResponse
    {
        $user = $request->user();

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
        if (Hash::check($validated['passcode'], $user->passcode)) {
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
