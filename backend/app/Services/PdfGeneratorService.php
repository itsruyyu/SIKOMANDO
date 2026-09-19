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
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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

        $qr = \App\Models\QrIdentity::where('qrable_type', Proposal::class)
            ->where('qrable_id', $proposal->id)
            ->where('status', \App\Enums\QrStatus::ACTIVE)
            ->first();

        if (! $qr) {
            try {
                $qr = app(QrService::class)->generateFor(
                    $proposal,
                    \App\Enums\QrType::PROPOSAL,
                    $actor,
                    null,
                    [
                        'proposal_number' => $proposal->proposal_number,
                    ]
                );
            } catch (\Throwable) {
                // Non-blocking
            }
        }

        $pdf = Pdf::loadView('pdf.proposal-summary', [
            'proposal' => $proposal,
            'qr' => $qr,
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
            'proposal.budgetItems',
            'decider',
            'issuer',
        ]);

        $proposal = $decision->proposal;

        $qrSurat = \App\Models\QrIdentity::where('qrable_type', Decision::class)
            ->where('qrable_id', $decision->id)
            ->where('status', \App\Enums\QrStatus::ACTIVE)
            ->first();

        if (! $qrSurat) {
            try {
                $qrSurat = app(QrService::class)->generateFor(
                    $decision,
                    \App\Enums\QrType::DECISION_SK,
                    $decision->decider ?? $decision->issuer ?? $actor,
                    null,
                    [
                        'decision_number' => $decision->decision_number,
                    ]
                );
            } catch (\Throwable) {
                // Non-blocking
            }
        }

        $verificationUrl = $qrSurat?->verification_url ?? url("/api/v1/public/verify/{$decision->id}");

        // 1. Base64 QR Surat (Keabsahan Dokumen SK)
        $qrSuratSvg = null;
        if ($qrSurat && $qrSurat->qr_code_path && file_exists(storage_path('app/public/' . $qrSurat->qr_code_path))) {
            $qrSuratSvg = file_get_contents(storage_path('app/public/' . $qrSurat->qr_code_path));
        }
        if (! $qrSuratSvg) {
            $qrSuratSvg = (string) QrCode::format('svg')->size(140)->margin(1)->errorCorrection('M')->generate($verificationUrl);
        }
        $qrSuratBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrSuratSvg);

        // 2. Base64 QR Tanda Tangan Pejabat Pengesah
        $sigPayload = $verificationUrl . (str_contains($verificationUrl, '?') ? '&' : '?') . 'signature=verified&signer=' . urlencode($decision->decider?->name ?? 'Gubernur Sulut');
        $qrTtdSvg = (string) QrCode::format('svg')->size(140)->margin(1)->errorCorrection('H')->generate($sigPayload);
        $qrTtdBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrTtdSvg);

        $approvedAmount = (float) ($decision->approved_amount ?: ($proposal?->approved_amount ?: ($proposal?->requested_amount ?: 0)));
        $requestedAmount = (float) ($proposal?->requested_amount ?: ($proposal?->budgetItems?->sum('subtotal') ?: 0));

        $pdf = Pdf::loadView('pdf.decision-document', [
            'decision' => $decision,
            'proposal' => $proposal,
            'qr' => $qrSurat,
            'qrSurat' => $qrSurat,
            'qrSuratBase64' => $qrSuratBase64,
            'qrTtdBase64' => $qrTtdBase64,
            'verificationUrl' => $verificationUrl,
            'approvedAmount' => $approvedAmount,
            'requestedAmount' => $requestedAmount,
            'terbilang' => $approvedAmount > 0 ? self::terbilang($approvedAmount) : 'Nol',
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

    /**
     * Konversi nominal angka ke kata terbilang Bahasa Indonesia.
     */
    public static function terbilang(float $number): string
    {
        $bilangan = [
            '', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima',
            'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas',
        ];

        $number = abs((int) round($number));
        if ($number === 0) {
            return '';
        }

        if ($number < 12) {
            return $bilangan[$number];
        }

        if ($number < 20) {
            return self::terbilang($number - 10).' Belas';
        }

        if ($number < 100) {
            $sisa = $number % 10;
            return self::terbilang((int) ($number / 10)).' Puluh'.($sisa > 0 ? ' '.self::terbilang($sisa) : '');
        }

        if ($number < 200) {
            $sisa = $number - 100;
            return 'Seratus'.($sisa > 0 ? ' '.self::terbilang($sisa) : '');
        }

        if ($number < 1000) {
            $sisa = $number % 100;
            return self::terbilang((int) ($number / 100)).' Ratus'.($sisa > 0 ? ' '.self::terbilang($sisa) : '');
        }

        if ($number < 2000) {
            $sisa = $number - 1000;
            return 'Seribu'.($sisa > 0 ? ' '.self::terbilang($sisa) : '');
        }

        if ($number < 1000000) {
            $sisa = $number % 1000;
            return self::terbilang((int) ($number / 1000)).' Ribu'.($sisa > 0 ? ' '.self::terbilang($sisa) : '');
        }

        if ($number < 1000000000) {
            $sisa = $number % 1000000;
            return self::terbilang((int) ($number / 1000000)).' Juta'.($sisa > 0 ? ' '.self::terbilang($sisa) : '');
        }

        if ($number < 1000000000000) {
            $sisa = (int) fmod($number, 1000000000);
            return self::terbilang((int) ($number / 1000000000)).' Miliar'.($sisa > 0 ? ' '.self::terbilang($sisa) : '');
        }

        $sisa = (int) fmod($number, 1000000000000);
        return self::terbilang((int) ($number / 1000000000000)).' Triliun'.($sisa > 0 ? ' '.self::terbilang($sisa) : '');
    }
}
