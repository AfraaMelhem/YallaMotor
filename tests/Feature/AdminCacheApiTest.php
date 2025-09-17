<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminCacheApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set admin API key for testing
        config(['app.admin_api_key' => 'test-admin-key-123']);
    }

    public function test_cache_purge_requires_api_key(): void
    {
        $response = $this->postJson('/api/v1/admin/cache/purge');

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid or missing API key'
            ]);
    }

    public function test_cache_purge_with_invalid_api_key(): void
    {
        $response = $this->postJson('/api/v1/admin/cache/purge', [], [
            'X-Api-Key' => 'invalid-key'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid or missing API key'
            ]);
    }

    public function test_cache_purge_with_valid_api_key(): void
    {
        // Set some cache values
        Cache::put('test_key_1', 'value1', 300);
        Cache::put('test_key_2', 'value2', 300);

        $response = $this->postJson('/api/v1/admin/cache/purge', [
            'keys' => ['test_key_1', 'test_key_2']
        ], [
            'X-Api-Key' => 'test-admin-key-123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'purged_keys',
                    'purged_count',
                    'query_time_ms'
                ],
                'correlation_id'
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'Cache purged successfully'
            ]);

        // Verify keys were purged
        $this->assertNull(Cache::get('test_key_1'));
        $this->assertNull(Cache::get('test_key_2'));
    }

    public function test_cache_purge_by_tags(): void
    {
        $response = $this->postJson('/api/v1/admin/cache/purge', [
            'tags' => ['listing:123', 'country:US']
        ], [
            'X-Api-Key' => 'test-admin-key-123'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ]);
    }

    public function test_cache_purge_all(): void
    {
        // Set some cache values
        Cache::put('test_all_1', 'value1', 300);
        Cache::put('test_all_2', 'value2', 300);

        $response = $this->postJson('/api/v1/admin/cache/purge', [], [
            'X-Api-Key' => 'test-admin-key-123'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ]);

        // Verify all cache was cleared
        $this->assertNull(Cache::get('test_all_1'));
        $this->assertNull(Cache::get('test_all_2'));
    }

    public function test_cache_purge_validation_errors(): void
    {
        $response = $this->postJson('/api/v1/admin/cache/purge', [
            'keys' => ['valid_key', 123], // Invalid key type
            'tags' => ['valid:tag', 'invalid tag with spaces']
        ], [
            'X-Api-Key' => 'test-admin-key-123'
        ]);

        $response->assertStatus(400)
            ->assertJsonStructure([
                'status',
                'message',
                'errors',
                'correlation_id'
            ]);
    }

    public function test_cache_status_endpoint(): void
    {
        $response = $this->getJson('/api/v1/admin/cache/status', [
            'X-Api-Key' => 'test-admin-key-123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'cache_driver',
                    'timestamp',
                    'uptime_check'
                ],
                'correlation_id'
            ])
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'uptime_check' => 'ok'
                ]
            ]);
    }

    public function test_cache_status_requires_api_key(): void
    {
        $response = $this->getJson('/api/v1/admin/cache/status');

        $response->assertStatus(401);
    }

    public function test_correlation_id_in_admin_responses(): void
    {
        $correlationId = 'admin-test-123';

        $response = $this->postJson('/api/v1/admin/cache/purge', [], [
            'X-Api-Key' => 'test-admin-key-123',
            'X-Correlation-ID' => $correlationId
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('correlation_id', $correlationId);
    }

    public function test_cache_purge_logs_activity(): void
    {
        // This would ideally test that logs are written, but for now we just verify the endpoint works
        $response = $this->postJson('/api/v1/admin/cache/purge', [
            'keys' => ['log_test_key']
        ], [
            'X-Api-Key' => 'test-admin-key-123'
        ]);

        $response->assertStatus(200);
    }

    public function test_cache_purge_response_includes_timing(): void
    {
        $response = $this->postJson('/api/v1/admin/cache/purge', [], [
            'X-Api-Key' => 'test-admin-key-123'
        ]);

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('query_time_ms', $data);
        $this->assertIsFloat($data['query_time_ms']);
        $this->assertGreaterThan(0, $data['query_time_ms']);
    }
}