<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Decision;
use App\Models\Disbursement;
use App\Models\Evaluation;
use App\Models\FieldSurvey;
use App\Models\LpjSubmission;
use App\Models\Proposal;
use App\Models\Verification;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PdfDocumentController extends Controller
{
    public function __construct(
        protected PdfGeneratorService $pdfService
    ) {}

    /**
     * Generate proposal summary PDF.
     */
    public function generateProposalPdf(Proposal $proposal, Request $request): Response
    {
        Gate::authorize('view', $proposal);

        return $this->pdfService->generateProposalSummary($proposal, $request->user());
    }

    /**
     * Generate verification report PDF.
     */
    public function generateVerificationPdf(Proposal $proposal, Verification $verification, Request $request): Response
    {
        Gate::authorize('view', $verification);

        return $this->pdfService->generateVerificationReport($verification, $request->user());
    }

    /**
     * Generate evaluation report PDF.
     */
    public function generateEvaluationPdf(Proposal $proposal, Evaluation $evaluation, Request $request): Response
    {
        Gate::authorize('view', $evaluation);

        return $this->pdfService->generateEvaluationReport($evaluation, $request->user());
    }

    /**
     * Generate field survey report PDF.
     */
    public function generateFieldSurveyPdf(Proposal $proposal, FieldSurvey $fieldSurvey, Request $request): Response
    {
        Gate::authorize('view', $fieldSurvey);

        return $this->pdfService->generateFieldSurveyReport($fieldSurvey, $request->user());
    }

    /**
     * Generate decision / SK PDF.
     */
    public function generateDecisionPdf(Decision $decision, Request $request): Response
    {
        Gate::authorize('view', $decision);

        return $this->pdfService->generateDecisionDocument($decision, $request->user());
    }

    /**
     * Generate disbursement receipt PDF.
     */
    public function generateDisbursementPdf(Disbursement $disbursement, Request $request): Response
    {
        Gate::authorize('view', $disbursement);

        return $this->pdfService->generateDisbursementReceipt($disbursement, $request->user());
    }

    /**
     * Generate LPJ report PDF.
     */
    public function generateLpjPdf(LpjSubmission $lpj, Request $request): Response
    {
        Gate::authorize('view', $lpj);

        return $this->pdfService->generateLpjReport($lpj, $request->user());
    }
}
