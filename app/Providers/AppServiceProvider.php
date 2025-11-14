<?php

namespace App\Providers;

use Carbon\Carbon;
use App\Models\Pph;
use App\Models\Bank;
use App\Models\Role;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Termin;
use App\Models\Perihal;
use App\Models\Supplier;
use App\Models\ArPartner;
use App\Models\Department;
use App\Models\BankAccount;
use App\Models\Pengeluaran;
use App\Models\BisnisPartner;
use App\Models\MemoPembayaran;
use App\Observers\PphObserver;
use App\Observers\BankObserver;
use App\Observers\RoleObserver;
use App\Observers\UserObserver;
use App\Observers\TerminObserver;
use App\Observers\PerihalObserver;
use App\Observers\SupplierObserver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use App\Observers\ArPartnerObserver;
use Illuminate\Support\Facades\Auth;
use App\Observers\DepartmentObserver;
use App\Observers\BankAccountObserver;
use App\Observers\PengeluaranObserver;
use Illuminate\Support\ServiceProvider;
use App\Observers\BisnisPartnerObserver;
use App\Observers\MemoPembayaranObserver;

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
        Carbon::setLocale('id');
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
                    if (!$user) return null;

                    // Reload user data from database to ensure latest data is loaded
                    $user = User::find($user->id);
                    if (!$user) return null;

                    // Pastikan relasi ter-load agar data department/role muncul di frontend
                    if ($user instanceof User) {
                        $user->load(['department', 'role', 'departments']);
                    }

                    // Debug: Log user data
                    // Log::info('AppServiceProvider - User data being shared', [
                    //     'user_id' => $user->id,
                    //     'phone' => $user->phone,
                    //     'department_id' => $user->department_id,
                    //     'role_id' => $user->role_id,
                    //     'has_passcode' => !empty($user->passcode),
                    //     'passcode_length' => $user->passcode ? strlen($user->passcode) : 0,
                    //     'passcode_hash' => $user->passcode ? substr($user->passcode, 0, 20) . '...' : 'null',
                    //     'timestamp' => now()->toDateTimeString(),
                    // ]);

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
                        'extra_permissions' => $user->extra_permissions ?? [],
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
                        'has_passcode' => !empty($user->passcode),
                    ];
                },
            ],
        ]);
    }
}
