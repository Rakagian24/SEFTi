<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
}
