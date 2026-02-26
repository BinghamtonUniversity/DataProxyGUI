<?php

namespace App\Providers;

use App\Models\User;
use App\Models\ProxyServerConfig;
use App\Policies\UserPolicy;
use App\Policies\ProxyServerConfigPolicy;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(User::class, UserPolicy::class);
        Gate::define('manage_users', fn(User $user) => $user->super_admin);

        // Gate::policy needs model binding to ProxyServerConfig, but we want to avoid it since it has config data (username/password) 
        // and we want to resolve it dynamically based on the slug in the URL. So we define a Gate that uses the ServerUserPolicyService directly.
        // Gate::policy(ProxyServerConfig::class, ProxyServerConfigPolicy::class); 
        Gate::define('server_admin', function (?User $user, ?string $serverSlug) {
            if (!$user || !$serverSlug) {
                return false;
            }
            return app(\App\Services\ServerUserPolicyService::class)->isAdminForServer($serverSlug);
        });


    }
}
