<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GrantProgramController;
use App\Http\Controllers\Api\V1\ProposalController;
use App\Http\Controllers\Api\V1\RevisionController;
use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\VerificationController;
use App\Http\Controllers\Api\V1\EvaluationController;
use App\Http\Controllers\Api\NotificationController;
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

        Route::post('/proposals/{proposal}/submit', [
            ProposalController::class,
            'submit',
        ]);

        Route::get('/proposals/{proposal}', [
            ProposalController::class,
            'show',
        ]);

        Route::get('/proposals/{proposal}/revisions', [
            RevisionController::class,
            'index',
        ]);

        Route::post('/proposals/{proposal}/revisions', [
            RevisionController::class,
            'store',
        ]);

        Route::get('/proposals/{proposal}/revisions/{revision}', [
            RevisionController::class,
            'show',
        ]);

        Route::post('/proposals/{proposal}/revisions/{revision}/submit', [
            RevisionController::class,
            'submit',
        ]);

        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
            Route::post('/{notification}/read', [NotificationController::class, 'markAsRead']);
        });

        Route::get('/activities', [
            ActivityController::class,
            'index',
        ]);

        Route::prefix('proposals/{proposal}/verifications')
        ->scopeBindings()
        ->group(function () {
            Route::get('/', [
                VerificationController::class,
                'index',
            ])->name('proposals.verifications.index');

            Route::post('/', [
                VerificationController::class,
                'store',
            ])->name('proposals.verifications.store');

            Route::get('/{verification}', [
                VerificationController::class,
                'show',
            ])->name('proposals.verifications.show');

            Route::patch('/{verification}/items/{item}', [
                VerificationController::class,
                'updateItem',
            ])->name('proposals.verifications.items.update');

            Route::post('/{verification}/complete', [
                VerificationController::class,
                'complete',
            ])->name('proposals.verifications.complete');
        });  
        
        Route::prefix('proposals/{proposal}/evaluations')
        ->controller(EvaluationController::class)
        ->group(function () {
            Route::get('/', 'index')
                ->name('proposals.evaluations.index');

            Route::post('/', 'store')
                ->name('proposals.evaluations.store');

            Route::get('/{evaluation}', 'show')
                ->name('proposals.evaluations.show');

            Route::patch('/{evaluation}/items/{item}', 'updateItem')
                ->name('proposals.evaluations.items.update');

            Route::post('/{evaluation}/complete', 'complete')
                ->name('proposals.evaluations.complete');
        });
    });
});