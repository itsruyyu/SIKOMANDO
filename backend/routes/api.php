<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GrantProgramController;
use App\Http\Controllers\Api\V1\ProposalController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [
        AuthController::class,
        'login',
    ])->middleware('throttle:login');

    Route::get('/grant-programs', [
        GrantProgramController::class,
        'index',
    ]);

    Route::get('/grant-programs/{grantProgram}', [
        GrantProgramController::class,
        'show',
    ]);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [
            AuthController::class,
            'me',
        ]);

        Route::post('/auth/logout', [
            AuthController::class,
            'logout',
        ]);

        Route::get('/proposals', [
            ProposalController::class,
            'index',
        ]);

        Route::post('/proposals', [
            ProposalController::class,
            'store',
        ]);

        Route::get('/proposals/{proposal}', [
            ProposalController::class,
            'show',
        ]);
    });
});