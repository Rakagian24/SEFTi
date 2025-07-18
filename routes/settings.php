<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Settings\PasscodeController;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/security', [PasscodeController::class, 'edit'])->name('settings.passcode.edit');
    Route::put('settings/security', [PasscodeController::class, 'update'])->name('settings.passcode.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');

    // Message (Chat) routes
    Route::prefix('settings/message')->name('settings.message.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Settings\MessageController::class, 'index'])->name('index');
        Route::get('/{conversation}', [\App\Http\Controllers\Settings\MessageController::class, 'show'])->name('show');
        Route::post('/', [\App\Http\Controllers\Settings\MessageController::class, 'store'])->name('store');
        Route::post('/{conversation}/send', [\App\Http\Controllers\Settings\MessageController::class, 'sendMessage'])->name('send');
        Route::put('/message/{message}', [\App\Http\Controllers\Settings\MessageController::class, 'update'])->name('update');
        Route::delete('/message/{message}', [\App\Http\Controllers\Settings\MessageController::class, 'destroy'])->name('destroy');
        Route::delete('/{conversation}', [\App\Http\Controllers\Settings\MessageController::class, 'deleteConversation'])->name('deleteConversation');
        Route::get('/available-contacts', [\App\Http\Controllers\Settings\MessageController::class, 'availableContacts'])->name('availableContacts');
    });
});
