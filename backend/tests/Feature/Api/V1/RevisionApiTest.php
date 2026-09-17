<?php

namespace Tests\Feature\Api\V1;

use App\Models\GrantProgram;
use App\Models\Organization;
use App\Models\Proposal;
use App\Models\Revision;
use App\Models\RevisionItem;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RevisionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(MasterDataSeeder::class);
    }

    private function createUser(string $roleCode): User
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $role = Role::query()
            ->where('code', $roleCode)
            ->firstOrFail();

        $user->roles()->attach($role->id);

        return $user;
    }

    private function createProposal(
        User $applicant,
        string $status = 'verification'
    ): Proposal {
        $program = GrantProgram::query()->firstOrCreate(
            [
                'code' => 'PROGRAM-REVISION-TEST',
            ],
            [
                'name' => 'Program Pengujian Revisi',
                'fiscal_year' => now()->year,
                'status' => 'active',
                'is_active' => true,
                'created_by' => $applicant->id,
                'updated_by' => $applicant->id,
            ]
        );

        $organization = Organization::factory()->create([
            'created_by' => $applicant->id,
            'updated_by' => $applicant->id,
        ]);

        return Proposal::factory()->create([
            'grant_program_id' => $program->id,
            'organization_id' => $organization->id,
            'applicant_id' => $applicant->id,
            'created_by' => $applicant->id,
            'updated_by' => $applicant->id,
            'status' => $status,
        ]);
    }

    public function test_authorized_actor_can_create_revision(): void
    {
        $verifikator = $this->createUser('VERIFIKATOR');
        $pemohon = $this->createUser('PEMOHON');

        $proposal = $this->createProposal($pemohon);

        Sanctum::actingAs(
            $verifikator,
            ['*']
        );

        $response = $this->postJson(
            "/api/v1/proposals/{$proposal->id}/revisions",
            [
                'reason' => 'Dokumen proposal perlu diperbaiki.',
                'items' => [
                    [
                        'item_code' => 'DOC-001',
                        'field_name' => 'background',
                        'description' => 'Perbaiki uraian latar belakang.',
                        'old_value' => 'Latar belakang lama.',
                        'new_value' => null,
                        'notes' => 'Mohon diperjelas dan dilengkapi.',
                    ],
                ],
            ]
        );

        $response
            ->assertSuccessful()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Permintaan revisi berhasil dibuat.'
            )
            ->assertJsonPath('data.revision_number', 1)
            ->assertJsonPath('data.status', 'requested');

        $this->assertDatabaseHas('revisions', [
            'proposal_id' => $proposal->id,
            'requested_by' => $verifikator->id,
            'revision_number' => 1,
            'status' => 'requested',
        ]);

        $this->assertDatabaseHas('revision_items', [
            'revision_id' => $response->json('data.id'),
            'item_code' => 'DOC-001',
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('proposals', [
            'id' => $proposal->id,
            'status' => 'revision',
        ]);
    }

    public function test_revision_creation_requires_reason_and_items(): void
    {
        $verifikator = $this->createUser('VERIFIKATOR');
        $pemohon = $this->createUser('PEMOHON');

        $proposal = $this->createProposal($pemohon);

        Sanctum::actingAs(
            $verifikator,
            ['*']
        );

        $response = $this->postJson(
            "/api/v1/proposals/{$proposal->id}/revisions",
            []
        );

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors([
                'reason',
                'items',
            ]);
    }

    public function test_pemohon_cannot_create_revision(): void
    {
        $pemohon = $this->createUser('PEMOHON');

        $proposal = $this->createProposal($pemohon);

        Sanctum::actingAs(
            $pemohon,
            ['*']
        );

        $response = $this->postJson(
            "/api/v1/proposals/{$proposal->id}/revisions",
            [
                'reason' => 'Permintaan revisi dari pemohon.',
                'items' => [
                    [
                        'description' => 'Item revisi tidak valid.',
                    ],
                ],
            ]
        );

        $response
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_user_can_view_proposal_revisions(): void
    {
        $verifikator = $this->createUser('VERIFIKATOR');
        $pemohon = $this->createUser('PEMOHON');

        $proposal = $this->createProposal($pemohon);

        $revision = Revision::query()->create([
            'proposal_id' => $proposal->id,
            'requested_by' => $verifikator->id,
            'revision_number' => 1,
            'status' => 'requested',
            'reason' => 'Proposal perlu diperbaiki.',
            'requested_at' => now(),
        ]);

        RevisionItem::query()->create([
            'revision_id' => $revision->id,
            'item_code' => 'ITEM-001',
            'field_name' => 'title',
            'description' => 'Perbaiki judul proposal.',
            'status' => 'open',
        ]);

        Sanctum::actingAs(
            $pemohon,
            ['*']
        );

        $response = $this->getJson(
            "/api/v1/proposals/{$proposal->id}/revisions"
        );

        $response
            ->assertSuccessful()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $revision->id)
            ->assertJsonPath('data.0.revision_number', 1);
    }

    public function test_user_can_view_revision_detail(): void
    {
        $verifikator = $this->createUser('VERIFIKATOR');
        $pemohon = $this->createUser('PEMOHON');

        $proposal = $this->createProposal($pemohon);

        $revision = Revision::query()->create([
            'proposal_id' => $proposal->id,
            'requested_by' => $verifikator->id,
            'revision_number' => 1,
            'status' => 'requested',
            'reason' => 'Proposal perlu diperbaiki.',
            'requested_at' => now(),
        ]);

        Sanctum::actingAs(
            $pemohon,
            ['*']
        );

        $response = $this->getJson(
            "/api/v1/proposals/{$proposal->id}/revisions/{$revision->id}"
        );

        $response
            ->assertSuccessful()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $revision->id)
            ->assertJsonPath('data.revision_number', 1);
    }

    public function test_revision_detail_from_another_proposal_returns_not_found(): void
    {
        $verifikator = $this->createUser('VERIFIKATOR');
        $pemohon = $this->createUser('PEMOHON');

        $proposalA = $this->createProposal($pemohon);
        $proposalB = $this->createProposal($pemohon);

        $revision = Revision::query()->create([
            'proposal_id' => $proposalB->id,
            'requested_by' => $verifikator->id,
            'revision_number' => 1,
            'status' => 'requested',
            'reason' => 'Proposal perlu diperbaiki.',
            'requested_at' => now(),
        ]);

        Sanctum::actingAs(
            $pemohon,
            ['*']
        );

        $response = $this->getJson(
            "/api/v1/proposals/{$proposalA->id}/revisions/{$revision->id}"
        );

        $response
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_pemohon_cannot_resubmit_revision_with_open_items(): void
    {
        $verifikator = $this->createUser('VERIFIKATOR');
        $pemohon = $this->createUser('PEMOHON');

        $proposal = $this->createProposal($pemohon, 'revision');

        $revision = Revision::query()->create([
            'proposal_id' => $proposal->id,
            'requested_by' => $verifikator->id,
            'revision_number' => 1,
            'status' => 'requested',
            'reason' => 'Proposal perlu diperbaiki.',
            'requested_at' => now(),
        ]);

        RevisionItem::query()->create([
            'revision_id' => $revision->id,
            'item_code' => 'ITEM-001',
            'field_name' => 'title',
            'description' => 'Perbaiki judul proposal.',
            'status' => 'open',
        ]);

        Sanctum::actingAs(
            $pemohon,
            ['*']
        );

        $response = $this->postJson(
            "/api/v1/proposals/{$proposal->id}/revisions/{$revision->id}/submit"
        );

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors([
                'items',
            ]);
    }

    public function test_pemohon_can_resubmit_revision_after_all_items_resolved(): void
    {
        $verifikator = $this->createUser('VERIFIKATOR');
        $pemohon = $this->createUser('PEMOHON');

        $proposal = $this->createProposal($pemohon, 'revision');

        $revision = Revision::query()->create([
            'proposal_id' => $proposal->id,
            'requested_by' => $verifikator->id,
            'revision_number' => 1,
            'status' => 'requested',
            'reason' => 'Proposal perlu diperbaiki.',
            'requested_at' => now(),
        ]);

        RevisionItem::query()->create([
            'revision_id' => $revision->id,
            'item_code' => 'ITEM-001',
            'field_name' => 'title',
            'description' => 'Perbaiki judul proposal.',
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        Sanctum::actingAs(
            $pemohon,
            ['*']
        );

        $response = $this->postJson(
            "/api/v1/proposals/{$proposal->id}/revisions/{$revision->id}/submit"
        );

        $response
            ->assertSuccessful()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Proposal berhasil diajukan ulang setelah revisi.'
            )
            ->assertJsonPath('data.status', 'submitted');

        $this->assertDatabaseHas('revisions', [
            'id' => $revision->id,
            'status' => 'submitted',
        ]);

        $this->assertDatabaseHas('proposals', [
            'id' => $proposal->id,
            'status' => 'submitted',
        ]);
    }
}
