<?php

use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\ApprovalController;
use App\Http\Controllers\Api\V1\AssignmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DecisionController;
use App\Http\Controllers\Api\V1\DisbursementController;
use App\Http\Controllers\Api\V1\EvaluationController;
use App\Http\Controllers\Api\V1\FieldSurveyController;
use App\Http\Controllers\Api\V1\GrantProgramController;
use App\Http\Controllers\Api\V1\InternalDashboardController;
use App\Http\Controllers\Api\V1\LpjController;
use App\Http\Controllers\Api\V1\PdfDocumentController;
use App\Http\Controllers\Api\V1\ProposalController;
use App\Http\Controllers\Api\V1\ProposalDocumentController;
use App\Http\Controllers\Api\V1\Public\PublicAnnouncementController;
use App\Http\Controllers\Api\V1\Public\PublicGrantProgramController;
use App\Http\Controllers\Api\V1\Public\PublicStatisticController;
use App\Http\Controllers\Api\V1\Public\PublicTransparencyController;
use App\Http\Controllers\Api\V1\RankingController;
use App\Http\Controllers\Api\V1\RevisionController;
use App\Http\Controllers\Api\V1\UserManagementController;
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

    // Public Portal Routes (No authentication required)
    Route::prefix('public')->group(function () {
        // Grant Programs
        Route::get('/grant-programs', [PublicGrantProgramController::class, 'index'])->name('public.grant-programs.index');
        Route::get('/grant-programs/{grantProgram}', [PublicGrantProgramController::class, 'show'])->name('public.grant-programs.show');
        Route::get('/grant-programs/{grantProgram}/timeline', [PublicGrantProgramController::class, 'timeline'])->name('public.grant-programs.timeline');
        Route::get('/grant-programs/{grantProgram}/documents', [PublicGrantProgramController::class, 'documents'])->name('public.grant-programs.documents');

        // Announcements
        Route::get('/announcements', [PublicAnnouncementController::class, 'index'])->name('public.announcements.index');
        Route::get('/announcements/{announcement}', [PublicAnnouncementController::class, 'show'])->name('public.announcements.show');

        // Statistics
        Route::get('/statistics', [PublicStatisticController::class, 'index'])->name('public.statistics.index');
        Route::get('/statistics/summary', [PublicStatisticController::class, 'summary'])->name('public.statistics.summary');

        // Transparency
        Route::get('/transparency', [PublicTransparencyController::class, 'index'])->name('public.transparency.index');
        Route::get('/transparency/{grantProgram}', [PublicTransparencyController::class, 'show'])->name('public.transparency.show');
    });

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

        Route::prefix('proposals/{proposal}/documents')
            ->scopeBindings()
            ->controller(ProposalDocumentController::class)
            ->group(function () {
                Route::get('/', 'index')->name('proposals.documents.index');
                Route::post('/', 'store')->name('proposals.documents.store');
                Route::get('/{document}', 'show')->name('proposals.documents.show');
                Route::get('/{document}/download', 'download')->name('proposals.documents.download');
                Route::post('/{document}/replace', 'replace')->name('proposals.documents.replace');
                Route::get('/{document}/versions', 'versions')->name('proposals.documents.versions');
                Route::delete('/{document}', 'destroy')->name('proposals.documents.destroy');
            });

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

                Route::get('/{fieldSurvey}/documents/{document}/download', 'downloadDocument')
                    ->name('proposals.field-surveys.documents.download');

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

        // Disbursement routes
        Route::prefix('proposals/{proposal}/disbursement-plans')
            ->scopeBindings()
            ->controller(DisbursementController::class)
            ->group(function () {
                Route::post('/', 'storePlan')
                    ->name('proposals.disbursement-plans.store');
            });

        Route::get('proposals/{proposal}/disbursements', [DisbursementController::class, 'proposalDisbursements'])
            ->name('proposals.disbursements.index');

        Route::get('proposals/{proposal}/disbursement-summary', [DisbursementController::class, 'proposalSummary'])
            ->name('proposals.disbursement-summary.show');

        Route::prefix('disbursements')
            ->controller(DisbursementController::class)
            ->group(function () {
                Route::get('/', 'index')
                    ->name('disbursements.index');
                Route::get('/{disbursement}', 'show')
                    ->name('disbursements.show');
                Route::post('/{disbursement}/verify', 'verify')
                    ->name('disbursements.verify');
                Route::post('/{disbursement}/approve', 'approve')
                    ->name('disbursements.approve');
                Route::post('/{disbursement}/transactions', 'recordTransaction')
                    ->name('disbursements.transactions.store');
            });

        // LPJ and Closing routes
        Route::prefix('proposals/{proposal}/lpj')
            ->scopeBindings()
            ->controller(LpjController::class)
            ->group(function () {
                Route::get('/', 'proposalLpjs')
                    ->name('proposals.lpj.index');
                Route::post('/', 'store')
                    ->name('proposals.lpj.store');
            });

        Route::get('proposals/{proposal}/closing-summary', [LpjController::class, 'closingSummary'])
            ->name('proposals.closing-summary.show');

        Route::post('proposals/{proposal}/close', [LpjController::class, 'closeProposal'])
            ->name('proposals.close');

        Route::prefix('lpj')
            ->controller(LpjController::class)
            ->group(function () {
                Route::get('/', 'index')
                    ->name('lpj.index');
                Route::get('/{lpj}', 'show')
                    ->name('lpj.show');
                Route::patch('/{lpj}', 'update')
                    ->name('lpj.update');
                Route::post('/{lpj}/submit', 'submit')
                    ->name('lpj.submit');
                Route::post('/{lpj}/review', 'review')
                    ->name('lpj.review');
                Route::post('/{lpj}/request-revision', 'requestRevision')
                    ->name('lpj.request-revision');
                Route::post('/{lpj}/approve', 'approve')
                    ->name('lpj.approve');
                Route::post('/{lpj}/reject', 'reject')
                    ->name('lpj.reject');
                Route::post('/{lpj}/finalize', 'finalize')
                    ->name('lpj.finalize');
                Route::post('/{lpj}/documents', 'uploadDocument')
                    ->name('lpj.documents.store');
                Route::get('/{lpj}/documents/{document}/download', 'downloadDocument')
                    ->name('lpj.documents.download');
            });

        // User Management Routes (Super Admin + Admin)
        Route::prefix('users')
            ->controller(UserManagementController::class)
            ->group(function () {
                Route::get('/', 'index')->name('users.index');
                Route::post('/', 'store')->name('users.store');
                Route::get('/by-role/{roleCode}', 'listByRole')->name('users.by-role');
                Route::get('/{user}', 'show')->name('users.show');
                Route::patch('/{user}', 'update')->name('users.update');
                Route::post('/{user}/toggle-active', 'toggleActive')->name('users.toggle-active');
                Route::post('/{user}/assign-role', 'assignRole')->name('users.assign-role');
                Route::post('/{user}/remove-role', 'removeRole')->name('users.remove-role');
                Route::post('/{user}/reset-password', 'resetPassword')->name('users.reset-password');
            });

        // Assignment Management Routes
        Route::prefix('assignments')
            ->controller(AssignmentController::class)
            ->group(function () {
                Route::get('/', 'index')->name('assignments.index');
                Route::get('/my', 'myAssignments')->name('assignments.my');
                Route::get('/workload', 'workload')->name('assignments.workload');
                Route::post('/', 'store')->name('assignments.store');
                Route::get('/{assignment}', 'show')->name('assignments.show');
                Route::post('/{assignment}/revoke', 'revoke')->name('assignments.revoke');
            });

        Route::get('proposals/{proposal}/assignments', [AssignmentController::class, 'proposalAssignments'])
            ->name('proposals.assignments.index');

        // Internal Dashboard Routes
        Route::prefix('internal/dashboard')
            ->controller(InternalDashboardController::class)
            ->group(function () {
                Route::get('/workload', 'workloadSummary')->name('internal.dashboard.workload');
                Route::get('/tasks', 'tasksByStatus')->name('internal.dashboard.tasks');
                Route::get('/assignment-history', 'assignmentHistory')->name('internal.dashboard.assignment-history');
            });

        // PDF Generation Routes
        Route::prefix('pdf')
            ->controller(PdfDocumentController::class)
            ->group(function () {
                Route::get('/proposals/{proposal}', 'generateProposalPdf')->name('pdf.proposal');
                Route::get('/proposals/{proposal}/verifications/{verification}', 'generateVerificationPdf')->name('pdf.verification');
                Route::get('/proposals/{proposal}/evaluations/{evaluation}', 'generateEvaluationPdf')->name('pdf.evaluation');
                Route::get('/proposals/{proposal}/field-surveys/{fieldSurvey}', 'generateFieldSurveyPdf')->name('pdf.field-survey');
                Route::get('/decisions/{decision}', 'generateDecisionPdf')->name('pdf.decision');
                Route::get('/disbursements/{disbursement}', 'generateDisbursementPdf')->name('pdf.disbursement');
                Route::get('/lpj/{lpj}', 'generateLpjPdf')->name('pdf.lpj');
            });
    });
});
