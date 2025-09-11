<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class RoleAuthorizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register middleware alias
        $this->app['router']->aliasMiddleware('role', \App\Http\Middleware\RoleMiddleware::class);

        // Register Blade directives
        $this->registerBladeDirectives();

        // Register Gates
        $this->registerGates();
    }

    protected function registerBladeDirectives()
    {
        // @role directive
        Blade::directive('role', function ($expression) {
            return "<?php if(auth()->check() && auth()->user()->hasRole({$expression})): ?>";
        });

        Blade::directive('endrole', function () {
            return "<?php endif; ?>";
        });

        // @permission directive
        Blade::directive('permission', function ($expression) {
            return "<?php if(auth()->check() && auth()->user()->hasPermission({$expression})): ?>";
        });

        Blade::directive('endpermission', function () {
            return "<?php endif; ?>";
        });

        // @anypermission directive
        Blade::directive('anypermission', function ($expression) {
            return "<?php if(auth()->check() && auth()->user()->hasAnyPermission({$expression})): ?>";
        });

        Blade::directive('endanypermission', function () {
            return "<?php endif; ?>";
        });

        // @allpermissions directive
        Blade::directive('allpermissions', function ($expression) {
            return "<?php if(auth()->check() && auth()->user()->hasAllPermissions({$expression})): ?>";
        });

        Blade::directive('endallpermissions', function () {
            return "<?php endif; ?>";
        });
    }

    protected function registerGates()
    {
        // Define gates for each permission
        $permissions = [
            'purchase_order',
            'memo_pembayaran',
            'bpb',
            'anggaran',
            'approval',
            'bank',
            'supplier',
            'bisnis_partner',
            'payment_voucher',
            'daftar_list_bayar',
            'bank_masuk',
            'bank_keluar',
            'po_outstanding',
            'termin',
        ];

        foreach ($permissions as $permission) {
            Gate::define($permission, function ($user) use ($permission) {
                return $user->hasPermission($permission);
            });
        }

        // Define admin gate
        Gate::define('admin', function ($user) {
            return $user->hasRole('Admin');
        });

        // Define menu access gates
        Gate::define('access-purchase-order', function ($user) {
            return $user->hasPermission('purchase_order');
        });

        Gate::define('access-bank', function ($user) {
            return $user->hasPermission('bank');
        });

        Gate::define('access-supplier', function ($user) {
            return $user->hasPermission('supplier');
        });

        Gate::define('access-bisnis-partner', function ($user) {
            return $user->hasPermission('bisnis_partner');
        });

        Gate::define('access-bank-masuk', function ($user) {
            return $user->hasPermission('bank_masuk');
        });

        Gate::define('access-bank-matching', function ($user) {
            return $user->hasPermission('bank_masuk');
        });

        Gate::define('access-approval', function ($user) {
            return $user->hasPermission('approval');
        });

        Gate::define('access-master-data', function ($user) {
            return $user->hasRole('Admin');
        });
    }
}
