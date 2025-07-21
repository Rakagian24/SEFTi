<?php

namespace App\Providers;

use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Inertia::share([
            'auth' => [
                'user' => function () {
                    $user = Auth::user();
                    if (!$user) return null;
                    Log::info('PHOTO VALUE', ['photo' => $user->photo]);
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'photo' => $user->photo
                            ? (str_starts_with($user->photo, 'http')
                                ? $user->photo
                                : asset('storage/' . ltrim($user->photo, '/')))
                            : null,
                        // tambahkan field lain jika perlu
                    ];
                },
            ],
        ]);
    }
}
