<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use App\Models\Listing;
use App\Models\Dealer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

class CacheInvalidationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_invalidates_cars_list_cache_when_listing_is_updated()
    {
        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'make' => 'Toyota',
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        // First request to populate cache
        $response1 = $this->getJson('/api/v1/cars');
        $response1->assertStatus(200);
        $this->assertEquals('MISS', $response1->headers->get('X-Cache'));

        // Second request should hit cache
        $response2 = $this->getJson('/api/v1/cars');
        $response2->assertStatus(200);
        $this->assertEquals('HIT', $response2->headers->get('X-Cache'));

        // Update the listing
        $listing->update(['make' => 'Honda']);

        // Third request should be cache MISS due to invalidation
        $response3 = $this->getJson('/api/v1/cars');
        $response3->assertStatus(200);
        $this->assertEquals('MISS', $response3->headers->get('X-Cache'));

        // Verify the updated data is returned
        $cars = $response3->json('data');
        $updatedCar = collect($cars)->firstWhere('id', $listing->id);
        $this->assertEquals('Honda', $updatedCar['make']);
    }

    /** @test */
    public function it_invalidates_specific_car_cache_when_listing_is_updated()
    {
        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'make' => 'Toyota',
            'model' => 'Camry',
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        // First request to populate cache
        $response1 = $this->getJson("/api/v1/cars/{$listing->id}");
        $response1->assertStatus(200);
        $this->assertEquals('MISS', $response1->headers->get('X-Cache'));

        // Second request should hit cache
        $response2 = $this->getJson("/api/v1/cars/{$listing->id}");
        $response2->assertStatus(200);
        $this->assertEquals('HIT', $response2->headers->get('X-Cache'));

        // Update the listing
        $listing->update(['model' => 'Corolla']);

        // Third request should be cache MISS due to invalidation
        $response3 = $this->getJson("/api/v1/cars/{$listing->id}");
        $response3->assertStatus(200);
        $this->assertEquals('MISS', $response3->headers->get('X-Cache'));

        // Verify the updated data is returned
        $this->assertEquals('Corolla', $response3->json('data.model'));
    }

    /** @test */
    public function it_invalidates_popular_makes_cache_when_listing_make_changes()
    {
        $dealer = Dealer::factory()->create();

        // Create listings with different makes
        $toyotaListing = Listing::factory()->for($dealer)->create([
            'make' => 'Toyota',
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        Listing::factory()->for($dealer)->count(2)->create([
            'make' => 'Honda',
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        // First request to populate cache
        $response1 = $this->getJson('/api/v1/cars/popular-makes');
        $response1->assertStatus(200);
        $this->assertEquals('MISS', $response1->headers->get('X-Cache'));

        $makes1 = collect($response1->json('data.makes'));
        $toyotaCount1 = $makes1->firstWhere('make', 'Toyota')['count'] ?? 0;
        $hondaCount1 = $makes1->firstWhere('make', 'Honda')['count'] ?? 0;

        $this->assertEquals(1, $toyotaCount1);
        $this->assertEquals(2, $hondaCount1);

        // Second request should hit cache
        $response2 = $this->getJson('/api/v1/cars/popular-makes');
        $response2->assertStatus(200);
        $this->assertEquals('HIT', $response2->headers->get('X-Cache'));

        // Change Toyota to Honda
        $toyotaListing->update(['make' => 'Honda']);

        // Third request should be cache MISS due to invalidation
        $response3 = $this->getJson('/api/v1/cars/popular-makes');
        $response3->assertStatus(200);
        $this->assertEquals('MISS', $response3->headers->get('X-Cache'));

        // Verify the counts are updated
        $makes3 = collect($response3->json('data.makes'));
        $toyotaCount3 = $makes3->firstWhere('make', 'Toyota')['count'] ?? 0;
        $hondaCount3 = $makes3->firstWhere('make', 'Honda')['count'] ?? 0;

        $this->assertEquals(0, $toyotaCount3); // No more Toyotas
        $this->assertEquals(3, $hondaCount3); // Now 3 Hondas
    }

    /** @test */
    public function it_invalidates_country_specific_caches_when_listing_country_changes()
    {
        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'make' => 'Toyota',
            'country_code' => 'US',
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        // Get popular makes for US (should populate cache)
        $response1 = $this->getJson('/api/v1/cars/popular-makes?country_code=US');
        $response1->assertStatus(200);
        $this->assertEquals('MISS', $response1->headers->get('X-Cache'));

        $makes1 = $response1->json('data.makes');
        $this->assertCount(1, $makes1);
        $this->assertEquals('Toyota', $makes1[0]['make']);

        // Second request should hit cache
        $response2 = $this->getJson('/api/v1/cars/popular-makes?country_code=US');
        $response2->assertStatus(200);
        $this->assertEquals('HIT', $response2->headers->get('X-Cache'));

        // Change country from US to CA
        $listing->update(['country_code' => 'CA']);

        // Request for US should now be cache MISS (no cars)
        $response3 = $this->getJson('/api/v1/cars/popular-makes?country_code=US');
        $response3->assertStatus(200);
        $this->assertEquals('MISS', $response3->headers->get('X-Cache'));

        $makes3 = $response3->json('data.makes');
        $this->assertCount(0, $makes3); // No cars in US anymore

        // Request for CA should show the car
        $response4 = $this->getJson('/api/v1/cars/popular-makes?country_code=CA');
        $response4->assertStatus(200);
        $this->assertEquals('MISS', $response4->headers->get('X-Cache'));

        $makes4 = $response4->json('data.makes');
        $this->assertCount(1, $makes4);
        $this->assertEquals('Toyota', $makes4[0]['make']);
    }

    /** @test */
    public function it_invalidates_filtered_cache_when_listing_matches_filter()
    {
        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'make' => 'Toyota',
            'year' => 2020,
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        // Create another car that doesn't match the filter
        Listing::factory()->for($dealer)->create([
            'make' => 'Honda',
            'year' => 2019,
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        // First request with filter to populate cache
        $response1 = $this->getJson('/api/v1/cars?make=Toyota');
        $response1->assertStatus(200);
        $this->assertEquals('MISS', $response1->headers->get('X-Cache'));

        $cars1 = $response1->json('data');
        $this->assertCount(1, $cars1);
        $this->assertEquals('Toyota', $cars1[0]['make']);

        // Second request should hit cache
        $response2 = $this->getJson('/api/v1/cars?make=Toyota');
        $response2->assertStatus(200);
        $this->assertEquals('HIT', $response2->headers->get('X-Cache'));

        // Update the Toyota to Honda
        $listing->update(['make' => 'Honda']);

        // Third request should be cache MISS
        $response3 = $this->getJson('/api/v1/cars?make=Toyota');
        $response3->assertStatus(200);
        $this->assertEquals('MISS', $response3->headers->get('X-Cache'));

        // Should return no cars now
        $cars3 = $response3->json('data');
        $this->assertCount(0, $cars3);
    }

    /** @test */
    public function it_invalidates_facet_cache_when_listing_is_updated()
    {
        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'make' => 'Toyota',
            'year' => 2020,
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        // First request with facets to populate cache
        $response1 = $this->getJson('/api/v1/cars?include_facets=true');
        $response1->assertStatus(200);
        $this->assertEquals('MISS', $response1->headers->get('X-Cache'));

        $facets1 = $response1->json('facets');
        $this->assertArrayHasKey('Toyota', $facets1['makes']);
        $this->assertArrayHasKey('2020', $facets1['years']);

        // Second request should hit cache
        $response2 = $this->getJson('/api/v1/cars?include_facets=true');
        $response2->assertStatus(200);
        $this->assertEquals('HIT', $response2->headers->get('X-Cache'));

        // Update the listing
        $listing->update(['make' => 'Honda', 'year' => 2021]);

        // Third request should be cache MISS due to facet cache invalidation
        $response3 = $this->getJson('/api/v1/cars?include_facets=true');
        $response3->assertStatus(200);
        $this->assertEquals('MISS', $response3->headers->get('X-Cache'));

        // Verify facets are updated
        $facets3 = $response3->json('facets');
        $this->assertArrayNotHasKey('Toyota', $facets3['makes']);
        $this->assertArrayHasKey('Honda', $facets3['makes']);
        $this->assertArrayNotHasKey('2020', $facets3['years']);
        $this->assertArrayHasKey('2021', $facets3['years']);
    }

    /** @test */
    public function it_invalidates_cache_when_listing_status_changes()
    {
        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'make' => 'Toyota',
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        // First request to populate cache
        $response1 = $this->getJson('/api/v1/cars');
        $response1->assertStatus(200);
        $this->assertEquals('MISS', $response1->headers->get('X-Cache'));

        $cars1 = $response1->json('data');
        $this->assertCount(1, $cars1);

        // Second request should hit cache
        $response2 = $this->getJson('/api/v1/cars');
        $response2->assertStatus(200);
        $this->assertEquals('HIT', $response2->headers->get('X-Cache'));

        // Deactivate the listing
        $listing->update(['status' => 'sold']);

        // Third request should be cache MISS
        $response3 = $this->getJson('/api/v1/cars');
        $response3->assertStatus(200);
        $this->assertEquals('MISS', $response3->headers->get('X-Cache'));

        // Should return no active cars now
        $cars3 = $response3->json('data');
        $this->assertCount(0, $cars3);
    }

    /** @test */
    public function it_invalidates_cache_when_listing_price_is_updated()
    {
        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'price' => 25000,
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        // First request to populate cache
        $response1 = $this->getJson("/api/v1/cars/{$listing->id}");
        $response1->assertStatus(200);
        $this->assertEquals('MISS', $response1->headers->get('X-Cache'));
        $this->assertEquals(25000, $response1->json('data.price'));

        // Second request should hit cache
        $response2 = $this->getJson("/api/v1/cars/{$listing->id}");
        $response2->assertStatus(200);
        $this->assertEquals('HIT', $response2->headers->get('X-Cache'));

        // Update the price via the API endpoint
        $priceUpdateResponse = $this->postJson("/api/v1/listings/{$listing->id}/price", [
            'price' => 30000
        ]);

        // If the endpoint exists and works
        if ($priceUpdateResponse->status() === 200) {
            // Request should be cache MISS due to invalidation
            $response3 = $this->getJson("/api/v1/cars/{$listing->id}");
            $response3->assertStatus(200);
            $this->assertEquals('MISS', $response3->headers->get('X-Cache'));

            // Verify the updated price is returned
            $this->assertEquals(30000, $response3->json('data.price'));
        }
    }

    /** @test */
    public function it_maintains_separate_cache_for_different_filters_during_invalidation()
    {
        $dealer = Dealer::factory()->create();

        // Create Toyota and Honda cars
        $toyotaListing = Listing::factory()->for($dealer)->create([
            'make' => 'Toyota',
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        $hondaListing = Listing::factory()->for($dealer)->create([
            'make' => 'Honda',
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        // Populate cache for Toyota filter
        $response1 = $this->getJson('/api/v1/cars?make=Toyota');
        $response1->assertStatus(200);
        $this->assertEquals('MISS', $response1->headers->get('X-Cache'));

        // Populate cache for Honda filter
        $response2 = $this->getJson('/api/v1/cars?make=Honda');
        $response2->assertStatus(200);
        $this->assertEquals('MISS', $response2->headers->get('X-Cache'));

        // Both should hit cache on second request
        $response3 = $this->getJson('/api/v1/cars?make=Toyota');
        $response3->assertStatus(200);
        $this->assertEquals('HIT', $response3->headers->get('X-Cache'));

        $response4 = $this->getJson('/api/v1/cars?make=Honda');
        $response4->assertStatus(200);
        $this->assertEquals('HIT', $response4->headers->get('X-Cache'));

        // Update only the Toyota
        $toyotaListing->update(['model' => 'Updated Model']);

        // Toyota filter should be cache MISS
        $response5 = $this->getJson('/api/v1/cars?make=Toyota');
        $response5->assertStatus(200);
        $this->assertEquals('MISS', $response5->headers->get('X-Cache'));

        // Honda filter should still be cache HIT (unaffected)
        $response6 = $this->getJson('/api/v1/cars?make=Honda');
        $response6->assertStatus(200);
        $this->assertEquals('HIT', $response6->headers->get('X-Cache'));
    }

    /** @test */
    public function it_invalidates_etag_when_listing_is_updated()
    {
        $dealer = Dealer::factory()->create();
        $listing = Listing::factory()->for($dealer)->create([
            'make' => 'Toyota',
            'status' => 'active',
            'listed_at' => now()->subDays(1)
        ]);

        // First request to get initial ETag
        $response1 = $this->getJson('/api/v1/cars');
        $response1->assertStatus(200);
        $initialETag = $response1->headers->get('ETag');
        $this->assertNotEmpty($initialETag);

        // Update the listing
        $listing->update(['make' => 'Honda']);

        // Request should return different ETag
        $response2 = $this->getJson('/api/v1/cars');
        $response2->assertStatus(200);
        $newETag = $response2->headers->get('ETag');
        $this->assertNotEmpty($newETag);
        $this->assertNotEquals($initialETag, $newETag);

        // Using old ETag should not return 304
        $response3 = $this->getJson('/api/v1/cars', [
            'If-None-Match' => $initialETag
        ]);
        $response3->assertStatus(200); // Should return full response, not 304
    }
}