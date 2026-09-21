<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::unauthorized();
        }

        if ($user->hasRole('SUPER_ADMIN')) {
            return $next($request);
        }

        if (! $user->hasAnyRole($roles)) {
            return ApiResponse::forbidden('Anda tidak memiliki peran yang diizinkan untuk mengakses rute ini.');
        }

        return $next($request);
    }
}

