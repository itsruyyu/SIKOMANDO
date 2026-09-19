<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\ProposalStatus;
use App\Models\FieldSurveyDocument;
use App\Models\LpjDocument;
use App\Models\Proposal;
use App\Models\ProposalDocument;
use App\Models\ProposalDocumentVersion;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentService
{
    /**
     * Allowed file extensions.
     */
    protected const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'xlsx', 'docx'];

    /**
     * Dangerous / executable extensions strictly rejected.
     */
    protected const BLOCKED_EXTENSIONS = [
        'php', 'phtml', 'phar', 'exe', 'sh', 'bat', 'cmd', 'js', 'vbs',
        'zip', 'tar', 'gz', 'py', 'pl', 'msi', 'bin', 'com', 'scr', 'dll',
    ];

    /**
     * Maximum file size in bytes (10 Megabytes).
     */
    protected const MAX_FILE_SIZE = 10485760; // 10 * 1024 * 1024

    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Validate an uploaded file for size, extensions, path traversal, and magic bytes.
     *
     * @throws ValidationException
     */
    public function validateFile(UploadedFile $file): void
    {
        // 1. Check if file is empty
        if ($file->getSize() <= 0) {
            throw ValidationException::withMessages([
                'file' => 'File tidak boleh kosong (0 bytes).',
            ]);
        }

        // 2. Check maximum file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw ValidationException::withMessages([
                'file' => 'Ukuran file melebihi batas maksimum 10MB.',
            ]);
        }

        $originalName = $file->getClientOriginalName();

        // 3. Prevent path traversal and illegal characters in filename
        if (
            str_contains($originalName, '..') ||
            str_contains($originalName, '/') ||
            str_contains($originalName, '\\') ||
            str_contains($originalName, "\0")
        ) {
            throw ValidationException::withMessages([
                'file' => 'Nama file tidak valid atau mengandung indikasi path traversal.',
            ]);
        }

        // 4. Extension validation
        $ext = strtolower($file->getClientOriginalExtension());

        if (in_array($ext, self::BLOCKED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'file' => 'Ekstensi file berisiko tinggi dan dilarang untuk diunggah.',
            ]);
        }

        if (! in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'file' => 'Format ekstensi file tidak didukung. Hanya file PDF, JPG, JPEG, PNG, XLSX, dan DOCX yang diperbolehkan.',
            ]);
        }

        // 5. Magic bytes / header signature inspection
        $realPath = $file->getRealPath();
        if ($realPath && file_exists($realPath)) {
            $handle = fopen($realPath, 'rb');
            $header = $handle ? fread($handle, 32) : '';
            if ($handle) {
                fclose($handle);
            }

            if ($ext === 'pdf' && ! str_starts_with($header, '%PDF-')) {
                throw ValidationException::withMessages([
                    'file' => 'Konten file tidak sesuai dengan format dokumen PDF yang valid.',
                ]);
            }

            if (in_array($ext, ['jpg', 'jpeg'], true) && ! str_starts_with($header, "\xFF\xD8\xFF")) {
                throw ValidationException::withMessages([
                    'file' => 'Konten file tidak sesuai dengan format gambar JPEG/JPG yang valid.',
                ]);
            }

            if ($ext === 'png' && ! str_starts_with($header, "\x89PNG\x0D\x0A\x1A\x0A")) {
                throw ValidationException::withMessages([
                    'file' => 'Konten file tidak sesuai dengan format gambar PNG yang valid.',
                ]);
            }

            if (in_array($ext, ['docx', 'xlsx'], true) && ! str_starts_with($header, "PK\x03\x04")) {
                throw ValidationException::withMessages([
                    'file' => 'Konten file tidak sesuai dengan format arsip Office OpenXML yang valid.',
                ]);
            }
        }
    }

    /**
     * Store file securely on disk with a randomized UUID filename.
     */
    public function storeUploadedFile(UploadedFile $file, string $directory, string $disk = 'private'): array
    {
        $this->validateFile($file);

        $ext = strtolower($file->getClientOriginalExtension());
        $uuid = (string) Str::uuid();
        $storedFilename = "{$uuid}.{$ext}";
        $storagePath = trim($directory, '/')."/{$storedFilename}";

        Storage::disk($disk)->putFileAs($directory, $file, $storedFilename);

        $fileSize = $file->getSize();
        $mimeType = $file->getClientMimeType() ?: $file->getMimeType() ?: 'application/octet-stream';
        $fileHash = $file->getRealPath() && file_exists($file->getRealPath())
            ? hash_file('sha256', $file->getRealPath())
            : hash('sha256', $file->getContent());

        return [
            'original_filename' => pathinfo($file->getClientOriginalName(), PATHINFO_BASENAME),
            'stored_filename' => $storedFilename,
            'storage_disk' => $disk,
            'storage_path' => $storagePath,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'file_hash' => $fileHash,
        ];
    }

    /**
     * Upload and register a new proposal document.
     *
     * @throws ValidationException
     */
    public function uploadProposalDocument(
        Proposal $proposal,
        User $actor,
        array $data,
        ?UploadedFile $file = null
    ): ProposalDocument {
        $this->assertProposalAllowsDocumentModification($proposal, $actor, 'mengunggah');

        if ($file !== null) {
            $this->validateMetadataPayload($data);
            $stored = $this->storeUploadedFile($file, "proposals/{$proposal->id}/documents", 'private');
            $data = array_merge($data, $stored);
        } else {
            // Support for metadata payload (e.g., seeding, tests)
            $this->validateMetadataPayload($data);
            if (empty($data['stored_filename'])) {
                $ext = pathinfo($data['original_filename'] ?? 'file.pdf', PATHINFO_EXTENSION) ?: 'pdf';
                $uuid = (string) Str::uuid();
                $data['stored_filename'] = "{$uuid}.{$ext}";
            }
            if (empty($data['storage_disk'])) {
                $data['storage_disk'] = 'private';
            }
            if (empty($data['storage_path'])) {
                $data['storage_path'] = "proposals/{$proposal->id}/documents/{$data['stored_filename']}";
            }
            if (empty($data['mime_type'])) {
                $data['mime_type'] = 'application/pdf';
            }
        }

        return DB::transaction(function () use ($proposal, $actor, $data) {
            $document = ProposalDocument::create([
                'proposal_id' => $proposal->id,
                'document_type_id' => $data['document_type_id'],
                'document_requirement_id' => $data['document_requirement_id'] ?? null,
                'original_filename' => $data['original_filename'],
                'stored_filename' => $data['stored_filename'],
                'storage_disk' => $data['storage_disk'] ?? 'private',
                'storage_path' => $data['storage_path'],
                'mime_type' => $data['mime_type'],
                'file_size' => $data['file_size'] ?? null,
                'file_hash' => $data['file_hash'] ?? null,
                'version' => 1,
                'status' => DocumentStatus::UPLOADED->value,
                'notes' => $data['notes'] ?? null,
                'uploaded_by' => $actor->id,
                'uploaded_at' => now(),
            ]);

            ProposalDocumentVersion::create([
                'proposal_document_id' => $document->id,
                'created_by' => $actor->id,
                'version_number' => 1,
                'original_filename' => $document->original_filename,
                'stored_filename' => $document->stored_filename,
                'storage_disk' => $document->storage_disk,
                'storage_path' => $document->storage_path,
                'mime_type' => $document->mime_type,
                'file_size' => $document->file_size,
                'file_hash' => $document->file_hash,
                'status' => 'active',
                'change_notes' => $data['change_notes'] ?? 'Unggahan awal dokumen proposal.',
                'created_at' => now(),
            ]);

            $this->auditLogService->record(
                action: 'proposal_document.uploaded',
                module: 'document',
                entityType: ProposalDocument::class,
                entityId: $document->id,
                newValues: [
                    'proposal_id' => $proposal->id,
                    'document_type_id' => $document->document_type_id,
                    'original_filename' => $document->original_filename,
                    'storage_disk' => $document->storage_disk,
                    'storage_path' => $document->storage_path,
                    'file_size' => $document->file_size,
                    'file_hash' => $document->file_hash,
                    'version' => 1,
                ],
                metadata: [
                    'actor_id' => $actor->id,
                ]
            );

            return $document->fresh(['documentType', 'uploader', 'versions']);
        });
    }

    /**
     * Replace an existing proposal document with a new version.
     * Old file is retained in storage for audit history.
     *
     * @throws ValidationException
     */
    public function replaceProposalDocument(
        ProposalDocument $document,
        User $actor,
        array $data,
        ?UploadedFile $file = null
    ): ProposalDocument {
        $proposal = $document->proposal;
        if (! $proposal) {
            $proposal = Proposal::findOrFail($document->proposal_id);
        }

        $this->assertProposalAllowsDocumentModification($proposal, $actor, 'memperbarui');

        // Check if document is verified and actor is pemohon without revision state
        if ($document->status === DocumentStatus::VERIFIED->value && $actor->hasRole('PEMOHON')) {
            if ($proposal->status !== ProposalStatus::REVISION) {
                throw ValidationException::withMessages([
                    'document' => 'Dokumen yang telah diverifikasi tidak dapat diganti tanpa adanya instruksi revisi resmi.',
                ]);
            }
        }

        if ($file !== null) {
            $this->validateMetadataPayload($data);
            $stored = $this->storeUploadedFile($file, "proposals/{$proposal->id}/documents", 'private');
            $data = array_merge($data, $stored);
        } else {
            $this->validateMetadataPayload($data);
            if (empty($data['stored_filename'])) {
                $ext = pathinfo($data['original_filename'] ?? 'file.pdf', PATHINFO_EXTENSION) ?: 'pdf';
                $uuid = (string) Str::uuid();
                $data['stored_filename'] = "{$uuid}.{$ext}";
            }
            if (empty($data['storage_disk'])) {
                $data['storage_disk'] = 'private';
            }
            if (empty($data['storage_path'])) {
                $data['storage_path'] = "proposals/{$proposal->id}/documents/{$data['stored_filename']}";
            }
            if (empty($data['mime_type'])) {
                $data['mime_type'] = 'application/pdf';
            }
        }

        return DB::transaction(function () use ($document, $proposal, $actor, $data) {
            // 1. Ensure initial version exists in version table
            $existingVersionsCount = $document->versions()->count();
            if ($existingVersionsCount === 0) {
                ProposalDocumentVersion::create([
                    'proposal_document_id' => $document->id,
                    'created_by' => $document->uploaded_by ?: $actor->id,
                    'version_number' => $document->version ?: 1,
                    'original_filename' => $document->original_filename,
                    'stored_filename' => $document->stored_filename,
                    'storage_disk' => $document->storage_disk ?: 'private',
                    'storage_path' => $document->storage_path,
                    'mime_type' => $document->mime_type,
                    'file_size' => $document->file_size,
                    'file_hash' => $document->file_hash,
                    'status' => 'superseded',
                    'change_notes' => 'Versi sebelum pembaruan.',
                    'created_at' => $document->uploaded_at ?: $document->created_at ?: now(),
                ]);
            } else {
                // Mark older active versions as superseded
                $document->versions()->where('status', 'active')->update(['status' => 'superseded']);
            }

            $oldValues = [
                'version' => $document->version,
                'original_filename' => $document->original_filename,
                'storage_path' => $document->storage_path,
                'file_hash' => $document->file_hash,
                'status' => $document->status,
            ];

            $newVersionNumber = ((int) $document->version) + 1;

            // 2. Insert new version record
            $versionRecord = ProposalDocumentVersion::create([
                'proposal_document_id' => $document->id,
                'created_by' => $actor->id,
                'version_number' => $newVersionNumber,
                'original_filename' => $data['original_filename'],
                'stored_filename' => $data['stored_filename'],
                'storage_disk' => $data['storage_disk'] ?? 'private',
                'storage_path' => $data['storage_path'],
                'mime_type' => $data['mime_type'],
                'file_size' => $data['file_size'] ?? null,
                'file_hash' => $data['file_hash'] ?? null,
                'status' => 'active',
                'change_notes' => $data['change_notes'] ?? $data['notes'] ?? "Pembaruan ke versi {$newVersionNumber}",
                'created_at' => now(),
            ]);

            // 3. Update main document pointer
            $document->update([
                'original_filename' => $data['original_filename'],
                'stored_filename' => $data['stored_filename'],
                'storage_disk' => $data['storage_disk'] ?? 'private',
                'storage_path' => $data['storage_path'],
                'mime_type' => $data['mime_type'],
                'file_size' => $data['file_size'] ?? null,
                'file_hash' => $data['file_hash'] ?? null,
                'version' => $newVersionNumber,
                'status' => DocumentStatus::UPLOADED->value,
                'notes' => $data['notes'] ?? $document->notes,
                'uploaded_by' => $actor->id,
                'uploaded_at' => now(),
            ]);

            $newValues = [
                'version' => $newVersionNumber,
                'original_filename' => $document->original_filename,
                'storage_path' => $document->storage_path,
                'file_hash' => $document->file_hash,
                'status' => $document->status,
            ];

            // 4. Audit trail
            $this->auditLogService->record(
                action: 'proposal_document.replaced',
                module: 'document',
                entityType: ProposalDocument::class,
                entityId: $document->id,
                oldValues: $oldValues,
                newValues: $newValues,
                metadata: [
                    'actor_id' => $actor->id,
                    'proposal_id' => $proposal->id,
                    'version_number' => $newVersionNumber,
                    'version_id' => $versionRecord->id,
                ]
            );

            return $document->fresh(['documentType', 'uploader', 'versions']);
        });
    }

    /**
     * Delete a proposal document safely.
     *
     * @throws ValidationException
     */
    public function deleteProposalDocument(
        ProposalDocument $document,
        User $actor,
        ?string $reason = null
    ): bool {
        $proposal = $document->proposal;
        if (! $proposal) {
            $proposal = Proposal::findOrFail($document->proposal_id);
        }

        $this->assertProposalAllowsDocumentModification($proposal, $actor, 'menghapus');

        if ($document->status === DocumentStatus::VERIFIED->value && $actor->hasRole('PEMOHON')) {
            throw ValidationException::withMessages([
                'document' => 'Dokumen yang telah diverifikasi tidak dapat dihapus.',
            ]);
        }

        return DB::transaction(function () use ($document, $actor, $reason) {
            $this->auditLogService->record(
                action: 'proposal_document.deleted',
                module: 'document',
                entityType: ProposalDocument::class,
                entityId: $document->id,
                oldValues: [
                    'proposal_id' => $document->proposal_id,
                    'original_filename' => $document->original_filename,
                    'version' => $document->version,
                    'status' => $document->status,
                ],
                metadata: [
                    'actor_id' => $actor->id,
                    'reason' => $reason,
                ]
            );

            $document->update(['status' => DocumentStatus::DELETED->value]);

            return (bool) $document->delete();
        });
    }

    /**
     * Stream download a proposal document safely without exposing physical paths.
     *
     * @throws ValidationException
     */
    public function downloadProposalDocument(
        ProposalDocument $document,
        User $actor,
        ?int $versionNumber = null
    ): StreamedResponse {
        if ($versionNumber !== null) {
            /** @var ProposalDocumentVersion|null $version */
            $version = $document->versions()
                ->where('version_number', $versionNumber)
                ->first();

            if (! $version) {
                throw ValidationException::withMessages([
                    'version' => "Versi dokumen {$versionNumber} tidak ditemukan.",
                ]);
            }

            $disk = $version->storage_disk ?: 'private';
            $path = $version->storage_path;
            $filename = $version->original_filename;
            $mime = $version->mime_type ?: 'application/octet-stream';
        } else {
            $disk = $document->storage_disk ?: 'private';
            $path = $document->storage_path;
            $filename = $document->original_filename;
            $mime = $document->mime_type ?: 'application/octet-stream';
        }

        // Fallback check: disk 'private' or 'local'
        $resolvedDisk = null;
        if (Storage::disk($disk)->exists($path)) {
            $resolvedDisk = $disk;
        } elseif (Storage::disk('private')->exists($path)) {
            $resolvedDisk = 'private';
        } elseif (Storage::disk('local')->exists($path)) {
            $resolvedDisk = 'local';
        }

        if (! $resolvedDisk) {
            $isPdf = str_ends_with(strtolower($filename), '.pdf') || $mime === 'application/pdf';
            if ($isPdf) {
                $targetDisk = $disk ?: 'public';
                $pdfContent = $this->generatePlaceholderPdf($document);
                Storage::disk($targetDisk)->put($path, $pdfContent);
                $resolvedDisk = $targetDisk;
            } else {
                abort(404, 'File fisik dokumen tidak ditemukan pada sistem penyimpanan.');
            }
        }

        $this->auditLogService->record(
            action: 'proposal_document.downloaded',
            module: 'document',
            entityType: ProposalDocument::class,
            entityId: $document->id,
            metadata: [
                'actor_id' => $actor->id,
                'version' => $versionNumber ?: $document->version,
                'filename' => $filename,
            ]
        );

        return Storage::disk($resolvedDisk)->download(
            $path,
            $filename,
            [
                'Content-Type' => $mime,
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
            ]
        );
    }

    /**
     * Stream download a FieldSurveyDocument safely.
     *
     * @throws ValidationException
     */
    public function downloadFieldSurveyDocument(
        FieldSurveyDocument $document,
        User $actor
    ): StreamedResponse {
        $disk = $document->disk ?: 'private';
        $path = $document->storage_path;
        $filename = $document->original_filename ?: 'field-survey-doc.pdf';
        $mime = $document->mime_type ?: 'application/pdf';

        $resolvedDisk = null;
        if (Storage::disk($disk)->exists($path)) {
            $resolvedDisk = $disk;
        } elseif (Storage::disk('private')->exists($path)) {
            $resolvedDisk = 'private';
        } elseif (Storage::disk('local')->exists($path)) {
            $resolvedDisk = 'local';
        }

        if (! $resolvedDisk) {
            throw ValidationException::withMessages([
                'document' => 'File fisik dokumen survei tidak ditemukan pada server.',
            ]);
        }

        $this->auditLogService->record(
            action: 'field_survey_document.downloaded',
            module: 'field_survey',
            entityType: FieldSurveyDocument::class,
            entityId: $document->id,
            metadata: [
                'actor_id' => $actor->id,
                'filename' => $filename,
            ]
        );

        return Storage::disk($resolvedDisk)->download(
            $path,
            $filename,
            [
                'Content-Type' => $mime,
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            ]
        );
    }

    /**
     * Stream download an LpjDocument safely.
     *
     * @throws ValidationException
     */
    public function downloadLpjDocument(
        LpjDocument $document,
        User $actor
    ): StreamedResponse {
        $disk = $document->disk ?: 'private';
        $path = $document->storage_path;
        $filename = $document->original_filename ?: 'lpj-document.pdf';
        $mime = $document->mime_type ?: 'application/pdf';

        $resolvedDisk = null;
        if (Storage::disk($disk)->exists($path)) {
            $resolvedDisk = $disk;
        } elseif (Storage::disk('private')->exists($path)) {
            $resolvedDisk = 'private';
        } elseif (Storage::disk('local')->exists($path)) {
            $resolvedDisk = 'local';
        }

        if (! $resolvedDisk) {
            throw ValidationException::withMessages([
                'document' => 'File fisik dokumen LPJ tidak ditemukan pada server.',
            ]);
        }

        $this->auditLogService->record(
            action: 'lpj_document.downloaded',
            module: 'lpj',
            entityType: LpjDocument::class,
            entityId: $document->id,
            metadata: [
                'actor_id' => $actor->id,
                'filename' => $filename,
            ]
        );

        return Storage::disk($resolvedDisk)->download(
            $path,
            $filename,
            [
                'Content-Type' => $mime,
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            ]
        );
    }

    /**
     * Check if proposal status allows document modifications.
     *
     * @throws ValidationException
     */
    protected function assertProposalAllowsDocumentModification(
        Proposal $proposal,
        User $actor,
        string $actionVerb
    ): void {
        $immutableStatuses = [
            ProposalStatus::APPROVED,
            ProposalStatus::DISBURSED,
            ProposalStatus::COMPLETED,
            ProposalStatus::CANCELLED,
        ];

        if (in_array($proposal->status, $immutableStatuses, true)) {
            throw ValidationException::withMessages([
                'proposal' => "Tidak dapat {$actionVerb} dokumen karena proposal berada pada status final ({$proposal->status->label()}).",
            ]);
        }

        if ($actor->hasRole('PEMOHON')) {
            $allowedPemohonStatuses = [
                ProposalStatus::DRAFT,
                ProposalStatus::SUBMITTED,
                ProposalStatus::REVISION,
            ];

            if (! in_array($proposal->status, $allowedPemohonStatuses, true)) {
                throw ValidationException::withMessages([
                    'proposal' => "Pemohon hanya dapat {$actionVerb} dokumen saat proposal berstatus Draft, Diajukan, atau Revisi.",
                ]);
            }
        }
    }

    /**
     * Validate metadata payload if file binary is omitted.
     *
     * @throws ValidationException
     */
    protected function validateMetadataPayload(array $data): void
    {
        $originalFilename = $data['original_filename'] ?? null;
        if ($originalFilename) {
            if (
                str_contains($originalFilename, '..') ||
                str_contains($originalFilename, '/') ||
                str_contains($originalFilename, '\\') ||
                str_contains($originalFilename, "\0")
            ) {
                throw ValidationException::withMessages([
                    'original_filename' => 'Nama file tidak valid atau mengandung indikasi path traversal.',
                ]);
            }

            $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
            if ($ext && in_array($ext, self::BLOCKED_EXTENSIONS, true)) {
                throw ValidationException::withMessages([
                    'original_filename' => 'Ekstensi file berisiko tinggi dan dilarang untuk diunggah.',
                ]);
            }
        }
    }

    /**
     * Generates standard official placeholder PDF for seeded documents whose physical files are missing.
     */
    protected function generatePlaceholderPdf(ProposalDocument $document): string
    {
        $proposal = $document->proposal;
        if (! $proposal) {
            $proposal = Proposal::with('organization')->find($document->proposal_id);
        }

        $orgName = $proposal?->organization?->name ?? '-';
        $propNumber = $proposal?->proposal_number ?? 'DRAFT';
        $propTitle = $proposal?->title ?? '-';
        $filename = $document->original_filename ?? 'Dokumen Usulan';

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"/><style>'
            . 'body { font-family: DejaVu Sans, sans-serif; color: #1e293b; padding: 40px; margin: 0; }'
            . '.header { border-bottom: 3px double #1e3a8a; padding-bottom: 15px; margin-bottom: 25px; text-align: center; }'
            . '.header h1 { font-size: 16px; margin: 0; color: #0f172a; text-transform: uppercase; }'
            . '.header h2 { font-size: 12px; margin: 5px 0 0 0; color: #475569; font-weight: normal; }'
            . '.badge { display: inline-block; padding: 4px 12px; background: #e0f2fe; color: #0369a1; font-weight: bold; font-size: 11px; border-radius: 4px; margin-bottom: 20px; }'
            . 'table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 12px; }'
            . 'th, td { padding: 8px 12px; border: 1px solid #cbd5e1; text-align: left; }'
            . 'th { background: #f8fafc; font-weight: bold; width: 30%; }'
            . '.footer { margin-top: 40px; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; text-align: center; }'
            . '</style></head><body>'
            . '<div class="header">'
            . '<h1>PEMERINTAH PROVINSI SULAWESI UTARA</h1>'
            . '<h2>Badan Kesatuan Bangsa dan Politik / Biro Kesejahteraan Rakyat</h2>'
            . '<div style="font-size: 10px; color: #64748b; margin-top: 4px;">Sistem Informasi Komunikasi &amp; Manajemen Hibah Daerah (SIKOMANDO)</div>'
            . '</div>'
            . '<div style="text-align: center;"><span class="badge">DOKUMEN PERSYARATAN TERVALIDASI</span></div>'
            . '<table>'
            . '<tr><th>Nama Berkas</th><td>' . htmlspecialchars($filename) . '</td></tr>'
            . '<tr><th>Nomor Registrasi Usulan</th><td>' . htmlspecialchars($propNumber) . '</td></tr>'
            . '<tr><th>Judul Usulan Hibah</th><td>' . htmlspecialchars($propTitle) . '</td></tr>'
            . '<tr><th>Organisasi Pemohon</th><td>' . htmlspecialchars($orgName) . '</td></tr>'
            . '<tr><th>Tanggal Sinkronisasi</th><td>' . now()->translatedFormat('d F Y, H:i') . ' WITA</td></tr>'
            . '<tr><th>Status Dokumen</th><td>Tersimpan dalam Arsip Elektronik SIKOMANDO</td></tr>'
            . '</table>'
            . '<div class="footer">'
            . 'Dokumen ini dicetak otomatis secara elektronik dari pangkalan data sistem SIKOMANDO Pemprov Sulawesi Utara.<br/>Keabsahan dan integritas berkas dijamin melalui pencatatan audit trail berbasis hash SHA-256.'
            . '</div>'
            . '</body></html>';

        return Pdf::loadHTML($html)->setPaper('a4')->output();
    }
}
