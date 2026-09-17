<?php

namespace Tests\Feature\Api\V1\Public;

use App\Enums\AnnouncementStatus;
use App\Enums\DisbursementPlanStatus;
use App\Enums\DisbursementStatus;
use App\Enums\ProposalStatus;
use App\Models\Announcement;
use App\Models\Disbursement;
use App\Models\DisbursementPlan;
use App\Models\DocumentRequirement;
use App\Models\DocumentType;
use App\Models\GrantProgram;
use App\Models\Organization;
use App\Models\Proposal;
use App\Models\Requirement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicPortalApiTest extends TestCase
{
    use RefreshDatabase;

    private GrantProgram $activeProgram;

    private GrantProgram $inactiveProgram;

    private Organization $organizationA;

    private Organization $organizationB;

    private User $applicant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->applicant = User::factory()->create([
            'name' => 'Budi Pemohon Rahasia',
            'email' => 'budi.rahasia@example.com',
            'phone' => '081234567890',
        ]);

        $this->organizationA = Organization::factory()->create([
            'name' => 'Kelompok Tani Harapan Bangsa',
            'email' => 'organisasi.a@example.com',
            'phone' => '082199998888',
        ]);

        $this->organizationB = Organization::factory()->create([
            'name' => 'Yayasan Bina Usaha',
            'email' => 'organisasi.b@example.com',
            'phone' => '083177776666',
        ]);

        $this->activeProgram = GrantProgram::create([
            'id' => (string) Str::uuid(),
            'name' => 'Program Hibah Ketahanan Pangan 2026',
            'code' => 'PANGAN-2026',
            'description' => 'Bantuan penguatan ketahanan pangan desa.',
            'fiscal_year' => '2026',
            'status' => 'active',
            'registration_start_at' => now()->subDays(10),
            'registration_end_at' => now()->addDays(20),
            'minimum_amount' => 10000000,
            'maximum_amount' => 50000000,
            'total_budget' => 500000000,
            'is_active' => true,
        ]);

        $this->inactiveProgram = GrantProgram::create([
            'id' => (string) Str::uuid(),
            'name' => 'Program Internal Rahasia 2025',
            'code' => 'INTERNAL-2025',
            'description' => 'Program internal belum dibuka untuk umum.',
            'fiscal_year' => '2025',
            'status' => 'draft',
            'is_active' => false,
        ]);

        // Add document requirement to active program
        $docType = DocumentType::query()->firstOrCreate(
            ['code' => 'LEGALITY_DOC'],
            ['id' => (string) Str::uuid(), 'name' => 'Legalitas Kelompok', 'scope' => 'organization', 'is_active' => true]
        );

        $req = Requirement::query()->firstOrCreate(
            ['code' => 'REQ_LEGALITAS'],
            ['id' => (string) Str::uuid(), 'name' => 'Akta Notaris / SK', 'scope' => 'organization', 'is_mandatory' => true, 'is_active' => true]
        );

        DocumentRequirement::create([
            'id' => (string) Str::uuid(),
            'grant_program_id' => $this->activeProgram->id,
            'document_type_id' => $docType->id,
            'requirement_id' => $req->id,
            'scope' => 'organization',
            'is_mandatory' => true,
            'maximum_files' => 2,
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    // ==========================================
    // 1. PUBLIC GRANT PROGRAM TESTS
    // ==========================================

    public function test_guest_can_list_active_grant_programs_with_pagination_and_filtering(): void
    {
        $response = $this->getJson('/api/v1/public/grant-programs');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'PANGAN-2026')
            ->assertJsonPath('data.0.is_active', true);

        // Filter by fiscal year
        $filterOk = $this->getJson('/api/v1/public/grant-programs?fiscal_year=2026');
        $filterOk->assertOk()->assertJsonCount(1, 'data');

        $filterEmpty = $this->getJson('/api/v1/public/grant-programs?fiscal_year=2020');
        $filterEmpty->assertOk()->assertJsonCount(0, 'data');

        // Filter by keyword
        $kwOk = $this->getJson('/api/v1/public/grant-programs?keyword=Pangan');
        $kwOk->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_inactive_grant_program_is_not_listed_and_returns_404_on_detail(): void
    {
        // Must not be in list
        $listRes = $this->getJson('/api/v1/public/grant-programs');
        $listRes->assertOk();
        $codes = collect($listRes->json('data'))->pluck('code')->all();
        $this->assertNotContains('INTERNAL-2025', $codes);

        // Detail of inactive program must return 404
        $this->getJson("/api/v1/public/grant-programs/{$this->inactiveProgram->id}")
            ->assertNotFound();
    }

    public function test_grant_program_response_does_not_leak_internal_columns(): void
    {
        $response = $this->getJson("/api/v1/public/grant-programs/{$this->activeProgram->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Program Hibah Ketahanan Pangan 2026');

        $data = $response->json('data');
        $this->assertArrayNotHasKey('created_by', $data);
        $this->assertArrayNotHasKey('updated_by', $data);
        $this->assertArrayNotHasKey('deleted_at', $data);
        $this->assertArrayHasKey('timeline_url', $data);
        $this->assertArrayHasKey('documents_url', $data);
    }

    public function test_guest_can_view_grant_program_timeline_ordered_by_stages(): void
    {
        $response = $this->getJson("/api/v1/public/grant-programs/{$this->activeProgram->id}/timeline");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $stages = $response->json('data');
        $this->assertIsArray($stages);
        $this->assertNotEmpty($stages);

        $stageCodes = collect($stages)->pluck('stage_code')->all();
        $this->assertContains('REGISTRATION', $stageCodes);
        $this->assertContains('VERIFICATION', $stageCodes);
        $this->assertContains('DECISION', $stageCodes);
        $this->assertContains('DISBURSEMENT', $stageCodes);
        $this->assertContains('LPJ_SUBMISSION', $stageCodes);

        // Inactive program timeline returns 404
        $this->getJson("/api/v1/public/grant-programs/{$this->inactiveProgram->id}/timeline")
            ->assertNotFound();
    }

    public function test_guest_can_view_grant_program_public_documents(): void
    {
        $response = $this->getJson("/api/v1/public/grant-programs/{$this->activeProgram->id}/documents");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.document_type_name', 'Legalitas Kelompok')
            ->assertJsonPath('data.0.is_mandatory', true);

        $doc = $response->json('data.0');
        $this->assertArrayNotHasKey('storage_path', $doc);
        $this->assertArrayNotHasKey('stored_filename', $doc);
        $this->assertArrayNotHasKey('disk', $doc);

        // Inactive program documents returns 404
        $this->getJson("/api/v1/public/grant-programs/{$this->inactiveProgram->id}/documents")
            ->assertNotFound();
    }

    // ==========================================
    // 2. PUBLIC ANNOUNCEMENT TESTS
    // ==========================================

    public function test_guest_can_list_published_announcements_with_pinned_priority(): void
    {
        // 1. Regular published
        $a1 = Announcement::create([
            'id' => (string) Str::uuid(),
            'title' => 'Pengumuman Reguler Lama',
            'slug' => 'pengumuman-reguler-lama',
            'category' => 'general',
            'excerpt' => 'Ringkasan',
            'content' => 'Konten lengkap reguler',
            'status' => AnnouncementStatus::PUBLISHED,
            'is_pinned' => false,
            'published_at' => now()->subDays(5),
        ]);

        // 2. Pinned published (even if older date, should appear first)
        $a2 = Announcement::create([
            'id' => (string) Str::uuid(),
            'title' => 'Pemberitahuan Penting Pinned',
            'slug' => 'pemberitahuan-penting-pinned',
            'category' => 'schedule',
            'excerpt' => 'Ringkasan penting',
            'content' => 'Konten penting di-pin',
            'status' => AnnouncementStatus::PUBLISHED,
            'is_pinned' => true,
            'published_at' => now()->subDays(10),
        ]);

        $response = $this->getJson('/api/v1/public/announcements');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');

        // First item must be the pinned one
        $this->assertEquals('Pemberitahuan Penting Pinned', $response->json('data.0.title'));
        $this->assertTrue($response->json('data.0.is_pinned'));
    }

    public function test_draft_archived_and_future_scheduled_announcements_are_hidden_from_public(): void
    {
        // Draft
        Announcement::create([
            'id' => (string) Str::uuid(),
            'title' => 'Draft Announcement',
            'slug' => 'draft-announcement',
            'content' => 'Content',
            'status' => AnnouncementStatus::DRAFT,
            'published_at' => null,
        ]);

        // Archived
        Announcement::create([
            'id' => (string) Str::uuid(),
            'title' => 'Archived Announcement',
            'slug' => 'archived-announcement',
            'content' => 'Content',
            'status' => AnnouncementStatus::ARCHIVED,
            'published_at' => now()->subMonths(1),
        ]);

        // Future scheduled
        $future = Announcement::create([
            'id' => (string) Str::uuid(),
            'title' => 'Future Announcement',
            'slug' => 'future-announcement',
            'content' => 'Content',
            'status' => AnnouncementStatus::PUBLISHED,
            'published_at' => now()->addDays(5),
        ]);

        // Published active
        Announcement::create([
            'id' => (string) Str::uuid(),
            'title' => 'Live Announcement',
            'slug' => 'live-announcement',
            'content' => 'Content',
            'status' => AnnouncementStatus::PUBLISHED,
            'published_at' => now()->subHour(),
        ]);

        $response = $this->getJson('/api/v1/public/announcements');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Live Announcement');

        // Accessing future announcement directly returns 404
        $this->getJson("/api/v1/public/announcements/{$future->id}")
            ->assertNotFound();
    }

    public function test_guest_can_view_published_announcement_detail(): void
    {
        $announcement = Announcement::create([
            'id' => (string) Str::uuid(),
            'grant_program_id' => $this->activeProgram->id,
            'title' => 'Panduan Teknis Proposal',
            'slug' => 'panduan-teknis-proposal',
            'category' => 'guideline',
            'excerpt' => 'Ringkasan panduan',
            'content' => 'Konten lengkap panduan teknis proposal...',
            'status' => AnnouncementStatus::PUBLISHED,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson("/api/v1/public/announcements/{$announcement->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $announcement->id)
            ->assertJsonPath('data.title', 'Panduan Teknis Proposal')
            ->assertJsonPath('data.grant_program.code', 'PANGAN-2026');

        $data = $response->json('data');
        $this->assertArrayNotHasKey('created_by', $data);
        $this->assertArrayNotHasKey('updated_by', $data);
    }

    // ==========================================
    // 3. PUBLIC STATISTICS & TRANSPARENCY TESTS
    // ==========================================

    public function test_guest_can_view_public_statistics_summary(): void
    {
        // 1. Draft proposal (must NOT be counted)
        Proposal::create([
            'id' => (string) Str::uuid(),
            'grant_program_id' => $this->activeProgram->id,
            'organization_id' => $this->organizationA->id,
            'applicant_id' => $this->applicant->id,
            'proposal_number' => 'PROP-DRAFT',
            'title' => 'Proposal Draft Pemohon',
            'requested_amount' => 15000000,
            'status' => ProposalStatus::DRAFT,
        ]);

        // 2. In-process proposal (submitted)
        Proposal::create([
            'id' => (string) Str::uuid(),
            'grant_program_id' => $this->activeProgram->id,
            'organization_id' => $this->organizationA->id,
            'applicant_id' => $this->applicant->id,
            'proposal_number' => 'PROP-PROC',
            'title' => 'Proposal Masih Proses',
            'requested_amount' => 20000000,
            'status' => ProposalStatus::VERIFICATION,
        ]);

        // 3. Approved proposal (Org A)
        $approvedProp1 = Proposal::create([
            'id' => (string) Str::uuid(),
            'grant_program_id' => $this->activeProgram->id,
            'organization_id' => $this->organizationA->id,
            'applicant_id' => $this->applicant->id,
            'proposal_number' => 'PROP-APP-1',
            'title' => 'Proposal Disetujui 1',
            'requested_amount' => 30000000,
            'approved_amount' => 30000000,
            'status' => ProposalStatus::APPROVED,
        ]);

        // 4. Completed proposal (Org B)
        $completedProp = Proposal::create([
            'id' => (string) Str::uuid(),
            'grant_program_id' => $this->activeProgram->id,
            'organization_id' => $this->organizationB->id,
            'applicant_id' => $this->applicant->id,
            'proposal_number' => 'PROP-COMP',
            'title' => 'Proposal Selesai',
            'requested_amount' => 25000000,
            'approved_amount' => 25000000,
            'status' => ProposalStatus::COMPLETED,
        ]);

        // 5. Rejected proposal
        Proposal::create([
            'id' => (string) Str::uuid(),
            'grant_program_id' => $this->activeProgram->id,
            'organization_id' => $this->organizationB->id,
            'applicant_id' => $this->applicant->id,
            'proposal_number' => 'PROP-REJ',
            'title' => 'Proposal Ditolak',
            'requested_amount' => 10000000,
            'status' => ProposalStatus::REJECTED,
        ]);

        // Record a paid disbursement for approvedProp1
        $plan = DisbursementPlan::create([
            'id' => (string) Str::uuid(),
            'proposal_id' => $approvedProp1->id,
            'created_by' => $this->applicant->id,
            'plan_number' => 'PLAN-001',
            'total_stages' => 1,
            'planned_amount' => 30000000,
            'status' => DisbursementPlanStatus::COMPLETED,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        Disbursement::create([
            'id' => (string) Str::uuid(),
            'disbursement_plan_id' => $plan->id,
            'proposal_id' => $approvedProp1->id,
            'stage_number' => 1,
            'disbursement_number' => 'DISB/001',
            'planned_amount' => 30000000,
            'approved_amount' => 30000000,
            'paid_amount' => 30000000,
            'status' => DisbursementStatus::PAID,
            'paid_date' => now()->toDateString(),
        ]);

        $response = $this->getJson('/api/v1/public/statistics/summary');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.active_programs_count', 1)
            ->assertJsonPath('data.total_proposals_submitted', 4) // proc, app1, comp, rej (excludes draft)
            ->assertJsonPath('data.proposals_in_process', 1)
            ->assertJsonPath('data.proposals_approved', 2) // app1 + comp
            ->assertJsonPath('data.proposals_rejected', 1)
            ->assertJsonPath('data.proposals_completed', 1)
            ->assertJsonPath('data.recipient_organizations_count', 2) // Org A + Org B
            ->assertJsonPath('data.total_approved_amount', 55000000) // 30M + 25M
            ->assertJsonPath('data.total_disbursed_amount', 30000000);
    }

    public function test_guest_can_view_detailed_public_statistics_aggregated_without_pii(): void
    {
        $response = $this->getJson('/api/v1/public/statistics');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'summary',
                    'by_fiscal_year',
                    'by_program',
                ],
            ]);

        // Assert no PII in response string
        $content = $response->getContent();
        $this->assertStringNotContainsString('budi.rahasia@example.com', $content);
        $this->assertStringNotContainsString('081234567890', $content);
        $this->assertStringNotContainsString('Budi Pemohon', $content);
    }

    public function test_statistics_empty_state_returns_zero_consistently_without_errors(): void
    {
        // Delete all data to test empty state resilience
        Proposal::query()->forceDelete();
        Disbursement::query()->forceDelete();
        GrantProgram::query()->forceDelete();

        $response = $this->getJson('/api/v1/public/statistics/summary');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.active_programs_count', 0)
            ->assertJsonPath('data.total_proposals_submitted', 0)
            ->assertJsonPath('data.total_approved_amount', 0)
            ->assertJsonPath('data.total_disbursed_amount', 0);
    }

    public function test_guest_can_view_public_transparency_summary_and_detail(): void
    {
        $response = $this->getJson('/api/v1/public/transparency');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'overview',
                    'programs',
                ],
            ]);

        // Specific program transparency
        $detailRes = $this->getJson("/api/v1/public/transparency/{$this->activeProgram->id}");
        $detailRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.grant_program.code', 'PANGAN-2026');

        // Inactive program transparency returns 404
        $this->getJson("/api/v1/public/transparency/{$this->inactiveProgram->id}")
            ->assertNotFound();
    }

    // ==========================================
    // 4. SECURITY & PRIVACY TESTS
    // ==========================================

    public function test_public_endpoints_do_not_require_authentication_tokens(): void
    {
        // All these endpoints should succeed for guests (HTTP 200)
        $this->getJson('/api/v1/public/grant-programs')->assertOk();
        $this->getJson("/api/v1/public/grant-programs/{$this->activeProgram->id}")->assertOk();
        $this->getJson("/api/v1/public/grant-programs/{$this->activeProgram->id}/timeline")->assertOk();
        $this->getJson("/api/v1/public/grant-programs/{$this->activeProgram->id}/documents")->assertOk();
        $this->getJson('/api/v1/public/announcements')->assertOk();
        $this->getJson('/api/v1/public/statistics')->assertOk();
        $this->getJson('/api/v1/public/statistics/summary')->assertOk();
        $this->getJson('/api/v1/public/transparency')->assertOk();
    }

    public function test_internal_endpoints_remain_protected_by_sanctum_auth(): void
    {
        // Authenticated internal routes must return 401 Unauthorized for guests
        $this->getJson('/api/v1/proposals')->assertUnauthorized();
        $this->getJson('/api/v1/disbursements')->assertUnauthorized();
        $this->getJson('/api/v1/lpj')->assertUnauthorized();
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_public_endpoints_never_leak_storage_paths_or_absolute_paths(): void
    {
        $urls = [
            '/api/v1/public/grant-programs',
            "/api/v1/public/grant-programs/{$this->activeProgram->id}",
            "/api/v1/public/grant-programs/{$this->activeProgram->id}/timeline",
            "/api/v1/public/grant-programs/{$this->activeProgram->id}/documents",
            '/api/v1/public/announcements',
            '/api/v1/public/statistics',
            '/api/v1/public/statistics/summary',
            '/api/v1/public/transparency',
        ];

        foreach ($urls as $url) {
            $res = $this->getJson($url);
            $res->assertOk();
            $content = $res->getContent();

            // Zero absolute disk path exposure
            $this->assertStringNotContainsString('D:\\\\', $content);
            $this->assertStringNotContainsString('C:\\\\', $content);
            $this->assertStringNotContainsString('/var/', $content);
            $this->assertStringNotContainsString('/app/private', $content);
            $this->assertStringNotContainsString('storage/app', $content);
        }
    }
}
