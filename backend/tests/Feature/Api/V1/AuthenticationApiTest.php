<?php

namespace Tests\Feature\Api\V1;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'pemohon@test.local',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);

        $role = Role::where('code', 'PEMOHON')->firstOrFail();

        $user->roles()->attach($role->id);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'pemohon@test.local',
            'password' => 'Password123!',
            'device_name' => 'phpunit',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'pemohon@test.local');

        $this->assertNotEmpty(
            $response->json('data.token')
        );
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'pemohon@test.local',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'pemohon@test.local',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_view_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'pemohon@test.local',
            'is_active' => true,
        ]);

        $role = Role::where('code', 'PEMOHON')->firstOrFail();

        $user->roles()->attach($role->id);

        $token = $user
            ->createToken('phpunit')
            ->plainTextToken;

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/auth/me');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'pemohon@test.local')
            ->assertJsonPath('data.roles.0', 'PEMOHON');
    }

    public function test_unauthenticated_user_cannot_view_profile(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertUnauthorized();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $tokenResult = $user->createToken('test-token');

        $token = $tokenResult->plainTextToken;
        $tokenId = $tokenResult->accessToken->id;

        // Pastikan token berhasil dibuat.
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $tokenId,
            'tokenable_id' => $user->id,
        ]);

        // Logout menggunakan token tersebut.
        $logoutResponse = $this
            ->withToken($token)
            ->postJson('/api/v1/auth/logout');

        $logoutResponse
            ->assertOk()
            ->assertJsonPath('success', true);

        // Pastikan token benar-benar dihapus.
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId,
        ]);

        // Reset authentication state pada test application.
        $this->app['auth']->forgetGuards();

        // Coba gunakan kembali token yang sudah dihapus.
        $meResponse = $this
            ->withToken($token)
            ->getJson('/api/v1/auth/me');

        $meResponse->assertUnauthorized();
    }
}
