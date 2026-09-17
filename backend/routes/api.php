<?php

use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\ApprovalController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DecisionController;
use App\Http\Controllers\Api\V1\EvaluationController;
use App\Http\Controllers\Api\V1\FieldSurveyController;
use App\Http\Controllers\Api\V1\GrantProgramController;
use App\Http\Controllers\Api\V1\ProposalController;
use App\Http\Controllers\Api\V1\RankingController;
use App\Http\Controllers\Api\V1\RevisionController;
use App\Http\Controllers\Api\V1\VerificationController;
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
            ->scopeBindings()
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

                Route::delete('/{evaluation}', 'destroy')
                    ->name('proposals.evaluations.destroy');
            });

        Route::get('field-surveys/assigned', [FieldSurveyController::class, 'mySurveys'])
            ->name('field-surveys.assigned');

        Route::prefix('proposals/{proposal}/field-surveys')
            ->scopeBindings()
            ->controller(FieldSurveyController::class)
            ->group(function () {
                Route::get('/', 'index')
                    ->name('proposals.field-surveys.index');

                Route::post('/', 'store')
                    ->name('proposals.field-surveys.store');

                Route::get('/{fieldSurvey}', 'show')
                    ->name('proposals.field-surveys.show');

                Route::patch('/{fieldSurvey}/schedule', 'updateSchedule')
                    ->name('proposals.field-surveys.schedule.update');

                Route::post('/{fieldSurvey}/start', 'start')
                    ->name('proposals.field-surveys.start');

                Route::patch('/{fieldSurvey}/items/{item}', 'updateItem')
                    ->name('proposals.field-surveys.items.update');

                Route::post('/{fieldSurvey}/findings', 'storeFinding')
                    ->name('proposals.field-surveys.findings.store');

                Route::post('/{fieldSurvey}/documents', 'storeDocument')
                    ->name('proposals.field-surveys.documents.store');

                Route::patch('/{fieldSurvey}/result', 'fillResult')
                    ->name('proposals.field-surveys.result.update');

                Route::post('/{fieldSurvey}/submit', 'submit')
                    ->name('proposals.field-surveys.submit');

                Route::post('/{fieldSurvey}/review', 'review')
                    ->name('proposals.field-surveys.review');

                Route::post('/{fieldSurvey}/complete', 'complete')
                    ->name('proposals.field-surveys.complete');

                Route::delete('/{fieldSurvey}', 'destroy')
                    ->name('proposals.field-surveys.destroy');
            });

        Route::prefix('grant-programs/{grantProgram}/rankings')
            ->scopeBindings()
            ->controller(RankingController::class)
            ->group(function () {
                Route::get('/preview', 'preview')
                    ->name('grant-programs.rankings.preview');

                Route::post('/generate', 'generate')
                    ->name('grant-programs.rankings.generate');

                Route::get('/', 'index')
                    ->name('grant-programs.rankings.index');

                Route::get('/{ranking}', 'show')
                    ->name('grant-programs.rankings.show');

                Route::post('/{ranking}/review', 'review')
                    ->name('grant-programs.rankings.review');

                Route::post('/{ranking}/finalize', 'finalize')
                    ->name('grant-programs.rankings.finalize');

                Route::post('/{ranking}/regenerate', 'regenerate')
                    ->name('grant-programs.rankings.regenerate');
            });

        Route::get('proposals/{proposal}/recommendation', [RankingController::class, 'proposalRecommendation'])
            ->name('proposals.recommendation.show');

        // Approval routes
        Route::prefix('proposals/{proposal}/approvals')
            ->scopeBindings()
            ->controller(ApprovalController::class)
            ->group(function () {
                Route::get('/', 'proposalApprovals')
                    ->name('proposals.approvals.index');
                Route::post('/', 'store')
                    ->name('proposals.approvals.store');
            });

        Route::prefix('approvals')
            ->controller(ApprovalController::class)
            ->group(function () {
                Route::get('/', 'index')
                    ->name('approvals.index');
                Route::get('/{approval}', 'show')
                    ->name('approvals.show');
                Route::post('/{approval}/review', 'review')
                    ->name('approvals.review');
                Route::post('/{approval}/approve', 'approve')
                    ->name('approvals.approve');
                Route::post('/{approval}/reject', 'reject')
                    ->name('approvals.reject');
            });

        // Decision routes
        Route::get('proposals/{proposal}/decision', [DecisionController::class, 'proposalDecision'])
            ->name('proposals.decision.show');

        Route::prefix('decisions')
            ->controller(DecisionController::class)
            ->group(function () {
                Route::get('/', 'index')
                    ->name('decisions.index');
                Route::get('/{decision}', 'show')
                    ->name('decisions.show');
                Route::post('/{decision}/documents', 'generateDocument')
                    ->name('decisions.documents.store');
                Route::get('/{decision}/documents/{document}', 'showDocument')
                    ->name('decisions.documents.show');
                Route::get('/{decision}/documents/{document}/download', 'downloadDocument')
                    ->name('decisions.documents.download');
            });
    });
});
