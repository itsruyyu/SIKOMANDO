<?php

namespace Tests\Feature\Api\V1;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiErrorResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_receives_standard_unauthorized_response(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Autentikasi diperlukan.',
            ]);
    }

    public function test_invalid_login_receives_standard_validation_response(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'not-an-email',
            'password' => '',
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Data yang dikirim tidak valid.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
    }

    public function test_pemohon_receives_standard_forbidden_response(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $role = Role::query()->firstOrCreate(
            ['code' => 'PEMOHON'],
            [
                'name' => 'Pemohon',
                'description' => 'Role pemohon hibah',
            ]
        );

        $user->roles()->attach($role->id);

        $token = $user
            ->createToken('api-error-test')
            ->plainTextToken;

        $response = $this
            ->withToken($token)
            ->postJson('/api/v1/proposals', []);

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk melakukan tindakan ini.',
            ]);
    }
}
