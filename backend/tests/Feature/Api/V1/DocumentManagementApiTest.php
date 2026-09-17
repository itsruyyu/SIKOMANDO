<?php

namespace Tests\Feature\Api\V1;

use App\Enums\DocumentStatus;
use App\Enums\ProposalStatus;
use App\Models\DocumentType;
use App\Models\FieldSurvey;
use App\Models\FieldSurveyDocument;
use App\Models\GrantProgram;
use App\Models\LpjDocument;
use App\Models\LpjSubmission;
use App\Models\Organization;
use App\Models\Proposal;
use App\Models\ProposalDocument;
use App\Models\ProposalDocumentVersion;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentManagementApiTest extends TestCase
{
    use RefreshDatabase;

    private User $pemohon;

    private User $pemohonOther;

    private User $admin;

    private User $auditor;

    private User $surveyor;

    private Organization $organization;

    private Organization $organizationOther;

    private GrantProgram $grantProgram;

    private Proposal $proposal;

    private DocumentType $documentType;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');

        $superAdminRole = Role::query()->firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['id' => (string) Str::uuid(), 'name' => 'Super Administrator', 'is_system' => true, 'is_active' => true]
        );

        $adminRole = Role::query()->firstOrCreate(
            ['code' => 'ADMIN_SIKOMANDO'],
            ['id' => (string) Str::uuid(), 'name' => 'Admin SIKOMANDO', 'is_system' => true, 'is_active' => true]
        );

        $pemohonRole = Role::query()->firstOrCreate(
            ['code' => 'PEMOHON'],
            ['id' => (string) Str::uuid(), 'name' => 'Pemohon', 'is_system' => true, 'is_active' => true]
        );

        $auditorRole = Role::query()->firstOrCreate(
            ['code' => 'AUDITOR'],
            ['id' => (string) Str::uuid(), 'name' => 'Auditor', 'is_system' => true, 'is_active' => true]
        );

        $surveyorRole = Role::query()->firstOrCreate(
            ['code' => 'SURVEYOR'],
            ['id' => (string) Str::uuid(), 'name' => 'Surveyor', 'is_system' => true, 'is_active' => true]
        );

        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole->id);

        $this->auditor = User::factory()->create();
        $this->auditor->roles()->attach($auditorRole->id);

        $this->surveyor = User::factory()->create();
        $this->surveyor->roles()->attach($surveyorRole->id);

        $this->pemohon = User::factory()->create();
        $this->pemohon->roles()->attach($pemohonRole->id);

        $this->pemohonOther = User::factory()->create();
        $this->pemohonOther->roles()->attach($pemohonRole->id);

        $this->organization = Organization::factory()->create([
            'name' => 'Yayasan Komunitas Maju',
        ]);
        $this->pemohon->organizations()->attach($this->organization->id, [
            'id' => (string) Str::uuid(),
            'membership_role' => 'ketua',
        ]);

        $this->organizationOther = Organization::factory()->create([
            'name' => 'Lembaga Lain',
        ]);
        $this->pemohonOther->organizations()->attach($this->organizationOther->id, [
            'id' => (string) Str::uuid(),
            'membership_role' => 'ketua',
        ]);

        $this->grantProgram = GrantProgram::create([
            'id' => (string) Str::uuid(),
            'name' => 'Program Hibah Pemberdayaan 2026',
            'code' => 'HIBAH-2026',
            'fiscal_year' => 2026,
            'budget_allocation' => 1000000000,
            'is_active' => true,
        ]);

        $this->proposal = Proposal::create([
            'id' => (string) Str::uuid(),
            'grant_program_id' => $this->grantProgram->id,
            'organization_id' => $this->organization->id,
            'applicant_id' => $this->pemohon->id,
            'proposal_number' => 'PROP/2026/001',
            'title' => 'Pemberdayaan UMKM Digital',
            'requested_amount' => 50000000,
            'status' => ProposalStatus::DRAFT,
        ]);

        $this->documentType = DocumentType::create([
            'id' => (string) Str::uuid(),
            'code' => 'PROPOSAL_DOCUMENT',
            'name' => 'Dokumen Proposal Lengkap',
            'scope' => 'proposal',
            'is_active' => true,
        ]);
    }

    private function createValidPdf(string $name = 'proposal-rab.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            "%PDF-1.4\n%SIKOMANDO TEST PDF CONTENT\n%%EOF"
        );
    }

    public function test_pemohon_can_upload_valid_proposal_document_using_multipart_file(): void
    {
        Sanctum::actingAs($this->pemohon);

        $file = $this->createValidPdf('dokumen-usulan.pdf');

        $response = $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents", [
            'document_type_id' => $this->documentType->id,
            'notes' => 'Unggahan proposal tahap 1',
            'file' => $file,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.original_filename', 'dokumen-usulan.pdf')
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.status', 'uploaded')
            ->assertJsonPath('data.storage_disk', 'private');

        $documentId = $response->json('data.id');
        $storedFilename = $response->json('data.stored_filename');
        $storagePath = $response->json('data.storage_path');

        // Zero absolute path exposure
        $this->assertStringNotContainsString(':\\', $storagePath);
        $this->assertStringNotContainsString('/var/', $storagePath);

        // Assert physical file exists on private storage disk
        Storage::disk('private')->assertExists($storagePath);

        // Assert ProposalDocument record in database
        $this->assertDatabaseHas('proposal_documents', [
            'id' => $documentId,
            'proposal_id' => $this->proposal->id,
            'document_type_id' => $this->documentType->id,
            'original_filename' => 'dokumen-usulan.pdf',
            'version' => 1,
            'status' => 'uploaded',
        ]);

        // Assert initial ProposalDocumentVersion record created
        $this->assertDatabaseHas('proposal_document_versions', [
            'proposal_document_id' => $documentId,
            'version_number' => 1,
            'original_filename' => 'dokumen-usulan.pdf',
            'status' => 'active',
        ]);
    }

    public function test_pemohon_can_upload_proposal_document_using_metadata_payload(): void
    {
        Sanctum::actingAs($this->pemohon);

        $response = $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents", [
            'document_type_id' => $this->documentType->id,
            'original_filename' => 'metadata-proposal.pdf',
            'notes' => 'Metadata upload test',
            'mime_type' => 'application/pdf',
            'file_size' => 2048,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.original_filename', 'metadata-proposal.pdf')
            ->assertJsonPath('data.version', 1);

        $this->assertDatabaseHas('proposal_documents', [
            'proposal_id' => $this->proposal->id,
            'original_filename' => 'metadata-proposal.pdf',
            'version' => 1,
        ]);
    }

    public function test_upload_fails_when_file_extension_is_blocked_or_executable(): void
    {
        Sanctum::actingAs($this->pemohon);

        $phpFile = UploadedFile::fake()->createWithContent('shell.php', '<?php phpinfo(); ?>');

        $response = $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents", [
            'document_type_id' => $this->documentType->id,
            'file' => $phpFile,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);

        $exeFile = UploadedFile::fake()->createWithContent('installer.exe', 'MZ executable');

        $responseExe = $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents", [
            'document_type_id' => $this->documentType->id,
            'file' => $exeFile,
        ]);

        $responseExe->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_upload_fails_when_file_exceeds_max_size_limit(): void
    {
        Sanctum::actingAs($this->pemohon);

        // 11MB file exceeds 10MB limit
        $oversizedFile = UploadedFile::fake()->create('big-document.pdf', 11 * 1024, 'application/pdf');

        $response = $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents", [
            'document_type_id' => $this->documentType->id,
            'file' => $oversizedFile,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_upload_fails_when_file_is_empty(): void
    {
        Sanctum::actingAs($this->pemohon);

        $emptyFile = UploadedFile::fake()->createWithContent('empty.pdf', '');

        $response = $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents", [
            'document_type_id' => $this->documentType->id,
            'file' => $emptyFile,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_upload_fails_when_filename_has_path_traversal_attempts(): void
    {
        Sanctum::actingAs($this->pemohon);

        // 1. Path traversal in file original name
        $traversalFile = UploadedFile::fake()->createWithContent(
            'traversal..bad.pdf',
            "%PDF-1.4\ncontent\n%%EOF"
        );

        $response = $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents", [
            'document_type_id' => $this->documentType->id,
            'file' => $traversalFile,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);

        // 2. Path traversal in metadata original_filename
        $responseMeta = $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents", [
            'document_type_id' => $this->documentType->id,
            'original_filename' => '../../etc/passwd.pdf',
        ]);

        $responseMeta->assertUnprocessable()
            ->assertJsonValidationErrors(['original_filename']);
    }

    public function test_upload_fails_when_file_magic_bytes_do_not_match_extension(): void
    {
        Sanctum::actingAs($this->pemohon);

        // PDF extension but HTML/plain text content without %PDF- magic bytes
        $fakePdf = UploadedFile::fake()->createWithContent(
            'fake.pdf',
            '<html><body>Spoofed content</body></html>'
        );

        $response = $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents", [
            'document_type_id' => $this->documentType->id,
            'file' => $fakePdf,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_pemohon_can_list_proposal_documents(): void
    {
        Sanctum::actingAs($this->pemohon);

        $file1 = $this->createValidPdf('doc-1.pdf');
        $file2 = $this->createValidPdf('doc-2.pdf');

        $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents", [
            'document_type_id' => $this->documentType->id,
            'file' => $file1,
        ])->assertCreated();

        $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents", [
            'document_type_id' => $this->documentType->id,
            'file' => $file2,
        ])->assertCreated();

        $response = $this->getJson("/api/v1/proposals/{$this->proposal->id}/documents");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_pemohon_can_view_document_detail_and_version_history(): void
    {
        Sanctum::actingAs($this->pemohon);

        $file = $this->createValidPdf('detail-doc.pdf');

        $createRes = $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents", [
            'document_type_id' => $this->documentType->id,
            'file' => $file,
        ])->assertCreated();

        $docId = $createRes->json('data.id');

        // View detail
        $showRes = $this->getJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}");
        $showRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $docId)
            ->assertJsonPath('data.original_filename', 'detail-doc.pdf');

        // View versions
        $versionsRes = $this->getJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}/versions");
        $versionsRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.version_number', 1);
    }

    public function test_pemohon_can_download_proposal_document_stream(): void
    {
        Sanctum::actingAs($this->pemohon);

        $fileContent = "%PDF-1.4\nImportant Proposal Text\n%%EOF";
        $file = UploadedFile::fake()->createWithContent('downloadable.pdf', $fileContent);

        $createRes = $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents", [
            'document_type_id' => $this->documentType->id,
            'file' => $file,
        ])->assertCreated();

        $docId = $createRes->json('data.id');

        $downloadRes = $this->getJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}/download");

        $downloadRes->assertOk();
        $downloadRes->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('Important Proposal Text', $downloadRes->streamedContent());
    }

    public function test_pemohon_can_replace_document_creating_new_version_retaining_old_file(): void
    {
        Sanctum::actingAs($this->pemohon);

        $v1Content = "%PDF-1.4\nVersion 1 Content\n%%EOF";
        $file1 = UploadedFile::fake()->createWithContent('proposal-v1.pdf', $v1Content);

        $uploadRes = $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents", [
            'document_type_id' => $this->documentType->id,
            'file' => $file1,
        ])->assertCreated();

        $docId = $uploadRes->json('data.id');
        $v1Path = $uploadRes->json('data.storage_path');

        // Replace with v2
        $v2Content = "%PDF-1.4\nVersion 2 Content Updated\n%%EOF";
        $file2 = UploadedFile::fake()->createWithContent('proposal-v2-revisi.pdf', $v2Content);

        $replaceRes = $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}/replace", [
            'file' => $file2,
            'change_notes' => 'Pembaruan rincian anggaran sesuai koreksi',
        ]);

        $replaceRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.original_filename', 'proposal-v2-revisi.pdf');

        $v2Path = $replaceRes->json('data.storage_path');

        // Assert both files exist physically in private storage
        Storage::disk('private')->assertExists($v1Path);
        Storage::disk('private')->assertExists($v2Path);
        $this->assertNotEquals($v1Path, $v2Path);

        // Assert database has 2 versions
        $this->assertDatabaseCount('proposal_document_versions', 2);
        $this->assertDatabaseHas('proposal_document_versions', [
            'proposal_document_id' => $docId,
            'version_number' => 1,
            'status' => 'superseded',
        ]);
        $this->assertDatabaseHas('proposal_document_versions', [
            'proposal_document_id' => $docId,
            'version_number' => 2,
            'status' => 'active',
        ]);
    }

    public function test_pemohon_can_download_specific_older_version_of_document(): void
    {
        Sanctum::actingAs($this->pemohon);

        $v1Content = "%PDF-1.4\nHistoric Version 1 Data\n%%EOF";
        $file1 = UploadedFile::fake()->createWithContent('proposal-old.pdf', $v1Content);

        $uploadRes = $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents", [
            'document_type_id' => $this->documentType->id,
            'file' => $file1,
        ])->assertCreated();

        $docId = $uploadRes->json('data.id');

        $v2Content = "%PDF-1.4\nNewer Version 2 Data\n%%EOF";
        $file2 = UploadedFile::fake()->createWithContent('proposal-new.pdf', $v2Content);

        $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}/replace", [
            'file' => $file2,
        ])->assertOk();

        // Download v1 explicitly
        $downloadV1 = $this->getJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}/download?version=1");
        $downloadV1->assertOk();
        $this->assertStringContainsString('Historic Version 1 Data', $downloadV1->streamedContent());

        // Download current (v2)
        $downloadV2 = $this->getJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}/download");
        $downloadV2->assertOk();
        $this->assertStringContainsString('Newer Version 2 Data', $downloadV2->streamedContent());
    }

    public function test_cross_organization_or_unauthorized_user_cannot_access_or_download_document(): void
    {
        Sanctum::actingAs($this->pemohon);

        $file = $this->createValidPdf('confidential.pdf');
        $uploadRes = $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents", [
            'document_type_id' => $this->documentType->id,
            'file' => $file,
        ])->assertCreated();

        $docId = $uploadRes->json('data.id');

        // Switch to other pemohon
        Sanctum::actingAs($this->pemohonOther);

        // Attempt index
        $this->getJson("/api/v1/proposals/{$this->proposal->id}/documents")
            ->assertForbidden();

        // Attempt show
        $this->getJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}")
            ->assertForbidden();

        // Attempt download
        $this->getJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}/download")
            ->assertForbidden();

        // Attempt replace
        $replaceFile = $this->createValidPdf('tamper.pdf');
        $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}/replace", [
            'file' => $replaceFile,
        ])->assertForbidden();

        // Attempt delete
        $this->deleteJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}")
            ->assertForbidden();
    }

    public function test_cross_parent_id_scoping_prevents_accessing_document_with_wrong_proposal_id(): void
    {
        Sanctum::actingAs($this->pemohon);

        $file = $this->createValidPdf('scoping-doc.pdf');
        $uploadRes = $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents", [
            'document_type_id' => $this->documentType->id,
            'file' => $file,
        ])->assertCreated();

        $docId = $uploadRes->json('data.id');

        // Create proposal B belonging to the same pemohon
        $proposalB = Proposal::create([
            'id' => (string) Str::uuid(),
            'grant_program_id' => $this->grantProgram->id,
            'organization_id' => $this->organization->id,
            'applicant_id' => $this->pemohon->id,
            'proposal_number' => 'PROP/2026/002',
            'title' => 'Proposal Kedua',
            'requested_amount' => 30000000,
            'status' => ProposalStatus::DRAFT,
        ]);

        // Attempt accessing document of Proposal A through Proposal B route
        $this->getJson("/api/v1/proposals/{$proposalB->id}/documents/{$docId}")
            ->assertNotFound();

        $this->getJson("/api/v1/proposals/{$proposalB->id}/documents/{$docId}/download")
            ->assertNotFound();

        $this->deleteJson("/api/v1/proposals/{$proposalB->id}/documents/{$docId}")
            ->assertNotFound();
    }

    public function test_auditor_can_view_and_download_documents_read_only(): void
    {
        Sanctum::actingAs($this->pemohon);

        $file = $this->createValidPdf('auditor-check.pdf');
        $uploadRes = $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents", [
            'document_type_id' => $this->documentType->id,
            'file' => $file,
        ])->assertCreated();

        $docId = $uploadRes->json('data.id');

        // Switch to auditor
        Sanctum::actingAs($this->auditor);

        // Read operations permitted
        $this->getJson("/api/v1/proposals/{$this->proposal->id}/documents")->assertOk();
        $this->getJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}")->assertOk();
        $this->getJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}/download")->assertOk();
        $this->getJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}/versions")->assertOk();

        // Mutating operations forbidden for auditor
        $replaceFile = $this->createValidPdf('auditor-tamper.pdf');
        $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}/replace", [
            'file' => $replaceFile,
        ])->assertForbidden();

        $this->deleteJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}")
            ->assertForbidden();
    }

    public function test_replacement_and_deletion_are_prevented_when_proposal_is_finalized_or_document_verified(): void
    {
        Sanctum::actingAs($this->pemohon);

        $file = $this->createValidPdf('finalized-check.pdf');
        $uploadRes = $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents", [
            'document_type_id' => $this->documentType->id,
            'file' => $file,
        ])->assertCreated();

        $docId = $uploadRes->json('data.id');

        // 1. When document is marked verified and proposal not in revision
        ProposalDocument::where('id', $docId)->update(['status' => DocumentStatus::VERIFIED->value]);

        $replaceFile = $this->createValidPdf('cannot-replace.pdf');
        $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}/replace", [
            'file' => $replaceFile,
        ])->assertForbidden();

        $this->deleteJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}")
            ->assertForbidden();

        // 2. When proposal is approved (finalized state)
        ProposalDocument::where('id', $docId)->update(['status' => DocumentStatus::UPLOADED->value]);
        $this->proposal->update(['status' => ProposalStatus::APPROVED]);

        Sanctum::actingAs($this->admin);
        $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}/replace", [
            'file' => $replaceFile,
        ])->assertUnprocessable();

        $this->deleteJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}")
            ->assertUnprocessable();
    }

    public function test_document_actions_record_structured_audit_logs(): void
    {
        Sanctum::actingAs($this->pemohon);

        // 1. Upload
        $file1 = $this->createValidPdf('audit-sample.pdf');
        $uploadRes = $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents", [
            'document_type_id' => $this->documentType->id,
            'file' => $file1,
        ])->assertCreated();

        $docId = $uploadRes->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'proposal_document.uploaded',
            'module' => 'document',
            'entity_id' => $docId,
        ]);

        // 2. Download
        $this->getJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}/download")->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'proposal_document.downloaded',
            'module' => 'document',
            'entity_id' => $docId,
        ]);

        // 3. Replace
        $file2 = $this->createValidPdf('audit-sample-v2.pdf');
        $this->postJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}/replace", [
            'file' => $file2,
        ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'proposal_document.replaced',
            'module' => 'document',
            'entity_id' => $docId,
        ]);

        // 4. Delete
        $this->deleteJson("/api/v1/proposals/{$this->proposal->id}/documents/{$docId}", [
            'reason' => 'Dokumen keliru diunggah',
        ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'proposal_document.deleted',
            'module' => 'document',
            'entity_id' => $docId,
        ]);
    }

    public function test_authorized_surveyor_and_admin_can_download_field_survey_document(): void
    {
        // 1. Setup field survey
        $survey = FieldSurvey::create([
            'id' => (string) Str::uuid(),
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
            'scheduled_date' => now()->toDateString(),
            'status' => 'in_progress',
        ]);

        $filePath = "field-surveys/{$survey->id}/documents/survey-evidence.pdf";
        Storage::disk('private')->put($filePath, "%PDF-1.4\nSurvey Evidence Photo/Report\n%%EOF");

        $surveyDoc = FieldSurveyDocument::create([
            'id' => (string) Str::uuid(),
            'field_survey_id' => $survey->id,
            'uploaded_by' => $this->surveyor->id,
            'document_title' => 'Bukti Lapangan',
            'original_filename' => 'survey-evidence.pdf',
            'stored_filename' => 'survey-evidence.pdf',
            'disk' => 'private',
            'storage_path' => $filePath,
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'file_hash' => hash('sha256', 'test'),
            'version' => 1,
            'status' => 'uploaded',
        ]);

        // Surveyor can download
        Sanctum::actingAs($this->surveyor);
        $res = $this->getJson("/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}/documents/{$surveyDoc->id}/download");
        $res->assertOk();
        $this->assertStringContainsString('Survey Evidence Photo/Report', $res->streamedContent());

        // Admin can download
        Sanctum::actingAs($this->admin);
        $resAdmin = $this->getJson("/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}/documents/{$surveyDoc->id}/download");
        $resAdmin->assertOk();

        // Pemohon cannot download field survey document (403)
        Sanctum::actingAs($this->pemohon);
        $this->getJson("/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}/documents/{$surveyDoc->id}/download")
            ->assertForbidden();
    }

    public function test_pemohon_and_approver_can_download_lpj_document(): void
    {
        $lpj = LpjSubmission::create([
            'id' => (string) Str::uuid(),
            'proposal_id' => $this->proposal->id,
            'organization_id' => $this->organization->id,
            'submitted_by' => $this->pemohon->id,
            'lpj_number' => 'LPJ/2026/001',
            'status' => 'submitted',
            'total_realized_amount' => 50000000,
        ]);

        $filePath = "lpj/{$lpj->id}/documents/receipt.pdf";
        Storage::disk('private')->put($filePath, "%PDF-1.4\nLPJ Receipt Evidence\n%%EOF");

        $lpjDoc = LpjDocument::create([
            'id' => (string) Str::uuid(),
            'lpj_submission_id' => $lpj->id,
            'uploaded_by' => $this->pemohon->id,
            'document_type' => 'receipt',
            'document_title' => 'Kwitansi Pengeluaran',
            'original_filename' => 'receipt.pdf',
            'stored_filename' => 'receipt.pdf',
            'disk' => 'private',
            'storage_path' => $filePath,
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'file_hash' => hash('sha256', 'receipt-test'),
            'version' => 1,
            'status' => 'uploaded',
        ]);

        // Pemohon owner can download
        Sanctum::actingAs($this->pemohon);
        $res = $this->getJson("/api/v1/lpj/{$lpj->id}/documents/{$lpjDoc->id}/download");
        $res->assertOk();
        $this->assertStringContainsString('LPJ Receipt Evidence', $res->streamedContent());

        // Admin can download
        Sanctum::actingAs($this->admin);
        $resAdmin = $this->getJson("/api/v1/lpj/{$lpj->id}/documents/{$lpjDoc->id}/download");
        $resAdmin->assertOk();

        // Other pemohon cannot download (403)
        Sanctum::actingAs($this->pemohonOther);
        $this->getJson("/api/v1/lpj/{$lpj->id}/documents/{$lpjDoc->id}/download")
            ->assertForbidden();
    }
}
