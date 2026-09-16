<?php

namespace Tests\Feature\Api\V1;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActivityApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
        ]);
    }

    private function createUser(): User
    {
        return User::factory()->create([
            'is_active' => true,
        ]);
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('phpunit')->plainTextToken;
    }

    private function createAuditLog(
        User $actor,
        array $overrides = [],
    ): AuditLog {
        return AuditLog::query()->create(array_merge([
            'actor_id' => $actor->id,
            'action' => 'proposal.created',
            'module' => 'proposal',
            'entity_type' => 'App\\Models\\Proposal',
            'entity_id' => (string) Str::uuid(),
            'request_id' => 'request-test-001',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'old_values' => [],
            'new_values' => [
                'status' => 'draft',
            ],
            'metadata' => [
                'source' => 'phpunit',
            ],
            'occurred_at' => now(),
        ], $overrides));
    }

    public function test_guest_cannot_access_activities(): void
    {
        $this
            ->getJson('/api/v1/activities')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_activities(): void
    {
        $user = $this->createUser();

        $activity = $this->createAuditLog($user);

        $this
            ->withToken($this->tokenFor($user))
            ->getJson('/api/v1/activities')
            ->assertOk()
            ->assertJsonPath('data.0.id', $activity->id)
            ->assertJsonPath('data.0.action', 'proposal.created')
            ->assertJsonPath('data.0.module', 'proposal')
            ->assertJsonPath('data.0.actor.id', $user->id)
            ->assertJsonPath('data.0.actor.name', $user->name);
    }

    public function test_activity_response_does_not_expose_sensitive_audit_fields(): void
    {
        $user = $this->createUser();

        $this->createAuditLog($user);

        $response = $this
            ->withToken($this->tokenFor($user))
            ->getJson('/api/v1/activities')
            ->assertOk();

        $response->assertJsonMissingPath('data.0.old_values');
        $response->assertJsonMissingPath('data.0.new_values');
        $response->assertJsonMissingPath('data.0.metadata');
        $response->assertJsonMissingPath('data.0.ip_address');
        $response->assertJsonMissingPath('data.0.user_agent');
    }

    public function test_user_can_filter_activities_by_module(): void
    {
        $user = $this->createUser();

        $this->createAuditLog($user, [
            'module' => 'proposal',
            'action' => 'proposal.created',
        ]);

        $this->createAuditLog($user, [
            'module' => 'proposal_revision',
            'action' => 'revision.created',
        ]);

        $this
            ->withToken($this->tokenFor($user))
            ->getJson('/api/v1/activities?module=proposal')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.module', 'proposal');
    }

    public function test_user_can_filter_activities_by_action(): void
    {
        $user = $this->createUser();

        $this->createAuditLog($user, [
            'action' => 'proposal.created',
        ]);

        $this->createAuditLog($user, [
            'action' => 'revision.created',
        ]);

        $this
            ->withToken($this->tokenFor($user))
            ->getJson('/api/v1/activities?action=revision.created')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'revision.created');
    }

    public function test_user_can_paginate_activities(): void
    {
        $user = $this->createUser();

        $this->createAuditLog($user, [
            'request_id' => 'request-test-001',
        ]);

        $this->createAuditLog($user, [
            'request_id' => 'request-test-002',
        ]);

        $this->createAuditLog($user, [
            'request_id' => 'request-test-003',
        ]);

        $this
            ->withToken($this->tokenFor($user))
            ->getJson('/api/v1/activities?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_activity_includes_request_id_and_occurred_at(): void
    {
        $user = $this->createUser();

        $activity = $this->createAuditLog($user, [
            'request_id' => 'request-specific-001',
        ]);

        $this
            ->withToken($this->tokenFor($user))
            ->getJson('/api/v1/activities')
            ->assertOk()
            ->assertJsonPath(
                'data.0.request_id',
                'request-specific-001'
            )
            ->assertJsonPath(
                'data.0.occurred_at',
                $activity->occurred_at->toISOString()
            );
    }
}