<?php

namespace App\Providers;

use App\Models\Paste;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        Gate::before(function (User $user) {
            if ($user->isSuperAdmin())
                return true;
        });
        Gate::define('update-paste', function (User $user, Paste $paste) {
            return $user->id === $paste->user_id;
        });
        Gate::define('delete-paste', function (User $user, Paste $paste) {
            return $user->id === $paste->user_id;
        });
        Gate::define('is-admin', function (User $user) {
            return $user->isSuperAdmin();
        });
    }
}
