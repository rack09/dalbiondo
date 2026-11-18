<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

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
        Gate::define('manage-collaborators', function (User $user): bool {
            return $user->role === 'owner';
        });
        Gate::define('manage-featured-pizzas', fn (User $user): bool => $user->role === 'owner');
        Gate::define('manage-site-contact', fn (User $user): bool => in_array($user->role, ['owner', 'collaborator'], true));
        Gate::define('edit-own-profile', fn (User $user): bool => $user->role === 'collaborator');
        Gate::define('manage-staff-gallery', fn (User $user): bool => $user->role === 'owner');
    }
}
