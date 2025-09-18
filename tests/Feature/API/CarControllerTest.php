<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use App\Models\Listing;
use App\Models\Dealer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;

class CarControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_returns_paginated_cars_list()
    {
        // Create test data
        $dealer = Dealer::factory()->create();
        $listings = Listing::factory()
            ->count(25)
            ->for($dealer)
            ->create([
                'status' => 'active',
                'listed_at' => now()->subDays(1)
            ]);

        $response = $this->getJson('/api/v1/cars');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'make',
                        'model',
                        'year',
                        'price',
                        'status',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'meta' => [
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page',
                        'from',
                        'to'
                    ],
                    'filters_applied',
                    'query_time_ms'
                ]
            ])
            ->assertHeader('X-Cache')
            ->assertHeader('X-Cache-Key')
            ->assertHeader('X-Query-Time-ms')
            ->assertHeader('X-API-Version', 'v1')
            ->assertHeader('Cache-Control', 'public, max-age=300, s-maxage=300')
            ->assertHeader('ETag');

        // Verify pagination
        $this->assertEquals(20, count($response->json('data')));
        $this->assertEquals(25, $response->json('meta.pagination.total'));
        $this->assertEquals(1, $response->json('meta.pagination.current_page'));
    }

    /** @test */
    public function it_filters_cars_by_make()
    {
        $dealer = Dealer::factory()->create();

        // Create cars with different makes
        Listing::factory()->for($dealer)->create([
            'make' => 'Toyota',
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        Listing::factory()->for($dealer)->create([
            'make' => 'Honda',
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $response = $this->getJson('/api/v1/cars?make=Toyota');

        $response->assertStatus(200);

        $cars = $response->json('data');
        $this->assertCount(1, $cars);
        $this->assertEquals('Toyota', $cars[0]['make']);

        // Verify filters were applied
        $this->assertEquals(['make' => 'Toyota'], $response->json('meta.filters_applied'));
    }

    /** @test */
    public function it_filters_cars_by_year_range()
    {
        $dealer = Dealer::factory()->create();

        Listing::factory()->for($dealer)->create([
            'year' => 2020,
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        Listing::factory()->for($dealer)->create([
            'year' => 2015,
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $response = $this->getJson('/api/v1/cars?year_from=2018&year_to=2022');

        $response->assertStatus(200);

        $cars = $response->json('data');
        $this->assertCount(1, $cars);
        $this->assertEquals(2020, $cars[0]['year']);
    }

    /** @test */
    public function it_filters_cars_by_price_range()
    {
        $dealer = Dealer::factory()->create();

        Listing::factory()->for($dealer)->create([
            'price' => 25000,
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        Listing::factory()->for($dealer)->create([
            'price' => 35000,
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $response = $this->getJson('/api/v1/cars?price_from=20000&price_to=30000');

        $response->assertStatus(200);

        $cars = $response->json('data');
        $this->assertCount(1, $cars);
        $this->assertEquals(25000, $cars[0]['price']);
    }

    /** @test */
    public function it_respects_per_page_parameter()
    {
        $dealer = Dealer::factory()->create();
        Listing::factory()
            ->count(15)
            ->for($dealer)
            ->create([
                'status' => 'active',
                'listed_at' => now()->subDays(1)
            ]);

        $response = $this->getJson('/api/v1/cars?per_page=5');

        $response->assertStatus(200);

        $this->assertCount(5, $response->json('data'));
        $this->assertEquals(5, $response->json('meta.pagination.per_page'));
    }

    /** @test */
    public function it_limits_per_page_to_maximum_50()
    {
        $response = $this->getJson('/api/v1/cars?per_page=100');

        $response->assertStatus(200);
        $this->assertEquals(50, $response->json('meta.pagination.per_page'));
    }

    /** @test */
    public function it_includes_facets_when_requested()
    {
        $dealer = Dealer::factory()->create();

        // Create cars with different makes and years
        Listing::factory()->for($dealer)->create([
            'make' => 'Toyota',
            'year' => 2020,
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        Listing::factory()->for($dealer)->create([
            'make' => 'Honda',
            'year' => 2019,
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $response = $this->getJson('/api/v1/cars?include_facets=true');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta',
                'facets' => [
                    'makes',
                    'years'
                ]
            ]);

        $facets = $response->json('facets');
        $this->assertArrayHasKey('Toyota', $facets['makes']);
        $this->assertArrayHasKey('Honda', $facets['makes']);
        $this->assertArrayHasKey('2020', $facets['years']);
        $this->assertArrayHasKey('2019', $facets['years']);
    }

    /** @test */
    public function it_uses_cache_and_returns_cache_hit_on_subsequent_requests()
    {
        $dealer = Dealer::factory()->create();
        Listing::factory()->for($dealer)->create([
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        // First request - should be cache MISS
        $response1 = $this->getJson('/api/v1/cars');
        $response1->assertStatus(200);
        $this->assertEquals('MISS', $response1->headers->get('X-Cache'));

        // Second request - should be cache HIT
        $response2 = $this->getJson('/api/v1/cars');
        $response2->assertStatus(200);
        $this->assertEquals('HIT', $response2->headers->get('X-Cache'));

        // Cache keys should be the same
        $this->assertEquals(
            $response1->headers->get('X-Cache-Key'),
            $response2->headers->get('X-Cache-Key')
        );
    }

    /** @test */
    public function it_returns_304_when_etag_matches()
    {
        $dealer = Dealer::factory()->create();
        Listing::factory()->for($dealer)->create([
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        // First request to get ETag
        $response1 = $this->getJson('/api/v1/cars');
        $etag = $response1->headers->get('ETag');

        // Second request with If-None-Match header
        $response2 = $this->getJson('/api/v1/cars', [
            'If-None-Match' => $etag
        ]);

        $response2->assertStatus(304);
        $this->assertEquals('HIT-304', $response2->headers->get('X-Cache'));
        $this->assertEquals($etag, $response2->headers->get('ETag'));
    }

    /** @test */
    public function it_handles_search_parameter()
    {
        $dealer = Dealer::factory()->create();

        Listing::factory()->for($dealer)->create([
            'make' => 'Toyota',
            'model' => 'Camry',
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        Listing::factory()->for($dealer)->create([
            'make' => 'Honda',
            'model' => 'Civic',
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $response = $this->getJson('/api/v1/cars?search=Toyota');

        $response->assertStatus(200);

        // All returned cars should match the search term
        $cars = $response->json('data');
        foreach ($cars as $car) {
            $this->assertStringContainsStringIgnoringCase('Toyota', $car['make'] . ' ' . $car['model']);
        }
    }

    /** @test */
    public function it_sorts_cars_by_price()
    {
        $dealer = Dealer::factory()->create();

        Listing::factory()->for($dealer)->create([
            'price' => 30000,
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        Listing::factory()->for($dealer)->create([
            'price' => 20000,
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $response = $this->getJson('/api/v1/cars?sort_by=price&sort_direction=asc');

        $response->assertStatus(200);

        $cars = $response->json('data');
        $this->assertEquals(20000, $cars[0]['price']);
        $this->assertEquals(30000, $cars[1]['price']);
    }

    /** @test */
    public function it_returns_different_cache_keys_for_different_filters()
    {
        $dealer = Dealer::factory()->create();
        Listing::factory()->for($dealer)->create([
            'make' => 'Toyota',
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $response1 = $this->getJson('/api/v1/cars?make=Toyota');
        $response2 = $this->getJson('/api/v1/cars?make=Honda');

        $this->assertNotEquals(
            $response1->headers->get('X-Cache-Key'),
            $response2->headers->get('X-Cache-Key')
        );
    }

    /** @test */
    public function it_handles_error_gracefully()
    {
        // Mock an error condition by using invalid parameters that might cause issues
        $response = $this->getJson('/api/v1/cars?per_page=invalid');

        // Should still return 200 with default pagination
        $response->assertStatus(200);
        $this->assertEquals(20, $response->json('meta.pagination.per_page'));
    }

    /** @test */
    public function it_returns_car_details_by_id()
    {
        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $response = $this->getJson("/api/v1/cars/{$listing->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'make',
                    'model',
                    'year',
                    'price',
                    'status',
                    'dealer',
                    'events'
                ]
            ])
            ->assertHeader('X-Cache')
            ->assertHeader('X-Cache-Key')
            ->assertHeader('Cache-Control', 'public, max-age=600, s-maxage=600')
            ->assertHeader('ETag');

        $this->assertEquals($listing->id, $response->json('data.id'));
    }

    /** @test */
    public function it_returns_404_for_non_existent_car()
    {
        $response = $this->getJson('/api/v1/cars/99999');

        $response->assertStatus(404);
        $this->assertEquals('MISS', $response->headers->get('X-Cache'));
    }

    /** @test */
    public function it_returns_popular_makes()
    {
        $dealer = Dealer::factory()->create();

        // Create multiple cars with different makes
        Listing::factory()->for($dealer)->count(3)->create([
            'make' => 'Toyota',
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        Listing::factory()->for($dealer)->count(2)->create([
            'make' => 'Honda',
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $response = $this->getJson('/api/v1/cars/popular-makes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'makes' => [
                        '*' => [
                            'make',
                            'count'
                        ]
                    ],
                    'total_makes',
                    'country_code'
                ]
            ])
            ->assertHeader('X-Cache')
            ->assertHeader('Cache-Control', 'public, max-age=1800, s-maxage=1800');

        $makes = $response->json('data.makes');
        $this->assertEquals('Toyota', $makes[0]['make']);
        $this->assertEquals(3, $makes[0]['count']);
        $this->assertEquals('Honda', $makes[1]['make']);
        $this->assertEquals(2, $makes[1]['count']);
    }

    /** @test */
    public function it_filters_popular_makes_by_country()
    {
        $dealer = Dealer::factory()->create();

        Listing::factory()->for($dealer)->create([
            'make' => 'Toyota',
            'country_code' => 'US',
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        Listing::factory()->for($dealer)->create([
            'make' => 'BMW',
            'country_code' => 'DE',
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $response = $this->getJson('/api/v1/cars/popular-makes?country_code=US');

        $response->assertStatus(200);

        $makes = $response->json('data.makes');
        $this->assertCount(1, $makes);
        $this->assertEquals('Toyota', $makes[0]['make']);
        $this->assertEquals('US', $response->json('data.country_code'));
    }
}