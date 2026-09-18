<?php

namespace App\Providers;

use App\Models\Approval;
use App\Models\AuditLog;
use App\Models\Decision;
use App\Models\Disbursement;
use App\Models\Evaluation;
use App\Models\FieldSurvey;
use App\Models\LpjSubmission;
use App\Models\PolicyConfiguration;
use App\Models\Proposal;
use App\Models\ProposalAssignment;
use App\Models\ProposalDocument;
use App\Models\Ranking;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\Verification;
use App\Policies\ApprovalPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\DecisionPolicy;
use App\Policies\DisbursementPolicy;
use App\Policies\EvaluationPolicy;
use App\Policies\FieldSurveyPolicy;
use App\Policies\LpjPolicy;
use App\Policies\PolicyConfigurationPolicy;
use App\Policies\ProposalAssignmentPolicy;
use App\Policies\ProposalDocumentPolicy;
use App\Policies\ProposalPolicy;
use App\Policies\RankingPolicy;
use App\Policies\RecommendationPolicy;
use App\Policies\UserPolicy;
use App\Policies\VerificationPolicy;
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
                .'|'
                .$request->ip()
            );
        });

        Gate::policy(Verification::class, VerificationPolicy::class);
        Gate::policy(Evaluation::class, EvaluationPolicy::class);
        Gate::policy(FieldSurvey::class, FieldSurveyPolicy::class);
        Gate::policy(Ranking::class, RankingPolicy::class);
        Gate::policy(Recommendation::class, RecommendationPolicy::class);
        Gate::policy(Approval::class, ApprovalPolicy::class);
        Gate::policy(Decision::class, DecisionPolicy::class);
        Gate::policy(Disbursement::class, DisbursementPolicy::class);
        Gate::policy(LpjSubmission::class, LpjPolicy::class);
        Gate::policy(ProposalDocument::class, ProposalDocumentPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(ProposalAssignment::class, ProposalAssignmentPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(PolicyConfiguration::class, PolicyConfigurationPolicy::class);
    }
}
