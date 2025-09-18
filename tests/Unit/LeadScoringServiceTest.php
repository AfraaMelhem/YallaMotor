<?php

namespace Tests\Unit;

use App\Models\Dealer;
use App\Models\Lead;
use App\Models\Listing;
use App\Services\LeadScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeadScoringService $scoringService;
    private Listing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scoringService = new LeadScoringService();

        // Create test data
        $dealer = Dealer::factory()->create();
        $this->listing = Listing::factory()->create([
            'dealer_id' => $dealer->id,
            'price_cents' => 2500000, // $25,000
            'listed_at' => now()->subDays(3)
        ]);
    }

    public function test_scores_valid_email_correctly(): void
    {
        $lead = Lead::factory()->make([
            'listing_id' => $this->listing->id,
            'email' => 'john.doe@gmail.com',
            'phone' => null,
            'message' => null
        ]);

        $result = $this->scoringService->scoreLead($lead);

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('suggested_status', $result);
        $this->assertArrayHasKey('scoring_data', $result);

        // Should get points for valid email format and known domain
        $this->assertGreaterThan(15, $result['score']); // 5 for format + at least 10 for gmail
        $this->assertEquals('new', $result['suggested_status']);
    }

    public function test_scores_corporate_email_higher(): void
    {
        $lead1 = Lead::factory()->make([
            'listing_id' => $this->listing->id,
            'email' => 'john@company.com',
            'phone' => null,
            'message' => null
        ]);

        $lead2 = Lead::factory()->make([
            'listing_id' => $this->listing->id,
            'email' => 'john@gmail.com',
            'phone' => null,
            'message' => null
        ]);

        $result1 = $this->scoringService->scoreLead($lead1);
        $result2 = $this->scoringService->scoreLead($lead2);

        // Corporate-looking domain should get higher score
        $this->assertGreaterThan($result2['score'], $result1['score']);
    }

    public function test_scores_phone_number_correctly(): void
    {
        $lead = Lead::factory()->make([
            'listing_id' => $this->listing->id,
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'message' => null
        ]);

        $result = $this->scoringService->scoreLead($lead);

        // Should get points for valid phone and international format
        $phoneScore = $result['scoring_data']['phone']['score'] ?? 0;
        $this->assertEquals(20, $phoneScore); // 10 for length + 10 for international format
    }

    public function test_scores_message_quality(): void
    {
        $shortLead = Lead::factory()->make([
            'listing_id' => $this->listing->id,
            'email' => 'test@example.com',
            'message' => 'Hi'
        ]);

        $detailedLead = Lead::factory()->make([
            'listing_id' => $this->listing->id,
            'email' => 'test@example.com',
            'message' => 'I am very interested in purchasing this vehicle and would like to schedule a test drive to discuss financing options.'
        ]);

        $shortResult = $this->scoringService->scoreLead($shortLead);
        $detailedResult = $this->scoringService->scoreLead($detailedLead);

        $shortMessageScore = $shortResult['scoring_data']['message']['score'] ?? 0;
        $detailedMessageScore = $detailedResult['scoring_data']['message']['score'] ?? 0;

        $this->assertGreaterThan($shortMessageScore, $detailedMessageScore);
    }

    public function test_scores_intent_keywords_in_message(): void
    {
        $intentLead = Lead::factory()->make([
            'listing_id' => $this->listing->id,
            'email' => 'test@example.com',
            'message' => 'I want to buy this car and need financing information.'
        ]);

        $neutralLead = Lead::factory()->make([
            'listing_id' => $this->listing->id,
            'email' => 'test@example.com',
            'message' => 'This is a nice car with good features.'
        ]);

        $intentResult = $this->scoringService->scoreLead($intentLead);
        $neutralResult = $this->scoringService->scoreLead($neutralLead);

        $this->assertGreaterThan($neutralResult['score'], $intentResult['score']);
    }

    public function test_scores_listing_price_interaction(): void
    {
        // Create high-value listing
        $highValueListing = Listing::factory()->create([
            'dealer_id' => $this->listing->dealer_id,
            'price_cents' => 10000000 // $100,000
        ]);

        $highValueLead = Lead::factory()->make([
            'listing_id' => $highValueListing->id,
            'email' => 'test@example.com'
        ]);

        $regularLead = Lead::factory()->make([
            'listing_id' => $this->listing->id,
            'email' => 'test@example.com'
        ]);

        $highValueResult = $this->scoringService->scoreLead($highValueLead);
        $regularResult = $this->scoringService->scoreLead($regularLead);

        // High-value listing should get more points
        $this->assertGreaterThan($regularResult['score'], $highValueResult['score']);
    }

    public function test_scores_recent_listing_bonus(): void
    {
        // Create recent listing
        $recentListing = Listing::factory()->create([
            'dealer_id' => $this->listing->dealer_id,
            'listed_at' => now()->subDays(1)
        ]);

        $recentLead = Lead::factory()->make([
            'listing_id' => $recentListing->id,
            'email' => 'test@example.com'
        ]);

        $oldLead = Lead::factory()->make([
            'listing_id' => $this->listing->id, // 3 days old
            'email' => 'test@example.com'
        ]);

        $recentResult = $this->scoringService->scoreLead($recentLead);
        $oldResult = $this->scoringService->scoreLead($oldLead);

        // Recent listing should get bonus points
        $this->assertGreaterThan($oldResult['score'], $recentResult['score']);
    }

    public function test_penalizes_duplicate_leads(): void
    {
        // Create existing lead
        Lead::factory()->create([
            'email' => 'duplicate@example.com',
            'created_at' => now()->subDays(15)
        ]);

        $duplicateLead = Lead::factory()->make([
            'listing_id' => $this->listing->id,
            'email' => 'duplicate@example.com'
        ]);

        $uniqueLead = Lead::factory()->make([
            'listing_id' => $this->listing->id,
            'email' => 'unique@example.com'
        ]);

        $duplicateResult = $this->scoringService->scoreLead($duplicateLead);
        $uniqueResult = $this->scoringService->scoreLead($uniqueLead);

        // Duplicate should have lower score
        $this->assertLessThan($uniqueResult['score'], $duplicateResult['score']);
    }

    public function test_suggests_qualified_status_for_high_scores(): void
    {
        // Create lead that should get high score
        $highQualityLead = Lead::factory()->make([
            'listing_id' => $this->listing->id,
            'email' => 'executive@bigcompany.com',
            'phone' => '+1234567890',
            'message' => 'I am interested in purchasing this vehicle immediately. Please contact me to discuss financing and schedule a test drive.'
        ]);

        $result = $this->scoringService->scoreLead($highQualityLead);

        $this->assertGreaterThanOrEqual(70, $result['score']);
        $this->assertEquals('qualified', $result['suggested_status']);
    }

    public function test_business_hours_scoring(): void
    {
        // Mock business hours (this is a simplified test)
        $businessHoursLead = Lead::factory()->make([
            'listing_id' => $this->listing->id,
            'email' => 'test@example.com',
            'created_at' => now()->setHour(14) // 2 PM
        ]);

        $result = $this->scoringService->scoreLead($businessHoursLead);

        // Should have behavior scoring data
        $this->assertArrayHasKey('behavior', $result['scoring_data']);
        $behaviorData = $result['scoring_data']['behavior'];
        $this->assertArrayHasKey('details', $behaviorData);
    }

    public function test_source_scoring(): void
    {
        $websiteLead = Lead::factory()->make([
            'listing_id' => $this->listing->id,
            'email' => 'test@example.com',
            'source' => 'website'
        ]);

        $apiLead = Lead::factory()->make([
            'listing_id' => $this->listing->id,
            'email' => 'test@example.com',
            'source' => 'api'
        ]);

        $websiteResult = $this->scoringService->scoreLead($websiteLead);
        $apiResult = $this->scoringService->scoreLead($apiLead);

        // Website source should get higher score than API
        $this->assertGreaterThan($apiResult['score'], $websiteResult['score']);
    }

    public function test_invalid_email_gets_zero_score(): void
    {
        $invalidLead = Lead::factory()->make([
            'listing_id' => $this->listing->id,
            'email' => 'invalid-email'
        ]);

        $result = $this->scoringService->scoreLead($invalidLead);

        $emailScore = $result['scoring_data']['email']['score'] ?? 0;
        $this->assertEquals(0, $emailScore);
    }

    public function test_scoring_data_structure(): void
    {
        $lead = Lead::factory()->make([
            'listing_id' => $this->listing->id,
            'email' => 'test@gmail.com',
            'phone' => '+1234567890',
            'message' => 'I want to buy this car.'
        ]);

        $result = $this->scoringService->scoreLead($lead);

        // Verify structure
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('suggested_status', $result);
        $this->assertArrayHasKey('scoring_data', $result);
        $this->assertArrayHasKey('scored_at', $result);

        $scoringData = $result['scoring_data'];
        $this->assertArrayHasKey('email', $scoringData);
        $this->assertArrayHasKey('phone', $scoringData);
        $this->assertArrayHasKey('message', $scoringData);
        $this->assertArrayHasKey('listing', $scoringData);
        $this->assertArrayHasKey('behavior', $scoringData);

        // Verify all sections have score and details
        foreach (['email', 'phone', 'message', 'listing', 'behavior'] as $section) {
            $this->assertArrayHasKey('score', $scoringData[$section]);
            $this->assertArrayHasKey('details', $scoringData[$section]);
        }
    }

    public function test_score_bounds(): void
    {
        // Test various leads to ensure score is within expected bounds
        $leads = [
            Lead::factory()->make(['listing_id' => $this->listing->id, 'email' => 'invalid']),
            Lead::factory()->make(['listing_id' => $this->listing->id, 'email' => 'basic@example.com']),
            Lead::factory()->make([
                'listing_id' => $this->listing->id,
                'email' => 'executive@company.com',
                'phone' => '+1234567890',
                'message' => 'I want to buy this car immediately with cash payment.'
            ])
        ];

        foreach ($leads as $lead) {
            $result = $this->scoringService->scoreLead($lead);
            $score = $result['score'];

            $this->assertGreaterThanOrEqual(0, $score, 'Score should not be negative');
            $this->assertLessThanOrEqual(100, $score, 'Score should not exceed 100');
            $this->assertIsInt($score, 'Score should be an integer');
        }
    }
}