<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Proposal;
use App\Policies\ProposalPolicy;
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
        Gate::policy(Proposal::class, ProposalPolicy::class);

        Gate::before(function ($user, string $ability) {
            return $user->hasRole('SUPER_ADMIN') ? true : null;
        });
    }
}
