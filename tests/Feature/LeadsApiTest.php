<?php

namespace Tests\Feature;

use App\Models\Dealer;
use App\Models\Lead;
use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LeadsApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $dealer = Dealer::factory()->create([
            'name' => 'Test Dealer',
            'country_code' => 'US'
        ]);

        $this->listing = Listing::factory()->create([
            'dealer_id' => $dealer->id,
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2020,
            'price_cents' => 2500000,
            'status' => 'active'
        ]);
    }

    public function test_lead_creation_success(): void
    {
        Queue::fake();

        $leadData = [
            'listing_id' => $this->listing->id,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1234567890',
            'message' => 'I am interested in this car. Can we schedule a test drive?'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'listing_id',
                    'name',
                    'email',
                    'phone',
                    'message',
                    'source'
                ],
                'correlation_id'
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'Lead submitted successfully'
            ]);

        // Verify lead was created in database
        $this->assertDatabaseHas('leads', [
            'listing_id' => $this->listing->id,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'source' => 'api'
        ]);

        // Verify queue job was dispatched
        Queue::assertPushed(\App\Jobs\ScoreLeadJob::class);
    }

    public function test_lead_creation_validation_errors(): void
    {
        $response = $this->postJson('/api/v1/leads', [
            'listing_id' => 'invalid',
            'name' => '',
            'email' => 'invalid-email'
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'listing_id',
                    'name',
                    'email'
                ]
            ]);
    }

    public function test_lead_creation_nonexistent_listing(): void
    {
        $leadData = [
            'listing_id' => 99999,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonPath('errors.listing_id.0', 'A valid listing ID is required.');
    }

    public function test_lead_creation_inactive_listing(): void
    {
        // Create inactive listing
        $inactiveListing = Listing::factory()->create([
            'dealer_id' => $this->listing->dealer_id,
            'status' => 'sold'
        ]);

        $leadData = [
            'listing_id' => $inactiveListing->id,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonPath('errors.listing_id.0', 'This listing is no longer available for leads.');
    }

    public function test_lead_creation_duplicate_prevention(): void
    {
        // Create first lead
        $leadData = [
            'listing_id' => $this->listing->id,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com'
        ];

        $response1 = $this->postJson('/api/v1/leads', $leadData);
        $response1->assertStatus(201);

        // Try to create duplicate lead within 24 hours
        $response2 = $this->postJson('/api/v1/leads', $leadData);
        $response2->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'You have already submitted a lead for this listing today.');
    }

    public function test_lead_rate_limiting(): void
    {
        $leadData = [
            'listing_id' => $this->listing->id,
            'name' => 'John Doe',
            'email' => 'test@example.com'
        ];

        // Submit 5 leads (the limit)
        for ($i = 1; $i <= 5; $i++) {
            $leadData['email'] = "test{$i}@example.com";
            $response = $this->postJson('/api/v1/leads', $leadData);
            $response->assertStatus(201);
        }

        // 6th lead should be rate limited
        $leadData['email'] = 'test6@example.com';
        $response = $this->postJson('/api/v1/leads', $leadData);
        $response->assertStatus(429);
    }

    public function test_lead_creation_headers(): void
    {
        $leadData = [
            'listing_id' => $this->listing->id,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201)
            ->assertHeader('X-Lead-ID')
            ->assertHeader('X-Query-Time-ms')
            ->assertHeader('X-API-Version', 'v1')
            ->assertHeader('X-Rate-Limit-Remaining');
    }

    public function test_lead_creation_with_optional_fields(): void
    {
        $leadData = [
            'listing_id' => $this->listing->id,
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '+1987654321',
            'message' => 'Very interested in this vehicle!',
            'source' => 'website'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('leads', [
            'listing_id' => $this->listing->id,
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '+1987654321',
            'message' => 'Very interested in this vehicle!',
            'source' => 'website'
        ]);
    }

    public function test_lead_creation_data_cleaning(): void
    {
        $leadData = [
            'listing_id' => $this->listing->id,
            'name' => '  John Doe  ',
            'email' => '  JOHN.DOE@EXAMPLE.COM  ',
            'phone' => '(123) 456-7890'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201);

        // Verify data was cleaned
        $this->assertDatabaseHas('leads', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '(123) 456-7890'
        ]);
    }

    public function test_lead_statistics_endpoint(): void
    {
        // Create some test leads
        Lead::factory()->count(3)->create(['status' => 'new']);
        Lead::factory()->count(2)->create(['status' => 'qualified']);
        Lead::factory()->count(1)->create(['status' => 'converted']);

        $response = $this->getJson('/api/v1/leads');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'total_leads',
                    'qualified_leads',
                    'converted_leads',
                    'average_score',
                    'leads_by_status',
                    'leads_by_source'
                ]
            ]);
    }

    public function test_lead_detail_endpoint(): void
    {
        $lead = Lead::factory()->create([
            'listing_id' => $this->listing->id
        ]);

        $response = $this->getJson("/api/v1/leads/{$lead->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'listing_id',
                    'name',
                    'email',
                    'status',
                    'listing' => [
                        'id',
                        'make',
                        'model',
                        'dealer'
                    ]
                ]
            ]);
    }

    public function test_lead_detail_not_found(): void
    {
        $response = $this->getJson('/api/v1/leads/99999');

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error'
            ]);
    }

    public function test_correlation_id_in_responses(): void
    {
        $correlationId = 'test-correlation-123';

        $response = $this->postJson('/api/v1/leads', [
            'listing_id' => $this->listing->id,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com'
        ], [
            'X-Correlation-ID' => $correlationId
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('correlation_id', $correlationId);
    }
}