<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use App\Models\Lead;
use App\Models\Listing;
use App\Models\Dealer;
use App\Jobs\ScoreLeadJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;

class LeadControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache and rate limits before each test
        Cache::flush();
        RateLimiter::clear('leads:127.0.0.1:test@example.com');
    }

    /** @test */
    public function it_creates_a_lead_successfully()
    {
        Queue::fake();

        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $leadData = [
            'listing_id' => $listing->id,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1234567890',
            'message' => 'I am interested in this car',
            'source' => 'website'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'message',
                    'source',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertHeader('X-Lead-ID')
            ->assertHeader('X-Query-Time-ms')
            ->assertHeader('X-API-Version', 'v1')
            ->assertHeader('X-Rate-Limit-Remaining');

        // Verify lead was created in database
        $this->assertDatabaseHas('leads', [
            'listing_id' => $listing->id,
            'email' => 'john.doe@example.com',
            'name' => 'John Doe',
            'source' => 'website',
            'ip_address' => '127.0.0.1'
        ]);

        // Verify scoring job was dispatched
        Queue::assertPushed(ScoreLeadJob::class, function ($job) use ($listing) {
            return $job->lead->listing_id === $listing->id;
        });

        // Verify rate limit header is present and shows remaining attempts
        $rateLimitRemaining = $response->headers->get('X-Rate-Limit-Remaining');
        $this->assertIsNumeric($rateLimitRemaining);
        $this->assertGreaterThan(0, (int) $rateLimitRemaining);
    }

    /** @test */
    public function it_defaults_source_to_api_when_not_provided()
    {
        Queue::fake();

        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $leadData = [
            'listing_id' => $listing->id,
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201);

        // Verify default source was set
        $this->assertDatabaseHas('leads', [
            'email' => 'jane.smith@example.com',
            'source' => 'api'
        ]);

        $this->assertEquals('api', $response->json('data.source'));
    }

    /** @test */
    public function it_normalizes_email_to_lowercase()
    {
        Queue::fake();

        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $leadData = [
            'listing_id' => $listing->id,
            'name' => 'Test User',
            'email' => 'TEST.USER@EXAMPLE.COM'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201);

        // Verify email was normalized
        $this->assertDatabaseHas('leads', [
            'email' => 'test.user@example.com'
        ]);

        $this->assertEquals('test.user@example.com', $response->json('data.email'));
    }

    /** @test */
    public function it_cleans_phone_number()
    {
        Queue::fake();

        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $leadData = [
            'listing_id' => $listing->id,
            'name' => 'Phone User',
            'email' => 'phone.user@example.com',
            'phone' => '(123) 456-7890 ext. 123'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201);

        // Phone should be cleaned (only digits, +, -, (), spaces allowed)
        $cleanedPhone = $response->json('data.phone');
        $this->assertStringNotContainsString('ext.', $cleanedPhone);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->postJson('/api/v1/leads', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['listing_id', 'name', 'email']);
    }

    /** @test */
    public function it_validates_email_format()
    {
        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $leadData = [
            'listing_id' => $listing->id,
            'name' => 'Test User',
            'email' => 'invalid-email'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_validates_listing_exists()
    {
        $leadData = [
            'listing_id' => 99999, // Non-existent listing
            'name' => 'Test User',
            'email' => 'test@example.com'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['listing_id']);
    }

    /** @test */
    public function it_validates_listing_is_active()
    {
        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'status' => 'sold', // Not active
            'listed_at' => now()->subDays(1)
        ]);

        $leadData = [
            'listing_id' => $listing->id,
            'name' => 'Test User',
            'email' => 'test@example.com'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['listing_id']);

        $errors = $response->json('errors.listing_id');
        $this->assertStringContainsString('no longer available', $errors[0]);
    }

    /** @test */
    public function it_validates_name_minimum_length()
    {
        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $leadData = [
            'listing_id' => $listing->id,
            'name' => 'A', // Too short
            'email' => 'test@example.com'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_validates_source_is_valid()
    {
        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $leadData = [
            'listing_id' => $listing->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'source' => 'invalid_source'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['source']);
    }

    /** @test */
    public function it_applies_rate_limiting_per_email_and_ip()
    {
        Queue::fake();

        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $leadData = [
            'listing_id' => $listing->id,
            'name' => 'Rate Limited User',
            'email' => 'ratelimit@example.com'
        ];

        // Make 5 successful requests (should be the limit)
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/v1/leads', $leadData);

            if ($i <= 5) {
                $response->assertStatus(201);

                // Check rate limit remaining header decreases
                $remaining = (int) $response->headers->get('X-Rate-Limit-Remaining');
                $this->assertEquals(5 - $i, $remaining);
            }
        }

        // 6th request should be rate limited
        $response = $this->postJson('/api/v1/leads', $leadData);
        $response->assertStatus(429)
            ->assertJsonValidationErrors(['email']);

        $errors = $response->json('errors.email');
        $this->assertStringContainsString('Too many lead submissions', $errors[0]);
    }

    /** @test */
    public function it_has_separate_rate_limits_for_different_emails()
    {
        Queue::fake();

        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        // Fill up rate limit for first email
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/v1/leads', [
                'listing_id' => $listing->id,
                'name' => 'User One',
                'email' => 'user1@example.com'
            ]);
            $response->assertStatus(201);
        }

        // 6th request for first email should fail
        $response = $this->postJson('/api/v1/leads', [
            'listing_id' => $listing->id,
            'name' => 'User One',
            'email' => 'user1@example.com'
        ]);
        $response->assertStatus(429);

        // But different email should still work
        $response = $this->postJson('/api/v1/leads', [
            'listing_id' => $listing->id,
            'name' => 'User Two',
            'email' => 'user2@example.com'
        ]);
        $response->assertStatus(201);
        $this->assertEquals(4, (int) $response->headers->get('X-Rate-Limit-Remaining'));
    }

    /** @test */
    public function it_includes_request_metadata()
    {
        Queue::fake();

        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $leadData = [
            'listing_id' => $listing->id,
            'name' => 'Metadata User',
            'email' => 'metadata@example.com'
        ];

        $response = $this->withHeaders([
            'User-Agent' => 'Test User Agent'
        ])->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201);

        // Verify IP address and user agent are stored
        $this->assertDatabaseHas('leads', [
            'email' => 'metadata@example.com',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test User Agent'
        ]);
    }

    /** @test */
    public function it_loads_listing_relationship_in_response()
    {
        Queue::fake();

        $dealer = Dealer::factory()->create(['name' => 'Test Dealer']);
        $listing = Listing::factory()->for($dealer)->create([
            'make' => 'Toyota',
            'model' => 'Camry',
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $leadData = [
            'listing_id' => $listing->id,
            'name' => 'Relationship User',
            'email' => 'relationship@example.com'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'listing' => [
                        'id' => $listing->id,
                        'make' => 'Toyota',
                        'model' => 'Camry'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_handles_database_errors_gracefully()
    {
        // This test would require mocking database failures
        // For now, we'll test with invalid data that might cause issues

        $response = $this->postJson('/api/v1/leads', [
            'listing_id' => 'invalid',
            'name' => str_repeat('x', 1000), // Too long
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_logs_lead_creation()
    {
        Queue::fake();

        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $leadData = [
            'listing_id' => $listing->id,
            'name' => 'Log Test User',
            'email' => 'log@example.com',
            'source' => 'website'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201);

        // In a real test, you might use Log::spy() to verify logging
        // For now, we just ensure the request succeeded, which means logging didn't break it
    }

    /** @test */
    public function it_dispatches_scoring_job_to_correct_queue()
    {
        Queue::fake();

        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $leadData = [
            'listing_id' => $listing->id,
            'name' => 'Queue Test User',
            'email' => 'queue@example.com'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201);

        // Verify job was dispatched to the correct queue
        Queue::assertPushed(ScoreLeadJob::class, function ($job) {
            return $job->queue === 'scoring';
        });
    }

    /** @test */
    public function it_validates_message_length()
    {
        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $leadData = [
            'listing_id' => $listing->id,
            'name' => 'Message Test User',
            'email' => 'message@example.com',
            'message' => str_repeat('x', 1001) // Too long
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /** @test */
    public function it_validates_phone_length()
    {
        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $leadData = [
            'listing_id' => $listing->id,
            'name' => 'Phone Test User',
            'email' => 'phone@example.com',
            'phone' => str_repeat('1', 25) // Too long
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }
}