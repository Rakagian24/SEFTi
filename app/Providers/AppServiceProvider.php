<?php

namespace App\Providers;

use Inertia\Inertia;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Department;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\BisnisPartner;
use App\Models\ArPartner;
use App\Models\User;
use App\Models\Pph;
use App\Models\Pengeluaran;
use App\Models\Perihal;
use App\Models\Termin;
use App\Observers\BankObserver;
use App\Observers\BankAccountObserver;
use App\Observers\DepartmentObserver;
use App\Observers\RoleObserver;
use App\Observers\SupplierObserver;
use App\Observers\BisnisPartnerObserver;
use App\Observers\ArPartnerObserver;
use App\Observers\UserObserver;
use App\Observers\PphObserver;
use App\Observers\PengeluaranObserver;
use App\Observers\PerihalObserver;
use App\Observers\TerminObserver;
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
        // Register observers for all master data
        Bank::observe(BankObserver::class);
        BankAccount::observe(BankAccountObserver::class);
        Department::observe(DepartmentObserver::class);
        Role::observe(RoleObserver::class);
        Supplier::observe(SupplierObserver::class);
        BisnisPartner::observe(BisnisPartnerObserver::class);
        ArPartner::observe(ArPartnerObserver::class);
        User::observe(UserObserver::class);
        Pph::observe(PphObserver::class);
        Pengeluaran::observe(PengeluaranObserver::class);
        Perihal::observe(PerihalObserver::class);
        Termin::observe(TerminObserver::class);

        Inertia::share([
            'auth' => [
                'user' => function () {
                    $user = Auth::user();
                    if (!$user) return null;
                    // Pastikan relasi departments tersedia
                    $user->loadMissing(['departments:id,name']);
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'photo' => $user->photo
                            ? (str_starts_with($user->photo, 'http')
                                ? $user->photo
                                : asset('storage/' . ltrim($user->photo, '/')))
                            : null,
                        'departments' => $user->departments->map(function ($d) {
                            return [
                                'id' => $d->id,
                                'name' => $d->name,
                            ];
                        }),
                    ];
                },
            ],
        ]);
    }
}
