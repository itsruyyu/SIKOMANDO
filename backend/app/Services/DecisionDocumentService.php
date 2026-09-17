<?php

namespace App\Services;

use App\Enums\DecisionDocumentStatus;
use App\Enums\DecisionResult;
use App\Models\Decision;
use App\Models\DecisionDocument;
use App\Models\DecisionDocumentVersion;
use App\Models\DecisionTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DecisionDocumentService
{
    public function __construct(
        protected NumberingService $numberingService,
        protected AuditLogService $auditLogService
    ) {}

    public function generateDocument(
        Decision $decision,
        User $actor,
        array $options = []
    ): DecisionDocument {
        if ($decision->result !== DecisionResult::APPROVED) {
            throw ValidationException::withMessages([
                'decision' => 'Dokumen SK hanya dapat diterbitkan untuk keputusan yang telah disetujui (APPROVED).',
            ]);
        }

        return DB::transaction(function () use ($decision, $actor, $options) {
            $proposal = $decision->proposal()->with(['organization', 'grantProgram'])->firstOrFail();

            // 1. Resolve template
            $template = DecisionTemplate::query()
                ->where(function ($query) {
                    $query->where('document_type', 'decision_letter')
                        ->orWhere('code', 'SK_PENETAPAN_HIBAH');
                })
                ->where('is_active', true)
                ->first();

            $templateVersion = $template?->versions()
                ->whereIn('status', ['active', 'approved'])
                ->where(function ($query) {
                    $query->whereNull('effective_from')
                        ->orWhere('effective_from', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('effective_until')
                        ->orWhere('effective_until', '>=', now());
                })
                ->orderByDesc('created_at')
                ->first();

            // 2. Render content
            $rawContent = $templateVersion?->template_content
                ?: $this->getDefaultTemplateContent();

            $renderedContent = $this->renderTemplate($rawContent, [
                'decision_number' => $decision->decision_number,
                'decision_date' => $decision->decision_date ? $decision->decision_date->format('d F Y') : date('d F Y'),
                'title' => $decision->title ?: 'SURAT KEPUTUSAN PENETAPAN PENERIMA HIBAH',
                'proposal_number' => $proposal->proposal_number,
                'proposal_title' => $proposal->title,
                'organization_name' => $proposal->organization?->name ?: 'Penerima Hibah',
                'approved_amount' => 'Rp '.number_format((float) ($decision->approved_amount ?: $proposal->requested_amount), 0, ',', '.'),
                'program_name' => $proposal->grantProgram?->name ?: 'Program Hibah SIKOMANDO',
                'fiscal_year' => $proposal->grantProgram?->fiscal_year ?: date('Y'),
                'signer_name' => $decision->issuer?->name ?: $actor->name,
                'summary' => $decision->summary ?: 'Berdasarkan hasil evaluasi, survei lapangan, dan perankingan resmi.',
            ]);

            // 3. Save to private storage
            $disk = 'private';
            $safeName = Str::slug($decision->decision_number ?: 'sk-'.$decision->id);
            $filename = $safeName.'.html';
            $storagePath = 'decisions/'.$decision->id.'/'.$filename;

            Storage::disk($disk)->put($storagePath, $renderedContent);

            $fileSize = strlen($renderedContent);
            $fileHash = hash('sha256', $renderedContent);

            // 4. Create DecisionDocument
            $document = DecisionDocument::create([
                'decision_id' => $decision->id,
                'decision_template_id' => $template?->id,
                'decision_template_version_id' => $templateVersion?->id,
                'uploaded_by' => $actor->id,
                'document_type' => 'decision_letter',
                'document_title' => $decision->title ?: 'Surat Keputusan Penetapan Hibah',
                'original_filename' => $filename,
                'stored_filename' => $filename,
                'disk' => $disk,
                'storage_path' => $storagePath,
                'mime_type' => 'text/html',
                'file_size' => $fileSize,
                'file_hash' => $fileHash,
                'version' => 1,
                'status' => DecisionDocumentStatus::ACTIVE,
                'generated_at' => now(),
                'notes' => $options['notes'] ?? 'Dokumen SK diterbitkan secara otomatis dari sistem.',
            ]);

            // 5. Create version history
            DecisionDocumentVersion::create([
                'decision_document_id' => $document->id,
                'created_by' => $actor->id,
                'version_number' => 1,
                'original_filename' => $filename,
                'stored_filename' => $filename,
                'disk' => $disk,
                'storage_path' => $storagePath,
                'mime_type' => 'text/html',
                'file_size' => $fileSize,
                'file_hash' => $fileHash,
                'status' => 'active',
                'change_notes' => 'Penerbitan versi awal SK.',
                'created_at' => now(),
            ]);

            // 6. Record audit trail
            $this->auditLogService->record(
                action: 'decision.document_generated',
                module: 'decision',
                entityType: DecisionDocument::class,
                entityId: $document->id,
                newValues: [
                    'decision_id' => $decision->id,
                    'document_number' => $decision->decision_number,
                    'template_id' => $template?->id,
                    'template_version_id' => $templateVersion?->id,
                    'storage_path' => $storagePath,
                    'file_hash' => $fileHash,
                ]
            );

            return $document->fresh(['template', 'templateVersion', 'uploader']);
        });
    }

    public function downloadDocument(DecisionDocument $document): StreamedResponse
    {
        $disk = $document->disk ?: 'private';

        if (! Storage::disk($disk)->exists($document->storage_path)) {
            throw ValidationException::withMessages([
                'document' => 'File dokumen keputusan tidak ditemukan pada penyimpanan server.',
            ]);
        }

        return Storage::disk($disk)->download(
            $document->storage_path,
            $document->original_filename ?: 'surat-keputusan.html',
            [
                'Content-Type' => $document->mime_type ?: 'text/html',
            ]
        );
    }

    private function renderTemplate(string $template, array $data): string
    {
        $content = $template;
        foreach ($data as $key => $value) {
            $content = str_replace('{{'.$key.'}}', (string) $value, $content);
            $content = str_replace('{{ '.$key.' }}', (string) $value, $content);
        }

        return $content;
    }

    private function getDefaultTemplateContent(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{title}}</title>
    <style>
        body { font-family: 'Times New Roman', serif; line-height: 1.6; margin: 40px; color: #111; }
        .header { text-align: center; border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 25px; }
        .header h2 { margin: 0; text-transform: uppercase; font-size: 18px; }
        .header h3 { margin: 5px 0; font-size: 16px; font-weight: normal; }
        .content { margin-bottom: 30px; text-align: justify; }
        .table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table td { padding: 6px 10px; vertical-align: top; }
        .table td.label { width: 25%; font-weight: bold; }
        .footer { margin-top: 50px; float: right; width: 300px; text-align: center; }
        .footer .signature { margin-top: 70px; font-weight: bold; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="header">
        <h2>PEMERINTAH REPUBLIK INDONESIA</h2>
        <h3>{{program_name}} TAHUN ANGGARAN {{fiscal_year}}</h3>
        <p><strong>NOMOR: {{decision_number}}</strong></p>
    </div>
    <div class="content">
        <p>Menimbang dan seterusnya, memutuskan dan menetapkan:</p>
        <table class="table">
            <tr>
                <td class="label">Nomor Proposal</td>
                <td>: {{proposal_number}}</td>
            </tr>
            <tr>
                <td class="label">Judul Usulan</td>
                <td>: {{proposal_title}}</td>
            </tr>
            <tr>
                <td class="label">Nama Penerima</td>
                <td>: {{organization_name}}</td>
            </tr>
            <tr>
                <td class="label">Besaran Hibah</td>
                <td>: <strong>{{approved_amount}}</strong></td>
            </tr>
            <tr>
                <td class="label">Keterangan</td>
                <td>: {{summary}}</td>
            </tr>
        </table>
        <p>Keputusan ini berlaku sejak tanggal ditetapkan dengan ketentuan apabila di kemudian hari terdapat kekeliruan akan diperbaiki sebagaimana mestinya.</p>
    </div>
    <div class="footer">
        <p>Ditetapkan pada tanggal: {{decision_date}}</p>
        <p>Pejabat Berwenang,</p>
        <div class="signature">{{signer_name}}</div>
    </div>
</body>
</html>
HTML;
    }
}
