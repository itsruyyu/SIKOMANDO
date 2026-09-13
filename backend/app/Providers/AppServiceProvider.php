<?php

namespace App\Providers;

use App\Models\Proposal;
use App\Models\User;
use App\Policies\ProposalPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Proposal::class, ProposalPolicy::class);

        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('SUPER_ADMIN') ? true : null;
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(
                strtolower($request->input('email', 'guest'))
                . '|'
                . $request->ip()
            );
        });
    }
}