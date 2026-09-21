<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Decision;
use App\Models\Disbursement;
use App\Models\Evaluation;
use App\Models\FieldSurvey;
use App\Models\LpjSubmission;
use App\Models\Proposal;
use App\Models\Verification;
use App\Services\PdfGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

class PdfDocumentController extends Controller
{
    public function __construct(
        protected PdfGeneratorService $pdfService
    ) {}

    /**
     * Issue a temporary signed URL for direct browser viewing/downloading without query token.
     */
    public function issueSignedUrl(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:proposal,verification,evaluation,field-survey,decision,disbursement,lpj'],
            'id' => ['required', 'uuid'],
            'secondary_id' => ['nullable', 'uuid'],
        ]);

        match ($validated['type']) {
            'proposal' => Gate::authorize('view', Proposal::findOrFail($validated['id'])),
            'decision' => Gate::authorize('view', Decision::findOrFail($validated['id'])),
            'disbursement' => Gate::authorize('view', Disbursement::findOrFail($validated['id'])),
            'lpj' => Gate::authorize('view', LpjSubmission::findOrFail($validated['id'])),
            'verification' => Gate::authorize('view', ! empty($validated['secondary_id']) ? Verification::findOrFail($validated['secondary_id']) : Verification::where('proposal_id', $validated['id'])->firstOrFail()),
            'evaluation' => Gate::authorize('view', ! empty($validated['secondary_id']) ? Evaluation::findOrFail($validated['secondary_id']) : Evaluation::where('proposal_id', $validated['id'])->firstOrFail()),
            'field-survey' => Gate::authorize('view', ! empty($validated['secondary_id']) ? FieldSurvey::findOrFail($validated['secondary_id']) : FieldSurvey::where('proposal_id', $validated['id'])->firstOrFail()),
        };

        $url = URL::temporarySignedRoute(
            'pdf.signed',
            now()->addMinutes(5),
            [
                'type' => $validated['type'],
                'id' => $validated['id'],
                'secondary_id' => $validated['secondary_id'] ?? null,
            ]
        );

        return ApiResponse::success([
            'download_url' => $url,
            'expires_at' => now()->addMinutes(5)->toIso8601String(),
        ], 'Tautan unduhan bertanda tangan berhasil dibuat.');
    }

    /**
     * Download or view PDF via validated signed URL.
     */
    public function downloadSigned(Request $request, string $type, string $id): Response
    {
        $user = $request->user();

        return match ($type) {
            'proposal' => $this->pdfService->generateProposalSummary(Proposal::findOrFail($id), $user),
            'decision' => $this->pdfService->generateDecisionDocument(Decision::findOrFail($id), $user),
            'disbursement' => $this->pdfService->generateDisbursementReceipt(Disbursement::findOrFail($id), $user),
            'lpj' => $this->pdfService->generateLpjReport(LpjSubmission::findOrFail($id), $user),
            'verification' => $this->pdfService->generateVerificationReport(
                $request->filled('secondary_id') ? Verification::findOrFail($request->query('secondary_id')) : Verification::where('proposal_id', $id)->firstOrFail(),
                $user
            ),
            'evaluation' => $this->pdfService->generateEvaluationReport(
                $request->filled('secondary_id') ? Evaluation::findOrFail($request->query('secondary_id')) : Evaluation::where('proposal_id', $id)->firstOrFail(),
                $user
            ),
            'field-survey' => $this->pdfService->generateFieldSurveyReport(
                $request->filled('secondary_id') ? FieldSurvey::findOrFail($request->query('secondary_id')) : FieldSurvey::where('proposal_id', $id)->firstOrFail(),
                $user
            ),
            default => abort(404, 'Tipe dokumen tidak ditemukan.'),
        };
    }

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
