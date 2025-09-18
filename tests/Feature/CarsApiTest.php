<?php

namespace Tests\Feature;

use App\Models\Dealer;
use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CarsApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->createTestData();
    }

    private function createTestData(): void
    {
        // Create dealers
        $dealerUS = Dealer::factory()->create([
            'name' => 'Test Dealer US',
            'country_code' => 'US'
        ]);

        $dealerCA = Dealer::factory()->create([
            'name' => 'Test Dealer CA',
            'country_code' => 'CA'
        ]);

        // Create listings
        Listing::factory()->create([
            'dealer_id' => $dealerUS->id,
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2020,
            'price_cents' => 2500000, // $25,000
            'mileage_km' => 50000,
            'country_code' => 'US',
            'city' => 'New York',
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        Listing::factory()->create([
            'dealer_id' => $dealerUS->id,
            'make' => 'Honda',
            'model' => 'Civic',
            'year' => 2019,
            'price_cents' => 2200000, // $22,000
            'mileage_km' => 40000,
            'country_code' => 'US',
            'city' => 'New York',
            'status' => 'active',
            'listed_at' => now()->subDays(2)
        ]);

        Listing::factory()->create([
            'dealer_id' => $dealerCA->id,
            'make' => 'Toyota',
            'model' => 'Prius',
            'year' => 2021,
            'price_cents' => 2800000, // $28,000
            'mileage_km' => 30000,
            'country_code' => 'CA',
            'city' => 'Toronto',
            'status' => 'active',
            'listed_at' => now()->subDays(3)
        ]);

        Listing::factory()->create([
            'dealer_id' => $dealerUS->id,
            'make' => 'BMW',
            'model' => '3 Series',
            'year' => 2018,
            'price_cents' => 3500000, // $35,000
            'mileage_km' => 60000,
            'country_code' => 'US',
            'city' => 'New York',
            'status' => 'sold',
            'listed_at' => now()->subDays(4)
        ]);
    }

    public function test_cars_endpoint_returns_success_response(): void
    {
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
                        'price_formatted',
                        'mileage_km',
                        'mileage_miles',
                        'age_years',
                        'status',
                        'status_color',
                        'dealer' => [
                            'id',
                            'name',
                            'country_code',
                            'country_name'
                        ]
                    ]
                ],
                'correlation_id'
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'Cars retrieved successfully'
            ]);
    }

    public function test_cars_endpoint_filters_by_make(): void
    {
        $response = $this->getJson('/api/v1/cars?make=Toyota');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        foreach ($data as $car) {
            $this->assertEquals('Toyota', $car['make']);
        }
    }

    public function test_cars_endpoint_filters_by_country(): void
    {
        $response = $this->getJson('/api/v1/cars?country_code=US');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        foreach ($data as $car) {
            $this->assertEquals('US', $car['dealer']['country_code']);
        }
    }

    public function test_cars_endpoint_filters_by_status_default_active(): void
    {
        $response = $this->getJson('/api/v1/cars');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        foreach ($data as $car) {
            $this->assertEquals('active', $car['status']);
        }
    }

    public function test_cars_endpoint_filters_by_price_range(): void
    {
        $response = $this->getJson('/api/v1/cars?price_min_cents=2300000&price_max_cents=2700000');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        foreach ($data as $car) {
            $priceCents = $car['price'] * 100;
            $this->assertGreaterThanOrEqual(2300000, $priceCents);
            $this->assertLessThanOrEqual(2700000, $priceCents);
        }
    }

    public function test_cars_endpoint_filters_by_year_range(): void
    {
        $response = $this->getJson('/api/v1/cars?year_min=2019&year_max=2020');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        foreach ($data as $car) {
            $this->assertGreaterThanOrEqual(2019, $car['year']);
            $this->assertLessThanOrEqual(2020, $car['year']);
        }
    }

    public function test_cars_endpoint_supports_pagination(): void
    {
        $response = $this->getJson('/api/v1/cars?per_page=2');

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 2);

        $data = $response->json('data');
        $this->assertLessThanOrEqual(2, count($data));
    }

    public function test_cars_endpoint_respects_max_per_page_limit(): void
    {
        $response = $this->getJson('/api/v1/cars?per_page=100');

        $response->assertStatus(200);

        // Should be limited to 50 maximum
        $data = $response->json('data');
        $this->assertLessThanOrEqual(50, count($data));
    }

    public function test_cars_endpoint_returns_facets_when_requested(): void
    {
        $response = $this->getJson('/api/v1/cars?include_facets=true');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'facets' => [
                    'makes',
                    'years'
                ]
            ]);

        $facets = $response->json('facets');
        $this->assertNotEmpty($facets['makes']);
        $this->assertNotEmpty($facets['years']);
    }

    public function test_cars_endpoint_supports_sorting(): void
    {
        $response = $this->getJson('/api/v1/cars?sort_by=price_cents&sort_direction=asc');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        // Check if prices are in ascending order
        $prices = array_column($data, 'price');
        $sortedPrices = $prices;
        sort($sortedPrices);
        $this->assertEquals($sortedPrices, $prices);
    }

    public function test_cars_endpoint_cache_headers(): void
    {
        $response = $this->getJson('/api/v1/cars');

        $response->assertStatus(200)
            ->assertHeader('Cache-Control')
            ->assertHeader('ETag')
            ->assertHeader('X-Cache')
            ->assertHeader('X-Query-Time-ms')
            ->assertHeader('X-API-Version', 'v1');
    }

    public function test_cars_endpoint_cache_hit_miss_behavior(): void
    {
        // Clear cache to ensure clean state
        Cache::flush();

        // First request should be a cache MISS
        $response1 = $this->getJson('/api/v1/cars?make=Toyota');
        $response1->assertStatus(200)
            ->assertHeader('X-Cache', 'MISS');

        // Second identical request should be a cache HIT
        $response2 = $this->getJson('/api/v1/cars?make=Toyota');
        $response2->assertStatus(200)
            ->assertHeader('X-Cache', 'HIT');

        // Verify same data returned
        $this->assertEquals($response1->json('data'), $response2->json('data'));
    }

    public function test_cars_endpoint_304_response_with_etag(): void
    {
        $response1 = $this->getJson('/api/v1/cars?make=Toyota');
        $response1->assertStatus(200);

        $etag = $response1->headers->get('ETag');
        $this->assertNotNull($etag);

        // Send request with If-None-Match header
        $response2 = $this->getJson('/api/v1/cars?make=Toyota', [
            'If-None-Match' => $etag
        ]);

        $response2->assertStatus(304)
            ->assertHeader('X-Cache', 'HIT-304');
    }

    public function test_car_detail_endpoint(): void
    {
        $listing = Listing::where('status', 'active')->first();

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
                    'dealer',
                    'events'
                ],
                'correlation_id'
            ])
            ->assertJsonPath('data.id', $listing->id);
    }

    public function test_car_detail_endpoint_not_found(): void
    {
        $response = $this->getJson('/api/v1/cars/99999');

        $response->assertStatus(404)
            ->assertJsonStructure([
                'status',
                'message',
                'correlation_id'
            ])
            ->assertJson([
                'status' => 'error'
            ]);
    }

    public function test_cars_endpoint_validation_errors(): void
    {
        $response = $this->getJson('/api/v1/cars?year_min=invalid&per_page=999');

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors'
            ]);
    }

    public function test_cars_endpoint_performance_under_400ms(): void
    {
        // Warm up cache
        $this->getJson('/api/v1/cars?make=Toyota');

        $startTime = microtime(true);
        $response = $this->getJson('/api/v1/cars?make=Toyota');
        $endTime = microtime(true);

        $response->assertStatus(200);

        $responseTime = ($endTime - $startTime) * 1000;
        $this->assertLessThan(400, $responseTime, "Response time was {$responseTime}ms, expected < 400ms");
    }

    public function test_cache_invalidation_after_listing_update(): void
    {
        // First request to populate cache
        $response1 = $this->getJson('/api/v1/cars?make=Toyota');
        $response1->assertStatus(200);

        $initialCount = count($response1->json('data'));

        // Update a listing (should trigger cache invalidation)
        $listing = Listing::where('make', 'Toyota')->first();
        $listing->update(['price_cents' => 3000000]);

        // Request again - should get fresh data
        $response2 = $this->getJson('/api/v1/cars?make=Toyota');
        $response2->assertStatus(200);

        // Verify the price was updated in the response
        $toyotaCars = collect($response2->json('data'))->where('id', $listing->id);
        $this->assertNotEmpty($toyotaCars);

        $updatedCar = $toyotaCars->first();
        $this->assertEquals(30000, $updatedCar['price']); // $30,000
    }
}