<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiLoggingMiddleware
{
    protected array $maskedKeys = [
        'password',
        'password_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'secret',
        'bank_account_number',
        'account_number',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $statusCode = $response->getStatusCode();
        $method = $request->method();
        $path = $request->path();
        $ip = $request->ip();
        $userId = $request->user()?->id;
        $requestId = $request->attributes->get('request_id');

        $logContext = [
            'method' => $method,
            'path' => $path,
            'status' => $statusCode,
            'duration_ms' => $duration,
            'ip' => $ip,
            'user_id' => $userId,
            'request_id' => $requestId,
        ];

        $message = sprintf('API [%s] %s %d (%sms)', $method, $path, $statusCode, $duration);

        if ($statusCode >= 500) {
            Log::error($message, $logContext);
        } elseif ($statusCode >= 400) {
            Log::warning($message, $logContext);
        } else {
            Log::info($message, $logContext);
        }

        return $response;
    }
}
