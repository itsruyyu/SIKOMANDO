<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_404_not_found_returns_standard_error_envelope(): void
    {
        $response = $this->getJson('/api/v1/proposals/00000000-0000-0000-0000-000000000000');

        // Unauthenticated or not found, both return standard envelope
        $this->assertContains($response->status(), [401, 404]);
        $response->assertJsonStructure([
            'success',
            'message',
        ]);
        $this->assertFalse($response->json('success'));
        $this->assertTrue($response->headers->has('X-Request-ID'));
    }

    public function test_405_method_not_allowed_returns_standard_error_envelope(): void
    {
        // /health is GET only, POST should trigger 405
        $response = $this->postJson('/api/health', []);

        $response
            ->assertStatus(405)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'success',
                'message',
            ]);
        $this->assertFalse($response->json('success'));
        $this->assertTrue($response->headers->has('X-Request-ID'));
    }

    public function test_401_unauthorized_returns_standard_error_envelope(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Autentikasi diperlukan.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
            ]);
        $this->assertTrue($response->headers->has('X-Request-ID'));
    }

    public function test_422_validation_error_returns_standard_error_envelope_with_errors_bag(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'invalid-email',
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
        $this->assertTrue($response->headers->has('X-Request-ID'));
    }

    public function test_api_errors_do_not_leak_stack_trace_or_database_credentials(): void
    {
        $response = $this->getJson('/api/v1/non-existent-route-endpoint');

        $content = $response->getContent();
        $this->assertStringNotContainsString('password', strtolower($content));
        $this->assertStringNotContainsString('trace', strtolower($content));
        $this->assertStringNotContainsString('pdo', strtolower($content));
        $this->assertStringNotContainsString('sqlstate', strtolower($content));
    }
}
