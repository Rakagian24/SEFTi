<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\ModernAuthController;
use App\Http\Controllers\OtpVerificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [ModernAuthController::class, 'create'])
        ->name('register');

    Route::post('register', [ModernAuthController::class, 'register']);

    Route::get('login', [ModernAuthController::class, 'create'])
        ->name('login');

    Route::post('login', [ModernAuthController::class, 'login']);

    Route::get('auth', [AuthenticatedSessionController::class, 'create'])
        ->name('auth');

    // OTP Verification Routes
    Route::get('otp/verify', [OtpVerificationController::class, 'show'])
        ->name('otp.verify');
    Route::post('otp/verify', [OtpVerificationController::class, 'verify'])
        ->name('otp.verify.attempt'); // Ganti nama agar tidak duplikat
    Route::post('otp/resend', [OtpVerificationController::class, 'resend'])
        ->name('otp.resend');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
