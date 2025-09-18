<?php

namespace App\Services;

use App\Models\Lead;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class LeadScoringService
{
    public function __construct(
        private CacheService $cacheService
    ) {}
    public function scoreLead(Lead $lead): array
    {
        $score = 0;
        $scoringData = [];

        // Email scoring (20 points)
        $emailScore = $this->scoreEmail($lead->email);
        $score += $emailScore['score'];
        $scoringData['email'] = $emailScore;

        // Phone scoring (30 points)
        if ($lead->phone) {
            $phoneScore = $this->scorePhone($lead->phone);
            $score += $phoneScore['score'];
            $scoringData['phone'] = $phoneScore;
        }

        // Source scoring (10 points)
        $sourceScore = $this->scoreSource($lead->source);
        $score += $sourceScore['score'];
        $scoringData['source'] = $sourceScore;

        // Listing recency scoring (up to 40 points)
        $recencyScore = $this->scoreListingRecency($lead);
        $score += $recencyScore['score'];
        $scoringData['recency'] = $recencyScore;

        // Determine suggested status based on score
        $suggestedStatus = $this->determineSuggestedStatus($score);

        return [
            'score' => $score,
            'suggested_status' => $suggestedStatus,
            'scoring_data' => $scoringData,
            'scored_at' => now()->toISOString()
        ];
    }

    private function scoreEmail(string $email): array
    {
        $score = 0;
        $details = [];

        // Simple email validation (20 points if valid)
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $score = 20;
            $details['valid_format'] = true;
        } else {
            $details['valid_format'] = false;
        }

        return ['score' => $score, 'details' => $details];
    }


    private function scorePhone(string $phone): array
    {
        $score = 0;
        $details = [];

        // Clean phone number
        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        $details['cleaned'] = $cleaned;

        // Simple phone validation (30 points if valid)
        if (strlen($cleaned) >= 10) {
            $score = 30;
            $details['valid_format'] = true;
        } else {
            $details['valid_format'] = false;
        }

        return ['score' => $score, 'details' => $details];
    }

    private function scoreSource(string $source): array
    {
        $score = 0;
        $details = [];

        // Simple source scoring (10 points for any source)
        if (!empty($source)) {
            $score = 10;
            $details['source'] = $source;
        }

        return ['score' => $score, 'details' => $details];
    }

    private function scoreListingRecency(Lead $lead): array
    {
        $score = 0;
        $details = [];

        // Load listing
        $listing = $lead->listing;
        $details['listing_id'] = $listing->id;
        $details['listed_at'] = $listing->listed_at->toISOString();

        // Recency scoring (up to 40 points based on how recent the listing is)
        $daysSinceListed = now()->diffInDays($listing->listed_at);
        $details['days_since_listed'] = $daysSinceListed;

        if ($daysSinceListed <= 1) {
            $score = 40;
        } elseif ($daysSinceListed <= 7) {
            $score = 30;
        } elseif ($daysSinceListed <= 30) {
            $score = 20;
        } elseif ($daysSinceListed <= 90) {
            $score = 10;
        } else {
            $score = 0;
        }

        $details['recency_score'] = $score;
        return ['score' => $score, 'details' => $details];
    }


    private function determineSuggestedStatus(int $score): string
    {
        if ($score >= 80) {
            return 'qualified';
        } else {
            return 'new';
        }
    }

}
