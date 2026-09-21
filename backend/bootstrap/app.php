<?php

use App\Http\Middleware\ApiLoggingMiddleware;
use App\Http\Middleware\CorrelationIdMiddleware;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
        $middleware->append(CorrelationIdMiddleware::class);
        $middleware->append(SecurityHeaders::class);
        $middleware->append(ApiLoggingMiddleware::class);

        $middleware->alias([
            'active.user' => EnsureUserIsActive::class,
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        /*
        |--------------------------------------------------------------------------
        | API JSON Response
        |--------------------------------------------------------------------------
        */

        $exceptions->shouldRenderJsonWhen(
            function (Request $request, Throwable $exception): bool {
                return $request->is('api/*')
                    || $request->expectsJson();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | 422 - Validation Error
        |--------------------------------------------------------------------------
        */

        $exceptions->render(
            function (
                ValidationException $exception,
                Request $request
            ) {
                if (! $request->is('api/*')) {
                    return null;
                }

                return ApiResponse::validation(
                    errors: $exception->errors(),
                    message: 'Data yang dikirim tidak valid.'
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | 401 - Authentication Error
        |--------------------------------------------------------------------------
        */

        $exceptions->render(
            function (
                AuthenticationException $exception,
                Request $request
            ) {
                if (! $request->is('api/*')) {
                    return null;
                }

                return ApiResponse::unauthorized(
                    message: 'Autentikasi diperlukan.'
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | 403 - Authorization Error
        |--------------------------------------------------------------------------
        */

        $exceptions->render(
            function (
                AccessDeniedHttpException $exception,
                Request $request
            ) {
                if (! $request->is('api/*')) {
                    return null;
                }

                return ApiResponse::forbidden(
                    message: 'Anda tidak memiliki izin untuk melakukan tindakan ini.'
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | 404 - Model Not Found / Route Not Found
        |--------------------------------------------------------------------------
        */

        $exceptions->render(
            function (
                ModelNotFoundException|NotFoundHttpException $exception,
                Request $request
            ) {
                if (! $request->is('api/*')) {
                    return null;
                }

                return ApiResponse::notFound(
                    message: 'Data yang diminta tidak ditemukan.'
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | 405 - Method Not Allowed
        |--------------------------------------------------------------------------
        */

        $exceptions->render(
            function (
                MethodNotAllowedHttpException $exception,
                Request $request
            ) {
                if (! $request->is('api/*')) {
                    return null;
                }

                return ApiResponse::error(
                    message: 'Metode HTTP tidak diizinkan untuk rute ini.',
                    status: 405
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | 429 - Too Many Requests (Rate Limiting)
        |--------------------------------------------------------------------------
        */

        $exceptions->render(
            function (
                TooManyRequestsHttpException $exception,
                Request $request
            ) {
                if (! $request->is('api/*')) {
                    return null;
                }

                return ApiResponse::error(
                    message: 'Terlalu banyak permintaan. Silakan coba lagi nanti.',
                    status: 429
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Generic HTTP Exceptions (400, 409, 503, etc.)
        |--------------------------------------------------------------------------
        */

        $exceptions->render(
            function (
                HttpException $exception,
                Request $request
            ) {
                if (! $request->is('api/*')) {
                    return null;
                }

                return ApiResponse::error(
                    message: $exception->getMessage() ?: 'Terjadi kesalahan pada permintaan.',
                    status: $exception->getStatusCode()
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | 500 - Internal Server Error
        |--------------------------------------------------------------------------
        */

        $exceptions->render(
            function (
                Throwable $exception,
                Request $request
            ) {
                if (! $request->is('api/*')) {
                    return null;
                }

                report($exception);

                return ApiResponse::error(
                    message: 'Terjadi kesalahan pada server.',
                    status: 500
                );
            }
        );
    })
    ->create();
