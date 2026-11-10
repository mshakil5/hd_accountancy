<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Dedicated super-admin permission for the destructive maintenance
        // purge of the entire lead history. Routine admins must never be able
        // to delete genuine / won leads or their conversation audit trail.
        Gate::define('purge-leads', function (User $user) {
            return (bool) ($user->super_admin ?? false);
        });
    }

    public function register()
    {
      // Passport::ignoreRoutes();
    }
}
