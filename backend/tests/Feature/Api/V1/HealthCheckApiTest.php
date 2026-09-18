<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;

class HealthCheckApiTest extends TestCase
{
    public function test_v1_health_check_returns_healthy_status(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'healthy')
            ->assertJsonPath('data.services.database.status', 'up')
            ->assertJsonPath('data.services.cache.status', 'up')
            ->assertJsonPath('data.services.storage.status', 'up');
    }

    public function test_root_api_health_check_alias_returns_healthy_status(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'healthy');
    }

    public function test_health_check_never_leaks_credentials_or_filesystem_paths(): void
    {
        $response = $this->getJson('/api/v1/health');

        $content = $response->getContent();

        $this->assertStringNotContainsString('password', strtolower($content));
        $this->assertStringNotContainsString('postgres', $content);
        $this->assertStringNotContainsString('D:\\\\', $content);
        $this->assertStringNotContainsString('C:\\\\', $content);
        $this->assertStringNotContainsString('/var/www', $content);
    }
}
