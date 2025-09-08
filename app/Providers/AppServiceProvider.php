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
use App\Models\MemoPembayaran;
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
use App\Observers\MemoPembayaranObserver;
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
        MemoPembayaran::observe(MemoPembayaranObserver::class);

        Inertia::share([
            'auth' => [
                'user' => function () {
                    $user = Auth::user();
                    // Pastikan relasi ter-load agar data department/role muncul di frontend
                    if ($user instanceof User) {
                        $user->load(['department', 'role', 'departments']);
                    }
                    if (!$user) return null;

                    // Debug: Log user data
                    Log::info('AppServiceProvider - User data being shared', [
                        'user_id' => $user->id,
                        'phone' => $user->phone,
                        'department_id' => $user->department_id,
                        'role_id' => $user->role_id,
                    ]);

                    $primaryDepartment = $user->department ?: $user->departments->first();

                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'photo' => $user->photo
                            ? (str_starts_with($user->photo, 'http')
                                ? $user->photo
                                : '/storage/' . ltrim($user->photo, '/'))
                            : null,
                        'role' => $user->role ? [
                            'id' => $user->role->id,
                            'name' => $user->role->name,
                            'permissions' => $user->role->permissions ?? [],
                        ] : null,
                        'department' => $primaryDepartment ? [
                            'id' => $primaryDepartment->id,
                            'name' => $primaryDepartment->name,
                        ] : null,
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
