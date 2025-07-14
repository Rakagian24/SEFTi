<?php

namespace App\Http\Controllers;

use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class OtpVerificationController extends Controller
{
        /**
     * Show OTP verification page
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $phone = $request->query('phone');

        if (!$phone) {
            return redirect()->route('login');
        }

        // Generate OTP if not exists or expired
        $otpVerification = OtpVerification::where('phone', $phone)
            ->where('expires_at', '>', now())
            ->whereNull('verified_at')
            ->first();

        if (!$otpVerification) {
            $this->generateOtp($phone);
        }

        return Inertia::render('auth/OtpVerification', [
            'phone' => $phone,
        ]);
    }

    /**
     * Verify OTP
     */
    public function verify(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string|size:4',
        ]);

        $phone = $request->phone;
        $otp = $request->otp;

        $otpVerification = OtpVerification::where('phone', $phone)
            ->where('otp', $otp)
            ->where('expires_at', '>', now())
            ->whereNull('verified_at')
            ->first();

        if (!$otpVerification) {
            // Increment attempts
            $existingOtp = OtpVerification::where('phone', $phone)
                ->whereNull('verified_at')
                ->first();

            if ($existingOtp) {
                $existingOtp->incrementAttempts();
            }

            return back()->withErrors([
                'otp' => 'Kode OTP tidak valid atau sudah kedaluwarsa.'
            ]);
        }

        // Check if too many attempts
        if ($otpVerification->attempts >= 3) {
            return back()->withErrors([
                'otp' => 'Terlalu banyak percobaan. Silakan tunggu 10 menit sebelum mencoba lagi.'
            ]);
        }

        // Mark as verified
        $otpVerification->update([
            'verified_at' => now()
        ]);

        // Find and login user
        $user = User::where('phone', $phone)->first();
        if ($user) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        return redirect()->intended(route('dashboard', absolute: false))
            ->with('status', 'Verifikasi berhasil! Selamat datang.');
    }

    /**
     * Resend OTP
     */
    public function resend(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $phone = $request->phone;

        // Check if user exists
        $user = User::where('phone', $phone)->first();
        if (!$user) {
            return back()->withErrors([
                'phone' => 'Nomor telepon tidak ditemukan.'
            ]);
        }

        // Check resend limit (3 times in 15 minutes)
        $recentOtps = OtpVerification::where('phone', $phone)
            ->where('created_at', '>', now()->subMinutes(15))
            ->count();

        if ($recentOtps >= 3) {
            return back()->withErrors([
                'otp' => 'Terlalu banyak permintaan. Silakan tunggu 15 menit.'
            ]);
        }

        $this->generateOtp($phone);

        return back()->with('status', 'Kode OTP baru telah dikirim.');
    }

    /**
     * Generate new OTP
     */
    private function generateOtp(string $phone): void
    {
        // Delete existing unverified OTPs
        OtpVerification::where('phone', $phone)
            ->whereNull('verified_at')
            ->delete();

        // Generate new OTP
        $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        OtpVerification::create([
            'phone' => $phone,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
        ]);

        // TODO: Integrate with WhatsApp API to send OTP
        // For now, we'll just log it
        Log::info("OTP for {$phone}: {$otp}");
    }
}
