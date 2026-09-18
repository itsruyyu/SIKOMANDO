<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CorrelationIdMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $headerName = 'X-Request-ID';

        $incomingId = $request->header($headerName) ?: $request->header('X-Correlation-ID');

        if ($incomingId && preg_match('/^[a-zA-Z0-9_-]{8,64}$/', $incomingId)) {
            $requestId = $incomingId;
        } else {
            $requestId = (string) Str::uuid();
        }

        // Attach to request attributes
        $request->attributes->set('request_id', $requestId);

        // Share with Laravel's context logging
        Log::withContext([
            'request_id' => $requestId,
        ]);

        $response = $next($request);

        $response->headers->set($headerName, $requestId);

        return $response;
    }
}
