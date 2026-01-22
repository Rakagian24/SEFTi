<?php

namespace App\Http\Controllers;

use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;
use Inertia\Inertia;
use Inertia\Response;

class OtpVerificationController extends Controller
{
        /**
     * Show OTP verification page
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $phone = $this->normalizePhone($request->query('phone'));

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

        $phone = $this->normalizePhone($request->phone);
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
            // Mark phone as verified if not already
            if (is_null($user->phone_verified_at)) {
                $user->phone_verified_at = now();
                $user->save();
            }
            Auth::login($user);
            $request->session()->regenerate();

            // Clear all caches and reload user data to ensure latest passcode is loaded
            cache()->forget('users_active');
            cache()->forget('users_all');

            $user = User::find($user->id);
            if ($user) {
                Auth::setUser($user);
                // Force session to save the updated user
                $request->session()->put('login_web_' . sha1(User::class), $user->id);
            }
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

        $phone = $this->normalizePhone($request->phone);

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

        try {
            $sid = (string) config('services.twilio.sid');
            $token = (string) config('services.twilio.token');
            $from = (string) config('services.twilio.whatsapp_from');

            if ($sid && $token && $from) {
                $twilio = new TwilioClient($sid, $token);

                $messageBody = "Kode verifikasi SEFTi Anda: {$otp}. Berlaku 5 menit. Jangan bagikan kode ini kepada siapa pun.";

                // If the configured from number is a WhatsApp sender, prefix both with 'whatsapp:'
                if (!str_starts_with($from, 'whatsapp:')) {
                    $from = 'whatsapp:' . $from;
                }

                $to = $phone;
                if (!str_starts_with($to, 'whatsapp:')) {
                    $to = 'whatsapp:' . $to;
                }

                $twilio->messages->create($to, [
                    'from' => $from,
                    'body' => $messageBody,
                ]);
            } else {
                Log::warning('Twilio configuration is incomplete. Skipping OTP send.', [
                    'sid' => (bool) $sid,
                    'token' => (bool) $token,
                    'from' => (bool) $from,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send OTP via Twilio: ' . $e->getMessage(), [
                'phone' => $phone,
            ]);
        }

        Log::info("OTP for {$phone}: {$otp}");
    }

    private function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone ?? '');
        if (!$digits) {
            return '';
        }
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }
        if (!str_starts_with($digits, '62') && !str_starts_with($digits, '1') && !str_starts_with($digits, '44')) {
            $digits = '62' . ltrim($digits, '0');
        }
        return '+' . $digits;
    }
}
