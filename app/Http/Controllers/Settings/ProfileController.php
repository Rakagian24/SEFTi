<?php

namespace App\Http\Controllers\Settings;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Http\Requests\Settings\ProfileUpdateRequest;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user()->load(['department', 'role']);
        return Inertia::render('settings/Profile');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        // Debug isi request
        Log::info('Update profile request received', [
            'form_data' => $request->all(),
            'has_file_photo' => $request->hasFile('photo'),
            'photo_info' => $request->file('photo') ? [
                'original_name' => $request->file('photo')->getClientOriginalName(),
                'mime_type' => $request->file('photo')->getMimeType(),
                'size' => $request->file('photo')->getSize(),
            ] : null,
        ]);
        $data = $request->validated();

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Hapus foto lama jika ada
            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }
            $path = $request->file('photo')->store('profile-photos', 'public');
            $data['photo'] = $path;
        } else {
            unset($data['photo']); // Jangan update jika tidak ada file baru
        }

        // Handle password update jika diisi
        if (!empty($data['password'])) {
            $user->password = bcrypt($data['password']);
        }
        unset($data['password']);

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return to_route('profile.edit')->with('status', 'Profil berhasil diperbarui!');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
