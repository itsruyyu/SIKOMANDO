<?php

namespace Tests\Feature;

use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            \Database\Seeders\RolePermissionSeeder::class,
            \Database\Seeders\MasterDataSeeder::class,
        ]);

        $this->seed([
            \Database\Seeders\RolePermissionSeeder::class,
            \Database\Seeders\AdminUserSeeder::class,
            \Database\Seeders\MasterDataSeeder::class,
        ]);
    }

    public function test_super_admin_has_all_permissions(): void
    {
        $user = User::factory()->create();

        $role = Role::where('code', 'SUPER_ADMIN')->firstOrFail();

        $user->roles()->attach($role->id);

        $this->assertTrue($user->hasRole('SUPER_ADMIN'));
        $this->assertTrue($user->hasPermission('proposal.approve'));
        $this->assertTrue($user->hasPermission('audit.view'));
    }

    public function test_pemohon_cannot_approve_proposal(): void
    {
        $user = User::factory()->create();

        $role = Role::where('code', 'PEMOHON')->firstOrFail();

        $user->roles()->attach($role->id);

        $this->assertTrue($user->hasRole('PEMOHON'));
        $this->assertFalse($user->hasPermission('proposal.approve'));
    }

    public function test_evaluator_cannot_verify_proposal(): void
    {
        $user = User::factory()->create();

        $role = Role::where('code', 'EVALUATOR')->firstOrFail();

        $user->roles()->attach($role->id);

        $this->assertTrue($user->hasRole('EVALUATOR'));
        $this->assertFalse($user->hasPermission('proposal.verify'));
    }

    public function test_surveyor_cannot_approve_proposal(): void
    {
        $user = User::factory()->create();

        $role = Role::where('code', 'SURVEYOR')->firstOrFail();

        $user->roles()->attach($role->id);

        $this->assertTrue($user->hasRole('SURVEYOR'));
        $this->assertFalse($user->hasPermission('proposal.approve'));
    }

    public function test_auditor_cannot_mutate_proposal(): void
    {
        $user = User::factory()->create();

        $role = Role::where('code', 'AUDITOR')->firstOrFail();

        $user->roles()->attach($role->id);

        $this->assertTrue($user->hasRole('AUDITOR'));
        $this->assertFalse($user->hasPermission('proposal.update'));
        $this->assertFalse($user->hasPermission('proposal.approve'));
    }
}