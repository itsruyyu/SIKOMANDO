<?php

namespace Tests\Feature;

use App\Enums\DecisionDocumentStatus;
use App\Enums\DecisionResult;
use App\Enums\DecisionStatus;
use App\Enums\DisbursementPlanStatus;
use App\Enums\DisbursementStatus;
use App\Enums\ProposalStatus;
use App\Models\AuditLog;
use App\Models\Decision;
use App\Models\DecisionDocument;
use App\Models\Disbursement;
use App\Models\DisbursementPlan;
use App\Models\DisbursementTransaction;
use App\Models\GrantProgram;
use App\Models\Organization;
use App\Models\Proposal;
use App\Models\ProposalStatusHistory;
use App\Models\Role;
use App\Models\User;
use App\Services\DisbursementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SecurityAndFinancialIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(
            ['code' => 'ADMIN_SIKOMANDO'],
            ['id' => (string) Str::uuid(), 'name' => 'Admin SIKOMANDO', 'is_system' => true, 'is_active' => true]
        );
        Role::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['id' => (string) Str::uuid(), 'name' => 'Super Admin', 'is_system' => true, 'is_active' => true]
        );
    }

    public function test_account_locks_out_after_five_failed_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'victim@sikomando.test',
            'password' => Hash::make('CorrectPassword123!'),
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 4; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'victim@sikomando.test',
                'password' => 'WrongPassword',
            ]);
            $response->assertStatus(422);
            $user->refresh();
            $this->assertEquals($i, $user->failed_login_attempts);
            $this->assertNull($user->locked_until);
        }

        // 5th failed attempt triggers lockout
        $response5 = $this->postJson('/api/v1/auth/login', [
            'email' => 'victim@sikomando.test',
            'password' => 'WrongPassword',
        ]);
        $response5->assertStatus(422);
        $this->assertStringContainsString('terkunci sementara', json_encode($response5->json('errors')));

        $user->refresh();
        $this->assertEquals(5, $user->failed_login_attempts);
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->locked_until->isFuture());

        // Even with correct password, login is denied while locked
        $responseLocked = $this->postJson('/api/v1/auth/login', [
            'email' => 'victim@sikomando.test',
            'password' => 'CorrectPassword123!',
        ]);
        $this->assertTrue(in_array($responseLocked->status(), [422, 429], true));
    }

    public function test_user_can_manage_sessions_and_logout_all(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $token1 = $user->createToken('Device 1')->plainTextToken;
        $token2 = $user->createToken('Device 2')->plainTextToken;

        $this->assertCount(2, $user->tokens);

        // List sessions
        $response = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->getJson('/api/v1/auth/sessions');

        $response->assertOk()
            ->assertJsonPath('success', true);
        $this->assertCount(2, $response->json('data'));

        // Logout all
        $responseLogoutAll = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->postJson('/api/v1/auth/logout-all');

        $responseLogoutAll->assertOk();
        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
            'is_active' => true,
        ]);

        $token = $user->createToken('Device')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'OldPassword123!',
                'password' => 'NewSecurePassword123#',
                'password_confirmation' => 'NewSecurePassword123#',
                'revoke_other_sessions' => true,
            ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('NewSecurePassword123#', $user->fresh()->password));
    }

    public function test_audit_logs_are_strictly_append_only(): void
    {
        $user = User::factory()->create();

        $log = AuditLog::create([
            'actor_id' => $user->id,
            'action' => 'test.action',
            'module' => 'test',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'occurred_at' => now(),
        ]);

        // Model-level mutation block
        $this->expectException(\RuntimeException::class);
        $log->update(['action' => 'mutated.action']);
    }

    public function test_audit_logs_cannot_be_deleted(): void
    {
        $user = User::factory()->create();

        $log = AuditLog::create([
            'actor_id' => $user->id,
            'action' => 'test.action',
            'module' => 'test',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'occurred_at' => now(),
        ]);

        // Model-level deletion block
        $this->expectException(\RuntimeException::class);
        $log->delete();
    }

    public function test_proposal_status_history_is_append_only(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $program = GrantProgram::factory()->create();

        $proposal = Proposal::factory()->create([
            'organization_id' => $org->id,
            'grant_program_id' => $program->id,
            'applicant_id' => $user->id,
            'status' => ProposalStatus::SUBMITTED,
        ]);

        $history = ProposalStatusHistory::create([
            'proposal_id' => $proposal->id,
            'from_status' => ProposalStatus::DRAFT->value,
            'to_status' => ProposalStatus::SUBMITTED->value,
            'changed_by' => $user->id,
            'reason' => 'Initial submission',
            'changed_at' => now(),
        ]);

        $this->expectException(\RuntimeException::class);
        $history->update(['reason' => 'Tampered reason']);
    }

    public function test_disbursement_transaction_enforces_idempotency_and_ceiling(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('code', 'ADMIN_SIKOMANDO')->first());
        $applicant = User::factory()->create();
        $org = Organization::factory()->create();
        $program = GrantProgram::factory()->create();

        $proposal = Proposal::factory()->create([
            'organization_id' => $org->id,
            'grant_program_id' => $program->id,
            'applicant_id' => $applicant->id,
            'status' => ProposalStatus::APPROVED,
            'requested_amount' => 50000000,
            'approved_amount' => 50000000,
        ]);

        $decision = Decision::create([
            'proposal_id' => $proposal->id,
            'issued_by' => $admin->id,
            'decision_number' => 'SK-'.now()->format('Y').'-'.Str::upper(Str::random(8)),
            'decision_type' => 'grant_award',
            'status' => DecisionStatus::PUBLISHED,
            'result' => DecisionResult::APPROVED,
            'decision_date' => now()->toDateString(),
            'approved_amount' => 50000000,
            'title' => 'Surat Keputusan Penetapan',
            'issued_at' => now(),
        ]);

        DecisionDocument::create([
            'decision_id' => $decision->id,
            'uploaded_by' => $admin->id,
            'document_type' => 'decision_letter',
            'document_title' => 'Naskah SK Penetapan',
            'original_filename' => 'sk.pdf',
            'stored_filename' => 'sk_stored.pdf',
            'disk' => 'private',
            'storage_path' => 'decisions/documents/sk_stored.pdf',
            'status' => DecisionDocumentStatus::ACTIVE,
            'version' => 1,
            'generated_at' => now(),
        ]);

        $service = app(DisbursementService::class);

        $plan = $service->createPlan($proposal, $admin, [
            'planned_amount' => 50000000,
            'stages' => [
                [
                    'stage_number' => 1,
                    'planned_amount' => 50000000,
                    'bank_name' => 'Bank Mandiri',
                    'bank_account_number' => '1234567890',
                    'bank_account_name' => 'Yayasan Makmur',
                ],
            ],
        ]);

        $disbursement = $plan->disbursements->first();
        $disbursement->update(['status' => DisbursementStatus::APPROVED]);

        $idempotencyKey = (string) Str::uuid();

        // 1st execution
        $tx1 = $service->recordTransaction($disbursement, $admin, [
            'amount' => 50000000,
            'bank_reference' => 'REF-UNIQUE-001',
            'idempotency_key' => $idempotencyKey,
        ]);

        $this->assertEquals('REF-UNIQUE-001', $tx1->bank_reference);
        $this->assertEquals($idempotencyKey, $tx1->idempotency_key);

        // 2nd execution with same idempotency key returns exact same record without error
        $tx2 = $service->recordTransaction($disbursement, $admin, [
            'amount' => 50000000,
            'bank_reference' => 'REF-UNIQUE-001',
            'idempotency_key' => $idempotencyKey,
        ]);

        $this->assertEquals($tx1->id, $tx2->id);

        // Total count remains 1
        $this->assertEquals(1, DisbursementTransaction::where('disbursement_id', $disbursement->id)->count());

        // Disbursement is now PAID and Proposal transitioned to IMPLEMENTATION
        $disbursement->refresh();
        $this->assertEquals(DisbursementStatus::PAID, $disbursement->status);

        $proposal->refresh();
        $this->assertEquals(ProposalStatus::IMPLEMENTATION, $proposal->status);
    }

    public function test_disbursement_rejects_duplicate_bank_reference(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('code', 'ADMIN_SIKOMANDO')->first());
        $applicant = User::factory()->create();
        $org = Organization::factory()->create();
        $program = GrantProgram::factory()->create();

        $proposal = Proposal::factory()->create([
            'organization_id' => $org->id,
            'grant_program_id' => $program->id,
            'applicant_id' => $applicant->id,
            'status' => ProposalStatus::APPROVED,
            'requested_amount' => 100000000,
            'approved_amount' => 100000000,
        ]);

        $decision = Decision::create([
            'proposal_id' => $proposal->id,
            'issued_by' => $admin->id,
            'decision_number' => 'SK-'.now()->format('Y').'-'.Str::upper(Str::random(8)),
            'decision_type' => 'grant_award',
            'status' => DecisionStatus::PUBLISHED,
            'result' => DecisionResult::APPROVED,
            'decision_date' => now()->toDateString(),
            'approved_amount' => 100000000,
            'title' => 'Surat Keputusan Penetapan',
            'issued_at' => now(),
        ]);

        DecisionDocument::create([
            'decision_id' => $decision->id,
            'uploaded_by' => $admin->id,
            'document_type' => 'decision_letter',
            'document_title' => 'Naskah SK Penetapan',
            'original_filename' => 'sk.pdf',
            'stored_filename' => 'sk_stored.pdf',
            'disk' => 'private',
            'storage_path' => 'decisions/documents/sk_stored.pdf',
            'status' => DecisionDocumentStatus::ACTIVE,
            'version' => 1,
            'generated_at' => now(),
        ]);

        $service = app(DisbursementService::class);

        $plan = $service->createPlan($proposal, $admin, [
            'planned_amount' => 100000000,
            'stages' => [
                ['stage_number' => 1, 'planned_amount' => 50000000],
                ['stage_number' => 2, 'planned_amount' => 50000000],
            ],
        ]);

        $d1 = $plan->disbursements->where('stage_number', 1)->first();
        $d2 = $plan->disbursements->where('stage_number', 2)->first();

        $d1->update(['status' => DisbursementStatus::APPROVED]);
        $d2->update(['status' => DisbursementStatus::APPROVED]);

        $service->recordTransaction($d1, $admin, [
            'amount' => 50000000,
            'bank_reference' => 'REF-SHARED-123',
        ]);

        // Attempting to record d2 with the same bank reference must throw validation error
        $this->expectException(ValidationException::class);
        $service->recordTransaction($d2, $admin, [
            'amount' => 50000000,
            'bank_reference' => 'REF-SHARED-123',
        ]);
    }
}

