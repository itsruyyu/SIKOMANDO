<?php

namespace App\Services;

use App\Models\Decision;
use App\Models\Disbursement;
use App\Models\Evaluation;
use App\Models\FieldSurvey;
use App\Models\LpjSubmission;
use App\Models\Proposal;
use App\Models\User;
use App\Models\Verification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class PdfGeneratorService
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Generate proposal summary PDF.
     */
    public function generateProposalSummary(Proposal $proposal, User $actor): Response
    {
        $proposal->load([
            'grantProgram',
            'organization',
            'applicant',
            'budgetItems',
            'documents.documentType',
        ]);

        $pdf = Pdf::loadView('pdf.proposal-summary', [
            'proposal' => $proposal,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
            'generatedBy' => $actor->name,
        ])->setPaper('a4', 'portrait');

        $this->auditLogService->record(
            action: 'pdf.proposal_generated',
            module: 'pdf',
            entityType: Proposal::class,
            entityId: $proposal->id,
            metadata: ['actor_id' => $actor->id]
        );

        $safeName = Str::slug($proposal->proposal_number ?: $proposal->title).'-ringkasan.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$safeName}\"",
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Generate verification report PDF.
     */
    public function generateVerificationReport(Verification $verification, User $actor): Response
    {
        $verification->load([
            'proposal.grantProgram',
            'proposal.organization',
            'items.requirement',
            'items.documentType',
            'verifier',
        ]);

        $pdf = Pdf::loadView('pdf.verification-report', [
            'verification' => $verification,
            'proposal' => $verification->proposal,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
            'generatedBy' => $actor->name,
        ])->setPaper('a4', 'portrait');

        $this->auditLogService->record(
            action: 'pdf.verification_generated',
            module: 'pdf',
            entityType: Verification::class,
            entityId: $verification->id,
            metadata: ['actor_id' => $actor->id]
        );

        $safeName = Str::slug($verification->verification_number ?: 'verifikasi').'-laporan.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$safeName}\"",
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Generate evaluation report PDF.
     */
    public function generateEvaluationReport(Evaluation $evaluation, User $actor): Response
    {
        $evaluation->load([
            'proposal.grantProgram',
            'proposal.organization',
            'items.criteria',
            'evaluator',
        ]);

        $pdf = Pdf::loadView('pdf.evaluation-report', [
            'evaluation' => $evaluation,
            'proposal' => $evaluation->proposal,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
            'generatedBy' => $actor->name,
        ])->setPaper('a4', 'portrait');

        $this->auditLogService->record(
            action: 'pdf.evaluation_generated',
            module: 'pdf',
            entityType: Evaluation::class,
            entityId: $evaluation->id,
            metadata: ['actor_id' => $actor->id]
        );

        $safeName = Str::slug($evaluation->evaluation_number ?: 'evaluasi').'-laporan.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$safeName}\"",
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Generate field survey report PDF.
     */
    public function generateFieldSurveyReport(FieldSurvey $survey, User $actor): Response
    {
        $survey->load([
            'proposal.grantProgram',
            'proposal.organization',
            'items',
            'findings',
            'surveyor',
        ]);

        $pdf = Pdf::loadView('pdf.field-survey-report', [
            'survey' => $survey,
            'proposal' => $survey->proposal,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
            'generatedBy' => $actor->name,
        ])->setPaper('a4', 'portrait');

        $this->auditLogService->record(
            action: 'pdf.field_survey_generated',
            module: 'pdf',
            entityType: FieldSurvey::class,
            entityId: $survey->id,
            metadata: ['actor_id' => $actor->id]
        );

        $safeName = Str::slug($survey->survey_number ?: 'survei-lapangan').'-berita-acara.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$safeName}\"",
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Generate decision / SK PDF.
     */
    public function generateDecisionDocument(Decision $decision, User $actor): Response
    {
        $decision->load([
            'proposal.grantProgram',
            'proposal.organization',
            'decider',
        ]);

        $pdf = Pdf::loadView('pdf.decision-document', [
            'decision' => $decision,
            'proposal' => $decision->proposal,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
            'generatedBy' => $actor->name,
        ])->setPaper('a4', 'portrait');

        $this->auditLogService->record(
            action: 'pdf.decision_generated',
            module: 'pdf',
            entityType: Decision::class,
            entityId: $decision->id,
            metadata: ['actor_id' => $actor->id]
        );

        $safeName = Str::slug($decision->decision_number ?: 'surat-keputusan').'.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$safeName}\"",
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Generate disbursement receipt PDF.
     */
    public function generateDisbursementReceipt(Disbursement $disbursement, User $actor): Response
    {
        $disbursement->load([
            'proposal.grantProgram',
            'proposal.organization',
            'transactions',
        ]);

        $pdf = Pdf::loadView('pdf.disbursement-receipt', [
            'disbursement' => $disbursement,
            'proposal' => $disbursement->proposal,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
            'generatedBy' => $actor->name,
        ])->setPaper('a4', 'portrait');

        $this->auditLogService->record(
            action: 'pdf.disbursement_generated',
            module: 'pdf',
            entityType: Disbursement::class,
            entityId: $disbursement->id,
            metadata: ['actor_id' => $actor->id]
        );

        $safeName = Str::slug($disbursement->disbursement_number ?: 'bukti-pencairan').'.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$safeName}\"",
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Generate LPJ report PDF.
     */
    public function generateLpjReport(LpjSubmission $lpj, User $actor): Response
    {
        $lpj->load([
            'proposal.grantProgram',
            'proposal.organization',
            'items',
            'documents',
        ]);

        $pdf = Pdf::loadView('pdf.lpj-report', [
            'lpj' => $lpj,
            'proposal' => $lpj->proposal,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
            'generatedBy' => $actor->name,
        ])->setPaper('a4', 'portrait');

        $this->auditLogService->record(
            action: 'pdf.lpj_generated',
            module: 'pdf',
            entityType: LpjSubmission::class,
            entityId: $lpj->id,
            metadata: ['actor_id' => $actor->id]
        );

        $safeName = Str::slug($lpj->lpj_number ?: 'laporan-lpj').'.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$safeName}\"",
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
        ]);
    }
}
