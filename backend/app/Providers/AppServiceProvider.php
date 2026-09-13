<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Proposal;
use App\Policies\ProposalPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

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

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(
                strtolower($request->input('email', 'guest'))
                . '|' . $request->ip()
            );
        });
    }
}
