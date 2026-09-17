<?php

use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
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
