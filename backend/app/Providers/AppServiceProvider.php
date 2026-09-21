<?php

namespace App\Providers;

use App\Models\Approval;
use App\Models\AuditLog;
use App\Models\Decision;
use App\Models\DigitalSignature;
use App\Models\Disbursement;
use App\Models\Evaluation;
use App\Models\FieldSurvey;
use App\Models\Handover;
use App\Models\LpjSubmission;
use App\Models\MonitoringRecord;
use App\Models\PolicyConfiguration;
use App\Models\Proposal;
use App\Models\ProposalAssignment;
use App\Models\ProposalDocument;
use App\Models\QrIdentity;
use App\Models\Ranking;
use App\Models\RealizationPackage;
use App\Models\Receipt;
use App\Models\Recommendation;
use App\Models\SignatureProfile;
use App\Models\User;
use App\Models\Verification;
use App\Policies\ApprovalPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\DecisionPolicy;
use App\Policies\DigitalSignaturePolicy;
use App\Policies\DisbursementPolicy;
use App\Policies\EvaluationPolicy;
use App\Policies\FieldSurveyPolicy;
use App\Policies\HandoverPolicy;
use App\Policies\LpjPolicy;
use App\Policies\MonitoringRecordPolicy;
use App\Policies\PolicyConfigurationPolicy;
use App\Policies\ProposalAssignmentPolicy;
use App\Policies\ProposalDocumentPolicy;
use App\Policies\ProposalPolicy;
use App\Policies\QrIdentityPolicy;
use App\Policies\RankingPolicy;
use App\Policies\RealizationPackagePolicy;
use App\Policies\ReceiptPolicy;
use App\Policies\RecommendationPolicy;
use App\Policies\SignatureProfilePolicy;
use App\Policies\UserPolicy;
use App\Policies\VerificationPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Proposal::class, ProposalPolicy::class);
        // Production security assertion (BE-12)
        if (app()->isProduction() && config('app.debug')) {
            throw new RuntimeException('APP_DEBUG wajib bernilai false di environment production.');
        }

        // Centralized Password Policy (BE-10)
        Password::defaults(function () {
            return app()->isProduction()
                ? Password::min(12)->letters()->mixedCase()->numbers()->symbols()->uncompromised()
                : Password::min(8);
        });

        // Global super-admin bypass
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('SUPER_ADMIN') ? true : null;
        });

        // Rate Limiters (BE-10, BE-13)
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(
                strtolower($request->input('email', 'guest'))
                .'|'
                .$request->ip()
            );
            return [
                Limit::perMinute(5)->by(strtolower($request->input('email', 'guest')).'|'.$request->ip()),
                Limit::perMinute(15)->by(strtolower($request->input('email', 'guest'))),
                Limit::perMinute(30)->by($request->ip()),
            ];
        });

        RateLimiter::for('pdf', function (Request $request) {
            return Limit::perMinute(15)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('upload', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('qr-resolve', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('public', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        RateLimiter::for('client-logs', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        // Register Policies (BE-01, BE-04, BE-05, BE-06)
        Gate::policy(Proposal::class, ProposalPolicy::class);
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

        // Newly added policies (BE-01, BE-04, BE-05)
        Gate::policy(SignatureProfile::class, SignatureProfilePolicy::class);
        Gate::policy(DigitalSignature::class, DigitalSignaturePolicy::class);
        Gate::policy(Receipt::class, ReceiptPolicy::class);
        Gate::policy(RealizationPackage::class, RealizationPackagePolicy::class);
        Gate::policy(Handover::class, HandoverPolicy::class);
        Gate::policy(MonitoringRecord::class, MonitoringRecordPolicy::class);
        Gate::policy(QrIdentity::class, QrIdentityPolicy::class);
    }
}
